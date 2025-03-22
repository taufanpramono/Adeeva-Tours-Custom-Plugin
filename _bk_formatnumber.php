<?php 

//==================================
// Version : 1.1
// Build : Php Enggine
// Author : Taufan Pramono
// usage : Format Money Canger
//==================================




function moneyChanger($data) {
	$format = floatval($data);
	$value  = number_format($format,0,',','.');
	return 'IDR. '.$value;
}


 ?>