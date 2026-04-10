<?php
/*
* This is the file that gets called for this module when OpenRepeater rebuilds
* the configuration files for SVXLink. Settings for the config file are created
* as a PHP associative array, when the file is called it will convert it into
* the requiried INI format and write the config file to the appropriate location
* with the correct naming.
*/

$options = unserialize($cur_mod['moduleOptions']);

################################################################################
# This first part here starts the php array with 4 values that are common to 
# all modules. 
################################################################################

// Common variables
$module_config_array['Module'.$cur_mod['svxlinkName']] = [
	'NAME' => $cur_mod['svxlinkName'],
	'PLUGIN_NAME' => 'Tcl',
	'ID' => $cur_mod['svxlinkID'],
	'TIMEOUT' => '60',				
];


################################################################################
# This next part here appends more values onto to the array above.
################################################################################


// Add standard variables to the config
$module_config_array['Module'.$cur_mod['svxlinkName']] += [
	'ANALOG_GPIO_PATH' => $options['analog_path'],
	'RSSI_ANALOG_INPUT_PIN' => '1'
];


?>