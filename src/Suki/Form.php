<?php

/**
 * @package Ohara helper class
 * @version 1.1
 * @author Jessica González <suki@missallsunday.com>
 * @copyright Copyright (c) 2016, Jessica González
 * @license http://www.mozilla.org/MPL/2.0/
 */

namespace Suki;

class Form
{
	public $elements = array();
	protected $_buffer = '';
	protected $_app;
	protected $_options = array('name' => '',);
	protected $_counter = 0;

	public function __construct($app)
	{
		$this->_app = $app;
	}

	public function setOptions($options = array())
	{
		global $context;

		if (empty($options))
			return false;

		$this->_options = array_merge($this->_options, $options);

		// Always add the session if available.
		if (!empty($context['session_var']) && !empty($context['session_id']))
			$this->addHiddenField($context['session_var'], $context['session_id']);
	}

	protected function setText($text)
	{
		return $this->_app['tools']->text($this->_textPrefix . $text);
	}

	protected function addElement($element)
	{
		$this->elements[++$this->_counter] = $element;
	}

	public function getElements($id = 0)
	{
		return !empty($id) ? $this->elements[$id] : $this->elements;
	}

	public function modifyElement($id = 0, $data = array())
	{
		if (empty($id) || empty($data) || empty($this->elements[$id]))
			return false;

		$this->elements[$id] = $data;
	}

	protected function setParamValues(&$param)
	{
		// No text? use the name as a $txt key then!
		if (empty($param['text']))
			$param['text'] = $param['name'];

		// Give it a chance to use a full text string.
		$param['text']  = !empty($param['fullText']) ? $param['fullText'] : $this->setText($param['text']);
		$param['desc']  = !empty($param['fullDesc']) ? $param['fullDesc'] : $this->setText($param['name'] .'_sub');
	}

	public function addSelect($param = array())
	{
		// Kinda needs this...
		if (empty($param) || empty($param['name']))
			return;

		$this->setParamValues($param);

		$param['type'] = 'select';
		$param['html_start'] = '<'. $param['type'] .' name="'. (!empty($this->_options['name']) ? $this->_options['name'] .'['. $param['name'] .']' : $param['name']) .'">';
		$param['html_end'] = '</'. $param['type'] .'>';
		foreach($values as $k => $v)
			$param['values'][$k] = '<option value="' .$k. '" '. (isset($v[1]) && $v[1] == 'selected' ? 'selected="selected"' : '') .'>'. $this->_app['tools']->text($v[0]) .'</option>';

		return $this->addElement($param);
	}

	public function addCheckBox($param = array())
	{
		// Kinda needs this...
		if (empty($param) || empty($param['name']))
			return;

		$this->setParamValues($param);

		$param['type'] = 'checkbox';
		$param['value'] = 1;
		$param['checked'] = empty($param['checked']) ? '' : 'checked="checked"';
		$param['html'] = '<input type="'. $param['type'] .'" name="'. (!empty($this->_options['name']) ? $this->_options['name'] .'['. $param['name'] .']' : $param['name']) .'" id="'. $param['name'] .'" value="'. $param['value'] .'" '. $param['checked'] .' class="input_check" />';

		return $this->addElement($param);
	}

	public function addText($param = array())
	{
		// Kinda needs this...
		if (empty($param) || empty($param['name']))
			return;

		$this->setParamValues($param);

		$param['type'] = 'text';
		$param['size'] = empty($param['size'] ) ? 'size="20"' : 'size="'. $param['size'] .'"';
		$param['maxlength'] = empty($param['maxlength']) ? 'maxlength="20"' : 'maxlength="'. $param['maxlength'] .'"';
		$param['html'] = '<input type="'. $param['type'] .'" name="'. (!empty($this->_options['name']) ? $this->_options['name'] .'['. $param['name'] .']' : $param['name']) .'" id="'. $param['name'] .'" value="'. $param['value'] .'" '. $param['size'] .' '. $param['maxlength'] .' class="input_text" />';

		return $this->addElement($param);
	}

