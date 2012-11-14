<?php

App::uses('HttpSocket', 'Network/Http');

/**
 * @author David Mertl <dmertl@gmail.com>
 */
class HarvestHttpSocket extends HttpSocket {

	/**
	 * @var mixed
	 */
	protected $last_response;

	/**
	 * @var array
	 */
	protected $last_headers = array();

	/**
	 * Get response body and throw exception on non-200 response
	 * @inheritdoc
	 * @return string
	 * @throws FeedResponseException
	 */
	public function get($uri = null, $query = array(), $request = array()) {
		$this->last_response = parent::get($uri, $query, $request);
		$this->last_headers = $this->last_response->headers;
		if($this->last_response->code === '200') {
			return $this->last_response->body;
		} else {
			throw new FeedResponseException($this->last_response->reasonPhrase, $this->last_response->code);
		}
	}

	/**
	 * Get headers from last response
	 * @return array
	 */
	public function getLastHeaders() {
		return $this->last_headers;
	}

	/**
	 * Get the value of a header from the last response
	 * @param string $name Header name
	 * @return string|bool Header value or false if header was not sent
	 */
	public function getLastHeader($name) {
		return isset($this->last_headers[$name]) ? $this->last_headers[$name] : false;
	}

	/**
	 * Parses Content-Disposition header into an array
	 * @param string $header
	 * @return array
	 */
	public function parseContentDisposition($header) {
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

}
