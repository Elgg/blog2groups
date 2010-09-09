<?php

/**
 * Pushes blog posts to community forums through web services
 */

register_elgg_event_handler('init', 'system', 'blog2groups_init');

function blog2groups_init() {
	register_elgg_event_handler('create', 'object', 'blog2groups_push_post');
	register_elgg_event_handler('update', 'object', 'blog2groups_check_publish_status');
}

/**
 * Push the blog post to the configured site
 */
function blog2groups_push_post($event, $object_type, $object) {
	// work around Elgg bug with subtype
	$id = get_subtype_id('object', 'blog');
	if ($object->subtype !== 'blog' && $object->subtype !== $id) {
		return;
	}

	if ($object->access_id == ACCESS_PRIVATE) {
		return;
	}

	$url = get_plugin_setting('url', 'blog2groups');
	if (!$url) {
		return;
	}
	// work around a Elgg bug with encoding parameters
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

/**
 * Check for change in access status and push if going from private to public
 */
function blog2groups_check_publish_status($event, $object_type, $object) {

	if ($object->getSubtype() !== 'blog') {
		return;
	}

	$new_access = get_input('access');

	if ($new_access == ACCESS_PUBLIC && $object->access_id == ACCESS_PRIVATE) {
		$object->access_id = ACCESS_PUBLIC;
		blog2groups_push_post($event, $object_type, $object);
	}
}
