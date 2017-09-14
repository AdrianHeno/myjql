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
		if(isset($username) && isset($password) && isset($jql)){
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
	
	function create_issue(){//creates multimple issues from json object
		$username = $_POST['name'];
		$password = $_POST['password'];
		$sprint = $_POST['sprint'];
		//define json payload
		$data_string = '{"issueUpdates": [
						{
							"fields": {
							   "project":
							   { 
								  "id": "10800"
							   },
							   "summary": "Assisting team with development tasks",
							   "description": "[~paul] to assist other team members during sprint ' . $sprint . '",
							   "issuetype": {
								  "id": "3"
							   },
							   "customfield_10100":	'. $sprint .',
							   "customfield_10500":	"SPCOR-3388",
							    "assignee": {
									"name": "paul"
								},
							   "timetracking": {
									"originalEstimate": "1.5d"
								}
						   }
						},
						{
						   "fields": {
							   "project":
							   { 
								  "id": "10800"
							   },
							   "summary": "Mid Sprint Code Review",
							   "description": "[~stuart] to review the work of the team for sprint ' . $sprint . '",
							   "issuetype": {
								  "id": "3"
							   },
							   "customfield_10100":	'. $sprint .',
							   "customfield_10500":	"SPCOR-3388",
							   "assignee": {
									"name": "stuart"
								},
							   "timetracking": {
									"originalEstimate": "1d"
								}
						   }
						},
						{
						   "fields": {
							   "project":
							   { 
								  "id": "10800"
							   },
							   "summary": "Assisting team with development tasks",
							   "description": "[~stuart] to assist other team members during sprint ' . $sprint . '",
							   "issuetype": {
								  "id": "3"
							   },
							   "customfield_10100":	'. $sprint .',
							   "customfield_10500":	"SPCOR-3388",
							   "assignee": {
									"name": "stuart"
								},
							   "timetracking": {
									"originalEstimate": "1d"
								}
						   }
						}
					]}';
		
		$url = "https://tempurer.atlassian.net/rest/api/2/issue/bulk";
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
		echo $result;
		
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
	
	function get_current_sprint($username, $password, $project){//get the current sprint for a project
		$result = $this->jql($username, $password, 'project = ' . $project . ' and Sprint in(openSprints())');
		return $result;
	}
	
	function burndown($username, $password, $project){//This is a proof of concept and will need to be reworked if deemed worth the effort
		$current_sprint_id = $this->get_current_sprint_id($username, $password, $project);
		
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
		$total_seconds = 0;
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

		$issue_array = array();
		foreach($data->changes as $key => $changes){
			if(isset($changes[0]->statC->newValue)){
				$total_seconds = $total_seconds + $changes[0]->statC->newValue;//If the changes has hours add them to the total
				$issue_array[$changes[0]->key]['value'] = $changes[0]->statC->newValue;
			}
			if(isset($changes[0]->column->done) && $changes[0]->column->done == 'true'){
				$issue_array[$changes[0]->key]['done'] = date("Y-m-d", substr($key, 0, -3));
			}
		}
		$total_hours = ($total_seconds/60)/60;
		
		/*
		 *Find how many week days there are between sprint start and sprint end
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
		 *Now that we have an array of days and an array of issues, loop through the array of issues and add their value to the total for each day
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
			if($key > date("Y-m-d")){//Line needs to stop after today so break the loop if the $key is greater than today
				break;
			}
			$progress_total = $progress_total - (($sprint_day/60)/60);//Convert from seconds to hours and then subtract from total
			$progress_string = $progress_string . "," . $progress_total;
		}
		$progress_string = $total_hours . $progress_string;

		//Using Image Charts to generate graphs https://image-charts.com/documentation
		$chart_url = "https://image-charts.com/chart?cht=lc&chg=10,10,3,2&chd=t:" . $bench_string . "|" . $progress_string . "&chds=0," . $total_hours . "&chs=500x500&chco=999999,FF0000&chxt=x,y&chxr=0," . count($sprint_days) . ",0|1,0," . $total_hours  . "&chma=30,30,30,30";
		#header("Location: https://image-charts.com/chart?cht=lc&chg=10,10,3,2&chd=t:" . $bench_string . "|" . $progress_string . "&chds=0," . $total_hours . "&chs=500x500&chco=999999,FF0000&chxt=x,y&chxr=0," . count($sprint_days) . ",0|1,0," . $total_hours  . "&chma=30,30,30,30");
		
		/*
		 *Create array to house payload for slack
		 */
		$slack_payload = array (
			'attachments' => 
			array (
				'fallback' => 'Burndown Chart',
				'color' => '#36a64f',
				'title' => 'Burn Down',
				'title_link' => $chart_url,
				'image_url' => $chart_url,
				'thumb_url' => $chart_url,
			),
		);
		
		//Send encode and send the payload
		echo json_encode($slack_payload);
	}
	
	
	function get_current_sprint_id($username, $password, $project){//Get the current sprint ID for a project
		$jql_result = json_decode($this->get_current_sprint($username, $password, $project));//Get the current sprint for the project

		foreach($jql_result->issues as $issue){//We only need to check 1 of the issues not all
			$sprint_string = explode("[id=", $issue->fields->customfield_10115[0]); //The ID is stored in a string with a bunch of other cruft
			$sprint_id = explode(",",$sprint_string[1]);
			return $sprint_id[0];
			break;//We have what we need, break out of loop
		}
	}
}