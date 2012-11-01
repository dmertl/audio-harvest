<?php

App::uses('AppShell', 'Console/Command');

/**
 * @author David Mertl <dmertl@gmail.com>
 */
class ScrapeShell extends AppShell {

	var $uses = array('Feed');

	public function main() {
//		$this->Feeds->execute();
//		$this->FeedItems->execute();
//		$this->Links->execute();
//		$this->Mp3s->execute();
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