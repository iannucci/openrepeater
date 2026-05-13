<?php
/**
 * Convert legacy linear Morse-amplitude (1..1000) to dBFS for svxlink CW.tcl.
 * Modern svxlink emits a deprecation warning on positive amplitudes (legacy
 * linear scale). Negative dBFS values pass through. Smart-convert: positive
 * inputs get converted; non-positive (already dBFS or 0) pass through unchanged.
 */
if (!function_exists('orp_cw_amp_dbfs')) {
    function orp_cw_amp_dbfs($v) {
        if (is_numeric($v) && $v > 0) {
            return number_format(20.0 * log10($v / 1000.0), 2, '.', '');
        }
        return $v;
    }
}
?>
<?php
#####################################################################################################
# SXVLink GPIO Config Class
#####################################################################################################

class SVXLink_TCL {

    private $settingsArray;    
    private $idPath = "/var/lib/openrepeater/sounds/identification/";
    private $courtesyPath = "/var/lib/openrepeater/sounds/courtesy_tones/";


	public function __construct($settingsArray) {
		$this->settingsArray = $settingsArray;
	}



	###############################################
	# Simplex Logic
	###############################################

	public function alias_SimplexLogic($new_namespace) {
		$orig_file = file_get_contents("/usr/share/svxlink/events.d/SimplexLogic.tcl");
		$new_file = str_replace("SimplexLogic", $new_namespace, $orig_file);
		return $new_file;
	}



	###############################################
	# Repeater Logic
	###############################################

	public function alias_RepeaterLogic($new_namespace) {
		$orig_file = file_get_contents("/usr/share/svxlink/events.d/RepeaterLogic.tcl");
		$new_file = str_replace("RepeaterLogic", $new_namespace, $orig_file);

		# Replace default tones at end of roger beep
		$search_beep = '/playTone 400 900 50[\s\S]+?playSilence 500\R/';
		$replace_beep = '';
		$new_file = preg_replace( $search_beep, $replace_beep, $new_file );

		# Strip the voice-ID block from repeater_up.
		#
		# Upstream RepeaterLogic.tcl plays "spellWord $mycall" + "repeater"
		# whenever the repeater is activated on certain reasons (DTMF,
		# MODULE, AUDIO, TONE — anything except SQL_OPEN / CTCSS_OPEN /
		# SQL_RPT_REOPEN), throttled by min_time_between_ident (default
		# 120 s). On busy repeaters this fires a voice ID every couple of
		# minutes during an active QSO, which the W6EI operator finds
		# annoying — they want only the scheduled short (CW, every 10 min)
		# and long (voice, every 60 min) IDs. Period.
		#
		# Strip the entire `if {($reason != "SQL_OPEN") ...}` block out of
		# repeater_up. What remains: `set repeater_is_up 1;` — exactly
		# matching install/tcl/ORP_RepeaterLogic_Port1.tcl's simple form.
		$search_repeater_up_id =
			'/^  if \{\(\$reason != "SQL_OPEN"\) && \(\$reason != "CTCSS_OPEN"\) &&\s+\(\$reason != "SQL_RPT_REOPEN"\)\} \{[\s\S]+?^  \}\n/m';
		$replace_repeater_up_id = '';
		$new_file = preg_replace( $search_repeater_up_id, $replace_repeater_up_id, $new_file );

		# Strip the voice-ID block from repeater_down.
		#
		# Upstream RepeaterLogic.tcl ALSO fires "spellWord $mycall" +
		# "repeater" inside repeater_down whenever the repeater closes
		# after user activity (any reason other than SQL_FLAP_SUP),
		# throttled by min_time_between_ident (default 120 s). On a busy
		# repeater this tacks a voice ID onto the end of user transmissions
		# every ~2 min. The earlier strip in repeater_up covered the
		# opening side; this strip covers the closing side. Both fixes are
		# required — they're independent code paths in upstream 24.02 that
		# were not present (or not enabled) in older svxlink versions.
		#
		# Strip just the three action lines, leaving the SQL_FLAP_SUP
		# handler and the prev_ident throttle bookkeeping intact.
		$search_repeater_down_id =
			'/^  spellWord \$mycall;\s*\n  playMsg "Core" "repeater";\s*\n  playSilence 250;\s*\n/m';
		$replace_repeater_down_id = '';
		$new_file = preg_replace( $search_repeater_down_id, $replace_repeater_down_id, $new_file );

		return $new_file;
	}



