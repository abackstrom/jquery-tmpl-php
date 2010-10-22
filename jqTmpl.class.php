<?php

require_once 'phpQuery.php';

/**
 *
 */
class jqTmpl {
	public $templates = array();
	public $pq = null;

	/**
	 *
	 */
	public function load_document( $html ) {
		$pq = phpQuery::newDocument( $html );

		$this->pq($pq);

		return $this->pq();
	}//end load_document

	/**
	 * Render a template using some provided template variables.
	 *
	 * @param $tmpl string|phpQueryObject a template as a string or a phpQuery selection
	 * @param $data array associative array of data to populate into the template
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
	}//end template_from_string

	/**
	 *
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
	}//end qp

	/**
	 *
	 */
	public function preparse( $tmpl ) {
		$tmpl = preg_replace( '/\$\{\s*([^\}]*?)\s*\}/', "{{= $1}}", $tmpl );
		return $tmpl;
	}//end preparse

	/**
	 *
	 */
	public function __construct() {
	}//end __construct
}//end class jqTmpl
