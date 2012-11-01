<?php
App::uses('Mp3', 'Model');

/**
 * Mp3 Test Case
 * @property Mp3 $Mp3
 */
class Mp3Test extends CakeTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'app.mp3',
		'app.link',
		'app.feed_item',
		'app.feed'
	);

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->Mp3 = ClassRegistry::init('Mp3');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Mp3);

		parent::tearDown();
	}

	function testGetBasicInfoId3v2() {
		$actual = $this->Mp3->getBasicInfo(TESTS . 'Fixture' . DS . 'mp3s' . DS . '01 Still Getting It ft. Skrillex.mp3');
		$this->assertEqual('Foreign Beggars', $actual['artist']);
		$this->assertEqual('Still Getting It ft. Skrillex', $actual['title']);
		$this->assertEqual('The Harder They Fall EP', $actual['album']);
		$this->assertEqual(240.065275, $actual['playtime_seconds']);
	}

}
