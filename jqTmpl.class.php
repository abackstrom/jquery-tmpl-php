<?php

require_once 'HTML5/Parser.php';

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
	public $_templates = array();

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
		$dom = HTML5_Parser::parse( $html );

		// TODO: getElementById?
		$nodes = $dom->getElementsByTagName('script');

		// reset cached templates lists
		$this->templates = $this->_templates = array();

		foreach( $nodes as $node ) {
			if( $id = $node->getAttribute('id') ) {
				$this->_templates[$id] = $node->nodeValue;
			}
		}
	}//end load_document

	/**
	 * Fetch a template from the loaded DOM document by its id.
	 *
	 * @param $id string the id, with or without #
	 * @return string html string
	 */
	public function tmpl_by_id( $id ) {
		// remove # if it's there
		if( "#" === substr($id, 0, 1) ) {
			$id = substr($id, 1);
		}

		if( isset($this->templates[$id]) ) {
			return $this->templates[$id];
		}

		if( isset($this->_templates[$id]) ) {
			return $this->templates[$id] = $this->preparse($this->_templates[$id]);
		}

		return null;
	}//end getElementById

	/**
	 * Render a template using some provided template variables.
	 *
	 * 1. $tpl->tmpl('<i>Hi, ${name}</i>', array('name' => 'Adam')); // "<i>Hi, Adam</i>"
	 *
	 * 2. $tpl->load_document('<div id="which">Mmm, {{= fruit}}</div>');
	 *    $tpl->tmpl( $tpl->pq('#which'), array('fruit' => 'Donuts')); // "Mmm, Donuts"
	 *
	 * @param $tmpl string|phpQueryObject a template as a string or a phpQuery selection
	 * @param $data array array of data to populate into the template
	 * @return the rendered template string
	 */
	public function tmpl( $tmpl, $data = null, $options = array() ) {
		if( preg_match('/^#-?[_a-zA-Z]+[_a-zA-Z0-9-]*$/', $tmpl) ) {
			$tmpl_string = $this->tmpl_by_id( $tmpl );
		} else {
			$tmpl_string = $tmpl;
			$tmpl_string = $this->preparse( $tmpl_string );
		}

		preg_match_all( '/\{\{(?<slash>\/?)(?<type>\w+|.)(?:\((?<fnargs>(?:[^\}]|\}(?!\}))*?)?\))?(?:\s+(?<target>.*?)?)?(?<parens>\((?<args>(?:[^\}]|\}(?!\}))*?)\))?\s*\}\}/', $tmpl_string, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE );

		$state = array(
			'eof' => strlen($tmpl_string), // length of the template string
			'pos' => 0,                    // current position in template string
			'if' => 0,                     // if statement depth
			'skip' => false,               // true if we are skipping output (ie. due to a false if condition)
			'depth' => 0,                  // recursion depth
		);

		if( is_object($data) || ! isset($data) ) {
			$html = $this->parse( $tmpl_string, $data, $matches, $state );
		} elseif( is_array($data) ) {
			$html = '';

			foreach( $data as $row ) {
				$state_this = $state; // reset this pass-by-reference var 
				$matches_this = $matches;

				$html .= $this->parse( $tmpl_string, $row, $matches_this, $state_this );
			}
		} else {
			throw new UnsupportedDataTypeException;
		}

		return $html;
	}//end tmpl

	/**
	 * Parse a template to build an HTML string.
	 */
	public function parse( $tmpl, $data, &$matches, &$state ) {
		$html = '';

		extract( $state, EXTR_REFS );

		$depth += 1;

		while(true) {
			$this->debug( "skip mode is [%s], depth [%d]", $skip ? 'on' : 'off', $depth );

			if( empty($matches) ) {
				if( $pos < $eof ) {
					// just copy rest of string
					$html .= substr($tmpl, $pos);
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
			if( $skip == false && $pos < $match[0][1] ) {
				$this->debug('adding string [%s]', substr($tmpl, $pos, $match[0][1] - $pos));

				$html .= substr($tmpl, $pos, $match[0][1] - $pos);

				$pos = $match[0][1];
			}

			$type = $match['type'][0];
			$target = isset($match['target']) ? $match['target'][0] : null;
			$slash = $match['slash'][0] == '/';

			$this->debug("type is [%s%s]; target is [%s]\n", $slash ? '/' : '', $type, $target);

			// move string position after the template tag
			$pos = $match[0][1] + strlen($match[0][0]);

			//
			// what is our template tag?
			//

			// {{= expression}}
			if( $type == '=' && $skip == 0 ) {
				$html .= htmlentities($data->$target);
			}
			
			// {{html expression}}
			elseif( $type == 'html' && $skip == 0 ) {
				$html .= $data->$target;
			}

			// {{if}}, {{/if}}
			elseif( $type == 'if' ) {
				if( ! $slash ) {
					if( $skip ) {
						// we're already skipping this block, just throw away the parsing
						$this->parse( $tmpl, $data, $matches, $state );
						$skip = true; // force this back to true, in case the above
						              // parse modified it.
						continue;
					}

					$skip = (bool)$data->$target == false;

					$html .= $this->parse( $tmpl, $data, $matches, $state );
				}

				// closing tag
				else {
					$depth -= 1;
					return $html;
				}
			}

			// {{else}}
			elseif( $type == 'else' ) {
				// this will happen in a recursed parse()
				
				// flip skip from the parent; if the parent {{if}} showed, we want to hide,
				// and vice-versa
				$skip = !$skip;

				// let processing continue as normal
			}

			// {{each foo}}, {{each(i, b) foo}}, {{/each}}
			elseif( $type == 'each' ) {
				if( $slash ) {
					return $html;
				}

				if( ! $data->$target ) {
					continue;
				}

				$reset_matches = $matches;
				$reset_pos = $pos;
				$data_copy = $data;

				if( $match['fnargs'][0] ) {
					list($index_name, $value_name) = explode(',', $match['fnargs'][0]);

					$index_name = trim($index_name);
					$value_name = trim($value_name);
				} else {
					$index_name = 'index';
					$value_name = 'value';
				}

				// repeat the loop over this each block
				foreach( $data->$target as $index => $value ) {
					// reset first so last iteration ends in the correct place
					$matches = $reset_matches;
					$pos = $reset_pos;

					$data_copy->$index_name = $index;
					$data_copy->$value_name = $value;

					$html .= $this->parse( $tmpl, $data_copy, $matches, $state);
				}
			}

			// {{tmpl "#foo"}}
			elseif( $type == 'tmpl' ) {
				if( $skip ) {
					continue;
				}

				$target = trim($target, '\'"');

				$html .= $this->tmpl( $target, $data );
			}

			// {{! comment}}
			elseif( $type == '!' ) {
				// "comment tag, skipped by parser"
			}

			// unknown type
			else {
				throw new UnknownTagException("Template command not found: " . $type);
			}

			$this->debug( "remaining pattern: [%s]", substr($tmpl, $pos) );
		}

		$depth -= 1;
		return $html;
	}//end parse

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

class UnknownTagException extends RuntimeException { }
class UnsupportedDataTypeException extends RuntimeException { }
