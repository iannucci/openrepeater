<?php
/*
* This is the file that gets called for this module when OpenRepeater rebuilds the configuration files for SVXLink.
* Settings for the config file are created as a PHP associative array, when the file is called it will convert it into
* the requiried INI format and write the config file to the appropriate location with the correct naming.
*/


$options = !empty($cur_mod['moduleOptions']) ? unserialize($cur_mod['moduleOptions']) : [];

// Add Linebreaks into Description
$echolink_clean_desc = preg_replace('/\r\n?/', "\\n", trim($options['description']));

// Build Config
$module_config_array['Module'.$cur_mod['svxlinkName']] = [
	'NAME' => $cur_mod['svxlinkName'],
	'ID' => $cur_mod['svxlinkID'],
	'TIMEOUT' => $options['timeout'],
	'SERVERS' => $options['server'],
	'CALLSIGN' => $options['callSign'],
	'PASSWORD' => $options['password'],
	// Accept audio from any AMPRNet (44.0.0.0/8) source even when the
	// directory-registered peer IP does not match the audio source IP.
	// The EchoLink proxy farm that serves AMPRNet clients load-balances
	// across adjacent IPs (e.g. 44.32.128.51, 44.40.160.5, 44.40.160.6),
	// and svxlink 24.02's strict "station->ip() != ip" check would
	// otherwise reject those relayed audio packets and drop the QSO.
	'ALLOW_IP' => '44.0.0.0/8',
	'SYSOPNAME' => $options['sysop'],
	'LOCATION' => '[ORP] '.$options['location'],
	'MAX_QSOS' => $options['max_qsos'],
	'MAX_CONNECTIONS' => $options['connections'],
	'LINK_IDLE_TIMEOUT' => $options['idle_timeout'],
	'DESCRIPTION' => $echolink_clean_desc,
	'USE_GSM_ONLY' => '1',

];

if($options['proxy_server']) {
	$module_config_array['Module'.$cur_mod['svxlinkName']]['PROXY_SERVER'] = $options['proxy_server'];
	if($options['proxy_port']) {
		$module_config_array['Module'.$cur_mod['svxlinkName']]['PROXY_PORT'] = $options['proxy_port'];
	}
	if($options['proxy_password']) {
		$module_config_array['Module'.$cur_mod['svxlinkName']]['PROXY_PASSWORD'] = $options['proxy_password'];
	}
}

?>
