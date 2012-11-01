<?php

Configure::write('FeedItem.blacklist', array(
	'indie_sabbath' => array(
		'Feed' => array(
			'title' => '/Earmilk/i'
		),
		'FeedItem' => array(
			'title' => '/.*Indie Sabbath.*/i'
		)
	)
));