<?php

include '../jqTmpl.class.php';
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
