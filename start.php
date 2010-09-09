<?php

/**
 * Pushes blog posts to community forums through web services
 */

register_elgg_event_handler('init', 'system', 'blog2groups_init');

function blog2groups_init() {
	register_elgg_event_handler('create', 'object', 'blog2groups_push_post');
}

function blog2groups_push_post($event, $object_type, $object) {
	// work around Elgg bug with subtype
	$id = get_subtype_id('object', 'blog');
	if ($object->subtype !== 'blog' && $object->subtype !== $id) {
		return;
	}

	$url = get_plugin_setting('url', 'blog2groups');
	if (!$url) {
		return;
	}
	// work around a bug with Elgg encoding parameters
	$url = str_replace('&amp;', '&', $url);

	$body = $object->summary . "\n\n" . $object->description;

	$params = array(
		'username' => $object->getOwnerEntity()->username,
		'title' => $object->title,
		'body' => $body,
	);
	$post_data = http_build_query($params);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$json = curl_exec($ch);
	curl_close($ch);

	$result = json_decode($json);
	if ($result->status != 0) {
		error_log("Failed to send blog post: $result->message");
	}
}
