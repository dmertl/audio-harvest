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
			'url' => 'http://chemicaljump.com/wp-content/uploads/2011/06/Fake-Blood-Live-at-Rockness-Festival-(Essential-Mix).mp3',
			'filename' => 'Fake-Blood-Live-at-Rockness-Festival-(Essential-Mix).mp3',
			'hash' => '53946a7f4e1e879ff43f22dbe66d3d90',
			'size' => 65536000,
			'name' => 'Live at Rockness Festival (Essential Mix)',
			'artist' => 'Fake Blood',
			'length' => 200,
			'bitrate' => 320000,
			'created' => '2012-10-25 23:00:30',
			'downloaded' => '2012-10-25 23:00:30',
			'error' => null
		),
		array(
			'id' => 2,
			'link_id' => 1,
			'url' => 'http://www.example.com',
			'filename' => 'no_conflict',
			'hash' => 'no conflict',
			'size' => null,
			'name' => 'no conflict',
			'artist' => 'no conflict',
			'length' => null,
			'bitrate' => null,
			'created' => '2012-10-25 23:00:30',
			'downloaded' => '2012-10-25 23:00:30',
			'error' => null
		),
		array(
			'id' => 3,
			'link_id' => 1,
			'url' => 'http://www.example.com',
			'filename' => 'high_bitrate',
			'hash' => 'conflict hash',
			'size' => null,
			'name' => 'conflict',
			'artist' => 'conflict',
			'length' => null,
			'bitrate' => 320000,
			'created' => '2012-10-25 23:00:30',
			'downloaded' => '2012-10-25 23:00:30',
			'error' => null
		),
		array(
			'id' => 4,
			'link_id' => 1,
			'url' => 'http://www.example.com',
			'filename' => 'low_bitrate',
			'hash' => 'conflict hash',
			'size' => null,
			'name' => 'conflict name',
			'artist' => 'conflict artist',
			'length' => null,
			'bitrate' => 120000,
			'created' => '2012-10-25 23:00:30',
			'downloaded' => '2012-10-25 23:00:30',
			'error' => null
		),
		array(
			'id' => 5,
			'link_id' => 1,
			'url' => 'http://www.example.com',
			'filename' => 'identical_bitrate_1',
			'hash' => 'conflict_2',
			'size' => null,
			'name' => 'conflict_2',
			'artist' => 'conflict_2',
			'length' => null,
			'bitrate' => 320000,
			'created' => '2012-10-25 23:00:30',
			'downloaded' => '2012-10-25 23:00:30',
			'error' => null
		),
		array(
			'id' => 6,
			'link_id' => 1,
			'url' => 'http://www.example.com',
			'filename' => 'identical_bitrate_2',
			'hash' => 'conflict_2',
			'size' => null,
			'name' => 'conflict_2',
			'artist' => 'conflict_2',
			'length' => null,
			'bitrate' => 320000,
			'created' => '2012-10-25 23:00:30',
			'downloaded' => '2012-10-25 23:00:30',
			'error' => null
		),
	);

}
