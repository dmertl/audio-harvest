<?php

App::uses('FileDownloader', 'Lib');

/**
 * @author David Mertl <dmertl@gmail.com>
 */
class FileDownloaderTest extends CakeTestCase {

	/* @var string */
	protected $basePath;

	public function setUp() {
		$this->basePath = TMP . 'tests' . DS . 'FileDownloader';
		mkdir($this->basePath);
	}

	public function tearDown() {
		shell_exec('rm -rf ' . $this->basePath);
	}

	public function testSaveFileUsingUrlFilename() {
		$downloader = new TestFileDownloader();
		$actual = $downloader->save('/test', $this->basePath);
		$this->assertEqual($actual, $this->basePath . DS . 'test');
		$this->assertEqual(file_exists($actual), true);
	}

	public function testSaveFileUsingHeaderFilename() {
		$downloader = new TestFileDownloader();
		$downloader->getHttpSocket()->testResponseHeaders = array(
			'Content-Disposition' => 'Content-Disposition: attachment; filename="test_cd"'
		);
		$actual = $downloader->save('/test', $this->basePath);
		$this->assertEqual($actual, $this->basePath . DS . 'test_cd');
		$this->assertEqual(file_exists($actual), true);
	}

	public function testSaveFileUsingTempnam() {
		$downloader = new TestFileDownloader();
		$actual = $downloader->save('', $this->basePath);
		$this->assertEqual(dirname($actual), $this->basePath);
		$this->assertEqual(file_exists($actual), true);
	}

	public function testSaveDirectlyToFile() {
		$downloader = new TestFileDownloader();
		$actual = $downloader->save('', $this->basePath . DS . 'test');
		$this->assertEqual($actual, $this->basePath . DS . 'test');
		$this->assertEqual(file_exists($actual), true);
	}

	//error test cases

	public function testSaveRequestFailure() {
		$this->expectException('FileDownloadException', 'Unable to make http request.');
		$downloader = new TestFileDownloader();
		$downloader->getHttpSocket()->testResponseFailure = true;
		$downloader->save('', '');
	}

	public function testSaveNon200Response() {
		$this->expectException('FileDownloadException', 'Request error (404) Not Found.');
		$downloader = new TestFileDownloader();
		$downloader->getHttpSocket()->testResponseCode = 404;
		$downloader->getHttpSocket()->testResponseReasonPhrase = 'Not Found';
		$downloader->save('', '');
	}

	public function testSaveEmptyResponse() {
		$this->expectException('FileDownloadException', 'Empty response.');
		$downloader = new TestFileDownloader();
		$downloader->getHttpSocket()->testResponseBody = '';
		$downloader->save('', '');
	}

	public function testSaveSecurityCheck() {
		$subdir = $this->basePath . DS . 'save';
		mkdir($subdir);
		$this->expectException('FileDownloadException', 'Security error! File path "' . $subdir . '/../passwd" is not in directory "' . $subdir . '".');
		$downloader = new TestFileDownloader();
		$downloader->getHttpSocket()->testResponseHeaders = array(
			'Content-Disposition' => 'Content-Disposition: attachment; filename="../passwd"'
		);
		$downloader->save('', $subdir);
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