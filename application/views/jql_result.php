<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
	<title>JQL Form</title>

	<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">

<!-- Optional theme -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap-theme.min.css" integrity="sha384-fLW2N01lMqjakBkx3l/M9EahuwpSfeNvV63J5ezn3uZzapT0u7EYsXMjQV+0En5r" crossorigin="anonymous">

<!-- Latest compiled and minified JavaScript -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>
</head>
<body>
    <div class="container">
		<div class="starter-template">
			<nav class="navbar navbar-default">
				<div class="container-fluid">
					<div class="navbar-header">
						<h3>JQL Results</h3>
					</div>
					<a href="<?php echo $base_url ?>/myjql/jql_form" class="btn btn-success" style="float:right;">New Search</a>
				</div>
			</nav>
			<div class="panel panel-default">
				<!-- Default panel contents -->
				<div class="panel-heading">Result Pannel <span class="right"><?php echo $jql_result->total ?></span></div>

				<!-- Table -->
				<table class="table table-striped">
					<tr>
						<th>Jira Key</th>
						<th>Issue Summary</th>
						<th>Issue Epic</th>
						<th>Original Estimate (Hours)</th>
						<th>Spent (Hours)</th>
						<th>Remaining (Hours)</th>
						<th>Status</th>
					</tr>
					<?php
						$total_original_time_estimate = 0;
						$total_time_spent = 0;
						$total_time_estimate = 0;
						foreach($jql_result->issues as $issue){
							//Convert time from seconds to hours
							$original_time_estimate = ($issue->fields->timeoriginalestimate/60)/60;
							$time_spent = ($issue->fields->timespent/60)/60;
							
							//Build totals
							$total_original_time_estimate = $total_original_time_estimate + $original_time_estimate;
							$total_time_spent = $total_time_spent + $time_spent;

							//If the status is done then don't increase the total							
							if($issue->fields->status->statusCategory->id == 3){
								$time_estimate = 0;
								$total_time_estimate = $total_time_estimate + $time_estimate;
							}else{
								$time_estimate = ($issue->fields->timeestimate/60)/60;
								$total_time_estimate = $total_time_estimate + $time_estimate;
							}
							
							//Change the text colour to match the issue status
							$colour = "black";
							switch ($issue->fields->status->statusCategory->id) {
								case 1:
									$colour = "red";
									break;
								case 2:
									$colour = "blue";
									break;
								case 3:
									$colour = "green";
									break;
								case 4:
									$colour = "orange";
									break;
							}
					?>
					<tr>
						<td><?php echo $issue->key ?></td>
						<td><?php echo $issue->fields->summary ?></td>
						<td><?php if(isset($issue->fields->customfield_10002)){ echo $issue->fields->customfield_10002;} ?></td>
						<td><?php echo $original_time_estimate ?></td>
						<td><?php echo $time_spent ?></td>
						<td><?php echo $time_estimate ?></td>
						<td style="color: <?php echo $colour ?>"><?php echo $issue->fields->status->name ?></td>
					</tr>
					<?php
						}
						
						$percentage_complete = round(100-(($total_time_estimate / $total_original_time_estimate)*100), 2);
					?>
					<tr>
						<td><strong>Total:</strong></td>
						<td></td>
						<td></td>
						<td><?php echo $total_original_time_estimate . ' (' . round($total_original_time_estimate/8, 1) . ' Days)' ?></td>
						<td><?php echo $total_time_spent . ' (' . round($total_time_spent/8, 1) . ' Days)' ?></td>
						<td><?php echo $total_time_estimate . ' (' . round($total_time_estimate/8, 1) . ' Days)' ?></td>
						<td><?php echo $percentage_complete ?>% Complete</td>
					</tr>
				</table>
			</div>
			<div class="panel panel-warning">
				<div class="panel-heading"><h4>Warning!</h4></div>
				<div class="panel-body">
					<p>If an issue takes longer than the original time estimate is will not show in the % complete corectly</p>
				</div>
			</div>
		</div>
    </div>

</body>
</html>