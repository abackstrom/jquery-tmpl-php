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
* `{{else}}`, `{{else param}}`
* `{{! comment}}`
* `{{tmpl "selector"}}`, ie. `{{tmpl "#baz"}}`
* `{{each foo}}`, `{{each(k,v) foo}}`

Known unsupported:

* `{{tmpl(foo) "#bar"}}` -- data parameter to nested template
* `{{= func()}}` -- functions in template tags
* `{{= $item.func()}}` -- functions in options parameter to jqTmpl::tmpl()

Dependencies
------------

* [html5lib](http://code.google.com/p/html5lib/) -- "A Python and PHP implementations of a HTML parser based on the WHATWG HTML5 specification for maximum compatibility with major desktop web browsers."

Optional:

* [PHPUnit](http://www.phpunit.de/) -- running tests: `make test` or `phpunit t/jqTmpl.php`

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

	echo $t->tmpl( '#movieTemplate', $data );

Output (whitespace adjusted):

	<tr class="title"><td>The Red Violin</td></tr>
	<tr class="detail"><td>Director: Francois Girard</td></tr>

	<tr class="title"><td>Eyes Wide Shut</td></tr>
	<tr class="detail"><td>Director: Stanley Kubrick</td></tr>

	<tr class="title"><td>The Inheritance</td></tr>
	<tr class="detail"><td>Director: Mauro Bolognini</td></tr>

See Also
--------

* xyu's [jquery-tmpl-php](http://github.com/xyu/jquery-tmpl-php)
* awhatley's [jquery-tmpl.net](http://github.com/awhatley/jquery-tmpl.net)
