jquery-tmpl-php
===============

An attempt at implementing jquery-tmpl in PHP.

* [jquery-tmpl](http://github.com/jquery/jquery-tmpl)

Supported syntax:

* ${foo}, {{= foo}}
* {{html bar}}
* {{if foo}}
* {{else}}
* {{! comment}}
* {{tmpl "selector"}}, ie. {{tmpl "#baz"}}
* {{each foo}}, {{each(k,v) foo}}

Still unsupported:

* {{tmpl(foo) "#bar"}} -- data parameter to nested template

Possibly volatile:

* This project does not currently treat objects and arrays the same way jquery-tmpl treats those data types. For example, jquery-tmpl will render the template multiple times if you pass in an array of objects. jquery-tmpl-php should be modified to treat an input object differently than an input array, and remove the render_once option to jqTmpl::tmpl().

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
		array("Name" => "The Red Violin", "Director" => "Francois Girard"),
		array("Name" => "Eyes Wide Shut", "Director" => "Stanley Kubrick"),
		array("Name" => "The Inheritance", "Director" => "Mauro Bolognini")
	);

	echo $t->tmpl( $t->pq('#movieTemplate'), $data, array('render_once' => false) );

Output (whitespace adjusted):

	<tr class="title"><td>The Red Violin
	<tr class="detail"><td>Director: Francois Girard

	<tr class="title"><td>Eyes Wide Shut
	<tr class="detail"><td>Director: Stanley Kubrick

	<tr class="title"><td>The Inheritance
	<tr class="detail"><td>Director: Mauro Bolognini
