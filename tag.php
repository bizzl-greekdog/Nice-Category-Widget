<?php

if (!function_exists('tag')) {
	class Tag {
		private $name = '';
		private $forceClose = false;
		private $attributes = array();
		private $children = array();
		private $style = array();
		private $classes = array();
		
		private function parseCSS($input) {
			$input = strpos($input, ';') > -1 ? explode($input, ';') : array($input);
			foreach ($input as $entry) {
				$m = explode(':', $entry, 2);
				if ($m)
					$this->style[trim($m[0])] = trim($m[1]);
			}
		}

		public function __construct($tagName, $forceClose = false) {
	//		parent::__construct();
			$this->name = $tagName;
			$this->forceClose = $forceClose;
		}

		public function attr($name, $value = NULL) {
			if ($value !== NULL)
				$this->attributes[$name] = $value;
			elseif (is_array($name))
				$this->attributes = array_merge($this->attributes, $name);
			else
				if ($value == 'style')
					return clone $this->style;
				elseif ($value == 'class')
					return clone $this->classes;
				else
					return $this->attributes[$name];
			if (isset($this->attributes['style'])) {
				$this->parseCSS($this->attributes['style']);
				unset($this->attributes['style']);
			}
			if (isset($this->attributes['class'])) {
				$this->addClass($this->attributes['class']);
				unset($this->attributes['class']);
			}
			return $this;
		}
		
		public function css($name, $value = NULL) {
			if ($value !== NULL)
				$this->style[$name] = $value;
			elseif (is_array($name))
				$this->style = array_merge($this->style, $name);
			elseif (strpos($name, ':') > -1)
				$this->parseCSS($name);
			else
				return $this->style[$name];
			return $this;
		}
		
		private function cleanClasses($args) {
//			$args = func_get_args();
			$classes = array();
			foreach ($args as $arg)
				if (is_array($arg))
					$classes = array_merge($classes, $this->cleanClasses($arg));
				elseif (strstr($arg, ' ') > -1)
					$classes = array_merge($classes, $this->cleanClasses(explode(' ', $arg)));
				else
					$classes[] = $arg;
			return $classes;
		}
		
		public function addClass() {
			$classes = func_get_args();
			$this->classes = array_merge($this->classes, $this->cleanClasses($classes));
			$this->classes = array_unique($this->classes);
			return $this;
		}
		
		public function removeClass($classes) {
			if (!is_array($classes))
				if (strstr($classes, ' ') > -1)
					$classes = explode(' ', $classes);
				else
					$classes = array($classes);
			foreach ($classes as $class)
			$this->classes = array_diff($this->classes, $classes);
			return $this;
		}

		public function append($child) {
			if (func_num_args () > 1)
				$child = func_get_args();
			if (is_array($child))
				$this->children = array_merge($this->children, $child);
			else
				array_push($this->children, $child);
			return $this;
		}

		public function __toString() {
			$result = '<' . $this->name;
			foreach ($this->attributes as $key => $value)
					$result .= ' ' . $key . '="' . htmlentities2($value) . '"';
			if (count($this->style)) {
				$css = array();
				foreach ($this->style as $key => $value) {
					if (!$value)
						continue;
					if (intval($value))
						$value = "{$value}px";
					elseif (floatval($value))
						$value = "{$value}pt";
					elseif (is_array($value))
						$value = implode(' ', $value);
					else
						$value = strval($value);
					array_push($css, "{$key}: {$value}");
				}
				$css = implode('; ', $css);
				$result .= ' style="' . htmlentities2($css) . '"';
			}
			if (count($this->classes)) {
				$result .= ' class="' . htmlentities2(implode(' ', $this->classes)) . '"';
			}
			if (count($this->children) || $this->forceClose) {
				$result .= '>';
				foreach ($this->children as $child)
						$result .= $child;
				$result .= "</{$this->name}>";
			} else
				$result .= ' />';
			return $result;
		}
	}

	function tag($tagName, $forceClose = false) {
		return new Tag($tagName, $forceClose);
	}
	
	function br() {
		return new Tag('br', false);
	}
	
	function hr() {
		return new Tag('hr', false);
	}
	
	function div() {
		return new Tag('div', true);
	}
	
	function span() {
		return new Tag('span', true);
	}
	
	function iframe() {
		return new Tag('iframe', true);
	}
	
	function script($src = null) {
		$m = new Tag('script', true);
		if ($src)
			$m->attr('src', $src);
		return $m->attr('type', 'text/javascript');
	}
}
?>