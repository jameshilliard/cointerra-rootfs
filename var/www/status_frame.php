<?php
require_once 'includes/apiSocket.php';
require_once 'common.php';

ini_set('display_errors', '0');  //dev version
date_default_timezone_set('America/New_York');
?>



<?php
function isZombie($devs, $id) {
	if (isset($devs["ASC".$id])) {
		return ($devs["ASC".$id]['No Device'] == "true")?true:false;
	}
	return true;
}

$minerOffLineArr = array();
$sumArr = array();

$boardCnt = 0;
$boxCnt = count($minerList);

$htmlSummaryTable = '';
$htmlMetricsTable = '';

$StatusFrameStr = '';

foreach ($minerList as $m) {

	if(! in_array($m, $minerConfigedOffLine) ){
	
		$s = request($m,"summary");
		if($s != null){

			$d = request($m,"devs");
			$p = request($m,"pools");
			$stats = request($m,"stats");
			$summary = request($m,"summary");
			
			# the hashrate in $d is actually the 'share' hashrate
			# we want to display the raw hashrate which is in $stats.
			# below we iterate through the stats data to set the
			# 'Hashrate' appropriately for each CTA.
			foreach($stats as $ste) {
				if (! isset($ste['ID']) || 0 == preg_match('/CTA\d+/', $ste['ID'])) {
					continue;
				}
				$id = substr($ste['ID'], 3, 1);
				foreach($d as $el) {
					if (isset($el['ID']) && $id == $el['ID']) {
						#$d['ASC' . $el['ASC']]['Hashrate'] = gmp_strval(gmp_div_q($ste['Calc hashrate'], 1000000000));
						$d['ASC' . $el['ASC']]['Hashrate'] = substr($ste['Calc hashrate'], 0, strlen($ste['Calc hashrate'])-9)+0;
					}
				}
			}

			//$since = (time() - $elapsed =  $s['SUMMARY']['Elapsed']);
						
			$rigTotal5s=0;
			
			if (array_key_exists('SUMMARY', $summary)) {
				if (isset($summary['SUMMARY']['MHS av']))
					$sumArr['GHash'] = number_format($summary['SUMMARY']['MHS av']/1000, 2);
				if (isset($summary['SUMMARY']['MHS 5s']))
					$sumArr['GHash5s'] = number_format($summary['SUMMARY']['MHS 5s']/1000, 2);
				if (isset($summary['SUMMARY']['Difficulty Accepted']))
					$sumArr['Accepted'] = number_format($summary['SUMMARY']['Difficulty Accepted']);
				if (isset($summary['SUMMARY']['Difficulty Rejected']))
					$sumArr['Rejected'] = number_format($summary['SUMMARY']['Difficulty Rejected']);
				if (isset($summary['SUMMARY']['Hardware Errors']))
					$sumArr['Errors'] = number_format($summary['SUMMARY']['Hardware Errors']);
				if (isset($summary['SUMMARY']['Utility']))
					$sumArr['Utility'] = number_format($summary['SUMMARY']['Utility'], 2);
			}
			
			foreach ($stats as $value){
			
				if (array_key_exists('STATS', $value)) {
															
					//check for zombie results from cgminer
					preg_match('/CTA(?P<id>\d+)/', $value['ID'], $matches);
					if(isset($matches['id']) && !isZombie($d, $matches['id'])) {
						$boardCnt++;
						
						$htmlMetricsTable .= "<table class='execsum'  cellspacing=1 cellpadding=2><tr>";
						$htmlMetricsTable .= "<th>". $value['ID']." Metrics</th>";
						$htmlMetricsTable .= "<th>High</th>";
						$htmlMetricsTable .= "<th>Low</th>";
						$htmlMetricsTable .= "<th>Avg</th></tr>";
						
						//get temps
						$temps = array($value['CoreTemp0']/100,$value['CoreTemp1']/100,$value['CoreTemp2']/100,$value['CoreTemp3']/100);
						$htmlMetricsTable .= "<tr><td style='padding-left: 40px;'>Core Temp 1 (&degC)</td>";
						$htmlMetricsTable .= "<td>".max($temps)."</td>";
						$htmlMetricsTable .= "<td>".min($temps)."</td>";
						$htmlMetricsTable .= "<td>".number_format(array_sum($temps)/count($temps),2)."</td>";
						$htmlMetricsTable .= "</tr>";
												
						$temps = array($value['CoreTemp4']/100,$value['CoreTemp5']/100,$value['CoreTemp6']/100,$value['CoreTemp7']/100,);				
						$htmlMetricsTable .= "<tr><td style='padding-left: 40px;'>Core Temp 2 (&degC)</td>";
						$htmlMetricsTable .= "<td>".max($temps)."</td>";
						$htmlMetricsTable .= "<td>".min($temps)."</td>";
						$htmlMetricsTable .= "<td>".number_format(array_sum($temps)/count($temps),2)."</td>";
						$htmlMetricsTable .= "</tr>";
						
						
						$htmlMetricsTable .= "<tr><td style='padding-left: 40px;'>Ambient Temp (&degC)</td>";
						$htmlMetricsTable .= "<td>".($value['Ambient High']/100)."</td>";
						$htmlMetricsTable .= "<td>".($value['Ambient Low']/100)."</td>";
						$htmlMetricsTable .= "<td>".($value['Ambient Avg']/100)."</td>";
						$htmlMetricsTable .= "</tr>";
						
						//get fans
						$fans = array();
						$fans_found = 0;
						
						if (isset($value['FanRPM1']) && ($value['FanRPM0'] != 0) && ($value['FanRPM0'] < 65000)) {
							array_push($fans,$value['FanRPM0']);
							$fans_found++;
						}
						
						if (isset($value['FanRPM1']) && ($value['FanRPM1'] != 0) && ($value['FanRPM1'] < 65000)) {
							array_push($fans,$value['FanRPM1']);
							$fans_found++;
						}
						if (isset($value['FanRPM2']) && ($value['FanRPM2'] != 0) && ($value['FanRPM2'] < 65000)) {
							array_push($fans,$value['FanRPM2']);
							$fans_found++;
						}
						if (isset($value['FanRPM3']) && ($value['FanRPM3'] != 0) && ($value['FanRPM3'] < 65000)) {
							array_push($fans,$value['FanRPM3']);
							$fans_found++;
						}
						
						$fanWarningMessage = '';
						$color = '';
						if (isset($value['Board number'])) {
							if ((($value['Board number']) == 0) && ($fans_found < 2)) {
								$fanWarningMessage .= "color=red title='Expected 2 fans, but found only " . $fans_found . "'";
								$color = 'red';
							} else if ((($value['Board number']) == 1) && ($fans_found < 3)) {
								$fanWarningMessage .= "color=red title='Expected 3 fans, but found only " . $fans_found . "'";
								$color = 'red';
							} else {
								$fanWarningMessage .= "title='Found " . $fans_found . " fans as expected'";
								$color = 'black';
							}
						}
												
						$htmlMetricsTable .= "<tr><td style='padding-left: 40px;' " . $fanWarningMessage . "><font color=".$color.">Fan Speed (RPM)</font></td>";
						$htmlMetricsTable .= "<td>".number_format(max($fans))."</td>";
						$htmlMetricsTable .= "<td>".number_format(min($fans))."</td>";
						$htmlMetricsTable .= "<td>".number_format(array_sum($fans)/count($fans))."</td>";
						$htmlMetricsTable .= "</tr>";
						
						//get pumps
						$pumps = array($value['PumpRPM0'], $value['PumpRPM1']);
						$htmlMetricsTable .= "<tr><td style='padding-left: 40px;'>Pump Speed (RPM)</td>";
						$htmlMetricsTable .= "<td>".number_format(max($pumps))."</td>";
						$htmlMetricsTable .= "<td>".number_format(min($pumps))."</td>";
						$htmlMetricsTable .= "<td>".number_format(array_sum($pumps)/count($pumps))."</td>";
						$htmlMetricsTable .= "</tr>";
						
						$htmlMetricsTable .= "</table>";
					}
				}
			
			}
		
			$sumArr['GHash'] = 0;
			$sumArr['GHash5s'] = 0;
			$sumArr['Accepted'] = 0;
			$sumArr['Rejected'] = 0;
			$sumArr['Errors'] = 0;
			$sumArr['Utility'] = 0;

			foreach ($d as $key => $value){
			
				// Assemble summary table, one line per board
				if (isset($value['ASC']) && !isZombie($d,$value['ASC'])){
					
 					$htmlSummaryTable .= "<tr><td style='padding-left: 40px;'>CTA" . $value['ASC'] . "</td>";
 					
 					$htmlSummaryTable .= "<td>";
					$htmlSummaryTable .= number_format($value['Hashrate']) . " GH/s";
					$htmlSummaryTable .= "</td>";
					
					$sumArr['GHash'] += $value['Hashrate'];
					
					$htmlSummaryTable .= "<td>";
					$htmlSummaryTable .= number_format($value['Difficulty Accepted']);
					$htmlSummaryTable .= "</td>";
					
					$sumArr['Accepted'] += $value['Difficulty Accepted'];
					
					$htmlSummaryTable .= "<td>";
					$htmlSummaryTable .= number_format($value['Difficulty Rejected']);
					$htmlSummaryTable .= "</td>";
					
					$sumArr['Rejected'] += $value['Difficulty Rejected'];
					
					$htmlSummaryTable .= "<td>";
					$htmlSummaryTable .= number_format($value['Hardware Errors']);
					$htmlSummaryTable .= "</td></tr>";
					
					$sumArr['Errors'] += $value['Hardware Errors'];
					
 					$sumArr['Utility'] += $value['Utility'];
				}	
			}
			
			$htmlSummaryTableTotalsLine = "";
						
			if ($boardCnt > 0) {
				// add totals row
				$htmlSummaryTableTotalsLine .= "<tr style='font-weight:bold;'><td>Totals</td><td>";
				$htmlSummaryTableTotalsLine .= number_format($sumArr['GHash']) . " GH/s";
				$htmlSummaryTableTotalsLine .= "</td>";
					
				$htmlSummaryTableTotalsLine .= "<td>";
				$htmlSummaryTableTotalsLine .= number_format($sumArr['Accepted']);
				$htmlSummaryTableTotalsLine .= "</td>";
					
				$htmlSummaryTableTotalsLine .= "<td>";
				$htmlSummaryTableTotalsLine .= number_format($sumArr['Rejected']);
				$htmlSummaryTableTotalsLine .= "</td>";
					
				$htmlSummaryTableTotalsLine .= "<td>";
				$htmlSummaryTableTotalsLine .= number_format($sumArr['Errors']);
				$htmlSummaryTableTotalsLine .= "</td></tr>";
			}
		
		} else { 
			array_push($minerOffLineArr, $m);
			continue;
		}
	} else {
		array_push($minerOffLineArr, $m);
		continue;
	}
}

if(count($minerOffLineArr) > 0){
	$StatusFrameStr .= count($minerOffLineArr)." TerraMiner";
	if (count($minerList)>1) $htmlMainStr .= "s";
	$StatusFrameStr .= " Offline: <ul>";
	foreach($minerOffLineArr as $minerOff){
		$StatusFrameStr .= "<li> " . $minerOff . "</span>";
	}
	$StatusFrameStr .= "</ul><hr>";
}

$StatusFrameStr .= "<table class='execsum' cellspacing=0 cellpadding=1><tr><th></th><th ";
$StatusFrameStr .= ">Hash Rate</th><th ";
$StatusFrameStr .= ">Accepted</th>	<th ";
$StatusFrameStr .= ">Rejected</th> <th ";
$StatusFrameStr .= ">Errors</th> </tr> ";
$StatusFrameStr .= $htmlSummaryTableTotalsLine;
$StatusFrameStr .= $htmlSummaryTable;
$StatusFrameStr .= "</table>";
$StatusFrameStr .= $htmlMetricsTable;

$StatusFrameStr .= "</table>";
?>

<?php echo $StatusFrameStr ?>
