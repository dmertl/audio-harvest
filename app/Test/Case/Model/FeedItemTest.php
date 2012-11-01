<?php
App::uses('FeedItem', 'Model');

/**
 * FeedItem Test Case
 *
 */
class FeedItemTest extends CakeTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'app.feed_item',
		'app.feed',
		'app.link'
	);

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->FeedItem = ClassRegistry::init('FeedItem');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->FeedItem);

		parent::tearDown();
	}

}
