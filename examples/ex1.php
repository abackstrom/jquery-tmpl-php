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
	array("Name" => "The Red Violin", "Director" => "Francois Girard"),
	array("Name" => "Eyes Wide Shut", "Director" => "Stanley Kubrick"),
	array("Name" => "The Inheritance", "Director" => "Mauro Bolognini")
);
echo $t->tmpl( $t->pq('#movieTemplate'), $data, array('render_once' => false) );
