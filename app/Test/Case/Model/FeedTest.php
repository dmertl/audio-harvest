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
		$this->Feed->FeedItem->Link = ClassRegistry::init('Link');
	}

	public function tearDown() {
		unset($this->Feed);
		parent::tearDown();
	}

	public function testScrapeAll() {
		//TODO: test scraping multiple feeds
	}

	public function testScrapeCreatesFeedItems() {
//		$feed = array('Feed' => array('link' => 'test'));
//		$this->Feed->testResponse = file_get_contents(TESTS . 'Fixture' . DS . 'feed.xml');
//		$this->Feed->scrape($feed);
		//TODO: test scrape creates FeedItem records
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
		$feed = array('Feed' => array('id' => 1, 'link' => 'test'));
		$this->Feed->scrape($feed);
		$expected = time();
		$actual = strtotime($this->Feed->field('last_scraped', array('id' => 1)));
		$this->assertWithinMargin($actual, $expected, 1, 'last_scraped was not updated.');
	}

	public function testScrapeInvalidXml() {
		$feed = array('Feed' => array('id' => 1, 'link' => 'test'));
		$this->Feed->testResponse = '<?xml version="1.0" encoding="UTF-8"?> <asdf';
		$this->Feed->scrape($feed);
		$expected = time();
		$actual = strtotime($this->Feed->field('last_scraped', array('id' => 1)));
		$this->assertWithinMargin($actual, $expected, 1, 'last_scraped was not updated.');
	}

	public function testScrapeExistingLink() {
		//Test scraping an existing link does not create a duplicate record
	}

	public function testScrapeNoItems() {
		$this->Feed->query('TRUNCATE TABLE feed_items');
		$xml = $this->getSampleFeed(array());
		$this->Feed->testResponse = $xml;
		$this->Feed->scrape(array('Feed' => array('id' => 1, 'link' => 'test')));
		$actual = $this->Feed->FeedItem->find('all');
		var_dump($actual);
		$this->assertEqual(empty($actual), true);
		$expected = time();
		$actual = strtotime($this->Feed->field('last_scraped', array('id' => 1)));
		$this->assertWithinMargin($actual, $expected, 1, 'last_scraped was not updated.');
	}

	public function testScrapeItemWithNoTitle() {
		$this->Feed->query('TRUNCATE TABLE feed_items');
		$xml = $this->getSampleFeed(array(array('link' => 'http://www.example.com/')));
		$this->Feed->testResponse = $xml;
		$this->Feed->scrape(array('Feed' => array('id' => 1, 'link' => 'test')));
		$actual = $this->Feed->find('all');
		$this->assertEqual(empty($actual), true);
		$expected = time();
		$actual = strtotime($this->Feed->field('last_scraped', array('id' => 1)));
		$this->assertWithinMargin($actual, $expected, 1, 'last_scraped was not updated.');
	}

	public function testScrapeItemWithNoLink() {
		$this->Feed->query('TRUNCATE TABLE feed_items');
		$xml = $this->getSampleFeed(array(array('title' => 'Test Title')));
		$this->Feed->testResponse = $xml;
		$this->Feed->scrape(array('Feed' => array('id' => 1, 'link' => 'test')));
		$actual = $this->Feed->find('all');
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