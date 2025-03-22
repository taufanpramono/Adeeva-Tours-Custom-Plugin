<?php 

//==================================
// Version : 1.1
// Build : Php Enggine & WP Hook
// Author : Taufan Pramono
// usage : Auto Sale & Card
//==================================





//harga 
function cardPriceControl() {
    $control = get_field('packet_or_day');
    if($control == 'Packet') {
        return '';
    } elseif($control == 'Day Per Day') {
        return do_shortcode('[price_sc]');
    } else {
        return '';
    }
}
add_shortcode('harga_card','cardPriceControl');

function salePriceControl() {
    $control = get_field('packet_or_day');
    if($control == 'Packet') {
        return '';
    } elseif($control == 'Day Per Day') {
        return do_shortcode('[sale_sub]');
    } else {
        return '';
    }
}
add_shortcode('sale_sc','salePriceControl');



function manageHargaCard() {
    $dayperday = get_field('masukan_tanggal'); 
    if ($dayperday && is_array($dayperday)) {
        $allTicketPrices = []; 
        
        foreach ($dayperday as $day) {
            $tikets = $day['informasi_tiket']; 
            
            // Cek apakah 'informasi_tiket' adalah array
            if (is_array($tikets)) {
                foreach ($tikets as $ticket) {
                    if (!empty($ticket['pilih_tiket']) && is_object($ticket['pilih_tiket'])) {
                        $hargaTiketFields = get_field('harga_tiket_pty', $ticket['pilih_tiket']->ID);
                        
                        if ($hargaTiketFields && is_array($hargaTiketFields)) {
                            foreach ($hargaTiketFields as $sub) {
                                $harga_asli = $sub['harga_tiket_rep'];

                                // Cek apakah ada potongan harga
                                if (!empty($sub['potongan_harga'])) {
                                    $potonganHarga = $sub['potongan_harga'] . '%';
                                    if (preg_match('/^\d+%$/', $potonganHarga)) {
                                        $potongan_nilai = str_replace('%', '', $potonganHarga) / 100;
                                        $harga_potongan = $harga_asli - ($harga_asli * $potongan_nilai);
                                        $allTicketPrices[] = $harga_potongan; // Tambahkan harga setelah potongan
                                    }
                                }

                                // Tambahkan harga asli juga untuk dibandingkan
                                $allTicketPrices[] = $harga_asli;
                            }
                        }
                    }
                }
            }
        }

        if (!empty($allTicketPrices)) {
            $lowestPrice = min($allTicketPrices); 
            return '<span class="harga-card">From ' . moneyChanger($lowestPrice) . '</span>'; 
        } else {
            return '';
        }
    } else {
        return '';
    }
}
add_shortcode('price_sc', 'manageHargaCard');





function saleStatus() {
    $dayperday = get_field('masukan_tanggal'); 
    $sale_found = false; // Variabel untuk melacak apakah ada "Sale" yang ditemukan
    
    if ($dayperday && is_array($dayperday)) {
        foreach ($dayperday as $day) {
            $tikets = $day['informasi_tiket']; 
            if ($tikets && is_array($tikets)) {
                foreach ($tikets as $ticket) {
                    if (!empty($ticket['pilih_tiket']) && is_object($ticket['pilih_tiket'])) {
                    $hargaTiketFields = get_field('harga_tiket_pty', $ticket['pilih_tiket']->ID);
                    if ($hargaTiketFields && is_array($hargaTiketFields)) {
                        foreach ($hargaTiketFields as $sub) {
                            if (!empty($sub['potongan_harga'])) {
                                $sale_found = true; // Jika ditemukan potongan harga, tandai sebagai sale
                                break 3; // Keluar dari semua loop setelah menemukan potongan
                            }
                        }
                    }
                  }
                }
            }
        }
    }
    
    // Jika ada sale ditemukan, tampilkan label "Sale!"
    if ($sale_found) {
        return '<span class="sale-label">Sale !</span>';
    } else {
        return ''; // Jika tidak ada sale, kembalikan string kosong
    }
}
add_shortcode('sale_sub','saleStatus');



//best seller 

function bestSeller() {
    $best = get_field('produk_ini_best_seller');
    if($best) {
        if($best=='Best Seller') {
            return '<span class="best-seller">&Star; Best Seller</span>';
        } elseif($best=='Tidak') {
            return '';
        } else {
            return '';
        }
    } else {
        return '';
    }
}
add_shortcode('best_seller','bestSeller');

//limittitle
function limitTitle() {
    $judul       = get_the_title();
    $limit       = 6;
    $judul_array = explode(' ', $judul);
    $jumcount    = count($judul_array);
    
    if($jumcount > $limit) {
        $judul_terbatas = array_slice($judul_array, 0, $limit);
        $judul_terbatas = implode(' ', $judul_terbatas);
        return $judul_terbatas.'...';
    } else {
        $judul_terbatas = array_slice($judul_array, 0, $limit);
        $judul_terbatas = implode(' ', $judul_terbatas);
        return $judul_terbatas;
    }
}
add_shortcode('title_sc','limitTitle');

