<?php

App::uses('Feed', 'Model');

/**
 * Feed Test Case
 * @property TestFeed $Feed
 */
class FeedTest extends CakeTestCase {

	public $fixtures = array(
		'app.feed',
		'app.feed_item',
		'app.link'
	);

	public function setUp() {
		parent::setUp();
		$this->Feed = ClassRegistry::init('TestFeed');
	}

	public function tearDown() {
		unset($this->Feed);
		parent::tearDown();
	}

	public function testScrapeAll() {
		$xml = $this->getSampleFeed(array(
				array('title' => 'test 1', 'link' => 'test 1')
			)
		);
		$this->Feed->getHttpSocket()->testResponseBody = $xml;
		$this->Feed->scrapeAll();

		//Assert Feed 1 last_scraped updated
		$expected = time();
		$actual = strtotime($this->Feed->field('last_scraped', array('id' => 1)));
		$this->assertWithinMargin($actual, $expected, 1, 'last_scraped was not updated.');

		//Assert Feed 2 last_scraped updated
		$expected = time();
		$actual = strtotime($this->Feed->field('last_scraped', array('id' => 2)));
		$this->assertWithinMargin($actual, $expected, 1, 'last_scraped was not updated.');
	}

	public function testScrapeCreatesFeedItems() {
		$xml = $this->getSampleFeed(array(
				array('title' => 'test 1', 'link' => 'test 1'),
				array('title' => 'test 2', 'link' => 'test 2')
		));
		$this->Feed->getHttpSocket()->testResponseBody = $xml;
		$this->Feed->scrape(array('Feed' => array('id' => 1, 'link' => 'test')));
		//Assert FeedItem test 1 created
		$item_1 = $this->Feed->FeedItem->find('first', array('conditions' => array('FeedItem.link' => 'test 1'), 'recursive' => -1));
		$this->assertEqual(count($item_1), 1);
		//Assert FeedItem test 2 created
		$item_2 = $this->Feed->FeedItem->find('first', array('conditions' => array('FeedItem.link' => 'test 2'), 'recursive' => -1));
		$this->assertEqual(count($item_2), 1);
		//Assert Feed last_scraped updated
		$expected = time();
		$actual = strtotime($this->Feed->field('last_scraped', array('id' => 1)));
		$this->assertWithinMargin($actual, $expected, 1, 'last_scraped was not updated.');
	}

	public function testBlacklistedItemsNotAdded() {
		Configure::write('FeedItem.blacklist', array(
			'test' => array(
				'Feed' => array(
					'title' => '/Chemical Jump/i'
				),
				'FeedItem' => array(
					'title' => '/test [0-1]/i'
				)
			)
		));
		$xml = $this->getSampleFeed(array(
			array('title' => 'test 1', 'link' => 'test 1'),
			array('title' => 'test 2', 'link' => 'test 2')
		));
		$this->Feed->getHttpSocket()->testResponseBody = $xml;
		$this->Feed->scrape(array('Feed' => array('id' => 1, 'link' => 'test', 'title' => 'Chemical Jump')));
		//Assert FeedItem test 1 not created
		$item_1 = $this->Feed->FeedItem->find('first', array('conditions' => array('FeedItem.link' => 'test 1'), 'recursive' => -1));
		$this->assertEqual(empty($item_1), true);
		//Assert FeedItem test 2 created
		$item_2 = $this->Feed->FeedItem->find('first', array('conditions' => array('FeedItem.link' => 'test 2'), 'recursive' => -1));
		$this->assertEqual(count($item_2), 1);
	}

	//Error tests

	public function testScrapeWithNon200Response() {
		$feed = array('Feed' => array('id' => 1, 'link' => 'test'));
		$this->Feed->getHttpSocket()->testResponseCode = '404';
		$this->Feed->scrape($feed);
		$expected = time();
		$actual = strtotime($this->Feed->field('last_scraped', array('id' => 1)));
		$this->assertWithinMargin($actual, $expected, 1, 'last_scraped was not updated.');
	}

	public function testScrapeWithEmptyResponse() {
		$feed = array('Feed' => array('id' => 1, 'link' => 'test'));
		$this->Feed->scrape($feed);
		$expected = time();
		$actual = strtotime($this->Feed->field('last_scraped', array('id' => 1)));
		$this->assertWithinMargin($actual, $expected, 1, 'last_scraped was not updated.');
	}

