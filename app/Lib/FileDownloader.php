<?php

App::uses('FileUtil', 'Lib');
App::uses('HttpSocket', 'Network/Http');

/**
 * Downloads a file
 * @author David Mertl <dave@onzra.com>
 */
class FileDownloader {

	/**
	 * @var HttpSocket
	 */
	protected $httpSocket;

	public function __construct() {
		$this->httpSocket = new HttpSocket(array('request' => array('redirect' => 5)));
	}

	public function get($url, $save_path = null) {
		$response = $this->httpSocket->get($url);
	}

}
