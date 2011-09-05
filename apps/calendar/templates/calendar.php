<?php
$hours = array(
	'allday' => $l->t('All day'),
	 0 => '00:00',
	 1 => '01:00',
	 2 => '02:00',
	 3 => '03:00',
	 4 => '04:00',
	 5 => '05:00',
	 6 => '06:00',
	 7 => '07:00',
	 8 => '08:00',
	 9 => '09:00',
	10 => '10:00',
	11 => '11:00',
	12 => '12:00',
	13 => '13:00',
	14 => '14:00',
	15 => '15:00',
	16 => '16:00',
	17 => '17:00',
	18 => '18:00',
	19 => '19:00',
	20 => '20:00',
	21 => '21:00',
	22 => '22:00',
	23 => '23:00',
);
$weekdays = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
?>
				<script type="text/javascript">
				var oc_cal_daylong = new Array("<?php echo $l -> t("Sunday");?>", "<?php echo $l -> t("Monday");?>", "<?php echo $l -> t("Tuesday");?>", "<?php echo $l -> t("Wednesday");?>", "<?php echo $l -> t("Thursday");?>", "<?php echo $l -> t("Friday");?>", "<?php echo $l -> t("Saturday");?>");
				var oc_cal_dayshort = new Array("<?php echo $l -> t("Sun.");?>", "<?php echo $l -> t("Mon.");?>", "<?php echo $l -> t("Tue.");?>", "<?php echo $l -> t("Wed.");?>", "<?php echo $l -> t("Thu.");?>", "<?php echo $l -> t("Fri.");?>", "<?php echo $l -> t("Sat.");?>");
				var oc_cal_monthlong = new Array("<?php echo $l -> t("January");?>", "<?php echo $l -> t("February");?>", "<?php echo $l -> t("March");?>", "<?php echo $l -> t("April");?>", "<?php echo $l -> t("May");?>", "<?php echo $l -> t("June");?>", "<?php echo $l -> t("July");?>", "<?php echo $l -> t("August");?>", "<?php echo $l -> t("September");?>", "<?php echo $l -> t("October");?>", "<?php echo $l -> t("November");?>", "<?php echo $l -> t("December");?>");
				var oc_cal_monthshort = new Array("<?php echo $l -> t("Jan.");?>", "<?php echo $l -> t("Feb.");?>", "<?php echo $l -> t("Mar.");?>", "<?php echo $l -> t("Apr.");?>", "<?php echo $l -> t("May");?>", "<?php echo $l -> t("Jun.");?>", "<?php echo $l -> t("Jul.");?>", "<?php echo $l -> t("Aug.");?>", "<?php echo $l -> t("Sep.");?>", "<?php echo $l -> t("Oct.");?>", "<?php echo $l -> t("Nov.");?>", "<?php echo $l -> t("Dec.");?>");
				var cw_label = "<?php echo $l->t("Week");?>";
				var cws_label = "<?php echo $l->t("Weeks");?>";
				</script>
				<div id="sysbox"></div>
				<div id="controls">
					<div>
						<form>
							<div id="view">
								<input type="button" value="1 <?php echo $l->t('Day');?>" id="onedayview_radio" onclick="Calendar.UI.setCurrentView('onedayview');"/>
								<input type="button" value="1 <?php echo $l->t('Week');?>" id="oneweekview_radio" onclick="Calendar.UI.setCurrentView('oneweekview');"/>
								<input type="button" value="4 <?php echo $l->t('Weeks');?>" id="fourweeksview_radio" onclick="Calendar.UI.setCurrentView('fourweeksview');"/>
								<input type="button" value="1 <?php echo $l->t('Month');?>" id="onemonthview_radio" onclick="Calendar.UI.setCurrentView('onemonthview');"/>
								<input type="button" value="<?php echo $l->t("Listview");?>" id="listview_radio" onclick="Calendar.UI.setCurrentView('listview');"/>
							</div>
						</form>
						<form>
							<div id="choosecalendar">
								<input type="button" id="today_input" value="<?php echo $l->t("Today");?>" onclick="oc_cal_switch2today();"/>
								<input type="button" id="choosecalendar_input" value="<?php echo $l->t("Calendars");?>" onclick="oc_cal_choosecalendar();" />
							</div>
						</form>
						<form>
							<div id="datecontrol">
								<input type="button" value="&nbsp;&lt;&nbsp;" id="datecontrol_left" onclick="Calendar.UI.updateDate('backward');"/>
								<input id="datecontrol_date" type="button" value=""/>
								<input type="button" value="&nbsp;&gt;&nbsp;" id="datecontrol_right" onclick="Calendar.UI.updateDate('forward');"/>
							</div>
						</form>
					</div>
				</div>
				<div id="calendar_holder">
					<div id="onedayview">
						<table>
							<thead>
								<tr>
									<th class="calendar_time"><?php echo $l->t("Time");?></th>
									<th id="onedayview_today" class="calendar_row" onclick="oc_cal_newevent($('#onedayview_today').attr('title'));"></th>
								</tr>
							</thead>
							<tbody>
