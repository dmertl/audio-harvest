<?php

App::uses('Mp3', 'Model');
App::uses('TestFileDownloader', 'Test/Case/Lib');

/**
 * Mp3 Test Case
 * @property Mp3 $Mp3
 * @property string $path
 * @property TestFileDownloader $downloader
 */
class Mp3Test extends CakeTestCase {

	public $fixtures = array(
		'app.mp3',
		'app.link',
		'app.feed_item',
		'app.feed'
	);

	public function setUp() {
		parent::setUp();
		$this->path = TMP . 'tests' . DS . 'Mp3';
		mkdir($this->path);
		Configure::write('download_folder', $this->path);
		$this->Mp3 = ClassRegistry::init('Mp3');
		$refl = new ReflectionClass('Mp3');
		$this->downloader = new TestFileDownloader();
		$downloader = $refl->getProperty('downloader');
		$downloader->setAccessible(true);
		$downloader->setValue($this->Mp3, $this->downloader);
	}

	public function tearDown() {
		parent::tearDown();
		shell_exec('rm -rf ' . $this->path);
	}

	public function testDownloadAll() {
		//Test that all undownloaded mp3s are downloaded
	}

	public function testDownloadExistingFile() {
		$this->Mp3->download(array(
			'Mp3' => array(
				'filename' => 'high_bitrate',
				'url' => 'http://www.example.com/test.mp3'
			)
		));
		$this->assertEqual($this->downloader->getHttpSocket()->testWasGetCalled, false);
	}

	public function testDownloadSavesFile() {
		$this->Mp3->download(array(
			'Mp3' => array(
				'url' => 'http://www.example.com/test.mp3'
			)
		));
		$this->assertEqual(file_exists($this->path . DS . 'test.mp3'), true);
	}

	public function testDownloadCreatesMp3Record() {
		$this->Mp3->download(array(
			'Mp3' => array(
				'url' => 'http://www.example.com/test.mp3'
			)
		));
		$actual = $this->Mp3->find('first', array(
			'conditions' => array('Mp3.url' => 'http://www.example.com/test.mp3'),
			'recursive' => -1
		));
		$this->assertEqual($actual['Mp3']['filename'], 'test.mp3');
		$this->assertEqual($actual['Mp3']['hash'], '098f6bcd4621d373cade4e832627b4f6');
		$this->assertEqual($actual['Mp3']['size'], '4');
		$this->assertWithinMargin(strtotime($actual['Mp3']['created']), time(), 2);
		$this->assertWithinMargin(strtotime($actual['Mp3']['downloaded']), time(), 2);
	}

	public function testDownloadResponseError() {
		$this->downloader->getHttpSocket()->testResponseCode = 404;
		$this->downloader->getHttpSocket()->testResponseReasonPhrase = 'Not Found';
		$this->Mp3->download(array(
			'Mp3' => array(
				'url' => 'http://www.example.com/test.mp3'
			)
		));
		$actual = $this->Mp3->find('first', array(
			'conditions' => array('Mp3.url' => 'http://www.example.com/test.mp3'),
			'recursive' => -1
		));
		$this->assertEqual($actual['Mp3']['error'], 'Request error (404) Not Found.');
	}

	public function testGetDataFromFile() {
		$expected = array(
			'filename' => '01 Still Getting It ft. Skrillex.mp3',
			'hash' => '299bb82a8f1862749e2768e6149beca1',
			'size' => 10071613,
			'length' => 240.065275,
			'bitrate' => 320000,
			'artist' => 'Foreign Beggars',
			'name' => 'Still Getting It ft. Skrillex',
			'album' => 'The Harder They Fall EP'
		);
		$actual = $this->Mp3->getDataFromFile(TESTS . 'Fixture' . DS . 'mp3s' . DS . '01 Still Getting It ft. Skrillex.mp3');
		$this->assertEqual($actual, $expected);
	}

	public function testResolveMp3ConflictsNoConflict() {
		touch($this->path . DS . 'Fake-Blood-Live-at-Rockness-Festival-(Essential-Mix).mp3');
		touch($this->path . DS . 'no_conflict');
		$new = $this->Mp3->read(null, '2');
		$this->Mp3->resolveMp3Conflicts($new);
		$this->assertEqual(file_exists($this->path . DS . 'Fake-Blood-Live-at-Rockness-Festival-(Essential-Mix).mp3'), true);
		$this->assertEqual(file_exists($this->path . DS . 'no_conflict'), true);
		$high = $this->Mp3->read(null, '1');
		$this->assertEqual($high['Mp3']['error'], null);
		$low = $this->Mp3->read(null, '2');
		$this->assertEqual($low['Mp3']['error'], null);
	}

	public function testResolveMp3ConflictsIdenticalBitrate() {
		touch($this->path . DS . 'identical_bitrate_1');
		touch($this->path . DS . 'identical_bitrate_2');
		$new = $this->Mp3->read(null, '6');
		$this->Mp3->resolveMp3Conflicts($new);
		$this->assertEqual(file_exists($this->path . DS . 'identical_bitrate_1'), true);
		$this->assertEqual(file_exists($this->path . DS . 'identical_bitrate_2'), false);
		$high = $this->Mp3->read(null, '5');
		$this->assertEqual($high['Mp3']['error'], null);
		$low = $this->Mp3->read(null, '6');
		$this->assertEqual($low['Mp3']['error'], 'Duplicate of 5');
	}

