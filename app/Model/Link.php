<?php

App::uses('AppModel', 'Model');
App::uses('LinkType', 'Lib/Enum');

/**
 * Link Model
 * @property FeedItem $FeedItem
 * @property Mp3 $Mp3
 */
class Link extends AppModel {

	/**
	 * @var string
	 */
	public $displayField = 'url';

	/**
	 * @var array
	 */
	public $belongsTo = array('FeedItem' => array());

	/**
	 * @var array
	 */
	public $hasMany = array('Mp3' => array());

	/**
	 * Scrape all unscraped links
	 */
	public function scrapeAll() {
		//Only supporting MP3 type links for now
		$links = $this->find('all', array(
				'conditions' => array(
					'Link.scraped' => 0,
					'Link.type' => LinkType::MP3
				),
				'recursive' => -1
			)
		);
		CakeLog::write('scrape', 'Processing ' . count($links) . ' links.');
		foreach($links as $link) {
			$this->scrape($link);
		}
		CakeLog::write('scrape', 'Finished processing ' . count($links) . ' links.');
	}

	/**
	 * Scrape a link
	 * @param array $link
	 */
	public function scrape($link) {
		CakeLog::write('scrape', 'Processing link ' . $link['Link']['url']);
		if(!$filename = $this->parseFilenameFromUrl($link['Link']['url'])) {
			$filename = null;
			CakeLog::write('scrape', 'Unable to parse filename from url ' . $link['Link']['url'] . ' for link ' . $link['Link']['id']);
		}
		$mp3 = array(
			'Mp3' => array(
				'link_id' => $link['Link']['id'],
				'url' => $link['Link']['url'],
				'filename' => $filename
			)
		);
		CakeLog::write('scrape', 'Adding mp3 ' . $filename);
		$this->Mp3->create();
		if(!$this->Mp3->save($mp3)) {
			CakeLog::write('scrape', 'Unable to save mp3 ' . $filename . ' from link ' . $link['Link']['id']);
		}
		$link['Link']['scraped'] = 1;
		if(!$this->save($link)) {
			CakeLog::write('scrape', 'Unable to save link ' . $link['Link']['id']);
		}
	}

	/**
	 * Parse filename from url
	 * @param string $url
	 * @return bool|string
	 */
	protected function parseFilenameFromUrl($url) {
		if(strpos($url, '/') === false) return false;
		return substr($url, strrpos($url, '/') + 1);
	}

}
