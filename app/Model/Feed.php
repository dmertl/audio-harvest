<?php

App::uses('AppModel', 'Model');
App::uses('FeedResponseException', 'Lib/Error/Exception');
App::uses('HarvestHttpSocket', 'Lib');

/**
 * Feed Model
 * @property FeedItem $FeedItem
 */
class Feed extends AppModel {

	/**
	 * Display field
	 * @var string
	 */
	public $displayField = 'title';

	/**
	 * hasMany associations
	 * @var array
	 */
	public $hasMany = array('FeedItem');

	/**
	 * @var HarvestHttpSocket
	 */
	protected $httpSocket;

	public function __construct($id = false, $table = null, $ds = null) {
		parent::__construct($id, $table, $ds);
		$this->httpSocket = new HarvestHttpSocket(array(
			'request' => array(
				'redirect' => 5
			)
		));
	}

	/**
	 * Scrape all feeds
	 */
	public function scrapeAll() {
		$feeds = $this->find('all', array('order' => 'last_scraped', 'recursive' => -1));
		CakeLog::write('scrape', 'Processing ' . count($feeds) . ' feeds.');
		foreach($feeds as $feed) {
			$this->scrape($feed);
		}
		CakeLog::write('scrape', 'Finished processing ' . count($feeds) . ' feeds.');
	}

	/**
	 * Scrape a feed and create FeedItem records
	 * @param array $feed Feed
	 */
	public function scrape($feed) {
		CakeLog::write('scrape', 'Processing feed ' . $feed['Feed']['link'] . '.');
		try {
			$feed_response = $this->httpSocket->get($feed['Feed']['link']);
			if($feed_xml = $this->toXmlSafe($feed_response)) {
				if(!empty($feed_xml->channel->item)) {
					foreach($feed_xml->channel->item as $item) {
						if($feed_item = $this->rssItemToFeedItem($item, $feed)) {
							if(!$this->FeedItem->isBlacklisted(array_merge($feed, $feed_item))) {
								if(!$this->FeedItem->find('count', array('conditions' => array('FeedItem.link' => $feed_item['FeedItem']['link'])))) {
									$this->saveFeedItem($feed_item);
								}
							}
						}
					}
				} else {
					CakeLog::write('scrape', 'Unable to process feed ' . $feed['Feed']['link'] . ': Feed missing channel or item: ' . $feed_response);
				}
			}
		} catch(FeedResponseException $e) {
			CakeLog::write('scrape', 'Unable to process feed ' . $feed['Feed']['link'] . ': ' . $e);
		}
		//Update last scraped
		$feed['Feed']['last_scraped'] = date('Y-m-d H:i:s');
		if(!$this->save($feed, false, array('last_scraped'))) {
			CakeLog::write('scrape', 'Unable to save feed ' . $feed['Feed']['id']);
		}
	}

	/**
	 * Convert string to SimpleXMLElement and log any errors
	 * @param string $string
	 * @return SimpleXMLElement
	 * @throws FeedResponseException
	 */
	protected function toXmlSafe($string) {
		libxml_use_internal_errors(true);
		try {
			if($xml = new SimpleXMLElement(trim($string))) {
				return $xml;
			} else {
				$error_string = json_encode(libxml_get_errors());
				libxml_clear_errors();
				throw new FeedResponseException('Unable to parse feed: ' . $error_string . ' Content:' . $string);
			}
		} catch(Exception $e) {
			throw new FeedResponseException($e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * Convert an RSS feed item to a FeedItem record
	 * @param SimpleXmlElement $item RSS item
	 * @param array $feed Feed
	 * @return array
	 * @throws FeedResponseException
	 */
	protected function rssItemToFeedItem($item, $feed) {
		if(!empty($item->title) && !empty($item->link)) {
			$feed_item = array(
				'FeedItem' => array(
					'feed_id' => $feed['Feed']['id'],
					'title' => (string)$item->title,
					'link' => (string)$item->link
				)
			);
			return $feed_item;
		} else {
			throw new FeedResponseException('Feed item missing title or link: ' . $item . ' Feed: ' . json_encode($feed));
		}
	}

	/**
	 * Save a FeedItem
	 * @param array $feed_item
	 * @throws FeedResponseException
	 */
	protected function saveFeedItem($feed_item) {
		$this->FeedItem->create();
		if(!$this->FeedItem->save($feed_item)) {
			throw new FeedResponseException('Unable to save feed item: ' . json_encode($feed_item));
		}
	}

}