	###############################################
	# Build Custom TCL
	###############################################

	public function logic_override() {

		$proc_header = '
			namespace eval Logic {
			';			

		$proc_content = $this->proc_short_id();
		$proc_content .= $this->proc_long_id();

		$proc_footer = "\n\t}\n";

		return $this->indent($proc_header, 0) . $this->indent($proc_content, 1) . $this->indent($proc_footer, 0);
	}



	###############################################
	# Proc Short ID
	###############################################

	private function proc_short_id() {
		$proc_header = '
		# Executed when a short identification should be sent
		proc send_short_ident {{hour -1} {minute -1}} {
		';

		$proc_content = '
		    global mycall;
		    variable CFG_TYPE;
		    playSilence 200;
		';
		
		$proc_content .= '
		    if {$CFG_TYPE == "Repeater"} {
		';

		switch ($this->settingsArray['ID_Short_Mode']) {
		    case "disabled":
		    	// Short ID - DISABLED
		        break;
		
		    case "morse":
		    	// Short ID - MORSE
				$proc_content .= $this->buildMorseID();
		        break;
		
		    case "voice":
		    	// Short ID - VOICE ID
				$proc_content .= $this->buildVoiceID();
				if ($this->settingsArray['ID_Short_AppendMorse'] == 'True') {
					$proc_content .= $this->buildMorseID();
				}
		        break;
		
		    case "custom":
		    	// Short ID - CUSTOM ID
				$proc_content .= $this->buildCustomID($this->settingsArray['ID_Short_CustomFile']);
				if ($this->settingsArray['ID_Short_AppendMorse'] == 'True') {
					$proc_content .= $this->buildMorseID();
				}
		        break;
		}

		$proc_content .= "\n\t    } else {\n";
		$proc_content .= $this->buildMorseID();
		$proc_content .= "\n\t    }\n";

		$proc_footer = "\n\t}\n";

		return $this->indent($proc_header, 1) . $this->indent($proc_content, 2) . $this->indent($proc_footer, 1);
	}



	###############################################
	# Proc Long ID
	###############################################

	private function proc_long_id() {
		$proc_header = '
		# Executed when a long identification (e.g. hourly) should be sent
		proc send_long_ident {hour minute} {
		';

		$proc_content = '
		    global mycall;
		    global loaded_modules;
		    global active_module;
		    global report_ctcss;
		    variable CFG_TYPE;
		    playSilence 200;
		';

		$proc_content .= '
		    if {$CFG_TYPE == "Repeater"} {
		';

		switch ($this->settingsArray['ID_Long_Mode']) {
		    case "disabled":
		    	// Long ID - DISABLED
		        break;
		
		    case "morse":
		    	// Long ID - MORSE
				$proc_content .= $this->buildMorseID();
		        break;
		
		    case "voice":
		    	// Long ID - VOICE ID
				$proc_content .= $this->buildVoiceID();
				if ($this->settingsArray['ID_Long_AppendTime'] == 'True') {
					$proc_content .= $this->buildTime();
				}
				$proc_content .= $this->buildPlAnnouncement();
				if ($this->settingsArray['ID_Long_AppendMorse'] == 'True') {
					$proc_content .= $this->buildMorseID();
				}
		        break;

		    case "custom":
		    	// Long ID - CUSTOM ID
				$proc_content .= $this->buildCustomID($this->settingsArray['ID_Long_CustomFile']);
				if ($this->settingsArray['ID_Long_AppendTime'] == 'True') {
					$proc_content .= $this->buildTime();
				}
				$proc_content .= $this->buildPlAnnouncement();
				if ($this->settingsArray['ID_Long_AppendMorse'] == 'True') {
					$proc_content .= $this->buildMorseID();
				}
		        break;
		}

		$proc_content .= "\n\t    } else {\n";
		$proc_content .= $this->buildMorseID();
		$proc_content .= "\n\t    }\n";

		$proc_footer = "\n\t}\n";

		return $this->indent($proc_header, 1) . $this->indent($proc_content, 2) . $this->indent($proc_footer, 1);
	}



	###############################################
	# Identification Functions
	###############################################

	private function buildMorseID() {
		$morseID = '
		    CW::setAmplitude ' . orp_cw_amp_dbfs($this->settingsArray['ID_Morse_Amplitude']) . '
		    CW::setWpm ' . $this->settingsArray['ID_Morse_WPM'] . '
		    CW::setPitch ' . $this->settingsArray['ID_Morse_Pitch'] . '
		    CW::play $mycall' . $this->settingsArray['ID_Morse_Suffix'] . '
		    playSilence 500;
		';
		return $morseID;
	}
	
	private function buildVoiceID() {
		$voiceID = '
		    spellWord $mycall;
		    if {$CFG_TYPE == "Repeater"} {
		        playMsg "Core" "repeater";
		    }
		    playSilence 500;
		';
		return $voiceID;
	}
	
	private function buildCustomID($filename) {
		$customID = '
		    playFile "' . $this->idPath . $filename . '"
		    playSilence 500
		';
		return $customID;
	}
	
	private function buildTime() {
		$time = '
		    playMsg "Core" "the_time_is";
		    playSilence 100;
		    playTime $hour $minute;
		    playSilence 500;
		';
		return $time;
	}

	private function buildPlAnnouncement() {
		# Emits "PL is <freq> Hz" when REPORT_CTCSS is set in svxlink.conf.
		# Mirrors stock SVXLink Logic.tcl; gated at runtime so it is a no-op
		# when REPORT_CTCSS is unset or zero.
		$plAnnouncement = '
		    if {$report_ctcss > 0} {
		        playMsg "Core" "pl_is";
		        playFrequency $report_ctcss;
		        playSilence 500;
		    }
		';
		return $plAnnouncement;
	}



	###############################################
	# Courtesy Tone
	###############################################

	public function override_courtesy_tone($orig_file) {
		$search_for_function = '/proc send_rgr_sound {} {[\s\S]+?}\R/';
		
		$new_function = $this->proc_courtesy_tone();
	
		$new_file = preg_replace( $search_for_function, $new_function, $orig_file );

		return $new_file;
	}


	private function proc_courtesy_tone() {
		$proc_header = "
		proc send_rgr_sound {} {
		";

		$proc_content = "";

		switch ($this->settingsArray['courtesyMode']) {
		
		    case "disabled":
				// No Courtesy Tone Played 
				$proc_content .= '
					playSilence 100
					';
		        break;
		
		    case "beep":
				// Generic Beep Played
				$proc_content .= '
					playTone 660 500 200;
					playSilence 200
					';
		        break;
		
		    case "custom":
				// Play Custom Courtesy Tone
				$proc_content .= '
					playFile "' . $this->courtesyPath . $this->settingsArray['courtesy'] . '"
					playSilence 200
					';
		        break;
		}

		$proc_footer = "\n\t}\n";

		return $this->indent($proc_header, 1) . $this->indent($proc_content, 2) . $this->indent($proc_footer, 1);
	}



	###############################################
	# Indentation Level
	###############################################

	private function indent($string, $level = 0) {
		$string = preg_replace('/\t+/', '%%%%', $string);
		
		if ($level == 0) { $string = str_replace("%%%%", "", $string); }
		if ($level == 1) { $string = str_replace("%%%%", "\t", $string); }
		if ($level == 2) { $string = str_replace("%%%%", "\t\t", $string); }
		if ($level == 3) { $string = str_replace("%%%%", "\t\t\t", $string); }
		if ($level == 3) { $string = str_replace("%%%%", "\t\t\t\t", $string); }

		return $string;
	}	


}

?>