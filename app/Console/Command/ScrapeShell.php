<?php

App::uses('AppShell', 'Console/Command');

/**
 * @author David Mertl <dmertl@gmail.com>
 * @property Feed $Feed
 * @property FeedItem $FeedItem
 * @property Link $Link
 * @property Mp3 $Mp3
 */
class ScrapeShell extends AppShell {

	var $uses = array('Feed', 'FeedItem', 'Link', 'Mp3');

	public function main() {
//		$this->Feeds->execute();
//		$this->FeedItems->execute();
//		$this->Links->execute();
//		$this->Mp3s->execute();
	}

	public function feed() {
		if(!empty($this->args[0])) {
			$feed = $this->Feed->find('first', array(
					'conditions' => array('Feed.title' => $this->args[0]),
					'recursive' => -1
				)
			);
			if($feed) {
				$this->Feed->scrape($feed);
			} else {
				$this->out('Feed "' . $this->args[0] . '" not found.');
			}
		} else {
			$this->Feed->scrapeAll();
		}
	}

	public function feed_item() {
		$this->FeedItem->scrapeAll();
	}

	public function link() {
		$this->Link->scrapeAll();
	}

	public function mp3() {
		$this->Mp3->downloadAll();
	}

	public function getOptionParser() {
		$parser = parent::getOptionParser();
		$parser->addArgument('item', array('help' => 'A specific item to scrape'));
		return $parser;
	}

}

Class ScrapeShellO extends Shell {
	var $tasks = array('Feeds', 'FeedItems', 'Links', 'Mp3s');
	var $uses = array('Mp3');

	function main() {
		echo 'Available Tasks: ' . "\n";
		print_r($this->tasks);
	}

	function test() {
		App::import('Vendor', 'getid3/getid3', array('file' => 'getid3/getid3.php'));
		$this->Mp3s->removeIfDuplicate('/Users/dmertl/dev/personal/rss_mp3_downloader/app/downloads/07 Magic Fountain (Royalston remix).mp3');
		return;
		$root = '/Users/dmertl/Music/iTunes/iTunes Music';
		$artist = 'John Denver';
		$title = 'Country Roads (Pretty Lights Remix)';
		//Check artist folder exists
		if(file_exists($root . DS . $artist) && is_dir($root . DS . $artist)) {
			//Search sub folder for title filename
			echo 'Artist dir exists' . "\n";
			if($folder = new Folder($root . DS . $artist)) {
				echo 'Searching ' . $root . DS . $artist . DS . '*' . DS . $title . '.*' . "\n";
				$results = $folder->findRecursive(preg_quote($title) . '\..*');
				if(!empty($results)) {
					echo 'Found ' . count($results) . ' matching files' . "\n";
					foreach($results as $result) {
						$this->Mp3->getBasicInfo($result);
					}
				} else {
					echo 'Found no matching files' . "\n";
				}
			} else {
				echo 'Unable to open Folder at ' . $root . DS . $artist . "\n";
			}
		} else {
			echo 'Artist dir does not exist' . "\n";
		}
	}

	function batch() {
		$this->Feeds->execute();
		$this->FeedItems->execute();
		$this->Links->execute();
		$this->Mp3s->execute();
	}
}