<?php

/**
 * @author David Mertl <dmertl@gmail.com>
 */
class TestFileDownloader extends FileDownloader {
	public function __construct() {
		parent::__construct();
		$this->httpSocket = new TestHttpSocket();
	}

	public function getHttpSocket() {
		return $this->httpSocket;
	}
}

/**
 * @author David Mertl <dmertl@gmail.com>
 */
class TestHttpSocket {
	public $testResponseFailure = false;
	public $testResponseBody = 'test';
	public $testResponseCode = '200';
	public $testResponseReasonPhrase = 'OK';
	public $testResponseHeaders = array();
	public $testWasGetCalled = false;

	public function get($url) {
		$this->testWasGetCalled = true;
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