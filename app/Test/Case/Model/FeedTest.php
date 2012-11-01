<?php

App::uses('Feed', 'Model');

/**
 * Feed Test Case
 * @property TestFeed $Feed
 */
class FeedTest extends CakeTestCase {

	public $fixtures = array(
		'app.feed',
		'app.feed_item'
	);

	public function setUp() {
		parent::setUp();
		$this->Feed = ClassRegistry::init('TestFeed');
		$this->Feed->FeedItem = ClassRegistry::init('FeedItem');
	}

	public function tearDown() {
		unset($this->Feed);
		parent::tearDown();
	}

	public function testScrapeAll() {

	}

	public function testScrape() {
//		$feed = array('Feed' => array('link' => 'test'));
//		$this->Feed->testResponse = file_get_contents(TESTS . 'Fixture' . DS . 'feed.xml');
//		$this->Feed->scrape($feed);
		//TODO: test
	}

	//Error tests

	public function testScrapeWithNon200Response() {
		$feed = array('Feed' => array('id' => 1, 'link' => 'test'));
		$this->Feed->testResponseCode = '404';
		$this->Feed->scrape($feed);
		$expected = time();
		$actual = strtotime($this->Feed->field('last_scraped', array('id' => 1)));
		$this->assertWithinMargin($actual, $expected, 1, 'last_scraped was not updated.');
	}

	public function testScrapeWithEmptyResponse() {
		$feed = array('Feed' => array('link' => 'test'));
		$this->Feed->scrape($feed);
		$expected = time();
		$actual = strtotime($this->Feed->field('last_scraped', array('id' => 1)));
		$this->assertWithinMargin($actual, $expected, 1, 'last_scraped was not updated.');
	}

	public function testScrapeInvalidXml() {
		$feed = array('Feed' => array('link' => 'test'));
		$this->Feed->testResponse = '<?xml version="1.0" encoding="UTF-8"?> <asdf';
		$this->Feed->scrape($feed);
		$expected = time();
		$actual = strtotime($this->Feed->field('last_scraped', array('id' => 1)));
		$this->assertWithinMargin($actual, $expected, 1, 'last_scraped was not updated.');
	}

	public function testScrapeNoItems() {
		//Test scraping a feed with valid XML, but no feed items does not generate an error
	}

	public function testScrapeItemWithNoTitle() {
		//Test scraping a feed with an item with no title does not save and no error
	}

	public function testScrapeItemWithNoLink() {
		//Same as title
	}

}

class TestFeed extends Feed {

	public $useTable = 'feeds';
	public $alias = 'Feed';
	public $name = 'Feed';

	public $testResponseCode = '200';
	public $testResponse = '';

	protected function getFeedLinkContent($url) {
		if($this->testResponseCode === '200') {
			return $this->testResponse;
		} else {
			throw new FeedResponseException('', $this->testResponseCode);
		}
	}

}