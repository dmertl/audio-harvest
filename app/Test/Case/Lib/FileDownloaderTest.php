<?php

App::uses('FileDownloader', 'Lib');
App::uses('TestFileDownloader', 'Test/Case/Lib');

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
		$downloader->getHttpSocket()->testResponseCode = '404';
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
