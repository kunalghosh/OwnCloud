<form id="calendar">
        <fieldset class="personalblock">
                <label for="timezone"><strong><?php echo $l->t('Timezone');?></strong></label>
		<select id="timezone" name="timezone">
                <?php foreach($_['timezones'] as $timezone):
			if ( preg_match( '/^(America|Antartica|Arctic|Asia|Atlantic|Europe|Indian|Pacific)\//', $timezone ) ):
				$ex=explode('/', $timezone, 2);//obtain continent,city
				if ($continent!=$ex[0]):
					if ($continent!="") echo '</optgroup>';
					echo '<optgroup label="'.$ex[0].'">';
				endif;
				$city=$ex[1];
				$continent=$ex[0];
				echo '<option value="'.$timezone.'"'.($_['timezone'] == $timezone?' selected="selected"':'').'>'.$city.'</option>';
			endif;
                endforeach;?>
                </select><span id="timezoneerror"></span>
        </fieldset>
</form>