<?php foreach($hours as $time => $time_label): ?>
								<tr>
									<td class="calendar_time"><?php echo $time_label ?></td>
									<td class="calendar_row <?php echo $time ?>" onclick="oc_cal_newevent($('#onedayview_today').attr('title'), '<?php echo $time ?>');"></td>
								</tr>
<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<div id="oneweekview">
						<table>
							<thead>
								<tr>
									<th class="calendar_time"><?php echo $l->t("Time");?></th>
<?php foreach($weekdays as $weekdaynr => $weekday): ?>
									<th class="calendar_row <?php echo $weekday ?> <?php echo $weekdaynr > 4 ? 'weekend_thead' : '' ?>" onclick="oc_cal_newevent($('#oneweekview th.<?php echo $weekday ?>').attr('title'));"></th>
<?php endforeach; ?>
								</tr>
							</thead>
							<tbody>
<?php foreach($hours as $time => $time_label): ?>
								<tr>
									<td class="calendar_time"><?php echo $time_label?></td>
<?php foreach($weekdays as $weekdaynr => $weekday): ?>
									<td class="<?php echo $weekday ?> <?php echo $time ?> calendar_row <?php echo $weekdaynr > 4 ? 'weekend_row' : '' ?>" onclick="oc_cal_newevent($('#oneweekview th.<?php echo $weekday ?>').attr('title'), '<?php echo $time ?>');"></td>
<?php endforeach; ?>
								</tr>
<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<div id="fourweeksview">
						<table>
							<thead>
								<tr>
									<th class="calendar_row calw"><?php echo $l -> t("CW");?></th>
<?php foreach($weekdays as $weekdaynr => $weekday): ?>
									<th class="calendar_row <?php echo $weekdaynr > 4 ? 'weekend_thead' : '' ?>"><?php echo $l->t(ucfirst($weekday)) ?></th>
<?php endforeach; ?>
								</tr>
							</thead>
							<tbody>
<?php foreach(range(1, 4) as $week): ?>
								<tr class="week_<?php echo $week ?>">
									<td class="calw"></td>
<?php foreach($weekdays as $weekdaynr => $weekday): ?>
									<td class="day <?php echo $weekday ?> <?php echo $weekdaynr > 4 ? 'weekend' : '' ?>" onclick="oc_cal_newevent($('#fourweeksview .week_<?php echo $week ?> .<?php echo $weekday ?>').attr('title'))">
									<div class="dateinfo"></div>
									<div class="events"></div>
									</td>
<?php endforeach; ?>
								</tr>
<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<div id="onemonthview">
						<table>
							<thead>
								<tr>
<?php foreach($weekdays as $weekdaynr => $weekday): ?>
									<th class="calendar_row <?php echo $weekdaynr > 4 ? 'weekend_thead' : '' ?> <?php echo $weekday ?>"><?php echo $l->t(ucfirst($weekday));?></th>
<?php endforeach; ?>
								</tr>
							</thead>
							<tbody>
<?php foreach(range(1, 6) as $week): ?>
								<tr class="week_<?php echo $week ?>">
<?php foreach($weekdays as $weekdaynr => $weekday): ?>
									<td class="day <?php echo $weekday ?> <?php echo $weekdaynr > 4 ? 'weekend' : '' ?>" onclick="oc_cal_newevent($('#onemonthview .week_<?php echo $week ?> .<?php echo $weekday ?>').attr('title'))">
									<div class="dateinfo"></div>
									<div class="events"></div>
									</td>
<?php endforeach; ?>
								</tr>
<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<div id="listview">
						
					</div>
				</div>
				<!-- Dialogs -->
				<div id="dialog_holder"></div>
				<div id="parsingfail_dialog" title="Parsing Fail">
					<?php echo $l->t("There was a fail, while parsing the file."); ?>
				</div>
				<!-- End of Dialogs -->
				<script type="text/javascript">
				<?php
				//use last view as default on the next
				echo "Calendar.UI.setCurrentView(\"" . OC_Preferences::getValue(OC_USER::getUser(), "calendar", "currentview", "onemonthview") . "\");\n";
				 
				?>
				</script>
