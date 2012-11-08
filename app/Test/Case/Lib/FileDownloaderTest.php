<?php

App::uses('FileDownloader', 'Lib');

/**
 * @author David Mertl <dmertl@gmail.com>
 */
class FileDownloaderTest extends CakeTestCase {

	public function testGetSavesFileUsingUrlFilename() {
		$downloader = new TestFileDownloader();
		$actual = $downloader->get('/test', TESTS . 'Fixture' . DS . 'FileDownloader');
		$this->assertEqual($actual, TESTS . 'Fixture' . DS . 'FileDownloader' . DS . 'test');
		$this->assertEqual(file_exists($actual), true);
		unlink($actual);
	}

	public function testGetSavesFileUsingHeaderFilename() {
		$downloader = new TestFileDownloader();
		$downloader->getHttpSocket()->testResponseHeaders = array(
			'Content-Disposition' => 'Content-Disposition: attachment; filename="test_cd"'
		);
		$actual = $downloader->get('/test', TESTS . 'Fixture' . DS . 'FileDownloader');
		$this->assertEqual($actual, TESTS . 'Fixture' . DS . 'FileDownloader' . DS . 'test_cd');
		$this->assertEqual(file_exists($actual), true);
		unlink($actual);
	}

	public function testGetSavesFileUsingTempnam() {
		$downloader = new TestFileDownloader();
		$actual = $downloader->get('', TESTS . 'Fixture' . DS . 'FileDownloader');
		$this->assertEqual(dirname($actual), TESTS . 'Fixture' . DS . 'FileDownloader');
		$this->assertEqual(file_exists($actual), true);
		unlink($actual);
	}

	//error test cases

	public function testGetRequestFailure() {
		$this->expectException('FileDownloadException', 'Unable to make http request.');
		$downloader = new TestFileDownloader();
		$downloader->getHttpSocket()->testResponseFailure = true;
		$downloader->get('', '');
	}

	public function testGetNon200Response() {
		$this->expectException('FileDownloadException', 'Request error (404) Not Found.');
		$downloader = new TestFileDownloader();
		$downloader->getHttpSocket()->testResponseCode = 404;
		$downloader->getHttpSocket()->testResponseReasonPhrase = 'Not Found';
		$downloader->get('', '');
	}

	public function testGetEmptyResponse() {
		$this->expectException('FileDownloadException', 'Empty response.');
		$downloader = new TestFileDownloader();
		$downloader->getHttpSocket()->testResponseBody = '';
		$downloader->get('', '');
	}

	public function testSaveSecurityCheck() {
		$base_path = TESTS . 'Fixture' . DS . 'FileDownloader' . DS;
		$this->expectException('FileDownloadException', 'Security error! File path "' . $base_path . 'save/../passwd" is not in directory "' . $base_path . 'save".');
		$downloader = new TestFileDownloader();
		$downloader->getHttpSocket()->testResponseHeaders = array(
			'Content-Disposition' => 'Content-Disposition: attachment; filename="../passwd"'
		);
		$downloader->get('', $base_path . 'save');
	}

}

class TestFileDownloader extends FileDownloader {
	public function __construct() {
		parent::__construct();
		$this->httpSocket = new TestHttpSocket();
	}
	public function getHttpSocket() {
		return $this->httpSocket;
	}
}
class TestHttpSocket {
	public $testResponseFailure = false;
	public $testResponseBody = 'test';
	public $testResponseCode = 200;
	public $testResponseReasonPhrase = 'OK';
	public $testResponseHeaders = array();

	public function get($url) {
		if($this->testResponseFailure === false) {
			$response = new Object();
			$response->body = $this->testResponseBody;
			$response->code = $this->testResponseCode;
			$response->reasonPhrase = $this->testResponseReasonPhrase;
			$response->response = array(
				'header' => $this->testResponseHeaders
			);
			return $response;
		} else {
			return false;
		}
	}
}