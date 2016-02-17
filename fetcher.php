<?php

require('vendor/autoload.php');
require('conf.php');
require('utils.php');

$db = db_connect();

$results = $db->query('SELECT * FROM accounts ORDER BY id ASC');
while ($row = $results->fetchArray()) {
	try {
		$last_update = $row['lastupdate'];
		$rss = new SimplePie();
		$rss->set_feed_url($row['feed']);
		$rss->init();

		$items = $rss->get_items();
		$items = array_reverse($items);

		foreach($items as $item) {
			$pub = strtotime($item->get_date());

			if ($pub > $last_update) {
				$status = sprintf('%s - %s', $item->get_title(), $item->get_permalink());
				echo do_call('POST', '/api/statuses/update.json', ['status' => $status], [$row['nickname'], $row['password']]) . "\n";
				$last_update = $pub;
			}
		}

		if ($last_update != $row['lastupdate']) {
			$query = sprintf('UPDATE accounts SET lastupdate = %s WHERE id = %d', $last_update, $row['id']);
			$db->query($query);
		}

		unset($rss);
	}
	catch(Exception $e) {
		// dummy
	}
}

