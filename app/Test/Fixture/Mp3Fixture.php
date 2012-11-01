<?php
/**
 * Mp3Fixture
 *
 */
class Mp3Fixture extends CakeTestFixture {

/**
 * Fields
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'key' => 'primary'),
		'link_id' => array('type' => 'integer', 'null' => true, 'default' => null),
		'url' => array('type' => 'string', 'null' => false, 'default' => null, 'collate' => 'latin1_swedish_ci', 'charset' => 'latin1'),
		'filename' => array('type' => 'string', 'null' => true, 'default' => null, 'collate' => 'latin1_swedish_ci', 'charset' => 'latin1'),
		'hash' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => 32, 'collate' => 'latin1_swedish_ci', 'charset' => 'latin1'),
		'size' => array('type' => 'integer', 'null' => true, 'default' => null),
		'name' => array('type' => 'string', 'null' => true, 'default' => null, 'collate' => 'latin1_swedish_ci', 'charset' => 'latin1'),
		'artist' => array('type' => 'string', 'null' => true, 'default' => null, 'collate' => 'latin1_swedish_ci', 'charset' => 'latin1'),
		'length' => array('type' => 'integer', 'null' => true, 'default' => null),
		'bitrate' => array('type' => 'integer', 'null' => true, 'default' => null),
		'created' => array('type' => 'datetime', 'null' => true, 'default' => null),
		'downloaded' => array('type' => 'datetime', 'null' => true, 'default' => null),
		'error' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => 80, 'collate' => 'latin1_swedish_ci', 'charset' => 'latin1'),
		'indexes' => array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1)
		),
		'tableParameters' => array('charset' => 'latin1', 'collate' => 'latin1_swedish_ci', 'engine' => 'MyISAM')
	);

/**
 * Records
 *
 * @var array
 */
	public $records = array(
		array(
			'id' => 1,
			'link_id' => 1,
			'url' => 'Lorem ipsum dolor sit amet',
			'filename' => 'Lorem ipsum dolor sit amet',
			'hash' => 'Lorem ipsum dolor sit amet',
			'size' => 1,
			'name' => 'Lorem ipsum dolor sit amet',
			'artist' => 'Lorem ipsum dolor sit amet',
			'length' => 1,
			'bitrate' => 1,
			'created' => '2012-10-25 23:00:30',
			'downloaded' => '2012-10-25 23:00:30',
			'error' => 'Lorem ipsum dolor sit amet'
		),
	);

}
