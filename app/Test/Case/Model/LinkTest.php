<?php

App::uses('Link', 'Model');

/**
 * Link Test Case
 * @property Link $Link
 */
class LinkTest extends CakeTestCase {

	public $fixtures = array(
		'app.link',
		'app.feed_item',
		'app.feed',
		'app.mp3'
	);

	public function setUp() {
		parent::setUp();
		$this->Link = ClassRegistry::init('Link');
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function testScrapeAll() {
		$this->Link->query('TRUNCATE TABLE mp3s');
		$this->Link->scrapeAll();
		$mp3s = $this->Link->Mp3->find('all', array('recursive' => -1));
		foreach($mp3s as $mp3) {
			$this->assertEqual(in_array($mp3['Mp3']['url'], array(
				'http://www.example.com/test_1.mp3',
				'http://www.example.com/test_2.mp3'
			)), true);
		}
	}

	public function testScrape() {
		$this->Link->scrape(array('Link' => array('id' => 1, 'url' => 'http://www.example.com/test.mp3')));
		$mp3 = $this->Link->Mp3->find('first', array(
			'conditions' => array('url' => 'http://www.example.com/test.mp3'),
			'recursive' => -1
		));
		$this->assertEqual(!empty($mp3), true);
		$this->assertEqual($mp3['Mp3']['link_id'], '1');
		$this->assertEqual($mp3['Mp3']['filename'], 'test.mp3');
	}

	public function testScrapeUnparseableFilename() {
		$this->Link->scrape(array('Link' => array('id' => 1, 'url' => 'asdf')));
		$mp3 = $this->Link->Mp3->find('first', array(
			'conditions' => array('url' => 'asdf'),
			'recursive' => -1
		));
		$this->assertEqual(!empty($mp3), true);
		$this->assertEqual($mp3['Mp3']['link_id'], '1');
		$this->assertEqual($mp3['Mp3']['filename'], null);
	}

}
