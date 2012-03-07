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

class NewsPage extends Page {
	
	public static $db = array(
		'Datetime' => 'SS_Datetime',
		'Byline' => 'Text',
		'Weight' => 'Text',
		'SummaryTitle' => 'Text',
		'SummaryContent' => 'HTMLText',
		'RedirectLink' => 'Text'
	);
	
	public static $has_one = array(
		'Thumbnail' => 'Image'
	);
	
	public static $has_many = array(
	);
	
	private static $weights = array(
		'Normal'
	);
	
	public static function add_weight($weight) {
		self::$weights[] = $weight;
		$class = "NewsWeight{$weight}";
		if (ClassInfo::exists($class)) {
			Object::add_extension('NewsHolder', $class);
		}
	}
	
	public static function remove_weight($weight) {
		self::$weights = array_diff(self::$weights, array($weight));
		$class = "NewsWeight{$weight}";
		if (ClassInfo::exists($class)) {
			Object::remove_extension('NewsHolder', $class);
		}
	}
	
	public static function set_weights(array $types) {
		foreach (self::$weights as $weight) self::remove_weight($weight);
		foreach ($types as $weight) self::add_weight($weight);
	}
	
	public static function get_weights() {
		return array_values(self::$weights);
	}
	
	public function getCMSFields() {
		$fields = parent::getCMSFields();
		
		$weights = array();
		foreach (self::get_weights() as $weight) {
			$weights[$weight] = _t('NewsPage.WEIGHT' . strtoupper($weight), $weight);
		}
		$fields->addFieldToTab('Root.Content.Main', new DropdownField('Weight',
			$this->fieldLabel('Weight'), $weights, $this->Weight), 'Content');

		$fields->addFieldToTab('Root.Content.Main', new TextField('RedirectLink',
			$this->fieldLabel('RedirectLink')), 'Content');
		
		$fields->addFieldToTab('Root.Content.Main', new DatetimeField('Datetime',
			$this->fieldLabel('Datetime')), 'Content');
		$fields->fieldByName('Root.Content.Main.Datetime')
			->getDateField()->setConfig('dateformat', 'yyyy-MM-dd');
		
		$fields->addFieldToTab('Root.Content.Main', new TextField('Byline',
			$this->fieldLabel('Byline')));
		
		$fields->findOrMakeTab('Root.Content.Summary', $this->fieldLabel('SummaryTab'));
		
		$fields->addFieldToTab('Root.Content.Summary', new ImageField('Thumbnail',
			$this->fieldLabel('Thumbnail')));
		$fields->addFieldToTab('Root.Content.Summary', new TextField(
			'SummaryTitle', $this->fieldLabel('SummaryTitle')));
		$fields->addFieldToTab('Root.Content.Summary', new HTMLEditorField(
			'SummaryContent', $this->fieldLabel('SummaryContent')));
		
		return $fields;
	}
	
	public function fieldLabels($includeRelations = true) {
		$labels = parent::fieldLabels($includeRelations);
		
		$labels['SummaryTab'] = _t('NewsPage.SUMMARYTAB', 'Summmary');
		
		$labels['Datetime'] = _t('NewsPage.DATETIME', 'News date, news pages are sorted on this date');
		$labels['Byline'] = _t('NewsPage.BYLINE', 'Byline');
		$labels['Weight'] = _t('NewsPage.WEIGHT', 'Weight');
		$labels['SummaryTitle'] = _t('NewsPage.SUMMARYTITLE', 'SummaryTitle');
		$labels['SummaryContent'] = _t('NewsPage.SUMMARYCONTENT', 'SummaryContent');
		$labels['RedirectLink'] = _t('NewsPage.REDIRECTLINK', 'Redirect link');
		
		if ($includeRelations) {
			$labels['Thumbnail'] = _t('NewsPage.THUMBNAIL', 'Thumbnail');
		}
		
		return $labels;
	}
	
	public function onBeforeWrite() {
		parent::onBeforeWrite();
		if (!$this->Datetime)
			$this->Datetime = date('Y-m-d H:i:s');
	}
	
	/**
	 * This method is best explained through an example:
	 * <code>
	 * // Get the DBField for SummaryTitle. If it is blank, check
	 * // Title. If both are blank a DBField with a NULL value
	 * // will be returened.
	 * $title = $newsPage->OneFieldFrom('SummaryTitle', 'Title');
	 * </code>
	 * Or, used in a template:
	 * <code>
	 * // Same as above.
	 * $OneFieldFrom(SummaryTitle Title)
	 * </code>
	 * 
	 * This makes it possible to get user data from a desired
	 * field and getting less desired data if that field is
	 * blank, without cluttering the code with conditional
	 * statements.
	 * 
	 * @param string s1
	 * @param string s2
	 * @param string ...
	 * @param string sN
	 * 
	 * @return DBField
	 */
	public function OneFieldFrom() {
		$args = func_get_args();
		$fields = count($args) == 1 && is_string($args[0])?
			explode(' ', $args[0]): $args;
		
		foreach ($fields as $field) {
			if ($this->hasField($field) && $this->$field) {
				return $this->dbObject($field);
			}
		}
		$nullField = new Text('__NULL__');
		$nullField->setValue(null);
		return $nullField;
	}
	
	/**
	 * For displaying the date (and not the creation time) in
	 * the RSS feed for a NewsHolder.
	 */
	public function Date() {
		return DBField::create('Date', $this->Datetime);
	}
	
	/**
	 * Format $this->Datetime, primarily useful in templates.
	 * 
	 * @param string $format
	 * 
	 * @return string
	 */
	public function FormatDatetime($format = 'YYYY-MM-dd HH:mm') {
		require_once 'Zend/Date.php';
		if ($constant = @constant("Zend_Date::{$format}")) {
			$format = $constant;
		}
		$date = new Zend_Date(strtotime($this->Datetime),
			false, i18n::get_locale());
		return $date->toString($format);
	}
	
	public function ClosestNewsHolder() {
		$parent = $this->Parent();
		return $parent->ID && in_array('NewsHolder',
			$parent->getClassAncestry())? $parent: null;
	}
	
	public function TopmostNewsHolder() {
		$newsHolder = $this->ClosestNewsHolder();
		return $newsHolder? $newsHolder->TopmostNewsHolder(): null;
	}
	
	public function getRSSTitle() {
		return $this->SummaryTitle? $this->SummaryTitle: $this->Title;
	}
	
	public function getRSSContent() {
		if ($this->SummaryContent) {
			return $this->SummaryContent;
		}
		$content = $this->dbObject('Content')->Summary();
		return DBField::create('Text', $content);
	}
	
}

class NewsPage_Controller extends Page_Controller {
	
	public static $allowed_actions = array(
	);
	
	public function init() {
		parent::init();
        if ($this->response && $this->data()->RedirectLink) {
        	$this->response->redirect($this->data()->RedirectLink);
        }
	}
	
}