	public function testScrapeInvalidXml() {
		$feed = array('Feed' => array('id' => 1, 'link' => 'test'));
		$this->Feed->getHttpSocket()->testResponseBody = '<?xml version="1.0" encoding="UTF-8"?> <asdf';
		$this->Feed->scrape($feed);
		$expected = time();
		$actual = strtotime($this->Feed->field('last_scraped', array('id' => 1)));
		$this->assertWithinMargin($actual, $expected, 1, 'last_scraped was not updated.');
	}

	public function testScrapeExistingLinkDoesNotCreateDuplicate() {
		$xml = $this->getSampleFeed(array(array('title' => 'test', 'link' => 'Lorem ipsum dolor sit amet')));
		$this->Feed->getHttpSocket()->testResponseBody = $xml;
		$this->Feed->scrape(array('Feed' => array('id' => 1, 'link' => 'test')));
		$actual = $this->Feed->FeedItem->find('all');
		$this->assertEqual(count($actual), 3);
		$expected = time();
		$actual = strtotime($this->Feed->field('last_scraped', array('id' => 1)));
		$this->assertWithinMargin($actual, $expected, 1, 'last_scraped was not updated.');
	}

	public function testScrapeNoItems() {
		$this->Feed->query('TRUNCATE TABLE feed_items');
		$xml = $this->getSampleFeed(array());
		$this->Feed->getHttpSocket()->testResponseBody = $xml;
		$this->Feed->scrape(array('Feed' => array('id' => 1, 'link' => 'test')));
		$actual = $this->Feed->FeedItem->find('all');
		$this->assertEqual(empty($actual), true);
		$expected = time();
		$actual = strtotime($this->Feed->field('last_scraped', array('id' => 1)));
		$this->assertWithinMargin($actual, $expected, 1, 'last_scraped was not updated.');
	}

	public function testScrapeItemWithNoTitle() {
		$this->Feed->query('TRUNCATE TABLE feed_items');
		$xml = $this->getSampleFeed(array(array('link' => 'http://www.example.com/')));
		$this->Feed->getHttpSocket()->testResponseBody = $xml;
		$this->Feed->scrape(array('Feed' => array('id' => 1, 'link' => 'test')));
		$actual = $this->Feed->FeedItem->find('all');
		$this->assertEqual(empty($actual), true);
		$expected = time();
		$actual = strtotime($this->Feed->field('last_scraped', array('id' => 1)));
		$this->assertWithinMargin($actual, $expected, 1, 'last_scraped was not updated.');
	}

	public function testScrapeItemWithNoLink() {
		$this->Feed->query('TRUNCATE TABLE feed_items');
		$xml = $this->getSampleFeed(array(array('title' => 'Test Title')));
		$this->Feed->getHttpSocket()->testResponseBody = $xml;
		$this->Feed->scrape(array('Feed' => array('id' => 1, 'link' => 'test')));
		$actual = $this->Feed->FeedItem->find('all');
		$this->assertEqual(empty($actual), true);
		$expected = time();
		$actual = strtotime($this->Feed->field('last_scraped', array('id' => 1)));
		$this->assertWithinMargin($actual, $expected, 1, 'last_scraped was not updated.');
	}

	protected function getSampleFeed($items) {
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
	<channel>
		<title>Chemical Jump</title>
		<link>http://chemicaljump.com</link>
';
		foreach($items as $item) {
			$xml .= '		<item>';
			if(isset($item['title'])) {
				$xml .= '			<title>' . $item['title'] . '</title>';
			}
			if(isset($item['link'])) {
				$xml .= '			<link>' . $item['link'] . '</link>';
			}
			$xml .= '		</item>';
		}
		$xml .= '	</channel>
</rss>';
		return $xml;
	}

}

class MockHttpSocket {

	public $testResponseCode = '200';
	public $testResponseBody = '';
	public $testResponseReasonPhrase = '';

	public function get($url) {
		$response = new Object();
		$response->code = $this->testResponseCode;
		$response->body = $this->testResponseBody;
		$response->reasonPhrase = $this->testResponseReasonPhrase;
		return $response;
	}

}

class TestFeed extends Feed {

	public $useTable = 'feeds';
	public $alias = 'Feed';
	public $name = 'Feed';

	public function __construct($id = false, $table = null, $ds = null) {
		parent::__construct($id, $table, $ds);
		$this->httpSocket = new MockHttpSocket();
	}

	public function getHttpSocket() {
		return $this->httpSocket;
	}

}