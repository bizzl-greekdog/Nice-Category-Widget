<?php
/*
Plugin Name:	Nice Category Widget
Plugin URI:		https://github.com/bizzl-greekdog/Nice-Category-Widget
Description:	A nicer, filtering category widget.
Version:		1.0.0
Author:			Benjamin Kleiner
Author URI:		https://github.com/bizzl-greekdog
License:		LGPL3
*/
/*
    Copyright (c) 2011 Benjamin Kleiner <bizzl@users.sourceforge.net>
 
    This file is part of Nice Category Widget.

    Nice Category Widget is free software: you can redistribute it and/or modify
    it under the terms of the GNU Lesser General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Nice Category Widget is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Lesser General Public License for more details.

    You should have received a copy of the GNU Lesser General Public License
    along with Nice Category Widget. If not, see <http://www.gnu.org/licenses/>.
*/

if (!function_exists('join_path')) {

	function join_path() {
		$fuck = func_get_args();
		for ($i = 0; $i < count($fuck); $i++)
			if (is_array($fuck[$i]))
				array_splice($fuck, $i, 1, $fuck[$i]);
		$f = implode(DIRECTORY_SEPARATOR, $fuck);
		return preg_replace('/(?<!:)\\' . DIRECTORY_SEPARATOR . '+/', DIRECTORY_SEPARATOR, $f);
	}

}

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'tag.php');

class Nice_Category_Widget_Walker extends Walker_Category {
	
	public function start_lvl(&$output, $depth, $args) {}
	
	public function end_lvl(&$output, $depth, $args) {}
	
	public function start_el(&$output, $cat, $depth, $args) {
		$pad = str_repeat('&emsp;', $depth);
		$output .= group(
				span($pad)->css('white-space', 'pre'),
				checkbox(
					$args['id'] . '[]',
					$args['name'] . '-' . $cat->cat_ID,
					in_array($cat->term_id, $args['exclude']),
					$cat->name,
					$cat->term_id
				)->addClass('childof-' . $cat->category_parent, 'category-exclude-cb')->addClass($args['classes'])
		);
	}
	
	public function end_el(&$output, $page, $depth, $args) {}
}

class Nice_Category_Widget extends WP_Widget {

	protected static $domain = 'nice-category-widget';
	protected static $base = '';
	protected static $defaults = null;

	protected static function init_defaults() {
		self::$defaults = array(
			'title' => __('Nice Category Widget', self::$domain),
			'count' => false,
			'hierarchical' => false,
			'dropdown' => false,
			'exclude' => array()
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

	function Nice_Category_Widget() {
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
		extract($args);

		$title = apply_filters('widget_title', empty($instance['title']) ? __('Categories') : $instance['title'], $instance, $this->id_base);
		$c = $instance['count'] ? '1' : '0';
		$h = $instance['hierarchical'] ? '1' : '0';
		$d = $instance['dropdown'] ? '1' : '0';
		$e = $instance['exclude'];

		echo $before_widget;
		if ($title)
			echo $before_title . $title . $after_title;

		$cat_args = array('orderby' => 'name', 'show_count' => $c, 'hierarchical' => $h, 'exclude' => $e);

		if ($d) {
			$cat_args['show_option_none'] = __('Select Category');
			wp_dropdown_categories(apply_filters('widget_categories_dropdown_args', $cat_args));

			echo script(array(
				'code' => '
	var dropdown = document.getElementById("cat");
	function onCatChange() {
		if ( dropdown.options[dropdown.selectedIndex].value > 0 ) {
			location.href = "' . home_url() . '/?cat="+dropdown.options[dropdown.selectedIndex].value;
		}
	}
	dropdown.onchange = onCatChange;'
			));


		} else {
			$cat_args['title_li'] = '';
			$cat_args['echo'] = false;
			echo tag('ul')->append(wp_list_categories(apply_filters('widget_categories_args', $cat_args)));
		}

		echo $after_widget;
	}

	function update($new_instance, $old_instance) {
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['count'] = !empty($new_instance['count']) ? 1 : 0;
		$instance['hierarchical'] = !empty($new_instance['hierarchical']) ? 1 : 0;
		$instance['dropdown'] = !empty($new_instance['dropdown']) ? 1 : 0;
		$instance['exclude'] = $new_instance['exclude'];
		return $instance;
	}

	function form($instance) {
		//Defaults
		$instance = wp_parse_args((array)$instance, self::$defaults);
		$title = esc_attr($instance['title']);
		
		echo p('')->append(
			label($this->get_field_id('title'), __('Title:', self::$domain)),
			tag('input')->attr(array(
				'id' => $this->get_field_id('title'),
				'name' => $this->get_field_name('title'),
				'type' => 'text',
				'value' => $title
			))
		);
		
		echo checkbox($this->get_field_name('dropdown'), $this->get_field_id('dropdown'), $instance['dropdown'], __('Display as dropdown', self::$domain));
		echo checkbox($this->get_field_name('count'), $this->get_field_id('count'), $instance['count'], __('Show post counts', self::$domain));
		echo checkbox($this->get_field_name('hierarchical'), $this->get_field_id('hierarchical'), $instance['hierarchical'], __('Show hierarchy', self::$domain));

		// TODO Externalize script
		
		$exclude_cb_classes = preg_replace(array('/\[/', '/\]/'), array('-', ''), $this->get_field_name('exclude'));
		
		echo script(array('code' => '
jQuery(function($) {
	$("input.' . $exclude_cb_classes . '.category-exclude-cb").change(function() {
		var $this = $(this);
		var id = $this.attr("id").split("-").pop();
		if ($this.attr("checked")) {
			$("input.' . $exclude_cb_classes . '.childof-" + id).attr("checked", "checked");
			$("input.' . $exclude_cb_classes . '.childof-" + id).change();
		} else {
			parents = $this.attr("class").match(/childof-[0-9]+/g);
			for (var i in parents) {
				id = "#' . $this->get_field_id('exclude') . '-" + parents[i].split("-").pop();
				$(id).removeAttr("checked").change();
			}
		}
		
	});
});
'));
		
		echo h(__('Exclude:', self::$domain), 5);
		$cats = get_categories(array('hide_empty' => false, 'orderby' => 'name', 'hierarchical' => true));
		if (empty($cats)) {
			error_log('$cats is empty!!!');
		} else {
			$walker = new Nice_Category_Widget_Walker();
			echo p($walker->walk($cats, 0, array(
				'id' => $this->get_field_name('exclude'),
				'name' => $this->get_field_id('exclude'),
				'exclude' => $instance['exclude'],
				'classes' => $exclude_cb_classes
			)));
		}
		
	}
	
}

Nice_Category_Widget::init();
?>
