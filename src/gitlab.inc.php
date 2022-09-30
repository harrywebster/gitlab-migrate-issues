<?php

class gitlab
{
	// curl flags
	public $response = false;
	public $timeout = 10;
	public $header = null;
	public $label_types = [];

	// gitlab flags
	public $host = 'localhost';
	public $project_id = 11;
	public $token = null;
	public $debug = true;
	public $skip_comments = [
		"added (.*) of time spent at \d\d\d\d-\d\d-\d\d",
		"changed time estimate to \d",
		"mentioned in merge request !\d"
	];

	public function set_token($token)
	{
		$this->token = $token;
	}

	public function set_host($host)
	{
		$this->host = $host;
	}

	public function set_project_id($project_id)
	{
		$this->project_id = $project_id;
	}

	public function set_timeout($seconds = 10)
	{
		$this->timeout = $seconds;
	}

	public function get_label_types()
	{
		$response = exec('curl -s --header "PRIVATE-TOKEN: '.$this->token.'" "https://'.$this->host.'/api/v4/projects/'.$this->project_id.'/labels"');

		$label_types = [];
		foreach (json_decode($response) as $r) {
			$label_types[] = (array) $r;
		}

		return $label_types;
	}

	public function create_label($name, $color, $description, $priority)
	{
		$url = 'https://'.$this->host.'/api/v4/projects/'.$this->project_id.'/labels';
		$this->add_header('PRIVATE-TOKEN: '.$this->token);

		$params = [
			"id" => $this->project_id,
			"name" => $name,
			"color" => $color,
			"description" => $description,
			"priority" => $priority
		];

		$response = $this->post($url, $params);

		if ($response !== false) {
			$response = json_decode($response, true);
		}
		else{ 
			echo "Failed to create label:\n";
			echo var_dump($params);
			exit(1);
		}

		return true;
	}

	public function list_issues()
	{
		$on_page = 1;
		$response = [];

		$max_pages = 1000;

		echo "Find all issues:\n";

		while ($on_page <= $max_pages) {

			$url = 'https://'.$this->host.'/api/v4/projects/'.$this->project_id.'/issues?state=opened&scope=all&per_page=100&page='.$on_page;

			$this->add_header('PRIVATE-TOKEN: '.$this->token);
			$raw_response = $this->post($url);

			$curl_response = json_decode($raw_response, true) ?: [];

			echo "\tLoading page $on_page found ".count($curl_response).".\n";

			// we now have all the tickets.
			if (count($curl_response) == 0) break;

			foreach ($curl_response as $issue) {
				$response[] = [
					"id" => $issue["iid"],
					"title" => $issue["title"],
					"description" => $issue["description"],
					"created" => $issue["created_at"],
					"updated" => $issue["updated_at"],
					"status" => $issue["state"],
					"labels" => $issue['labels'] ?? []
				];
			}

			$on_page++;
		}

		return $response;
	}

	public function create_issue($id, $title, $description, $created, $labels=[])
	{
		$url = 'https://'.$this->host.'/api/v4/projects/'.$this->project_id.'/issues';
		$this->add_header('PRIVATE-TOKEN: '.$this->token);

		$params = [
			"id" => $this->project_id,
			"iid" => $id,
			"title" => $title,
			"description" => $description,
			"created_at" => $created,
			"labels" => implode(',',$labels)
		];

		$response = $this->post($url, $params);
		$response = json_decode($response, true);


		return $response;
	}

	public function get_comments($id)
	{
		$request = exec('curl -s --header "PRIVATE-TOKEN: '.$this->token.'" "https://'.$this->host.'/api/v4/projects/'.$this->project_id.'/issues/'.$id.'/notes"');
		$response = [];

		foreach (json_decode($request, true) as $comment) {

			// no comments found
			if (is_string($comment) and $comment = '404 Not found') return [];

			$found = false;
			foreach($this->skip_comments as $s) {
				if (preg_match('#'.$s.'#', $comment['body'])) $found = true;
			}
			if ($found) continue;

			$response[] = $comment;
		}

		return $response;
	}

	public function create_comment($id, $body, $created)
	{
		$url = 'https://'.$this->host.'/api/v4/projects/'.$this->project_id.'/issues/'.$id.'/notes';
		$this->add_header('PRIVATE-TOKEN: '.$this->token);

		$params = [
			"id" => $this->project_id,
			"iid" => $id,
			"body" => $body,
			"created_at" => $created
		];

		$response = $this->post($url, $params);
		$response = json_decode($response, true);

		return $response;
	}

	public function add_header( $arg )
	{
		$header = $this->header;

		if ( ! isset( $this->header ) or ! is_array( $this->header ) ) {
			$header = [ $arg ];
		}
		else{
			// only add the header if it doesn't exist already
			if ( ! in_array( $arg, $this->header ) ) $header[] = $arg;
		}

		$this->header = $header;
	}

	public function post( $url, $fields = [] )
	{
		$this->response = null;

		$fields_string = '';

		foreach($fields as $key=>$value) {
			$fields_string .= $key.'='.urlencode( $value ).'&';
		}
		rtrim($fields_string, '&');

		$ch = curl_init();

		curl_setopt($ch,CURLOPT_URL, $url);
		curl_setopt($ch,CURLOPT_POST, count($fields));
		curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string );
		curl_setopt($ch,CURLOPT_POST, $fields);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT, $this->timeout);
		curl_setopt($ch,CURLOPT_TIMEOUT, $this->timeout);

		// add custom headers
		if ( isset( $this->header ) && ! empty( $this->header )  ) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header );
		}

		$response = curl_exec($ch);

		curl_close($ch);

		if ($response === FALSE) return false;
		return $response;
	}
}
