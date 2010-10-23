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
	 * Boolean for debug output.
	 */
	public $debug = false;

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

		preg_match_all( '/\{\{(?<slash>\/?)(?<type>\w+|.)(?:\((?<fnargs>(?:[^\}]|\}(?!\}))*?)?\))?(?:\s+(?<target>.*?)?)?(?<parens>\((?<args>(?:[^\}]|\}(?!\}))*?)\))?\s*\}\}/', $tmpl_string, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE );

		$state = array(
			'eof' => strlen($tmpl_string), // length of the template string
			'pos' => 0,                    // current position in template string
			'if' => 0,                     // if statement depth
			'skip' => false,               // true if we are skipping output (ie. due to a false if condition)
			'depth' => 0,                  // recursion depth
		);

		$html = $this->parse( $tmpl_string, $data, $matches, $state );

		return $html;
	}//end tmpl

	/**
	 * Parse a template to build an HTML string.
	 */
	public function parse( $tmpl, $data, &$matches, &$state ) {
		$html = '';

		$state['depth'] += 1;

		while(true) {
			$this->debug( "skip mode is [%s], depth [%d]", $state['skip'] ? 'on' : 'off', $state['depth'] );
			if( empty($matches) ) {
				if( $state['pos'] < $state['eof'] ) {
					// just copy rest of string
					$html .= substr($tmpl, $state['pos']);
					break;
				}
				break;
			}

			//
			// we have another template tag
			//
			
			$match = array_shift($matches);

			// we have text between template tags; get us up to the next tag,
			// appending the intervening text if necessary (ie. not in skip mode)
			if( $state['skip'] == false && $state['pos'] < $match[0][1] ) {
				$this->debug('adding string [%s]', substr($tmpl, $state['pos'], $match[0][1] - $state['pos']));
				$html .= substr($tmpl, $state['pos'], $match[0][1] - $state['pos']);

				$state['pos'] = $match[0][1];
			}

			$type = $match['type'][0];
			$target = isset($match['target']) ? $match['target'][0] : null;
			$slash = $match['slash'][0] == '/';

			$this->debug("type is [%s]; target is [%s]\n", $type, $target);

			// move string position after the template tag
			$state['pos'] = $match[0][1] + strlen($match[0][0]);

			//
			// what is our template tag?
			//

			if( $type == '=' && $state['skip'] == 0 ) {
				$html .= htmlentities($data[ $target ]);
			} elseif( $type == 'html' && $state['skip'] == 0 ) {
				$html .= $data[ $target ];
			} elseif( $type == 'if' ) {
				if( ! $slash ) {
					if( $state['skip'] ) {
						// we're already skipping this block, just throw away the parsing
						$this->parse( $tmpl, $data, $matches, $state );
						$state['skip'] = true; // make sure this persists
						continue;
					}

					$prev_skip = $state['skip'];
					$state['skip'] = (bool)$data[$target] == false;

					$html .= $this->parse( $tmpl, $data, $matches, $state );
					$state['skip'] = $prev_skip;
				}

				// closing tag
				else {
					$state['depth'] -= 1;
					return $html;
				}
			} elseif( $type == 'else' ) {
				// this will happen in a recursed parse()
				
				// flip skip from the parent; if the parent {{if}} showed, we want to hide,
				// and vice-versa
				$state['skip'] = !$state['skip'];

				// let processing continue as normal
			}

			$this->debug( "remaining pattern: [%s]", substr($tmpl, $state['pos']) );
		}

		$state['depth'] -= 1;
		return $html;
	}//end parse

	/**
	 * Check to see what the next tag is.
	 * @param $matches array the array of matches
	 * @return string name of the next template tag
	 */
	public function peek_next_tag( &$matches ) {
		if( count($matches) == 0 ) {
			return null;
		}

		return $matches[0]['type'][0];
	}//end peek_next_tag

	/**
	 * Move to the next tag.
	 *
	 * @param $matches array the array of matches
	 * @param $state array the parsing state
	 */
	public function moveto_next_tag( &$matches, &$state ) {
	}//end moveto_next_tag

	/**
	 * Jump to the next template tag.
	 *
	 * @param $matches array the array of matches
	 * @param $state array the parsing state
	 * @param $html string that will receive any content leading up to the next tag; omit to ignore this content
	 * @return the next template tag
	 */
	public function next_tag( &$matches, &$state, &$html = null ) {

		return $match;
	}//end next_tag

	/**
	 * Debug function.
	 */
	public function debug() {
		if( $this->debug ) {
			$args = func_get_args();
			$s = array_shift($args);
			vprintf( $s . "\n", $args );
		}
	}//end debug

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
