<?php

//
// In the parent directory:
//
//     $ phpunit t/jqTmpl.php
//
// or:
//
//     $ make test
//

require dirname(__DIR__) . '/jqTmpl.class.php';

class jqTmplTest extends PHPUnit_Framework_TestCase {
	function testPreparse() {
		$t = new jqTmpl;

		$this->assertEquals(
			'{{= test}}',
			$t->preparse('${test}')
		);

		$this->assertEquals(
			'{{= test}}',
			$t->preparse('${ test }')
		);

		$this->assertEquals(
			'test',
			$t->preparse('test')
		);
	}

	function testTemplateFromString() {
		$t = new jqTmpl;

		$this->assertEquals(
			'foo',
			$t->tmpl('foo'),
			'no substitutions'
		);

		$this->assertEquals(
			'foofoofoo',
			$t->tmpl('foo{{= blah}}foo', array('blah' => 'foo')),
			'tag nested in text'
		);

		$this->assertEquals(
			'bar',
			$t->tmpl('${foo}', array('foo' => 'bar')),
			'simple substitution'
		);

		$this->assertEquals(
			'bar, adam',
			$t->tmpl('${foo}, {{= name}}', array('foo' => 'bar', 'name' => 'adam')),
			'two tags'
		);

		$this->assertEquals(
			'bar, adam',
			$t->tmpl('${ foo }, {{= name }}', array('foo' => 'bar', 'name' => 'adam')),
			'tags with extra whitespace'
		);
	}

	function testPhpQuery() {
		$t = new jqTmpl;

		$html = '<div id="foo">is foo</div><div id="bar">is bar</div>';
		
		$this->assertInstanceOf(
			'phpQueryObject',
			$t->load_document($html)
		);

		$this->assertEquals(
			1,
			count($t->pq('#foo'))
		);

		$this->assertEquals(
			'is foo',
			$t->pq('#foo')->text()
		);
	}

	/**
	 * @depends testPhpQuery
	 */
	function testTemplateFromPhpQuery() {
		$t = new jqTmpl;

		$html = '<script type="text/x-jquery-tmpl" id="foo">${bar}</script>' .
			'<script type="text/x-jquery-tmpl" id="shazbot">{{= greeting}}</script>';

		$t->load_document($html);

		$this->assertEquals(
			'nanu',
			$t->tmpl( $t->pq('#shazbot'), array('greeting' => 'nanu') ),
			'query by id'
		);

		$this->assertEquals(
			'adam',
			$t->tmpl( $t->pq('script'), array('bar' => 'adam') ),
			'query uses first found node'
		);

		$html = '<i>${foo}</i>';
		$t->load_document($html);
		$this->assertEquals(
			'<i>bar</i>',
			$t->tmpl( $t->pq(), array('foo' => 'bar') )
		);
	}

	function testIf() {
		$t = new jqTmpl;

		$this->assertEquals(
			'outside in if outside',
			$t->tmpl( 'outside {{if foo}}in if{{/if}} outside', array('foo' => 'yes') ),
			'true if condition'
		);

		$this->assertEquals(
			'outside  outside',
			$t->tmpl( 'outside {{if foo}}yes{{/if}} outside', array('foo' => false) ),
			'false if condition'
		);

		$this->assertEquals(
			'outside in else outside',
			$t->tmpl( 'outside {{if foo}}in if{{else}}in else{{/if}} outside', array('foo' => false) ),
			'else condition'
		);

		$this->assertEquals(
			'outelse1out',
			$t->tmpl( 'out{{if foo}}if1{{if foo}}if2{{else}}else2{{/if}}{{else}}else1{{/if}}out', array('foo' => false) ),
			'nested {{if}}, outer {{if}} false'
		);

		$this->assertEquals(
			'out-else1-else1if-out',
			$t->tmpl( 'out-{{if foo}}if1-{{if foo}}if2-{{else}}if2else-{{/if}}{{else}}else1-{{if bar}}else1if-{{else}}else1else-{{/if}}{{/if}}out', array('foo' => false, 'bar' => true) ),
			'nested {{if}}, outer {{if}} false, nesting in {{else}}'
		);
	}

	function testExpression() {
		$t = new jqTmpl;

		$this->assertEquals(
			'<i>War &amp; Peace</i>',
			$t->tmpl( '<i>${title}</i>', array('title' => 'War & Peace') ),
			'escaped expression'
		);

		$this->assertEquals(
			'<i>War & Peace</i>',
			$t->tmpl( '<i>{{html title}}</i>', array('title' => 'War & Peace') ),
			'unescaped expression'
		);
	}

