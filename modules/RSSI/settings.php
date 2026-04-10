<?php
/* 
 *	Settings Page for Module 
 *	
 *	This is included into a full page wrapper to be displayed. 
 */

?>


<!-- BEGIN FORM CONTENTS -->
<fieldset>
	<input type="hidden" name="digital_path" value="<?php echo $moduleSettings['digital_path']; ?>">
	<input type="hidden" name="analog_path" value="<?php echo $moduleSettings['analog_path']; ?>">
					  

<!-- *************************************************************************** -->
	<legend>Configure RSSI Input</legend>

	<div id="digitalWrap">

    <p class="sensorRow first">
        <span class="num">
            <input type="hidden" name="digitalNum[]" value="1">Analog input pin
        </span>
        
        <span>                                  
           <input id="rssi_analog_gpio" type="text" name="analogGPIO[]" placeholder="GPIO"  value="<?php echo $cur_child_array['RSSIgpio']; ?>" class="analogGPIO" required>
        </span>
    </p>

    <p class="sensorRow first">
        <span class="num">
            <input type="hidden" name="digitalNum[]" value="2">ADC precision
        </span>
        
        <span>                                  
           <input id="rssi_adc_bits" type="text" name="analogGPIO[]" placeholder="number of bits"  value="<?php echo $cur_child_array['RSSIadcbits']; ?>" class="analogGPIO" required>
        </span>
    </p>

    <p class="sensorRow first">
        <span class="num">
            <input type="hidden" name="digitalNum[]" value="3">ADC path
        </span>
        
        <span>                                  
           <input id="rssi_analog_path" type="text" name="analogGPIO[]" placeholder="number of bits"  value="<?php echo $cur_child_array['RSSIadcpath']; ?>" class="analogGPIO" required>
        </span>
    </p>

	<br>

<!-- *************************************************************************** -->

	<legend>Configure voltage-to-s-unit conversion</legend>
	<p>(May still add a toggle variable here to turn this section on & off)</p>
	<p>Some simple examples of Analog events (sliding scale inputs) - Fuel levels for generator, battery voltage, temperature, primary power supply voltage</p>

	<div id="analogWrap">
	<?php 
	$analogTypesArray = [
		'temperature' => 'Temperature',
		'battery_voltage' => 'Battery Voltage'
	];
	// Hidden DIV to pass above array to JavaScript as json array
	echo '<div id="analogTypeArray" style="display:none;">' . json_encode($analogTypesArray) . '</div>';

	$idNumAnalog = 1; // This will be replaced by a loop to load exsiting values 
	
	if ($moduleSettings['analog']) {
		ksort($moduleSettings['analog']);
		foreach($moduleSettings['analog'] as $cur_parent_array => $cur_child_array) { ?>
	
	
			<p class="sensorRow<?php if ($idNumAnalog == 1) { echo ' first'; } else { echo ' additional'; } ?>">
				<span class="num">
					<input type="hidden" name="analogNum[]" value="<?php echo $idNumAnalog; ?>">
					<?php echo $idNumAnalog; ?>
				</span>
				
				<span>									
					<input id="analogLabel<?php echo $idNumAnalog; ?>" type="text" name="analogLabel[]" placeholder="Analog Label" value="<?php echo $cur_child_array['label']; ?>" class="analogLabel" required>

					<select id="analogType<?php echo $idNumDigital; ?>" name="analogType[]" class="analogType" required>
						<option>Select Type</option>
						<?php 
						foreach($analogTypesArray as $value => $label) {
							if($cur_child_array['type'] == $value) { $sel_option = 'selected'; } else { $sel_option = ''; }
							echo '<option value="'.$value.'" '.$sel_option.'>'.$label.'</option>';
						}
						?>	
					</select>

					<input id="analogGPIO<?php echo $idNumAnalog; ?>" type="text" name="analogGPIO[]" placeholder="GPIO"  value="<?php echo $cur_child_array['gpio']; ?>" class="analogGPIO" required>

					<input id="analogHysterisis<?php echo $idNumAnalog; ?>" type="number" name="analogHysterisis[]" placeholder="Hysterisis"  value="<?php echo $cur_child_array['hysterisis']; ?>" class="analogHysterisis" required>
				</span>
	
				<?php if ($idNumAnalog == 1) { 
					echo '<a href="#" id="addAnalog" title="Add a analog sensor"><i class="icon-plus-sign"></i></a>';
				} else {
					echo '<a href="#" id="removeAnalog" title="Remove this analog sensor"><i class="icon-minus-sign"></i></a>';
				} ?>
			</p>
	
	
		<?php 
		$idNumAnalog++;
		}	
	} else {
		echo "there are no analog sensors...";
	}
	?>
	
	</div>
	
	<div id="analogCount"></div>

	<br>

<!-- *************************************************************************** -->
		
</fieldset>					
	
<!-- END FORM CONTENTS -->