	public function testResolveMp3ConflictsLowerBitrate() {
		touch($this->path . DS . 'high_bitrate');
		touch($this->path . DS . 'low_bitrate');
		$new = $this->Mp3->read(null, '4');
		$this->Mp3->resolveMp3Conflicts($new);
		$this->assertEqual(file_exists($this->path . DS . 'high_bitrate'), true);
		$this->assertEqual(file_exists($this->path . DS . 'low_bitrate'), false);
		$high = $this->Mp3->read(null, '3');
		$this->assertEqual($high['Mp3']['error'], null);
		$low = $this->Mp3->read(null, '4');
		$this->assertEqual($low['Mp3']['error'], 'Superior quality copy exists');
	}

	public function testResolveMp3ConflictsHigherBitrate() {
		touch($this->path . DS . 'high_bitrate');
		touch($this->path . DS . 'low_bitrate');
		$new = $this->Mp3->read(null, '3');
		$this->Mp3->resolveMp3Conflicts($new);
		$this->assertEqual(file_exists($this->path . DS . 'high_bitrate'), true);
		$this->assertEqual(file_exists($this->path . DS . 'low_bitrate'), false);
		$high = $this->Mp3->read(null, '3');
		$this->assertEqual($high['Mp3']['error'], null);
		$low = $this->Mp3->read(null, '4');
		$this->assertEqual($low['Mp3']['error'], 'Superior quality copy found');
	}

	public function testGetId3DataFromFile() {
		$actual = $this->Mp3->getId3DataFromFile(TESTS . 'Fixture' . DS . 'mp3s' . DS . '01 Still Getting It ft. Skrillex.mp3');
		$this->assertEqual('Foreign Beggars', $actual['artist']);
		$this->assertEqual('Still Getting It ft. Skrillex', $actual['name']);
		$this->assertEqual('The Harder They Fall EP', $actual['album']);
		$this->assertEqual(240.065275, $actual['length']);
	}

	public function testHashExists() {
		$this->assertEqual($this->Mp3->hashExists('53946a7f4e1e879ff43f22dbe66d3d90'), true);
		$this->assertEqual($this->Mp3->hashExists('dne'), false);
	}

	public function testFindCopyDoesNotFindSelf() {
		$mp3 = array(
			'Mp3' => array(
				'id' => 3,
				'artist' => 'conflict artist',
				'name' => 'conflict name'
			)
		);
		$actual = $this->Mp3->findCopy($mp3);
		$this->assertEqual($actual['Mp3']['id'], 4);
	}

	public function testFindCopyNameAndArtistNoHash() {
		$mp3 = array(
			'Mp3' => array(
				'id' => 3,
				'artist' => 'conflict artist',
				'name' => 'conflict name'
			)
		);
		$actual = $this->Mp3->findCopy($mp3);
		$this->assertEqual($actual['Mp3']['id'], 4);
	}

	public function testFindCopyNameAndArtistHashNotMatching() {
		$mp3 = array(
			'Mp3' => array(
				'id' => 3,
				'artist' => 'conflict artist',
				'name' => 'conflict name',
				'hash' => 'dne'
			)
		);
		$actual = $this->Mp3->findCopy($mp3);
		$this->assertEqual($actual['Mp3']['id'], 4);
	}

	public function testFindCopyHashNoNameOrArtist() {
		$mp3 = array(
			'Mp3' => array(
				'id' => 3,
				'hash' => 'conflict hash'
			)
		);
		$actual = $this->Mp3->findCopy($mp3);
		$this->assertEqual($actual['Mp3']['id'], 4);
	}

	public function testFindCopyHashNameAndArtistNotMatching() {
		$mp3 = array(
			'Mp3' => array(
				'id' => 3,
				'artist' => 'dne',
				'name' => 'dne',
				'hash' => 'conflict hash'
			)
		);
		$actual = $this->Mp3->findCopy($mp3);
		$this->assertEqual($actual['Mp3']['id'], 4);
	}

	public function testFindCopyNameAndArtistMustMatch() {
		$mp3 = array(
			'Mp3' => array(
				'id' => 3,
				'artist' => 'conflict artist',
				'name' => 'dne',
			)
		);
		$actual = $this->Mp3->findCopy($mp3);
		$this->assertEqual($actual, false);
	}

	public function testFindCopyHashNotMatching() {
		$mp3 = array(
			'Mp3' => array(
				'id' => 3,
				'hash' => 'dne'
			)
		);
		$actual = $this->Mp3->findCopy($mp3);
		$this->assertEqual($actual, false);
	}

	//error test cases

	public function testDownloadErrorDoesNotCreateFile() {

	}

	public function testDownloadErrorUpdatesMp3() {

	}

}
