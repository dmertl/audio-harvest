<?php
/**
 * FeedItemFixture
 */
class FeedItemFixture extends CakeTestFixture {

	/**
	 * Fields
	 * @var array
	 */
	public $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'key' => 'primary'),
		'feed_id' => array('type' => 'integer', 'null' => false, 'default' => null),
		'title' => array('type' => 'string', 'null' => true, 'default' => null, 'collate' => 'latin1_swedish_ci', 'charset' => 'latin1'),
		'link' => array('type' => 'string', 'null' => false, 'default' => null, 'collate' => 'latin1_swedish_ci', 'charset' => 'latin1'),
		'guid' => array('type' => 'string', 'null' => true, 'default' => null, 'collate' => 'latin1_swedish_ci', 'charset' => 'latin1'),
		'scraped' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => 4),
		'created' => array('type' => 'datetime', 'null' => true, 'default' => null),
		'indexes' => array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1)
		),
		'tableParameters' => array('charset' => 'latin1', 'collate' => 'latin1_swedish_ci', 'engine' => 'MyISAM')
	);

	/**
	 * Records
	 * @var array
	 */
	public $records = array(
		array(
			'id' => 1,
			'feed_id' => 1,
			'title' => 'Lorem ipsum dolor sit amet',
			'link' => 'Lorem ipsum dolor sit amet',
			'guid' => 'Lorem ipsum dolor sit amet',
			'scraped' => 1,
			'created' => '2012-10-25 22:59:54'
		),
		array(
			'id' => 2,
			'feed_id' => 1,
			'title' => 'Lorem ipsum dolor sit amet 2',
			'link' => 'Lorem ipsum dolor sit amet 2',
			'guid' => 'Lorem ipsum dolor sit amet 2',
			'scraped' => 0,
			'created' => '2012-10-25 22:59:54'
		),
		array(
			'id' => 3,
			'feed_id' => 2,
			'title' => 'Lorem ipsum dolor sit amet 3',
			'link' => 'Lorem ipsum dolor sit amet 3',
			'guid' => 'Lorem ipsum dolor sit amet 3',
			'scraped' => 0,
			'created' => '2012-10-25 22:59:54'
		)
	);

}
