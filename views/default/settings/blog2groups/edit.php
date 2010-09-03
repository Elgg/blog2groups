<?php

$url = get_plugin_setting('url', 'blog2groups');

echo '<label>' . elgg_echo('blog2groups:url') . ':</label>';
echo elgg_view('input/text', array(
	'internalname' => 'params[url]',
	'value' => $url,
));
