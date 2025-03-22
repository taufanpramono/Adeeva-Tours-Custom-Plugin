<?php 

//==================================
// Version : 1.1
// Build : Php Enggine & WP Hook
// Author : Taufan Pramono
// usage : Management Footer
//==================================




function copyRight() {
	$tahunKini = '2024';
	$tahun     = date('Y');
	$namaPt    = get_field('nama_pt','option');
	
	if($tahun && $namaPt) {
		if ($tahun == $tahunKini) {
			return '<div class="copy-right">© '.$tahun.'. '.$namaPt.'</div>';
		} else {
			return '<div class="copy-right">© '.$tahunKini.' - '.$tahun.'. '.$namaPt.'</div>';
		}
		
	}
}
add_shortcode('copy_sc','copyRight');


function contactUhui() {
	$contact = get_field('kontak_hp','option');
	if($contact) {
		return 'https://wa.me/62'.$contact;
	} else {
		return '#';
	}
}
add_shortcode('con_sc','contactUhui');





 ?>