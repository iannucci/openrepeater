###############################################################################
#  SVXlink RSSI module by Bob Iannucci (W6EI)
#  
#  This module enables the user to get signal strength reports of the signal
#  as seen by the repeater.
#
#  Options includ
#    Logging to the syslog
#    Voice announcement in S-units
#    DTMF reporting of dBM
#
###############################################################################

namespace eval RSSI {
	# Check if this module is loaded in the current logic core
	if {![info exists CFG_ID]} {
		return;
	}
	#
	# Extract the module name from the current namespace
	#
	set module_name [namespace tail [namespace current]]
	set rssi_dbm_latest -120.0
	set rssi_s_latest "S1"
	set rssi_active 0
	
	# A convenience function for printing out info prefixed by the module name
	#
	#   msg - The message to print
	#
	proc printInfo {msg} {
		variable module_name
		puts "$module_name: $msg"
	}

	#
	# A convenience function for calling an event handler
	#
	proc processEvent {ev} {
		variable module_name
		::processEvent "$module_name" "$ev"
	}
	 
	proc activateInit {} {
		set rssi_active 1
		printInfo "Module activated"
	}

	proc deactivateCleanup {} {
		set rssi_active 0
		printInfo "Module deactivated"
	}

	# Read the ADC and return the raw value 0..1023
	proc rssi_adc_value {analog_pin} {
		variable CFG_ANALOG_GPIO_PATH
		set ANALOG_RAW "_raw"
		set RSSI_READING [exec cat $CFG_ANALOG_GPIO_PATH$analog_pin$ANALOG_RAW]
		return $RSSI_READING
	}

	# Given an RSSI ADC value, convert it to dBm
	proc rssi_dbm {value} {
		# RSSI is reported by the Motorola CDM1550LS as an
		# analog voltage that runs from 0..5V
		#
		# The PiRepeater provides a 0..5V analog input, and reports
		# voltages in the 0..5V range as integers from 0..1023
		#
		# Convert integer to voltage:
		set voltage [expr $value * 0.004882814]

		# Convert from voltage to dBm:
		#
		# This formula was created in Excel based on a curve from
		# https://www.repeater-builder.com/motorola/cdm/cdm-acc-conn.html
		#
		# y = 52.87x3 - 282.02x2 + 538.97x - 442.42
		set dBm [expr (52.87 * ($voltage ** 3)) - (282.02 * ($voltage ** 2)) + (538.97 * $voltage) -442.42]
		return $dBm
	}

	# Given an RSSI dBm value, convert it to S-units
	proc rssi_s_units {dBm} {
		# Takes a signal strength in dBm and converts it to an S-unit string
		#   
		# dBM		S unit expression
		# -33		S9 + 40 dB
		# -43		S9 + 30 dB
		# -53		S9 + 20 dB
		# -63		S9 + 10 dB
		# -73		S9
		# -79		S8
		# -85		S7
		# -91		S6
		# -97		S5
		# -103		S4
		# -109		S3
		# -115		S2
		# -121		S1

		if { $dBm >= -33 } {
			return "S9 + 40 dB"
		} elseif { $dBm >= -43 } {
			return "S9 + 30 dB"
		} elseif { $dBm >= -53 } {
			return "S9 + 20 dB"
		} elseif { $dBm >= -63 } {
			return "S9 + 10 dB"
		} elseif { $dBm >= -73 } {
			return "S9"
		} elseif { $dBm >= -79 } {
			return "S8"
		} elseif { $dBm >= -85 } {
			return "S7"
		} elseif { $dBm >= -91 } {
			return "S6"
		} elseif { $dBm >= -97 } {
			return "S5"
		} elseif { $dBm >= -103 } {
			return "S4"
		} elseif { $dBm >= -109 } {
			return "S3"
		} elseif { $dBm >= -115 } {
			return "S2"
		} else {
			return "S1"
		}
	}

	# Read the RSSI ADC, convert it to dBm and S-units
	proc capture_rssi {} {
		variable rssi_dbm_latest
		variable rssi_s_latest
		variable CFG_RSSI_ANALOG_INPUT_PIN
		# move this to a config parameter
		# set ANALOG_PIN 1   
		set adc_value [rssi_adc_value $CFG_RSSI_ANALOG_INPUT_PIN]
		set dBm [rssi_dbm $adc_value]
		set s_units [rssi_s_units $dBm]
		set rssi_dbm_latest $dBm
		set rssi_s_latest $s_units
	}

	# Executed when all announcement messages has been played.
	# Note that this function also may be called even if it wasn't this module
	# that initiated the message playing.
	#
	proc allMsgsWritten {} {
	}

	# Interpret an S-unit string and play it as audio
	proc play_s_units {s_units} {
		set filename [string map {" " ""} $s_units]
		playMsg "RSSI" $filename
	}


	proc play_dBm {dBm} {
		playMsg "RSSI" "negative"
		playNumber $dBm
		playMsg "RSSI" "dB"
	}


	proc play_dBm_DTMF {dBm} {
		# positive integer only
		set int_dBm [expr abs(int($dBm))]
		set string [format "%d" $int_dBm]
		foreach char [split $string ""] {
			set filename "DTMF$char"
			playMsg "RSSI" $filename
			playSilence 100
		}
	}

	proc squelchOpen {is_open} {
		variable ::RSSI::rssi_dbm_latest
		variable ::RSSI::rssi_s_latest
		variable ::RSSI::rssi_active

		# capture the RSSI value
		if {$is_open} {
			RSSI::capture_rssi
			# puts [format "RSSI %.1f dBm; %s" $::RSSI::rssi_dbm_latest $::RSSI::rssi_s_latest]
		} else {
			set int_dBm [expr abs(int($::RSSI::rssi_dbm_latest))]
			playSilence 200
			# play_dBm $int_dBm
			# playSilence 200
			play_s_units $::RSSI::rssi_s_latest
			playSilence 200
			playMsg "RSSI" "DTMFstar"
			playSilence 100
			play_dBm_DTMF $::RSSI::rssi_dbm_latest
			playMsg "RSSI" "DTMF#"
			playSilence 200
		}
	}

	proc dtmfDigitReceived {char duration} {
		printInfo "DTMF digit $char received with duration $duration milliseconds"
	}

	proc dtmfCmdReceived {cmd} {
		# variable variants

		printInfo "DTMF command received: $cmd";

		if {$cmd == "0"} {
			processEvent "play_help"
		# } elseif {[string length $cmd] == 2} {
		# 	processEvent "play_standard $variants($cmd)"
		# } elseif {$cmd != ""} {
		# 	processEvent "play_sel_call $cmd"
		} else {
			deactivateModule
		}
	}
	
	# end of namespace
}


#
# This file has not been truncated
#
