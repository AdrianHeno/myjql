<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mytenkf extends CI_Controller {
	
	public function index()
	{
		echo 'index';
	}
	/*
	 * For details on data structure please refer to:
	 * https://github.com/10Kft/10kft-api
	 */
	
	//Connects to 10,000ft api and returns a decoded json object
	private function tenkf_connect($resource = array()){//Takes an array containing the specifics of the end point
		$url = $this->config->item('tenkf_base_url') . implode("/", $resource);//Inplode the array to buld the URI
		//Do cURL stuff
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'auth: ' . $this->config->item('tenkf_token')));
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
	
	function get_project($project_id){
		$project = $this->tenkf_connect(array('projects', $project_id));
		return $project;
	}
	
	function get_phases($project_id){//Phases are just projects that have a parent id
		$phases = $this->tenkf_connect(array('projects', $project_id, 'phases?per_page=20000'));
		return $phases;
	}
	
	function get_assignments($phase_id){//Phases are just projects that have a parent id
		$assignments = $this->tenkf_connect(array('projects', $phase_id, 'assignments?per_page=20000'));
		return $assignments;
	}
	
	function get_time_entries($phase_id){
		$time_entries = $this->tenkf_connect(array('projects', $phase_id, 'time_entries?per_page=20000'));
		return $time_entries;
	}
	
	function build_project_budget($project_id){
		$project_budget = 0;
		$project_budget_used = 0;
		$project_hours = 0;
		$project_hours_used = 0;
		
		$phases = $this->get_phases($project_id);
		foreach($phases->data as $phase){
			$assignments = $this->get_assignments($phase->id);
			foreach($assignments->data as $assignment){
				$project_budget = $project_budget + ($assignment->fixed_hours * $assignment->bill_rate);
				$project_hours = $project_hours + $assignment->fixed_hours;
			}
			
			$time_entries = $this->get_time_entries($phase->id);
			foreach($time_entries->data as $time_entry){
				$project_budget_used = $project_budget_used + ($time_entry->hours * $time_entry->bill_rate);
				$project_hours_used = $project_hours_used + $time_entry->hours;
			}
		}
		echo 'Project Budget: $' . $project_budget;
		echo '<hr />';
		echo 'Project Budget Used: $' . $project_budget_used;
		echo '<hr />';
		echo 'Project Hours: ' . $project_hours;
		echo '<hr />';
		echo 'Project Hours Used: ' . $project_hours_used;
	}
}