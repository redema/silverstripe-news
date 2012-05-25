<?php

/**
 * Copyright (c) 2012, Redema AB - http://redema.se/
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
 * * Neither the name of Redema, nor the names of its contributors may be used
 *   to endorse or promote products derived from this software without specific
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

class NewsPageTest extends FunctionalTest {
	
	public static $fixture_file = 'news/tests/NewsPageTest.yml';
	
	public static $use_draft_site = true;
	
	public function setUpOnce() {
		parent::setUpOnce();
		$this->savedNewsweights = NewsPage::get_weights();
	}
	
	public function tearDownOnce() {
		parent::tearDownOnce();
		NewsPage::set_weights($this->savedNewsweights);
	}
	
	public function testWeightManipulation() {
		NewsPage::set_weights(array());
		$weights = array('Normal', 'Teaser', 'Headline');
		do {
			NewsPage::set_weights($weights);
			$this->assertEquals($weights, NewsPage::get_weights());
			foreach (array_slice($weights, 1) as $w) {
				$this->assertTrue(Object::has_extension('NewsHolder',
					"NewsWeight{$w}"));
			}
			array_pop($weights);
		} while ($weights);
	}
	
	public function testOneFieldFrom() {
		$newsPages = DataObject::get('NewsPage');
		$this->assertInstanceOf('DataObjectSet', $newsPages);
		foreach ($newsPages as $newsPage) {
			foreach (array('SummaryTitle', 'Title') as $field) {
				// Template call.
				$this->assertEquals($newsPage->$field,
					$newsPage->OneFieldFrom('SummaryTitle Title')
						->getValue());
				
				// PHP call.
				$this->assertEquals($newsPage->$field,
					$newsPage->OneFieldFrom('SummaryTitle', 'Title')
						->getValue());
				
				$newsPage->$field = '';
			}
		}
	}
	
	public function testRedirectLink() {
		$newsPage = DataObject::get_one('NewsPage', "\"URLSegment\" = 'news3'");
		$this->assertInstanceOf('NewsPage', $newsPage);
		
        $savedAutoFollowRedirection = $this->autoFollowRedirection;
        $this->autoFollowRedirection = false;
		$response = $this->get($newsPage->Link());
		$this->autoFollowRedirection = $savedAutoFollowRedirection;
		
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals($newsPage->RedirectLink, $response->getHeader('Location'));
	}
	
	public function testFormatDatetime() {
		$newsPage = DataObject::get_one('NewsPage', "\"URLSegment\" = 'news3'");
		$this->assertInstanceOf('NewsPage', $newsPage);
		$this->assertEquals('2011-01-03 00:00', $newsPage->FormatDatetime());
		$this->assertEquals('2011-01-03', $newsPage->FormatDatetime('YYYY-MM-dd'));
		$this->assertTrue(strpos($newsPage->FormatDatetime('ERA'),
			'2011-01-03') == 0);
	}
	
}

