<?php
/*
 * Function requested by Ajax
 */
if(isset($_POST['func']) && !empty($_POST['func'])){
	switch($_POST['func']){
		case 'getCalender':
			getCalender($_POST['year'],$_POST['month']);
			break;
		case 'getEvents':
			getEvents($_POST['date']);
			break;
		default:
			break;
	}
}

/*
 * Get calendar full HTML
 */
function getCalender($year = '',$month = ''){
	$dateYear = ($year != '')?$year:date("Y");
	$dateMonth = ($month != '')?$month:date("m");
	$date = $dateYear.'-'.$dateMonth.'-01';
	$currentMonthFirstDay = date("N",strtotime($date));
	$totalDaysOfMonth = cal_days_in_month(CAL_GREGORIAN,$dateMonth,$dateYear);
	$totalDaysOfMonthDisplay = ($currentMonthFirstDay == 7)?($totalDaysOfMonth):($totalDaysOfMonth + $currentMonthFirstDay);
	$boxDisplay = ($totalDaysOfMonthDisplay <= 35)?35:42;
?>
	<div id="calender_section">
		<div class = 'col-md-7'>
			<h2>
	        	<a href="javascript:void(0);" onclick="getCalendar('calendar_div','<?php echo date("Y",strtotime($date.' - 1 Month')); ?>','<?php echo date("m",strtotime($date.' - 1 Month')); ?>');">
	        		<span class = 'glyphicon glyphicon-chevron-left'></span>
	        	</a>
	            <select name="month_dropdown" class="month_dropdown dropdown"><?php echo getAllMonths($dateMonth); ?></select>
	            <a href="javascript:void(0);" onclick="getCalendar('calendar_div','<?php echo date("Y",strtotime($date.' + 1 Month')); ?>','<?php echo date("m",strtotime($date.' + 1 Month')); ?>');">
	            	<span class = 'glyphicon glyphicon-chevron-right'></span>
	            </a>
	        </h2>
			<div id="calender_section_top">
				<ul>
					<li>Lun</li>
					<li>Mar</li>
					<li>Mie</li>
					<li>Jue</li>
					<li>Vie</li>
					<li>Sab</li>
					<li>Dom</li>
				</ul>
			</div>
			<div id="calender_section_bot">
				<ul>
				<?php 
					$dayCount = 1; 
					for($cb=1;$cb<=$boxDisplay;$cb++){
						if(($cb >= $currentMonthFirstDay+1 || $currentMonthFirstDay == 7) && $cb <= ($totalDaysOfMonthDisplay)){
							//Current date
							$currentDate = $dateYear.'-'.$dateMonth.'-'.$dayCount;
							$eventNum = 0;
							//Include db configuration file
							include 'dbConfig.php';
							//Get number of events based on the current date
							$result = $db->query("SELECT id_doctor_created FROM patient_consult WHERE DATE(date_planned) = '".$currentDate."' ");
							$eventNum = $result->num_rows;
							//Define date cell color
							if(strtotime($currentDate) == strtotime(date("Y-m-d"))){
								echo '<li date="'.$currentDate.'" class="grey date_cell">';
							}elseif($eventNum > 0){
								echo '<li date="'.$currentDate.'" class="light_sky date_cell">';
							}else{
								echo '<li date="'.$currentDate.'" class="date_cell">';
							}
							//Date cell
							echo '<span>';
							echo $dayCount;
							echo '</span>';
							
							echo '<a href="javascript:;" onclick="getEvents(\''.$currentDate.'\');">';
							echo $eventNum;
							echo '</a>';
							
							echo '</li>';
							$dayCount++;
				?>
				<?php }else{ ?>
					<li><span>&nbsp;</span></li>
				<?php } } ?>
				</ul>
			</div>
		</div>
		<div class = 'col-md-4'>
			<div id="event_list" class="none"></div>
		</div>
		
	</div>

	<script type="text/javascript">
		function getCalendar(target_div,year,month){
			$.ajax({
				type:'POST',
				url:'functions.php',
				data:'func=getCalender&year='+year+'&month='+month,
				success:function(html){
					$('#'+target_div).html(html);
				}
			});
		}
		
		function getEvents(date){
			$.ajax({
				type:'POST',
				url:'functions.php',
				data:'func=getEvents&date='+date,
				success:function(html){
					$('#event_list').html(html);
					$('#event_list').slideDown('slow');
				}
			});
		}
		
		function addEvent(date){
			$.ajax({
				type:'POST',
				url:'functions.php',
				data:'func=addEvent&date='+date,
				success:function(html){
					$('#event_list').html(html);
					$('#event_list').slideDown('slow');
				}
			});
		}
		
		$(document).ready(function(){
			$('.date_cell').mouseenter(function(){
				date = $(this).attr('date');
				$(".date_popup_wrap").fadeOut();
				$("#date_popup_"+date).fadeIn();	
			});
			$('.date_cell').mouseleave(function(){
				$(".date_popup_wrap").fadeOut();		
			});
			$('.month_dropdown').on('change',function(){
				getCalendar('calendar_div',$('.year_dropdown').val(),$('.month_dropdown').val());
			});
			$('.year_dropdown').on('change',function(){
				getCalendar('calendar_div',$('.year_dropdown').val(),$('.month_dropdown').val());
			});
			$(document).click(function(){
				$('#event_list').slideUp('slow');
			});
		});
	</script>
<?php
}

/*
 * Get months options list.
 */
function getAllMonths($selected = ''){
	$options = '';
	for($i=1;$i<=12;$i++)
	{
		$value = ($i < 10)?'0'.$i:$i;
		$selectedOpt = ($value == $selected)?'selected':'';
		$options .= '<option value="'.$value.'" '.$selectedOpt.' >'.date("F", mktime(0, 0, 0, $i+1, 0, 0)).'</option>';
	}
	return $options;
}

/*
 * Get years options list.
 */
function getYearList($selected = ''){
	$options = '';
	for($i=2015;$i<=2025;$i++)
	{
		$selectedOpt = ($i == $selected)?'selected':'';
		$options .= '<option value="'.$i.'" '.$selectedOpt.' >'.$i.'</option>';
	}
	return $options;
}

/*
 * Get events by date
 */
function getEvents($date = ''){
	//Include db configuration file
	include 'dbConfig.php';
	$eventListHTML = '';
	$date = $date?$date:date("Y-m-d");

	//Get events based on the current date
	$result = $db->query("SELECT patient_consult.`id_consult`, CONCAT(patient.`name`, ' ', patient.`lastname`) AS `fullname`, TIME(patient_consult.`date_planned`) AS `hour` FROM patient_consult JOIN patient ON patient_consult.`id_patient` = patient.`id_patient` WHERE DATE(date_planned) = '".$date."' ORDER BY `hour` ASC; ");

	if($result->num_rows> 0){
		$eventListHTML = '<h2>'.date("d M Y",strtotime($date)).'</h2>';
		$eventListHTML .= '<ul>';
		while($row = $result->fetch_assoc()){ 
            $eventListHTML .= '<li>'.$row['fullname'].' '.$row['hour'].'</li>';
        }
		$eventListHTML .= '</ul>';
	}
	echo $eventListHTML;
}
?>