<?php

require('vendor/autoload.php');
require('conf.php');
require('utils.php');

function output($data) {
	echo json_encode((object) $data);
	exit();
}

$db = db_connect();

if (isset($_POST['action'])) {
	switch ($_POST['action']) {
		case 'add':
			$url = $_POST['url'];
			
			if (strpos($url, 'http') !== 0)
				$url = 'http://' . $url;

			$rss = new SimplePie();
			$rss->set_feed_url($url);
			$test = $rss->init();
			
			if ($test == false) {
				output([
					'status' => 'error',
					'message' => 'The provided URL seems not be a valid feed'
				]);
			}

			$nickname = strtolower($rss->get_title());
			$nickname = preg_replace('/[^a-z0-9]/', '', $nickname);
			$password = random_string(10);
			$fullname = $rss->get_title();
			$bio = $rss->get_description();
			$homepage = $rss->get_link();

			$fields = array(
				'nickname' => $nickname,
				'password' => $password,
				'confirm' => $password,
				'fullname' => $fullname,
				'bio' => $bio . ' (unofficial account, proxy for the RSS feed)',
				'homepage' => $homepage
			);
			
			$result = do_call('POST', '/api/account/register.json', $fields);
			if ($result === false) {
				output([
					'status' => 'error',
					'message' => 'Unable to create the new account'
				]);
			}
			
			$result = json_decode($result);

			if ($rss->get_image_url() != null) {
				$image = resize_remote_image($rss->get_image_url(), 96, 96);
				$result = do_call('POST', '/api/account/update_profile_image.json', ['image' => curl_file_create($image)], [$nickname, $password]);
				$result = json_decode($result);
				@unlink($image);
			}

			$query = sprintf("INSERT INTO accounts (feed, url, fullname, nickname, password, image, lastupdate) VALUES ('%s', '%s', '%s', '%s', '%s', '%s', strftime('%%s', 'now'))",
						$url, $homepage, $fullname, $nickname, $password, $result->profile_image_url);
			$db->exec($query);

			output([
				'status' => 'ok',
				'message' => 'The new account has been created!',
				'nickname' => $nickname,
				'url' => $result->statusnet_profile_url
			]);

			break;
	}
}

?>

<html>

<head>
	<meta charset="utf-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">

	<title>GNUS</title>

	<link rel="stylesheet" type="text/css" href="css/bulma.css">
</head>

<body>
	<section class="section">
		<div class="container">
			<div class="heading">
				<h1 class="title"><img src="img/logo.png" /></h1>
				<h2 class="subtitle">
					Populating the GNU Social network, one feed at a time.
				</h2>
			</div>

			<hr/>

			<p>
				This is an instance of <strong>GNUS</strong>, a simple <strong>GNU Social</strong> accounts generator.
			</p>
			<p>
				Each managed account acts as a proxy for a given Atom/RSS feed, and is constantly updated with incoming news.
			</p>
			<p>
				The current instance is connected to <a href="<?php echo $conf['social']['url'] ?>">this social node</a>, but you can eventually run your own: check out code and instructions <a href="https://github.com/madbob/GNUS">here</a>.
			</p>
			<p>
				The name <i>gnus</i> is pronounced as the word <i>news</i>.
			</p>
		</div>
	</section>
	
	<section class="section">
		<div class="container">
			<div class="heading">
				<h1 class="title">Managed Accounts</h1>
			</div>

			<hr/>

			<div class="columns is-multiline">
				<?php
			
				$results = $db->query('SELECT * FROM accounts ORDER BY id DESC');
				while ($row = $results->fetchArray()) {
					?>
					
					<div class="column is-third">
						<div class="card">
							<div class="card-content">
								<div class="media">
									<div class="media-left">
										<figure class="image is-32x32">
											<img src="<?php echo $row['image'] ?>">
										</figure>
									</div>
									<div class="media-content">
										<p class="title is-5"><?php echo $row['fullname'] ?></p>
										<p class="subtitle is-6">@<?php echo $row['nickname'] ?></p>
									</div>
								</div>

								<div class="content">
									<p class="subtitle is-6"><a href="<?php echo sprintf('%s/%s', rtrim($conf['social']['url']), $row['nickname']) ?>">Follow Me!</a></p>
									<small>Last update: <?php echo date('d/m/Y G:i:s', $row['lastupdate']) ?></small>
								</div>
							</div>
						</div>
					</div>
					
					<?php
				}
			
				?>
			</div>
		</div>
	</section>

	<section class="section">
		<div class="container">
			<div class="heading">
				<h1 class="title">Add a Source</h1>
			</div>

			<hr/>

			<p>
				Contribute by adding a source: a new account will be created, ready to be followed on the GNU Social network!
			</p>

			<hr/>

			<form method="POST" id="adding">
				<p class="control">
					<input class="input" type="text" name="url" placeholder="URL of the Atom/RSS feed" autocomplete="off">
				</p>

				<p class="control">
					<button type="submit" class="button is-primary" autocomplete="off">Submit</button>
				</p>
			</form>
		</div>
	</section>

	<footer class="footer">
		<div class="container">
			<div class="content is-centered">
				<p>
					Created by <a href="http://madbob.org/">Roberto -MadBob- Guido</a>.
				</p>
			</div>
		</div>
	</footer>

	<script type="application/javascript" src="js/jquery-2.2.0.min.js"></script>
	<script type="application/javascript" src="js/gnus.js"></script>
</body>

</html>

