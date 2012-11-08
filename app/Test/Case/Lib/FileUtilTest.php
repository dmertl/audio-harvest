<?php

App::uses('FileUtil', 'Lib');

/**
 * @author David Mertl <dave@onzra.com>
 */
class FileUtilTest extends CakeTestCase {

	public function testUniqueFilenameWithExtension() {
		$path = TESTS . 'Fixture' . DS . 'FileUtil' . DS . 'test.ext';
		$expected = TESTS . 'Fixture' . DS . 'FileUtil' . DS . 'test_1.ext';
		$actual = FileUtil::incrementedUniqueFilename($path);
		$this->assertEqual($actual, $expected);
	}

	public function testUniqueFilenameWithoutExtension() {
		$path = TESTS . 'Fixture' . DS . 'FileUtil' . DS . 'test';
		$expected = TESTS . 'Fixture' . DS . 'FileUtil' . DS . 'test_1';
		$actual = FileUtil::incrementedUniqueFilename($path);
		$this->assertEqual($actual, $expected);
	}

	public function testUniqueFilenameNoConflict() {
		$path = TESTS . 'Fixture' . DS . 'FileUtil' . DS . 'test_nc';
		$expected = TESTS . 'Fixture' . DS . 'FileUtil' . DS . 'test_nc';
		$actual = FileUtil::incrementedUniqueFilename($path);
		$this->assertEqual($actual, $expected);
	}

	public function testUniqueFilenameExistingNumber() {
		$path = TESTS . 'Fixture' . DS . 'FileUtil' . DS . 'test_existing.ext';
		$expected = TESTS . 'Fixture' . DS . 'FileUtil' . DS . 'test_existing_2.ext';
		$actual = FileUtil::incrementedUniqueFilename($path);
		$this->assertEqual($actual, $expected);
	}

	public function testParseFilenameFromUrl() {
		$url = 'http://www.example.com/test.txt';
		$actual = FileUtil::parseFilenameFromUrl($url);
		$this->assertEqual($actual, 'test.txt');
	}

	public function testParseFilenameFromUrlWithQueryString() {
		$url = 'http://www.example.com/test.txt?key=value';
		$actual = FileUtil::parseFilenameFromUrl($url);
		$this->assertEqual($actual, 'test.txt');
	}

	public function testParseFilenameFromUrlNoSlashesReturnsFalse() {
		$url = 'test.txt';
		$actual = FileUtil::parseFilenameFromUrl($url);
		$this->assertEqual($actual, false);
	}

}
