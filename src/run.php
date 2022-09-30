<?php

include("gitlab.inc.php");

if (!getenv('TO_PROJECT_ID') or !getenv('FROM_PROJECT_ID') or !getenv('FROM_TOKEN') or !getenv('FROM_HOST') or !getenv('TO_TOKEN') or !getenv('TO_HOST')) {
	die('You must provide the docker env variables for FROM_TOKEN, FROM_HOST, TO_TOKEN and TO_HOST.');
}

// connect to source server
$gitlab_from = new gitlab();
$gitlab_from->set_token(getenv('FROM_TOKEN'));
$gitlab_from->set_host(getenv('FROM_HOST'));
$gitlab_from->set_project_id(getenv('FROM_PROJECT_ID'));

// connect to destination server
$gitlab_dest = new gitlab();
$gitlab_dest->set_token(getenv('TO_TOKEN'));
$gitlab_dest->set_host(getenv('TO_HOST'));
$gitlab_dest->set_project_id(getenv('TO_PROJECT_ID'));

// fetch labels from both servers
$labels_from = $gitlab_from->get_label_types();
$labels_dest = $gitlab_dest->get_label_types();

$missing_labels = [];

// fetch labels to migrate
foreach ($labels_from as $label_from) {

	$found = false;

	foreach ($labels_dest as $label_dest) {
		if (!isset($label_dest['name'])) continue; 
		if ($label_from['name'] == $label_dest['name']) $found = true;
	}

	if ($found == false) $missing_labels[] = $label_from;
}

// create all missing labels
if (count($missing_labels) > 0) {
	
	echo "Creating missing labels:\n";

	foreach ($missing_labels as $missing_label) {
		echo "\t".$missing_label['name'].' ';
		$response = $gitlab_dest->create_label($missing_label['name'], $missing_label['color'], $missing_label['description'], $missing_label['priority']);
		echo "[ok]\n";
	}	
}

// no labels to migrate.
else echo "No missing labels found.\n";

echo "\n";

// fetch all issues from old server
foreach ($gitlab_from->list_issues() as $ticket_from) {

	echo "Adding issue #".$ticket_from['id']." ".$ticket_from['title']." ";

	$ticket_dest = $gitlab_dest->create_issue(
		$ticket_from['id'],
		$ticket_from['title'],
		$ticket_from['description'],
		$ticket_from['created'],
		$ticket_from['labels']
	);

	$comments = $gitlab_from->get_comments($ticket_from['id']);

	foreach ($comments as $c) {
		$gitlab_dest->create_comment($ticket_dest['iid'], $c['body'], $c['created_at']);
	}

	echo "[ok]\n";
}

echo "\nDone.\n";