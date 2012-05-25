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

class NewsHolder extends Page {
	
	public static $db = array(
		'NewsPerPage' => 'Int',
		'RSSFromTopmostNewsHolder' => 'Boolean',
		'RSSLimit' => 'Int',
		'RSSTitle' => 'Text',
		'RSSDescription' => 'Text'
	);
	
	public static $has_one = array(
	);
	
	public static $has_many = array(
	);
	
	public static $defaults = array(
		'NewsPerPage' => '10',
		'RSSFromTopmostNewsHolder' => 'true',
		'RSSLimit' => '10'
	);
	
	public function getCMSFields() {
		$fields = parent::getCMSFields();
		
		$fields->findOrMakeTab('Root.Content.News', $this->fieldLabel('NewsTab'));
		
		$fields->addFieldToTab('Root.Content.News', new NumericField('NewsPerPage',
			$this->fieldLabel('NewsPerPage')));
		
		$fields->findOrMakeTab('Root.Content.RSS', $this->fieldLabel('RSSTab'));
		
		$fields->addFieldToTab('Root.Content.RSS', new CheckboxField('RSSFromTopmostNewsHolder',
			$this->fieldLabel('RSSFromTopmostNewsHolder')));
		$fields->addFieldToTab('Root.Content.RSS', new NumericField('RSSLimit',
			$this->fieldLabel('RSSLimit')));
		$fields->addFieldToTab('Root.Content.RSS', new TextField('RSSTitle',
			$this->fieldLabel('RSSTitle')));
		$fields->addFieldToTab('Root.Content.RSS', new TextField('RSSDescription',
			$this->fieldLabel('RSSDescription')));
		
		return $fields;
	}
	
	public function fieldLabels($includeRelations = true) {
		$labels = parent::fieldLabels($includeRelations);
		
		$labels['NewsTab'] = _t('NewsHolder.NEWSTAB', 'News');
		$labels['RSSTab'] = _t('NewsHolder.RSSTAB', 'RSS');
		
		$labels['NewsPerPage'] = _t('NewsHolder.NEWSPERPAGE',
			'News per page');
		$labels['RSSFromTopmostNewsHolder'] = _t('NewsHolder.RSSFROMTOPMOSTNEWSHOLDER',
			'Generate RSS using the top news holder');
		$labels['RSSLimit'] = _t('NewsHolder.RSSLIMIT', 'Number of news in the RSS feed');
		$labels['RSSTitle'] = _t('NewsHolder.RSSTITLE', 'RSS feed title');
		$labels['RSSDescription'] = _t('NewsHolder.RSSDESCRIPTION',
			'RSS feed description');
		
		if ($includeRelations) {
		}
		
		return $labels;
	}
	
	/**
	 * Collect NewsHolders recursively.
	 * 
	 * @param int $parentID
	 * 
	 * @return DataObjectSet
	 */
	protected function getNewsHolders($parentID) {
		$parentID = (int)$parentID;
		$collected = new DataObjectSet();
		$children = DataObject::get('NewsHolder', "\"ParentID\" = {$parentID}",
			"\"Sort\" DESC");
		if ($children) foreach ($children as $child) {
			$collected->push($child);
			$grandChildren = $this->getNewsHolders($child->ID);
			$collected->merge($grandChildren);
		}
		return $collected;
	}
	
	/**
	 * Cache for NewsAggregate().
	 * @var array
	 */
	protected $newsAggregateCache = array();
	
	/**
	 * Get an aggregate of news from this news archive. Nested
	 * NewsHolder are supported.
	 * 
	 * The result is cached.
	 * 
	 * @param int $start
	 * @param int $length
	 * @param string $filter
	 * @param string $sort
	 * @param boolean $cache
	 * 
	 * @return DataObjectSet
	 */
	public function NewsAggregate($start = 0, $length = -1, $filter = '',
			$sort = '', $cache = true) {
		
		$start = (int)$start;
		$length = (int)$length;
		if ($length < 0) {
			$length = (int)$this->NewsPerPage;
		}
		
		// Check for a cached result.
		$key = "{$start}{$length}{$filter}{$sort}";
		if ($cache && isset($this->newsAggregateCache[$key])) {
			return $this->newsAggregateCache[$key];
		}
		
		if (!$sort) $sort = '"Datetime" DESC';
		if ($filter) $filter = "AND {$filter}";
		
		// Find all requested news pages.
		$newsHolders = $this->getNewsHolders($this->ID);
		$newsHolderIDs = implode(',', array_merge(array($this->ID),
			$newsHolders->column('ID')));
		$where = "\"ParentID\" IN ({$newsHolderIDs}) {$filter}";
		$total = singleton('NewsPage')->extendedSQL($where)->unlimitedRowCount();
		$newsAggregate = DataObject::get('NewsPage', $where, $sort,
			'', "{$start}, {$length}");
		if ($newsAggregate) {
			$newsAggregate->setPageLimits($start, $length, $total);
		} else {
			$newsAggregate = new DataObjectSet();
		}
		$this->newsAggregateCache[$key] = $newsAggregate;
		return $this->newsAggregateCache[$key];
	}
	
	public function News($start = 0, $length = -1) {
		return $this->NewsAggregate($start, $length);
	}
	
	public function flushCache($persistant = true) {
		parent::flushCache($persistant);
		$this->newsAggregateCache = array();
	}
	
	public function RSSHolder() {
		return $this->RSSFromTopmostNewsHolder?
			$this->TopmostNewsHolder(): $this;
	}
	
	public function RSSFeed() {
		$holder = $this->RSSHolder();
		$title = $holder->RSSTitle? $holder->RSSTitle:
			SiteConfig::current_site_config()->Title;
		
		$lastModified = $holder->NewsAggregate(0, 1, '', '"LastEdited" DESC')->First();
		$lastModified = $lastModified? strtotime($lastModified->LastEdited): NULL;
		$rss = new RSSFeed($holder->News(0, $holder->RSSLimit), $holder->Link(),
			$title, $holder->RSSDescription, 'RSSTitle', 'RSSContent',
			'Byline', $lastModified);
		return $rss;
	}
	
	/**
	 * Get the topmost NewsHolder in the SiteTree hierarchy,
	 * when all direct parents are NewsHolders.
	 * 
	 * @return NewsHolder
	 */
	public function TopmostNewsHolder() {
		$topmost = $this;
		while ($topmost->ParentID && in_array('NewsHolder',
				$topmost->Parent()->getClassAncestry())) {
			$topmost = $topmost->Parent();
		}
		return $topmost;
	}
	
}

class NewsHolder_Controller extends Page_Controller {

	public static $allowed_actions = array(
		'rss'
	);
	
	public function init() {
		parent::init();
		Requirements::themedCSS('news');
		RSSFeed::linkToFeed("{$this->data()->RSSHolder()->Link()}rss");
	}
	
	public function rss() {
		$rss = $this->data()->RSSFeed();
		$rss->outputToBrowser();
	}
	
	public function News() {
		$start = (int)($this->request?
			$this->request->requestVar('start'): 0);
		return $this->data()->NewsAggregate($start);
	}
	
}

