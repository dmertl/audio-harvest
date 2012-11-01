<?php

class DATABASE_CONFIG {

	public $default = array(
		'datasource' => 'Database/Mysql',
		'persistent' => false,
		'host' => 'localhost',
		'login' => 'dev_user',
		'password' => 'hollywood',
		'database' => 'AudioHarvest',
		'prefix' => '',
		//'encoding' => 'utf8',
	);

	public $test = array(
		'datasource' => 'Database/Mysql',
		'persistent' => false,
		'host' => 'localhost',
		'login' => 'dev_user',
		'password' => 'hollywood',
		'database' => 'AudioHarvest_test',
		'prefix' => '',
		//'encoding' => 'utf8',
	);
}