	public function addTextArea($param = array())
	{
		// Kinda needs this...
		if (empty($param) || empty($param['name']))
			return;

		$this->setParamValues($param);

		$param['type'] = 'textarea';
		$param['value'] = empty($param['value']) ? '' : $param['value'];

		// To a void having a large and complicate ternary, split these options.
		$rows = 'rows="'. (!empty($param['size'] ) && !empty($param['size']['rows']) ? $param['size']['rows'] : 10) .'"';
		$cols = 'cols="'. (!empty($param['size'] ) && !empty($param['size']['cols']) ? $param['size']['cols'] : 40) .'"';
		$param['maxlength'] = 'maxlength="'. (!empty($param['size'] ) && !empty($param['size']['maxlength']) ? $param['size']['maxlength'] : 1024) .'"';
		$param['html'] = '<'. $param['type'] .' name="'. (!empty($this->_options['name']) ? $this->_options['name'] .'['. $param['name'] .']' : $param['name']) .'" id="'. $param['name'] .'" '. $rows .' '. $cols .' '. $param['maxlength'] .'>'. $param['value'] .'</'. $param['type'] .'>';

		return $this->addElement($param);
	}

	public function addHiddenField($name, $value)
	{
		$param['type'] = 'hidden';
		$param['html'] = '<input type="'. $param['type'] .'" name="'. $name .'" id="'. $name .'" value="'. $value .'" />';

		return $this->addElement($param);
	}

	public function addHr($custom = '')
	{
		$param['type'] = 'hr';
		$param['html'] = $custom ? $custom : '<br><hr><br>';

		return $this->addElement($param);
	}

	public function addHTML($param = array())
	{
		// Kinda needs this...
		if (empty($param) || empty($param['name']))
			return;

		$this->setParamValues($param);

		$param['type'] = 'html';

		return $this->addElement($param);
	}

	public function addButton($param = array())
	{
		// Kinda needs this...
		if (empty($param) || empty($param['name']))
			return;

		$this->setParamValues($param);

		$param['type'] = 'button';

		return $this->addElement($param);
	}

	public function addSection($param = array())
	{
		// Kinda needs this...
		if (empty($param) || empty($param['name']))
			return;

		$this->setParamValues($param);

		$param['type'] = 'section';

		return $this->addElement($param);
	}

	public function display()
	{
		$this->_buffer = '
	<dl class="settings">';

		foreach($this->elements as $el)
		{
			switch($el['type'])
			{
				case 'textarea':
				case 'checkbox':
				case 'text':
					$this->_buffer .= '
		<dt>
			<span>'. $el['text'] .'</span>
			<br><span class="smalltext">'. $el['desc'] .'</span>
		</dt>
		<dd>
			<input type="hidden" name="'. (!empty($this->_formOptions['name']) ? $this->_formOptions['name'] .'['. $el['name'] .']' : $el['name']) .'" value="0" />'. $el['html'] .'
		</dd>';
					break;
				case 'select':
					$this->_buffer .= '
		<dt>
			<span>'. $el['text'] .'</span>
			<br><span class="smalltext">'. $el['desc'] .'</span>
		</dt>
		<dd>
			<input type="hidden" name="'. (!empty($this->_formOptions['name']) ? $this->_formOptions['name'] .'['. $el['name'] .']' : $el['name']) .'" value="0" />'. $el['html_start'] .'';

					foreach($el['values'] as $k => $v)
						$this->_buffer .= $v;

					$this->_buffer .= $el['html_end'] .'
				</dd>';
					break;
				case 'hidden':
				case 'submit':
					$this->_buffer .= '
				<dt></dt>
				<dd>
					'. $el['html'] .'
				</dd>';
					break;
				case 'hr':
					$this->_buffer .= '
				</dl>
					'. $el['html'] .'
				<dl class="settings">';
					break;
				case 'html':
					$this->_buffer .= '
				<dt>
					<span>'. $el['text'] .'</span>
					<br><span class="smalltext">'. $el['desc'] .'</span>
				</dt>
				<dd>
					'. $el['html'] .'
				</dd>';
					break;
				case 'section':
				$this->_buffer .= '
				</dl>
				<div class="cat_bar">
					<h3 class="catbg">'. $el['text'] .'</h3>
				</div>
				<br>
				<dl class="settings">';
					break;
			}
		}

		$this->_buffer .= '
			</dl>';

		return $this->_buffer;
	}
}
