<?php

App::uses('HttpSocket', 'Network/Http');

/**
 * @author David Mertl <dmertl@gmail.com>
 */
class HarvestHttpSocket extends HttpSocket {

	/**
	 * Get response body and throw exception on non-200 response
	 * @inheritdoc
	 * @return string
	 * @throws FeedResponseException
	 */
	public function get($uri = null, $query = array(), $request = array()) {
		$response = parent::get($uri, $query, $request);
		if($response->code === '200') {
			return $response->body;
		} else {
			throw new FeedResponseException($response->reasonPhrase, $response->code);
		}
	}

}
