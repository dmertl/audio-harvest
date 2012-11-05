<?php

App::uses('AppModel', 'Model');
App::uses('FeedResponseException', 'Lib/Error/Exception');
App::uses('HttpSocket', 'Network/Http');
App::uses('LinkType', 'Lib/Enum');
App::import('Vendor', 'simplehtmldom/simple_html_dom');

/**
 * FeedItem Model
 * @property Feed $Feed
 * @property Link $Link
 */
class FeedItem extends AppModel {

	/**
	 * @var string
	 */
	public $displayField = 'title';

	/**
	 * @var array
	 */
	public $belongsTo = array('Feed');

	/**
	 * @var array
	 */
	public $hasMany = array('Link');

	/**
	 * @var array
	 */
	protected $blacklist;

	/**
	 * @var HttpSocket
	 */
	protected $httpSocket;

	public function __construct($id = false, $table = null, $ds = null) {
		parent::__construct($id, $table, $ds);
		if($blacklist = Configure::read('FeedItem.blacklist')) {
			$this->blacklist = $blacklist;
		}
		$this->httpSocket = new HttpSocket();
	}

	/**
	 * Check if a feed component is blacklisted
	 * @param $data
	 * @return bool
	 */
	public function isBlacklisted($data) {
		foreach($this->blacklist as $rule) {
			if($this->matches($data, $rule)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if feed data matches a blacklist rule
	 * @param array $data Feed data
	 * @param array $rule Blacklist rule data
	 * @return bool
	 */
	public function matches($data, $rule) {
		foreach($rule as $model_name => $fields) {
			if(empty($data[$model_name])) {
				return false;
			} else {
				foreach($fields as $field_name => $regex) {
					if(empty($data[$model_name][$field_name])) {
						return false;
					}
					if(!preg_match($regex, $data[$model_name][$field_name])) {
						return false;
					}
				}
			}
		}
		return true;
	}

	/**
	 * Scrape all unscraped feed items
	 */
	public function scrapeAll() {
		$feed_items = $this->FeedItem->find('all', array(
				'conditions' => array('FeedItem.scraped' => 0),
				'recursive' => -1
			)
		);
		CakeLog::write('scrape', 'Processing ' . count($feed_items) . ' feed items.');
		foreach($feed_items as $feed_item) {
			$this->scrape($feed_item);
		}
		CakeLog::write('scrape', 'Finished processing ' . count($feed_items) . ' feed items.');
	}

	/**
	 * Scrape a feed item
	 * @param array $feed_item
	 */
	public function scrape($feed_item) {
		CakeLog::write('scrape', 'Processing feed item ' . $feed_item['FeedItem']['link']);
		if($html = str_get_html($this->httpSocket->get($feed_item['FeedItem']['link']))) {
			//Anchor tags
			$links = $this->processAnchorTags($html->find('a'), $feed_item);
			//Embed tags
			$links = array_merge($links, $this->processEmbedTags($html->find('embed'), $feed_item));
			foreach($links as $link) {
				$exists = $this->Link->find('first', array(
						'conditions' => array('Link.url' => $link['Link']['url']),
						'recursive' => -1
					)
				);
				if(!$exists) {
					CakeLog::write('scrape', 'Adding new link ' . $link['Link']['url']);
					$this->Link->create();
					if(!$this->Link->save($link)) {
						CakeLog::write('scrape', 'Unable to save link ' . $link['Link']['url']);
					}
				}
			}
		} else {
			CakeLog::write('scrape', 'Unable to get HTML for feed item ' . $feed_item['FeedItem']['id']);
		}
		//Save scraped status
		$feed_item['FeedItem']['scraped'] = 1;
		if(!$this->FeedItem->save($feed_item, false, array('scraped'))) {
			CakeLog::write('scrape', 'Unable to save feed item ' . $feed_item['FeedItem']['id']);
		}
	}

	/**
	 * Process anchor tags into links
	 * @param array $tags
	 * @param array $feed_item
	 * @return array
	 */
	protected function processAnchorTags($tags, $feed_item) {
		$links = array();
		foreach($tags as $anchor) {
			$url = (string)$anchor->href;
			$link = array();
			if(strpos($url, '.mp3') === strlen($url) - strlen('.mp3')) {
				$link = array(
					'Link' => array(
						'feed_item_id' => $feed_item['FeedItem']['id'],
						'url' => $url,
						'type' => LinkType::MP3
					)
				);
			} elseif(strpos($url, 'mediafire.com') !== false) {
				$link = array(
					'Link' => array(
						'feed_item_id' => $feed_item['FeedItem']['id'],
						'url' => $url,
						'type' => LinkType::MEDIAFIRE
					)
				);
			} elseif(strpos($url, 'zshare.com') !== false) {
				$link = array(
					'Link' => array(
						'feed_item_id' => $feed_item['FeedItem']['id'],
						'url' => $url,
						'type' => LinkType::ZSHARE
					)
				);
			}
			if(!empty($link)) {
				$links[] = $link;
			}
		}
		return $links;
	}

	/**
	 * Process embed tags into links
	 * @param array $tags
	 * @param array $feed_item
	 * @return array
	 */
	protected function processEmbedTags($tags, $feed_item) {
		$links = array();
		foreach($tags as $embed) {
			$src = (string)$embed->src;
			$link = array();
			if(strpos($src, 'soundcloud.com') !== false) {
				$link = array(
					'Link' => array(
						'feed_item_id' => $feed_item['FeedItem']['id'],
						'url' => $src,
						'type' => LinkType::SOUNDCLOUD
					)
				);
			}
			if(!empty($link)) {
				$links[] = $link;
			}
		}
		return $links;
	}

}
