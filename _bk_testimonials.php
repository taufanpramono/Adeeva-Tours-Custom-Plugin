<?php 

//==================================
// Version : 1.1
// Build : Php Enggine & WP Hook
// Author : Taufan Pramono
// usage : Testimonials
//==================================



function showStar() {
	$star = get_field('ratting');
	if($star) {
		for($i=0; $i < $star; $i++) {
			echo '<span class="star">&#9733;</span>';
		}
		
		$sisaStar = 5 - $star;
		for($i=0; $i < $sisaStar; $i++) {
			echo '<span class="star">&#9734;</span>';
		}
	} else {
		return '';
	}
}
add_shortcode('star_sc','showStar');




 ?>