<?php
App::uses('FeedItem', 'Model');

/**
 * @property FeedItem $FeedItem
 */
class FeedItemTest extends CakeTestCase {

	public $fixtures = array(
		'app.feed_item',
		'app.feed',
		'app.link'
	);

	public function setUp() {
		parent::setUp();
		Configure::write('FeedItem.blacklist', array(
			'test' => array(
				'Feed' => array('title' => '/test feed/i'),
				'FeedItem' => array('title' => '/test feed item/i')
			)
		));
		$this->FeedItem = ClassRegistry::init('FeedItem');
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function testIsBlacklisted() {
		$data = array(
			'Feed' => array('title' => 'test feed'),
			'FeedItem' => array('title' => 'test feed item')
		);
		$this->assertEqual($this->FeedItem->isBlacklisted($data), true);
	}

	public function testIsBlacklistedNotMatching() {
		$data = array(
			'Feed' => array('title' => 'test fee'),
			'FeedItem' => array('title' => 'test feed ite')
		);
		$this->assertEqual($this->FeedItem->isBlacklisted($data), false);
	}

	public function testIsBlacklistedMissingField() {
		$data = array(
			'Feed' => array('title' => 'test feed'),
			'FeedItem' => array('link' => 'test')
		);
		$this->assertEqual($this->FeedItem->isBlacklisted($data), false);
	}

	public function testIsBlacklistedMissingModel() {
		$data = array(
			'Feed' => array('title' => 'test feed')
		);
		$this->assertEqual($this->FeedItem->isBlacklisted($data), false);
	}

}
