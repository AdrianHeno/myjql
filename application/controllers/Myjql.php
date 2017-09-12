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
		echo $result;
	}
}
