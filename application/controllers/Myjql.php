<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Myjql extends CI_Controller {
	
	public function index()
	{
		$data['base_url'] = $this->config->item('base_url');
		$this->load->view('home', $data);
	}
	
	public function jql_form(){
		$data['base_url'] = $this->config->item('base_url');
		$this->load->view('jql_form', $data);
	}
	
	function jql($username = null, $password = null, $jql = null){//Performs JQL request, returns either a view or object
		$return_json = FALSE;
		set_time_limit(600);
		if(isset($username) && isset($password) && isset($jql)){//if this is being called by another function return json
			$jql = $this->jql_convert($jql);
			$return_json = TRUE;
		}else{
			$username = $_POST['name'];
			$password = $_POST['password'];
			$jql = $this->jql_convert($_POST['jql']);
		}
		
		$url = "https://mentally-friendly.atlassian.net/rest/api/2/search/?maxResults=1000&jql=" . $jql;
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Basic ' . base64_encode("$username:$password")));
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curl, CURLOPT_VERBOSE, true);
		$verbose = fopen('php://temp', 'rw+');
		curl_setopt($curl, CURLOPT_STDERR, $verbose);
		
		$issue_list = (curl_exec($curl));
		$data['jql_result'] = json_decode($issue_list);
		if($return_json == TRUE){
			return $issue_list;
		}else{
			$data['base_url'] = $this->config->item('base_url');
			$this->load->view('jql_result', $data);
		}
	}
	
	function jql_convert($query){
		$query = str_replace('"Epic Link"', "cf[10005]",$query);
		return urlencode($query);
	}
	
	public function auto_create_form()
	{
		$data['base_url'] = $this->config->item('base_url');
		$this->load->view('jql_auto_create_form', $data);
	}
	
	private function create_meeting_issue($project_id, $sprint_id, $assignee){//creates multimple issues from json object
		$username = $this->config->item('jira_username');
		$password = $this->config->item('jira_password');
		//define json payload
		$data_string = '{"issueUpdates": [
						{
							"fields": {
							   "project":
							   { 
								  "id": "' . $project_id . '"
							   },
							   "summary": "--Sprint Meetings--",
							   "description": "Time for sprint artefacts",
							   "issuetype": {
								  "id": "10100"
							   },
							   "customfield_10115":	'. $sprint_id .',
							    "assignee": {
									"name": "' . $assignee . '"
								},
							   "timetracking": {
									"originalEstimate": "12.5h"
								},
								"labels":[
									"Meetings"
								]
						   }
						}
					]}';
		
		$url = $this->config->item('jira_base_url') . "issue/bulk";
		$curl = curl_init();
		
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			'Authorization: Basic ' . base64_encode("$username:$password"),
			'Content-Type: application/json',
			'Content-Length: ' . strlen($data_string))
		);
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);    
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curl, CURLOPT_VERBOSE, true);
		
		$result = curl_exec($curl);
		return TRUE;
		
	}
	
	private function jira_connect($resource = array()){//Takes an array containing the specifics of the end point
		if(stripos($resource[0], 'board') !== FALSE){//Unfortunately the JIRA Rest 2.0 doesn't include board endpoints, to get these we need to use the old 1.0 API
			$jira_url = $this->config->item('jira_base_url_v1');
		}else{
			$jira_url = $this->config->item('jira_base_url');
		}
		
		$url = $jira_url . implode("/", $resource);//Inplode the array to buld the URI

		$username = $this->config->item('jira_username');
		$password = $this->config->item('jira_password');
		//Do cURL stuff
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Basic ' . base64_encode("$username:$password")));
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curl, CURLOPT_VERBOSE, true);
		$verbose = fopen('php://temp', 'rw+');
		curl_setopt($curl, CURLOPT_STDERR, $verbose);
		
		$result = (curl_exec($curl));
		return json_decode($result);
	}
	
	function get_project($project_key){//Get project by KEY eg WINE
		$project = $this->jira_connect(array('project', $project_key));
		return $project;
	}
	
	function get_boards($project_key){//Get all agile boards for a project
		$boards = $this->jira_connect(array('board?projectKeyOrId=' . $project_key));
		return $boards;
	}
	
	function get_sprints($board_id){//Get all sprints for an agile board
		$sprints = $this->jira_connect(array('board', $board_id, 'sprint'));
		return $sprints;
	}
	
	/*
	 *Takes input from lack or url and checks if meeting issues have already been created for all of a projects FUTURE sprints on a per assignee basis
	 *if none are found it creates them
	 *Assignees are found either as a comma seperated strinf in the project description OR as a single user over ride in the $_GET['text']
	 */
	function create_meetings($project_key = null){
		/*
		 *Lets do some minimal validation and just die if anything fails
		 */
		if($_GET['token'] !== "zMt30ABKxewDQlfujov0APYr"){//If we don't get a valid token from slack, die
			die();
		}
		if(!isset($_GET['text']) || strlen($_GET['text']) < 2){ //Check if $_GET['text'] wass passed in, if not send error message and die
			$slack_payload = array (
				'text' => 'Please supply a valid project name after the /artefacts'
			);
			
			die();
		}else{
			$slack_payload = array (
				'text' => 'Working on that for you now, I will let you know when its done.'
			);
		}
		//Slack only gives us 3 seconds to respond...Nothing happens in Jira in under 3 seconds, so send a responce once validation passes and then use the responce url to notify the user once the operation is complete
		ob_start();
		header($_SERVER["SERVER_PROTOCOL"] . " 202 Accepted");
		header("Status: 202 Accepted");
		header("Content-Type: application/json");
		echo json_encode($slack_payload);//Send a payload back to slack so that the user knows that we are working
		header('Content-Length: '.ob_get_length());
		ob_end_flush();
		ob_flush();
		flush();
		
		$assignee_override = array();
		if($project_key == null && isset($_GET['text'])){//If project key is null an $_GET is set then this is a request from slack and not a direct call to the URL. Validation above should have taken care of that anyway with the token
			$text = explode(' ', $_GET['text']);//Did the user just supply a project or a user as well?
			$project_key = $text[0];//We should always have a project
			if(isset($text[1])){//If there is a second segement in the array this will be the user
				$assignee_override[] = $text[1];
			}
		}
		
		$issues_created = 0;
		$project = $this->get_project($project_key);//Get the project, we will need its ID and description later
		if(isset($assignee_override[0])){//Check if a user override was supplied
			$project_team = $assignee_override;//If it was use this instead of the user in the project description
		}else{
			$project_team = explode(',', $project->description);//We are storing the users in the project description as a comma seperated string. Turn this into an array
		}
		$project_boards = $this->get_boards($project_key);//Get all boards that this project is a part of
		foreach($project_boards->values as $project_board){
			$sprints = $this->get_sprints($project_board->id);//Get all sprints for this board
			foreach($sprints->values as $sprint){
				if($sprint->state == "future"){//If sprint is in the future
					foreach($project_team as $assignee){//For each user in the production team
						$jql = "project=" . $project_key . " AND assignee=" . $assignee . " AND sprint =" . $sprint->id . " AND labels = Meetings";
						$results = $this->jql($this->config->item('jira_username'), $this->config->item('jira_password'), $jql);
						$results = json_decode($results);
						if($results->total == 0){//If there aren't any results lets create some!
							if($this->create_meeting_issue($project->id, $sprint->id, $assignee) === TRUE){//If the issue is successfully created increment the counter
								++$issues_created;
							}
						}
					}
				}
			}
		}
		$slack_payload = array (
			'text' => $issues_created . ' JIRA Issues Created. Have a great day!'
		);
	
		//Send encode and send the payload
		$this->post_to_slack($_GET['response_url'], $slack_payload);
	}
	
	/*
	 *A function to take cate of posting delayed responces to slack
	 */
	private function post_to_slack($url, $payload){
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
		
		$json_response = curl_exec($curl);
		
		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		
		if ( $status != 201 ) {
			die("Error: call to URL $url failed with status $status, response $json_response, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
		}
		
		curl_close($curl);
	}
	
	public function web_safe_jql_form(){ //Loads form for generating a JQL Websafe GET string
		$data['base_url'] = $this->config->item('base_url');
		$this->load->view('web_safe_jql_form', $data);
	}
	
	function web_safe_jql(){//Take the POST and show an easy to copy JQL get string
		$data['jql'] = $this->jql_convert($_POST['jql']);
		$data['base_url'] = $this->config->item('base_url');
		$this->load->view('web_safe_jql_result', $data);
	}
	
	private function get_current_sprint($username, $password, $project){//get the current sprint for a project
		$result = $this->jql($username, $password, 'project = ' . $project . ' and Sprint in(openSprints())');
		return $result;
	}
	
	function burndown(){//This is a proof of concept and will need to be reworked if deemed worth the effort
		$username = $this->config->item('jira_username');
		$password = $this->config->item('jira_password');
		if(!isset($_GET['text']) || strlen($_GET['text']) < 2){ //Check if $_GET['text'] wass passed in, if not send error message and die
			$slack_payload = array (
				'text' => 'Please supply a valid project name after the /burndown'
			);
			//Send encode and send the payload
			header('Content-Type: application/json');
			echo json_encode($slack_payload);
			
			die();
		}
		
		$slack_payload = array (
				'text' => 'You want a graph!? Ok give me a minute...'
			);
		//Slack only gives us 3 seconds to respond...Nothing happens in Jira in under 3 seconds, so send a responce once validation passes and then use the responce url to notify the user once the operation is complete
		ob_start();
		header($_SERVER["SERVER_PROTOCOL"] . " 202 Accepted");
		header("Status: 202 Accepted");
		header("Content-Type: application/json");
		echo json_encode($slack_payload);//Send a payload back to slack so that the user knows that we are working
		header('Content-Length: '.ob_get_length());
		ob_end_flush();
		ob_flush();
		flush();
		
		$current_sprint_id = $this->get_current_sprint_id($username, $password, $_GET['text']);
		
		set_time_limit(600);
		$return_json = TRUE;
		//Call the atlassian graph data API
		$url = "https://mentally-friendly.atlassian.net/rest/greenhopper/1.0/rapid/charts/scopechangeburndownchart.json?rapidViewId=12&sprintId=" . $current_sprint_id . "&statisticFieldId=field_timeoriginalestimate";
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Basic ' . base64_encode("$username:$password")));
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curl, CURLOPT_VERBOSE, true);
		$verbose = fopen('php://temp', 'rw+');
		curl_setopt($curl, CURLOPT_STDERR, $verbose);
		
		$data = (curl_exec($curl));
		$data = json_decode($data);
		#echo $data;
		
		//Define the sprint timebox
		$sprint_start_date = '3000-12-31';
		$sprint_end_date = '2000-12-31';
		foreach($data->workRateData->rates as $rates){
			//Atlassian store ther unix time stamp as number of miliseconds since epoch -.-
			if(date("Y-m-d", substr($rates->start, 0, -3)) < $sprint_start_date){//Find sprint start date
				$sprint_start_date = date("Y-m-d", substr($rates->start, 0, -3));
			}
			if(date("Y-m-d", substr($rates->end, 0, -3)) > $sprint_end_date){//Find sprint end date
				$sprint_end_date = date("Y-m-d", substr($rates->end, 0, -3));
			}
		}

		//Find how much of the sprint has been completed
		$total_seconds = 0;
		$issue_array = array();
		foreach($data->changes as $key => $changes){//$changes is provided in chronological order, so we can just add remove add remove until we get to the end and we should have the right result
			if(isset($changes[0]->statC->newValue)){//If an item has a value add it to the array
				$issue_array[$changes[0]->key]['value'] = $changes[0]->statC->newValue;
			}elseif(isset($changes[0]->added) && $changes[0]->added == false){//If the item has "added": false this means it was removed from the sprint and needs to be unset from our array
				unset($issue_array[$changes[0]->key]);
			}
			if(isset($changes[0]->column->done) && $changes[0]->column->done == true){//If done is true then record the timestamp it was completed
				$issue_array[$changes[0]->key]['done'] = date("Y-m-d", substr($key, 0, -3));
			}
		}
		
		/*
		 *Now that we have a clean array loop and count to get the total
		 */
		foreach($issue_array as $ia){
			$total_seconds = $total_seconds + $ia['value'];//If the changes has hours add them to the total
		}
		$total_hours = ($total_seconds/60)/60;//Convert time from seconds to hours
		
		/*
		 *Find how many week days there are between sprint start and sprint end excluding weekends
		 *Add these days to an array that we can use to contain our burndown progress
		 */
		$sprint_days = array();
		$begin = new DateTime( $sprint_start_date );
		$end = new DateTime( $sprint_end_date );
		$end = $end->modify( '+1 day' ); 
		
		$interval = new DateInterval('P1D');
		$daterange = new DatePeriod($begin, $interval ,$end);
		
		foreach($daterange as $date){
			if($date->format("l") !== "Saturday" && $date->format("l") !== "Sunday"){//Don't include weekends
				$sprint_days[$date->format("Y-m-d")] = 0;
			}
		}
		
		
		/*
		 *Now that we have an array of days and an array of issues,
		 *loop through the array of issues and add their value to the total for each day
		 */
		foreach($issue_array as $issue){
			if(isset($issue['done'])){
				$sprint_days[$issue['done']] = $sprint_days[$issue['done']] + $issue['value'];
			}
		}
		
		/*
		 *Now that we have all of the data in a useable format, build the graph URL
		 */
		$bench_increment = round($total_hours/count($sprint_days), 2);
		$bench_daily = $total_hours;

		$bench_string = "";
		while($bench_daily > 0){//Create a string for the linear line for the optimal burndown bench mark
			$bench_string = $bench_string . round($bench_daily, 0) . ",";
			$bench_daily = $bench_daily - $bench_increment;//Reduce increment as we count down
		}
		$bench_string = $bench_string . "0";
		
		$progress_string = "";
		$progress_total = $total_hours;
		foreach($sprint_days as $key => $sprint_day){//Create a string for the sprint progress burn down
			if($key > $this->get_day_in_sydney()){//Line needs to stop after today so break the loop if the $key is greater than today
				break;
			}
			$progress_total = $progress_total - (($sprint_day/60)/60);//Convert from seconds to hours and then subtract from total
			$progress_string = $progress_string . "," . $progress_total;
		}
		$progress_string = $total_hours . $progress_string;

		//Using Image Charts to generate graphs https://image-charts.com/documentation
		$chart_url = "https://image-charts.com/chart?cht=lc&chg=10,10,3,2&chd=t:" . $bench_string . "|" . $progress_string . "&chds=0," . $total_hours . "&chs=500x500&chco=999999,FF0000&chxt=x,y&chxr=0," . count($sprint_days) . ",0|1,0," . $total_hours  . "&chma=30,30,30,30";
	
		/*
		 *Create array to house payload for slack
		 */
		$slack_payload = array (
			"response_type" => "in_channel",//This determines if the responce should be seen by all channel occuments or just the requester
			'attachments' => 
			array (
				0 =>
				array(
					'fallback' => $_GET['text'] . ' Current Sprint Burndown Chart',
					'color' => '#36a64f',
					'title' => $_GET['text'] . ' Current Sprint Burn Down',
					'title_link' => $chart_url,
					'image_url' => $chart_url,
					'thumb_url' => $chart_url
				),
			),
		);
		
		//encode and send the payload
		$this->post_to_slack($_GET['response_url'], $slack_payload);
		
	}
	
	function get_day_in_sydney(){//The server is in the USA so today isn't starting until 2pm. We need today to start at 12am
		$tz = 'Australia/Sydney';
		$timestamp = time();
		$dt = new DateTime("now", new DateTimeZone($tz)); //first argument "must" be a string
		$dt->setTimestamp($timestamp);
		return $dt->format('Y-m-d');
	}
	
	
	private function get_current_sprint_id($username, $password, $project){//Get the current sprint ID for a project
		$jql_result = json_decode($this->get_current_sprint($username, $password, $project));//Get the current sprint for the project

		foreach($jql_result->issues as $issue){//We only need to check 1 of the issues not all
			$sprint_string = explode("[id=", $issue->fields->customfield_10115[0]); //The ID is stored in a string with a bunch of other cruft
			$sprint_id = explode(",",$sprint_string[1]);
			return $sprint_id[0];
			break;//We have what we need, break out of loop
		}
	}
}