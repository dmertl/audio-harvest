<?php
/**
 * LinkFixture
 *
 */
class LinkFixture extends CakeTestFixture {

/**
 * Fields
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'key' => 'primary'),
		'feed_item_id' => array('type' => 'integer', 'null' => true, 'default' => null),
		'url' => array('type' => 'string', 'null' => false, 'default' => null, 'collate' => 'latin1_swedish_ci', 'charset' => 'latin1'),
		'type' => array('type' => 'integer', 'null' => false, 'default' => '1'),
		'scraped' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => 4),
		'created' => array('type' => 'datetime', 'null' => true, 'default' => null),
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
			'feed_item_id' => 1,
			'url' => 'http://www.example.com/test_1.mp3',
			'type' => 1,
			'scraped' => 0,
			'created' => '2012-10-25 23:00:21'
		),
		array(
			'id' => 2,
			'feed_item_id' => 1,
			'url' => 'http://www.example.com/test_2.mp3',
			'type' => 1,
			'scraped' => 0,
			'created' => '2012-10-25 23:00:21'
		),
		array(
			'id' => 3,
			'feed_item_id' => 1,
			'url' => 'Lorem ipsum dolor sit amet 3',
			'type' => 1,
			'scraped' => 1,
			'created' => '2012-10-25 23:00:21'
		),
		array(
			'id' => 4,
			'feed_item_id' => 1,
			'url' => 'Lorem ipsum dolor sit amet 4',
			'type' => 2,
			'scraped' => 1,
			'created' => '2012-10-25 23:00:21'
		)
	);

}