//REST API DARI SINI
// Pastikan function tidak dideklarasikan dua kali
if (!function_exists('register_discounted_tours_api')) {

    // Register REST API route
    function register_discounted_tours_api() {
        register_rest_route( 'tours/v1', '/discounted/', array(
            'methods' => 'GET',
            'callback' => 'get_discounted_tours',
        ));
    }
    add_action( 'rest_api_init', 'register_discounted_tours_api' );

    // Function to get discounted tours for API response
    function get_discounted_tours() {
        // Dapatkan array tour dengan tiket diskon
        $tour_ids_with_discounts = filter_tours_with_discounted_tickets();

        // Kembalikan array sebagai response JSON
        return rest_ensure_response( $tour_ids_with_discounts );
    }
}

// Function to get discounted tours
function filter_tours_with_discounted_tickets() {
    $tour_ids_with_discounts = []; 

    // Ambil semua 'tours'
    $tours = new WP_Query( array(
        'post_type' => 'tours',
        'posts_per_page' => -1, // Ambil semua tours
    ));

    // Loop untuk memeriksa setiap post
    if ( $tours->have_posts() ) {
        while ( $tours->have_posts() ) {
            $tours->the_post();
            $packet_or_day = get_field('packet_or_day');
            if ($packet_or_day === 'Day Per Day') {
                $masukan_tanggal = get_field('masukan_tanggal'); 
                if (!empty($masukan_tanggal) && is_array($masukan_tanggal)) {
                    foreach ($masukan_tanggal as $day) {
                        $informasi_tiket = $day['informasi_tiket']; 
                        if (!empty($informasi_tiket) && is_array($informasi_tiket)) {
                            foreach ($informasi_tiket as $tiket) {
                                $pilih_tiket = $tiket['pilih_tiket'];
                                if ($pilih_tiket) {
                                    $hargaTiketFields = get_field('harga_tiket_pty', $pilih_tiket->ID);
                                    if ($hargaTiketFields && is_array($hargaTiketFields)) {
                                        foreach ($hargaTiketFields as $sub) {
                                            if (!empty($sub['potongan_harga'])) {
                                                $tour_ids_with_discounts[] = get_the_ID();
                                                break 3;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        wp_reset_postdata();
    }

    return $tour_ids_with_discounts;
}

// Shortcode to return the API URL
function get_discounted_tours_api_url() {
    // Return the URL of the REST API endpoint
    return site_url('/wp-json/tours/v1/discounted/');
}
add_shortcode('discounted_tours_api_url', 'get_discounted_tours_api_url');


function custom_query_callback($query) {
    // Ambil data dari API
    $response = wp_remote_get('https://adeevatours.com/wp-json/tours/v1/discounted/');
    
    // Pastikan response valid
    if (is_wp_error($response)) {
        return; // Jika ada error, hentikan proses
    }

    // Decode hasil JSON menjadi array
    $tour_ids_with_discounts = json_decode(wp_remote_retrieve_body($response), true);

    // Cek apakah hasilnya berupa array dan tidak kosong
    if (!empty($tour_ids_with_discounts) && is_array($tour_ids_with_discounts)) {
        // Set 'post__in' dengan array hasil dari API
        $query->set('post__in', $tour_ids_with_discounts);
    } else {
        // Jika API kosong, set 'post__in' ke array kosong
        $query->set('post__in', [0]); // Menetapkan ID yang tidak mungkin ada untuk menghindari menampilkan semua post
    }
}
add_action('elementor/query/relational-posts', 'custom_query_callback');



function counterSale() {
     $tour_ids_with_discounts = []; 

    // Ambil semua 'tours'
    $tours = new WP_Query( array(
        'post_type' => 'tours',
        'posts_per_page' => -1, // Ambil semua tours
    ));

    // Loop untuk memeriksa setiap post
    if ( $tours->have_posts() ) {
        while ( $tours->have_posts() ) {
            $tours->the_post();
            $packet_or_day = get_field('packet_or_day');
            if ($packet_or_day === 'Day Per Day') {
                $masukan_tanggal = get_field('masukan_tanggal'); 
                if (!empty($masukan_tanggal) && is_array($masukan_tanggal)) {
                    foreach ($masukan_tanggal as $day) {
                        $informasi_tiket = $day['informasi_tiket']; 
                        if (!empty($informasi_tiket) && is_array($informasi_tiket)) {
                            foreach ($informasi_tiket as $tiket) {
                                $pilih_tiket = $tiket['pilih_tiket'];
                                if ($pilih_tiket) {
                                    $hargaTiketFields = get_field('harga_tiket_pty', $pilih_tiket->ID);
                                    if ($hargaTiketFields && is_array($hargaTiketFields)) {
                                        foreach ($hargaTiketFields as $sub) {
                                            if (!empty($sub['potongan_harga'])) {
                                                $tour_ids_with_discounts[] = get_the_ID();
                                                break 3;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        wp_reset_postdata();
    }

    return '( Found '.count($tour_ids_with_discounts).' )';
    
}
add_shortcode('counter_sale','counterSale');







 ?>