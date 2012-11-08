<?php

App::uses('FileDownloadException', 'Lib/Error/Exception');
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

	/**
	 * @var HttpResponse
	 */
	protected $lastResponse;

	/**
	 * @var string
	 */
	protected $errorMessage;

	public function __construct() {
		$this->httpSocket = new HttpSocket(array('request' => array('redirect' => 5)));
	}

	/**
	 * @param string $url
	 * @param string $save_path
	 * @return string Path to file
	 * @throws FileDownloadException
	 */
	public function get($url, $save_path) {
		$this->lastResponse = null;
		$this->errorMessage = null;
		if($response = $this->httpSocket->get($url)) {
			$this->lastResponse = $response;
			if($response->code === 200) {
				if(!empty($response->body)) {
					return $this->saveResponseToFile($response, $save_path, $url);
				} else {
					throw new FileDownloadException('Empty response.');
				}
			} else {
				throw new FileDownloadException('Request error (' . $response->code . ') ' . $response->reasonPhrase . '.');
			}
		} else {
			throw new FileDownloadException('Unable to make http request.');
		}
	}

	/**
	 * Save response to file
	 * @param HttpResponse $response
	 * @param string $save_path
	 * @param string $url
	 * @return string
	 * @throws FileDownloadException
	 */
	protected function saveResponseToFile($response, $save_path, $url) {
		//Flag to do unique filename check, necessary since tempnam creates the file
		$needs_unique = true;
		//Check if $save_path is a directory or file
		if(is_dir($save_path)) {
			$save_dir = $save_path;
			$headers = $response->response['header'];
			//Use filename from Content-Disposition header
			if(isset($headers['Content-Disposition'])) {
				$cd = $this->parseContentDisposition($headers['Content-Disposition']);
				if(isset($cd['params']['filename'])) {
					$path = $save_path . DS . $cd['params']['filename'];
				}
			}
			//Use filename from url
			if(!isset($path)) {
				if(($slash_pos = strpos($url, '/')) !== false) {
					$query_pos = strpos($url, '?');
					$length = $query_pos ? $query_pos - $slash_pos - 1 : strlen($url) - $slash_pos - 1;
					if($filename = substr($url, $slash_pos + 1, $length)) {
						$path = $save_path . DS . $filename;
					}
				}
			}
			//Use tempnam
			if(!isset($path)) {
				if(!$path = tempnam($save_path, 'fd_')) {
					throw new FileDownloadException('Unable to create temp file in "' . $save_path . '".');
				} else {
					$needs_unique = false;
				}
			}
		} else {
			$save_dir = dirname($save_path);
			$path = $save_path;
		}
		if(realpath(dirname($path)) !== realpath($save_dir)) {
			throw new FileDownloadException('Security error! File path "' . $path . '" is not in directory "' . $save_path . '".');
		}
		//Only get unique filename if necessary
		if($needs_unique) {
			$path = FileUtil::uniqueFilename($path);
		}
		if(@file_put_contents($path, $response->body) > 0) {
			return $path;
		} else {
			throw new FileDownloadException('Unable to save file to "' . $path . '".');
		}
	}

	/**
	 * Parses Content-Disposition header into an array
	 * @param string $header
	 * @return array
	 */
	protected function parseContentDisposition($header) {
		$content = substr($header, strpos($header, ':'));
		$params = explode(';', $content);
		$return = array(
			'type' => trim(array_shift($params)),
			'params' => array()
		);
		if(count($params) > 0) {
			foreach($params as $param) {
				$p = explode('=', $param);
				if(count($p) == 2) {
					$return['params'][trim($p[0])] = trim(str_replace('"', '', $p[1]));
				}
			}
		}
		return $return;
	}

	/**
	 * Get error message from last request
	 * @return string
	 */
	public function getError() {
		return $this->errorMessage;
	}

}