	function testEach() {
		$t = new jqTmpl;

		$data = array(
			'books' => array('one', 'two', 'three')
		);
		$this->assertEquals(
			'out-one-two-three-out',
			$t->tmpl( 'out-{{each books}}${value}-{{/each}}out', $data ),
			'simple {{each}} values'
		);

		$data = array(
			'books' => array('one', 'two', 'three')
		);
		$this->assertEquals(
			'out-0-1-2-out',
			$t->tmpl( 'out-{{each books}}${index}-{{/each}}out', $data ),
			'simple {{each}} indexes'
		);

		$data = array(
			'strs' => array(
				'some' => 'thing',
				'other' => 'stuff'
			)
		);
		$this->assertEquals(
			'out-some-thing-other-stuff-out',
			$t->tmpl( 'out-{{each strs}}${index}-${value}-{{/each}}out', $data ),
			'nested each'
		);

		$data = array(
			'names' => array(
				'old' => array('helen', 'francis', 'margaret'),
				'new' => array('fallon', 'ceylon', 'morley'),
				'crazy' => array('asdf', 'fffs', 'bbq')
			)
		);
		$this->assertEquals(
			'out-helen-francis-margaret-fallon-ceylon-morley-asdf-fffs-bbq-out',
			$t->tmpl( 'out-{{each names}}{{each value}}${value}-{{/each}}{{/each}}out', $data ),
			'nested each'
		);

		$data = array(
			'books' => array('one', 'two', 'three')
		);
		$this->assertEquals(
			'out-0-one-1-two-2-three-out',
			$t->tmpl( 'out-{{each(i,b) books}}${i}-${b}-{{/each}}out', $data ),
			'custom {{each}} params'
		);
		$this->assertEquals(
			'out-0-one-1-two-2-three-out',
			$t->tmpl( 'out-{{each( i, b ) books}}${i}-${b}-{{/each}}out', $data ),
			'custom {{each}} params (whitespace)'
		);
	}

	function testComment() {
		$t = new jqTmpl;

		$this->assertEquals(
			'',
			$t->tmpl( '{{! test }}' ),
			'comment only'
		);

		$this->assertEquals(
			'out-yes-out',
			$t->tmpl( 'out-{{if foo}}{{! test }}{{= foo}}-{{/if}}out', array('foo' => 'yes') ),
			'more complex comment'
		);
	}

	function testSubTemplate() {
		$t = new jqTmpl;

		$t->load_document('<script id="foo">foo</script><script id="bar">{{tmpl "#foo"}}</script>');
		$this->assertEquals(
			'foo',
			$t->tmpl( $t->pq('#bar') ),
			'simple subtemplate'
		);

		$t->load_document('<script id="foo">foo</script><script id="bar">bar-{{tmpl "#foo"}}-bar</script>');
		$this->assertEquals(
			'bar-foo-bar',
			$t->tmpl( $t->pq('#bar') ),
			'content before and after subtemplate'
		);

		$t->load_document('<script id="inner">inner-${bar}-inner</script><script id="outer">outer-{{tmpl "#inner"}}-outer</script>');
		$this->assertEquals(
			'outer-inner-foo-inner-outer',
			$t->tmpl( $t->pq('#outer'), array('bar' => 'foo') ),
			'expression in subtemplate'
		);

		$t->load_document('<script id="inner">inner</script><script id="outer">outer-{{if foo}}{{tmpl "#inner"}}{{/if}}-outer</script>');
		$this->assertEquals(
			'outer-inner-outer',
			$t->tmpl( $t->pq('#outer'), array('foo' => true) ),
			'{{tmpl}} in {{if}}, true condition'
		);
		$this->assertEquals(
			'outer--outer',
			$t->tmpl( $t->pq('#outer'), array('foo' => false) ),
			'{{tmpl}} in {{if}}, false condition'
		);

		$t->load_document('<script id="inner">inner</script><script id="outer">outer-{{if foo}}foo{{else}}{{tmpl "#inner"}}{{/if}}-outer</script>');
		$this->assertEquals(
			'outer-foo-outer',
			$t->tmpl( $t->pq('#outer'), array('foo' => true) ),
			'{{tmpl}} in {{else}}, true {{if}} condition'
		);
		$this->assertEquals(
			'outer-inner-outer',
			$t->tmpl( $t->pq('#outer'), array('foo' => false) ),
			'{{tmpl}} in {{else}}, true {{if}} condition'
		);
	}

	/**
	 * @expectedException UnknownTagException
	 */
	function testUnknownType() {
		$t = new jqTmpl;
		$t->tmpl( '{{foo}}' );
	}
}
