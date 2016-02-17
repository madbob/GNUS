<?php

function db_connect() {
	global $conf;
	
	$dbname = $conf['db']['path'];

	if (file_exists($dbname) == false) {
		$tmp = new SQLite3($dbname);
		$tmp->exec('CREATE TABLE accounts (id INTEGER PRIMARY KEY, feed TEXT, url TEXT, fullname TEXT, nickname TEXT, password TEXT, image TEXT, lastupdate NUMERIC)');
		$tmp->close();
	}

	return new SQLite3($dbname);
}

function random_string($length) {
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$clen = strlen($characters);
	$r = '';

	for ($i = 0; $i < $length; $i++)
		$r .= $characters[rand(0, $clen - 1)];

	return $r;
}

function do_call($method, $path, $fields, $auth = null) {
	global $conf;
	
	$url = rtrim($conf['social']['url'], '/') . $path;

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	if ($auth != null)
		curl_setopt($ch, CURLOPT_USERPWD, $auth[0] . ":" . $auth[1]);
	
	if ($method == 'POST') {
		curl_setopt($ch,CURLOPT_POST, true);
		curl_setopt($ch,CURLOPT_POSTFIELDS, $fields);
	}
	else if ($method == 'GET') {
		// dummy
	}
	
	curl_setopt($ch,CURLOPT_URL, $url);

	$result = curl_exec($ch);
	$reponse = curl_getinfo($ch);
	curl_close($ch);

	if ($reponse['http_code'] != 200)
		return false;
	else
		return $result;
}
 
function resize_remote_image($url, $width, $height) {
	$original = file_get_contents($url);
	$src = imagecreatefromstring($original);

	$dst = imagecreatetruecolor($width, $height);
	imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));

	$src_width = imagesx($src);
	$src_height = imagesy($src);

	$new_width = $width;
	$new_height = round($new_width * ($src_height / $src_width));
	$new_x = 0;
	$new_y = round(($height - $new_height) / 2);

	$next = $new_height > $height;

	if ($next) {
		$new_height = $height;
		$new_width = round($new_height * ($src_width / $src_height));
		$new_x = round(($width - $new_width) / 2);
		$new_y = 0;
	}

	imagecopyresampled($dst, $src , $new_x, $new_y, 0, 0, $new_width, $new_height, $src_width, $src_height);
	$path = tempnam(sys_get_temp_dir(), 'gnus_') . '.png';
	imagepng($dst, $path);
	return $path;
}

