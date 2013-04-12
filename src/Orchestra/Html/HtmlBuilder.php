<?php namespace Orchestra\Html;

class HtmlBuilder extends \Illuminate\Html\HtmlBuilder {

	/**
	 * Generate a HTML element
	 *
	 * @access public
	 * @param  string   $tag
	 * @param  mixed    $value
	 * @param  array    $attributes
	 * @return string
	 */
	public function create($tag = 'div', $value = null, $attributes = array())
	{
		if (is_array($value))
		{
			$attributes = $value;
			$value      = null;
		}

		$content = '<'.$tag.$this->attributes($attributes).'>';

		if ( ! is_null($value))
		{
			$content .= $this->entities($value).'</'.$tag.'>';
		}
		
		return $content;
	}

	/**
	 * Convert HTML characters to entities.
	 *
	 * The encoding specified in the application configuration file will be used.
	 *
	 * @access public
	 * @param  string   $value
	 * @return string
	 */
	public function entities($value)
	{
		if ($value instanceof Expression) return $value->get();
		
		return parent::entities($value);
	}

	/**
	 * Create a new HTML expression instance are used to inject HTML.
	 * 
	 * @access public
	 * @param  string      $value
	 * @return Expression
	 */
	public function raw($value)
	{
		return new Expression($value);
	}

	/**
	 * Build a list of HTML attributes from one or two array.
	 *
	 * @access public
	 * @param  array    $attributes
	 * @param  array    $defaults
	 * @return array
	 */
	public function decorate($attributes, $defaults = null)
	{
		// Special consideration to class, where we need to merge both string from
		// $attributes and $defaults and take union of both.
		$c1       = isset($defaults['class']) ? $defaults['class'] : '';
		$c2       = isset($attributes['class']) ? $attributes['class'] : '';
		$classes  = explode(' ', trim($c1.' '.$c2));
		$current  = array_unique($classes);
		$excludes = array();

		foreach ($current as $c)
		{
			if (starts_with($c, '!'))
			{
				$excludes[] = substr($c, 1);
				$excludes[] = $c;
			}
		}

		$class      = implode(' ', array_diff($current, $excludes));
		$attributes = array_merge($defaults, $attributes);

		empty($class) or $attributes['class'] = $class;

		return $attributes;
	}

}