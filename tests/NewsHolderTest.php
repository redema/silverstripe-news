<?php

/**
 * Copyright 2012 Charden Reklam Östersund AB (http://charden.se/)
 * Erik Edlund <erik@charden.se>
 * 
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 * 
 * * Redistributions of source code must retain the above copyright notice,
 *   this list of conditions and the following disclaimer.
 * 
 * * Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 * 
 * * Neither the name of Charden Reklam, nor the names of its contributors may be
 *   used to endorse or promote products derived from this software without specific
 *   prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 * ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

class NewsHolderTest extends FunctionalTest {
	
	public static $fixture_file = 'news/tests/NewsHolderTest.yml';
	
	public static $use_draft_site = true;
	
	public function setUpOnce() {
		parent::setUpOnce();
		$this->savedNewsweights = NewsPage::get_weights();
		NewsPage::set_weights(array(
			'Normal',
			'Teaser',
			'Headline'
		));
	}
	
	public function tearDownOnce() {
		parent::tearDownOnce();
		NewsPage::set_weights($this->savedNewsweights);
	}
	
	private function getNewsHolder($holderURLSegment, $flushCache = true) {
		$holder = DataObject::get_one('NewsHolder', sprintf(
			"\"URLSegment\" = '%s'", Convert::raw2sql($holderURLSegment)));
		$this->assertInstanceOf('NewsHolder', $holder);
		if ($flushCache) {
			$holder->flushCache();
		}
		return $holder;
	}
	
	private function getNewsPageURLSegments() {
		// Fetch all NewsPages and get an array of all their URL
		// segments sorted from the newest to the oldest NewsPage.
		return array_values(DataObject::get('NewsPage', '',
			'"Datetime" DESC')->map('ID', 'URLSegment'));
	}
	
	private function checkNewsPageDataObjectSet(DataObjectSet $newsPages,
			array $expectedURLSegments) {
		$actualURLSegments = array_values($newsPages->map('ID', 'URLSegment'));
		$this->assertEquals($expectedURLSegments, $actualURLSegments);
	}
	
	private function checkNewsAggregate($holderURLSegment, $start, $length,
			array $expectedURLSegments) {
		$holder = $this->getNewsHolder($holderURLSegment);
		$newsPages = $holder->NewsAggregate($start, $length);
		$this->assertInstanceOf('DataObjectSet', $newsPages);
		$this->checkNewsPageDataObjectSet($newsPages, $expectedURLSegments);
	}
	
	public function testNewsAggregate() {
		$allNewsPages = $this->getNewsPageURLSegments();
		
		$stop = count($allNewsPages);
		$lengths = array(1, 2, 3, 4, 8, 10);
		foreach ($lengths as $length) {
			for ($start = 0; $start < $stop; $start += $length) {
				$this->checkNewsAggregate('news', $start, $length, array_slice(
					$allNewsPages, $start, $length));
			}
		}
		
		$this->checkNewsAggregate('news', 0, -1, array_slice(
			$allNewsPages, 0, 10));
	}
	
	public function testWeights() {
		$holder = $this->getNewsHolder('news');
		
		$headline = $holder->NewsHeadline();
		$this->assertInstanceOf('NewsPage', $headline);
		$this->assertEquals('news9', $headline->URLSegment);
		
		$teasers = $holder->NewsTeasers();
		$this->checkNewsPageDataObjectSet($teasers, array(
			'news10',
			'news7',
			'news5',
			'news3',
			'news1',
			'nestednews4'
		));
	}
	
	public function testRSS() {
		$allNewsPages = $this->getNewsPageURLSegments();
		$holder = $this->getNewsHolder('news');
		$rssFeed = $holder->RSSFeed();
		$this->assertInstanceOf('RSSFeed', $rssFeed);
		$rssXml = $rssFeed->feedContent();
		
		$xml = simplexml_load_string($rssXml);
		$this->assertInstanceOf('SimpleXMLElement', $xml);
		$links = $xml->xpath('channel/item/link');
		for ($i = 0, $link = current($links); $link; $link = next($links), $i++) {
			$this->assertTrue((bool)preg_match(sprintf('/%s\/$/i', preg_quote(
				$allNewsPages[$i])), (string)$link));
		}
	}
	
	public function testTopmostNewsHolder() {
		$holder = $this->getNewsHolder('january');
		do {
			$this->assertEquals('news', $holder->TopmostNewsHolder()->URLSegment);
			$holder = $holder->Parent();
			$this->assertInstanceOf('NewsHolder', $holder);
		} while ($holder->ParentID);
		
		$holder = DataObject::get_one('NewsHolder', "\"URLSegment\" = 'xyzq'");
		$this->assertInstanceOf('NewsHolder', $holder);
		$this->assertEquals($holder, $holder->TopmostNewsHolder());
	}
	
}

