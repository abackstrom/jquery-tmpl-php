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
			'bar',
			$t->tmpl('${foo}', array('foo' => 'bar'))
		);

		$this->assertEquals(
			'bar, adam',
			$t->tmpl('${foo}, {{= name}}', array('foo' => 'bar', 'name' => 'adam'))
		);

		$this->assertEquals(
			'bar, adam',
			$t->tmpl('${ foo }, {{= name }}', array('foo' => 'bar', 'name' => 'adam'))
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
}
