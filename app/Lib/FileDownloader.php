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

	public function __construct() {
		$this->httpSocket = new HttpSocket(array('request' => array('redirect' => 5)));
	}

	/**
	 * @param string $url
	 * @param string $save_path
	 * @return string Path to file
	 * @throws FileDownloadException
	 */
	public function save($url, $save_path) {
		$this->lastResponse = null;
		if($response = $this->httpSocket->get($url)) {
			$this->lastResponse = $response;
			if($response->code === '200') {
				if(!empty($response->body)) {
					return $this->saveResponseToFile($response, $save_path, $url);
				} else {
					throw new FileDownloadException('Empty response.');
				}
			} else {
				throw new FileDownloadException('Request error (' . $response->code . ') ' . $response->reasonPhrase . '.', $response->code);
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
		//Check if $save_path is a directory or file
		if(is_dir($save_path)) {
			$save_dir = $save_path;
			if($filename = $this->determineFilename($url, $response)) {
				$path = $save_path . DS . $filename;
			} else {
				$path = $save_path . DS . $this->getTemporaryFilename();
			}
		} else {
			$save_dir = dirname($save_path);
			$path = $save_path;
		}
		if(realpath(dirname($path)) !== realpath($save_dir)) {
			throw new FileDownloadException('Security error! File path "' . $path . '" is not in directory "' . $save_path . '".');
		}
		$path = FileUtil::incrementedUniqueFilename($path);
		if(@file_put_contents($path, $response->body) > 0) {
			return $path;
		} else {
			throw new FileDownloadException('Unable to save file to "' . $path . '".');
		}
	}

	/**
	 * Attempt to get a filename from the request and response
	 * @param string $url
	 * @param HttpResponse $response
	 * @return string|bool
	 */
	protected function determineFilename($url, $response) {
		if($filename = $this->getFilenameFromHeaders($response->headers)) {
			return $filename;
		} else {
			return $this->getFilenameFromUrl($url);
		}
	}

	/**
	 * Get filename from Content-Disposition header
	 * @param array $headers Array of headers from request
	 * @return string|bool
	 */
	protected function getFilenameFromHeaders($headers) {
		if(isset($headers['Content-Disposition'])) {
			$cd = $this->parseContentDisposition($headers['Content-Disposition']);
			if(isset($cd['params']['filename'])) {
				return $cd['params']['filename'];
			}
		}
		return false;
	}

	/**
	 * Get filename from request URL
	 * @param string $url
	 * @return string|bool
	 */
	protected function getFilenameFromUrl($url) {
		return FileUtil::parseFilenameFromUrl($url);
	}

	/**
	 * @return string
	 */
	protected function getTemporaryFilename() {
		return uniqid('fd_');
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
	 * @return HttpResponse
	 */
	public function getLastResponse() {
		return $this->lastResponse;
	}

}
