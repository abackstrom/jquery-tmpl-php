jquery-tmpl-php
===============

An attempt at implementing jquery-tmpl in PHP.

* [jquery-tmpl](http://github.com/jquery/jquery-tmpl)

Syntax
------

jquery-tmpl is implemented by converting template code into valid JavaScript. As such, a number of the features do not translate well to PHP, such as the variety of places you can insert a function into jquery-tmpl template tags. The jqTmpl class supports the following subset of jquery-tmpl syntax:

* `${foo}`, `{{= foo}}`
* `{{html bar}}`
* `{{if foo}}`
* `{{else}}`
* `{{! comment}}`
* `{{tmpl "selector"}}`, ie. `{{tmpl "#baz"}}`
* `{{each foo}}`, `{{each(k,v) foo}}`

Known unsupported:

* `{{tmpl(foo) "#bar"}}` -- data parameter to nested template
* `{{= func()}}` -- functions in template tags
* `{{= $item.func()}}` -- functions in options parameter to jqTmpl::tmpl()

Dependencies
------------

* [phpQuery](http://code.google.com/p/phpquery/) -- jQuery-like selectors in PHP

Optional:

* [PHPUnit](http://www.phpunit.de/) -- running tests

Examples
--------

	<?php

	include 'jqTmpl.class.php';
	$t = new jqTmpl;

	$html = <<<EOF
	<script id="movieTemplate" type="text/x-jquery-tmpl"> 
		{{tmpl "#titleTemplate"}}
		<tr class="detail"><td>Director: \${Director}</td></tr>
	</script>

	<script id="titleTemplate" type="text/x-jquery-tmpl"> 
		<tr class="title"><td>\${Name}</td></tr>
	</script>
	EOF;

	$t->load_document($html);

	$data = array(
		(object)array("Name" => "The Red Violin", "Director" => "Francois Girard"),
		(object)array("Name" => "Eyes Wide Shut", "Director" => "Stanley Kubrick"),
		(object)array("Name" => "The Inheritance", "Director" => "Mauro Bolognini")
	);

	echo $t->tmpl( $t->pq('#movieTemplate'), $data, array('render_once' => false) );

Output (whitespace adjusted):

	<tr class="title"><td>The Red Violin
	<tr class="detail"><td>Director: Francois Girard

	<tr class="title"><td>Eyes Wide Shut
	<tr class="detail"><td>Director: Stanley Kubrick

	<tr class="title"><td>The Inheritance
	<tr class="detail"><td>Director: Mauro Bolognini
