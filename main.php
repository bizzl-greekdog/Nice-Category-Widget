<?php
/*
  Plugin Name: Nice Category Widget
  Plugin URI:
  Description: A nicer, filtering category widget.
  Version: 1.0.0
  Author: Benjamin Kleiner <bizzl@users.sourceforge.net>
  Author URI:
  License: LGPL3
 */

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'tag.php');

class Nice_Category_Widget extends WP_Widget {

	protected static $domain = 'nice-category-widget';
	protected static $base = '';
	protected static $defaults = null;

	protected static function init_defaults() {
		self::$defaults = array(
			'title' => __('Nice Category Widget', self::$domain),
		);
	}

	protected static function init_base() {
		self::$base = basename(dirname(__FILE__));
	}

	protected static function init_l10n() {
		$j = join_path(self::$base, 'locale');
		load_plugin_textdomain(self::$domain, false, $j);
	}

	public static function init() {
		self::init_base();
		self::init_l10n();
		self::init_defaults();
		
		add_action('widgets_init', array(__CLASS__, 'register_me'));
	}

	public static function register_me() {
		register_widget(__CLASS__);
	}

	/* Actual Widget Code */

	function Nice_Navigation_Widget() {
		$widget_ops = array(
			'classname' => self::$domain,
			'description' => __('A nicer, filtering category widget.', self::$domain)
		);

		$control_ops = array(
			'width' => 'auto',
			'height' => 350,
			'id_base' => self::$domain
		);

		parent::WP_Widget(self::$domain, __('Nice Category Widget', self::$domain), $widget_ops, $control_ops);
	}
	
	function widget($args, $instance) {
	}

	function update($new_instance, $old_instance) {
		return array_merge($old_instance, $new_instance);
	}

	function form($instance) {
	}
	
}

Nice_Category_Widget::init();
?>
