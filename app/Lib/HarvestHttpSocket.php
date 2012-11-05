<?php

App::uses('HttpSocket', 'Network/Http');

/**
 * @author David Mertl <dmertl@gmail.com>
 */
class HarvestHttpSocket extends HttpSocket {

	protected $last_headers = array();

	/**
	 * Get response body and throw exception on non-200 response
	 * @inheritdoc
	 * @return string
	 * @throws FeedResponseException
	 */
	public function get($uri = null, $query = array(), $request = array()) {
		$response = parent::get($uri, $query, $request);
		$this->last_headers = $response->response['header'];
		if($response->code === '200') {
			return $response->body;
		} else {
			throw new FeedResponseException($response->reasonPhrase, $response->code);
		}
	}

	/**
	 * Get header from last response
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
