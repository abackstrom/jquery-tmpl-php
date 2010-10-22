<?php

require_once 'phpQuery.php';

/**
 * jquery-tmpl implemented in PHP.
 *
 * $tpl = new jqTmpl;
 */
class jqTmpl {
	/**
	 * Future home of cached templates?
	 */
	public $templates = array();

	/**
	 * Load a new document into this template's phpQuery object so that we can
	 * specify templates by selectors.
	 *
	 * @param $html string the html fragment to load
	 * @return the created phpQueryObject
	 */
	public function load_document( $html ) {
		$pq = phpQuery::newDocument( $html );

		$this->pq($pq);

		return $this->pq();
	}//end load_document

	/**
	 * Render a template using some provided template variables.
	 *
	 * 1. $tpl->tmpl('<i>Hi, ${name}</i>', array('name' => 'Adam')); // "<i>Hi, Adam</i>"
	 *
	 * 2. $tpl->load_document('<div id="which">Mmm, {{= fruit}}</div>');
	 *    $tpl->tmpl( $tpl->pq('#which'), array('fruit' => 'Donuts')); // "Mmm, Donuts"
	 *
	 * @param $tmpl string|phpQueryObject a template as a string or a phpQuery selection
	 * @param $data array associative array of data to populate into the template
	 * @return the rendered template string
	 */
	public function tmpl( $tmpl, $data = array(), $options = array() ) {
		if( is_string($tmpl) ) {
			$tmpl_string = $tmpl;
		} elseif( $tmpl instanceof phpQueryObject ) {
			$tmpl_string = $tmpl->eq(0)->html();
		}

		$tmpl_string = $this->preparse( $tmpl_string );

		$tmpl_string = preg_replace_callback( "/{{= ([^}]+?)\s*}}/", function($m) use ($data) {
			return $data[$m[1]];
		}, $tmpl_string );

		return $tmpl_string;
	}//end tmpl

	/**
	 * Get, set, or query using the phpQuery object.
	 *
	 * @param $arg string|phpQueryObject if a string, query the dom and return the result. if a phpQuery object, use this object as our internal phpQuery object. if null, return the current phpQuery object.
	 * @return returns a phpQuery object, possibly the result of a selection
	 */
	public function pq( $arg = null ) {
		static $pq = null;

		if( $arg !== null ) {
			if( $arg instanceof phpQueryObject ) {
				return $pq = $arg; 
			} else {
				return pq($arg, $pq);
			}
		}

		return $pq;
	}//end pq

	/**
	 * Do some pre-rendering cleanup.
	 *
	 * @param $tmpl string the template string
	 * @return the cleaned string
	 */
	public function preparse( $tmpl ) {
		$tmpl = preg_replace( '/\$\{\s*([^\}]*?)\s*\}/', "{{= $1}}", $tmpl );
		return $tmpl;
	}//end preparse
}//end class jqTmpl
