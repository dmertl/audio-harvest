<?php
App::uses('FeedItem', 'Model');

/**
 * @property TestFeedItem $FeedItem
 */
class FeedItemTest extends CakeTestCase {

	public $fixtures = array(
		'app.feed_item',
		'app.feed',
		'app.link',
		'app.mp3'
	);

	public function setUp() {
		parent::setUp();
		Configure::write('FeedItem.blacklist', array(
			'test' => array(
				'Feed' => array('title' => '/test feed/i'),
				'FeedItem' => array('title' => '/test feed item/i')
			)
		));
		$this->FeedItem = ClassRegistry::init('TestFeedItem');
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

	public function testScrapeAll() {
		$this->FeedItem->getHttpSocket()->testResponseBody = array(
			'Lorem ipsum dolor sit amet' => '<a href="test1.mp3">',
			'Lorem ipsum dolor sit amet 2' => '<a href="test2.mp3">',
			'Lorem ipsum dolor sit amet 3' => '<a href="test3.mp3">'
		);
		$this->FeedItem->scrapeAll();
		//Assert correct links added
		$link_1 = $this->FeedItem->Link->find('count', array('conditions' => array('Link.url' => 'test1.mp3')));
		$this->assertEqual($link_1, 0);
		$link_2 = $this->FeedItem->Link->find('count', array('conditions' => array('Link.url' => 'test2.mp3')));
		$this->assertEqual($link_2, 1);
		$link_3 = $this->FeedItem->Link->find('count', array('conditions' => array('Link.url' => 'test3.mp3')));
		$this->assertEqual($link_3, 1);
		//Assert scraped updated
		$feed_2 = $this->FeedItem->field('scraped', array('FeedItem.id' => '2'));
		$this->assertEqual($feed_2, 1);
		$feed_3 = $this->FeedItem->field('scraped', array('FeedItem.id' => '3'));
		$this->assertEqual($feed_3, 1);
	}

	public function testScrape() {
		$this->FeedItem->query('TRUNCATE TABLE links');
		$this->FeedItem->getHttpSocket()->testResponseBody = array(
			'Lorem ipsum dolor sit amet 2' => '<a href="http://www.example.com/test1.mp3">
			<a href="http://www.mediafire.com/asdf">
			<a href="http://www.zshare.com/asdf">
			<a href="http://www.example.com/test_bad.jpg">
			<embed src="http://www.soundcloud.com/test4.mp3">
			<embed src="http://www.example.com/test_bad.mp3">'
		);
		$this->FeedItem->scrape(array('FeedItem' => array('link' => 'Lorem ipsum dolor sit amet 2', 'id' => 2)));
		$links = $this->FeedItem->Link->find('all', array('recursive' => -1));
		foreach($links as $link) {
			$this->assertEqual($link['Link']['feed_item_id'], 2);
			if($link['Link']['url'] == 'http://www.example.com/test1.mp3') {
				$this->assertEqual($link['Link']['type'], (string)LinkType::MP3);
			} else if($link['Link']['url'] == 'http://www.mediafire.com/asdf') {
				$this->assertEqual($link['Link']['type'], (string)LinkType::MEDIAFIRE);
			} else if($link['Link']['url'] == 'http://www.zshare.com/asdf') {
				$this->assertEqual($link['Link']['type'], (string)LinkType::ZSHARE);
			} else if($link['Link']['url'] == 'http://www.soundcloud.com/test4.mp3') {
				$this->assertEqual($link['Link']['type'], (string)LinkType::SOUNDCLOUD);
			} else {
				$this->fail('Invalid link url added, "' . $link['Link']['url'] . '"');
			}
		}
	}

	public function testScrapeNon200Response() {
		$this->FeedItem->getHttpSocket()->testResponseCode = 404;
		$this->FeedItem->scrape(array('FeedItem' => array('link' => 'Lorem ipsum dolor sit amet 2', 'id' => 2)));
		$scraped = $this->FeedItem->field('scraped', array('link' => 'Lorem ipsum dolor sit amet 2'));
		$this->assertEqual($scraped, 1);
	}

	public function testScrapeEmptyResponse() {
		$this->FeedItem->getHttpSocket()->testResponseBody = array(
			'Lorem ipsum dolor sit amet 2' => ''
		);
		$this->FeedItem->scrape(array('FeedItem' => array('link' => 'Lorem ipsum dolor sit amet 2', 'id' => 2)));
		$scraped = $this->FeedItem->field('scraped', array('link' => 'Lorem ipsum dolor sit amet 2'));
		$this->assertEqual($scraped, 1);
	}

}

class MockHarvestHttpSocket {

	public $testResponseCode = '200';
	public $testResponseBody = array();
	public $testResponseReasonPhrase = '';

	public function get($uri) {
		if($this->testResponseCode === '200') {
			return $this->testResponseBody[$uri];
		} else {
			throw new FeedResponseException($this->testResponseReasonPhrase, $this->testResponseCode);
		}
	}

}

class TestFeedItem extends FeedItem {

	public $useTable = 'feed_items';
	public $alias = 'FeedItem';
	public $name = 'FeedItem';

	public function __construct($id = false, $table = null, $ds = null) {
		parent::__construct($id, $table, $ds);
		$this->httpSocket = new MockHarvestHttpSocket();
	}

	public function getHttpSocket() {
		return $this->httpSocket;
	}

}
