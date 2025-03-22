<?php 

//==================================
// Version : 1.1
// Build : Php Enggine & WP Hook
// Author : Taufan Pramono
// usage : Ticket AJAX On Single Tour
//==================================




function formatTanggal($tanggal) {
    $timestamp = strtotime(str_replace('/', '-', $tanggal)); 
    return date('D, d M', $timestamp);
}


function manageDisplaySinggle() {
    $display = get_field('packet_or_day');
    if($display == 'Packet') {
        $tourlainnya = get_field('pilih_tour_lainnya');
        if ($tourlainnya) {
            echo '<h3> Tour Package </h3>';
            return do_shortcode('[elementor-template id="4639"]');
        } else {
            return '';
        }
        
    } elseif($display == 'Day Per Day') {
        $dayperday = get_field('masukan_tanggal');
        if($dayperday) {
            echo '<h3> Choose trip date and ticket </h3>';
            return do_shortcode('[day_sc]');
        } else {
            return '';
        }
    } else {
        return '';
    }
}
add_shortcode('display_sc','manageDisplaySinggle');

function dayPerDay() {
    $dayperday = get_field('masukan_tanggal');
    
    if ($dayperday && is_array($dayperday)) {
        // Urutkan berdasarkan tanggal terbaru
        usort($dayperday, function($a, $b) {
            return strtotime($b['tanggal_tour']) - strtotime($a['tanggal_tour']);
        });

        echo '<div class="tabs" id="tabs-anchor">';
        echo '<ul class="date-list">';

        foreach ($dayperday as $index => $day) {
//          echo '<pre>';
//          print_r($day);
//          echo '</pre>';
            echo '<li>';
            // Tambahkan kelas "active" pada tombol terbaru (pertama setelah pengurutan)
            $active_class = ($index === 0) ? 'active' : '';
            echo '<button class="date-button ' . esc_attr($active_class) . '" data-tanggal="' . esc_attr($day['tanggal_tour']) . '" data-postid="' . get_the_ID() . '">';
            echo esc_html(formatTanggal($day['tanggal_tour']));
            
           $tikets = $day['informasi_tiket'];
           $allticket = [];
           if(!empty($tikets)) {
           foreach ($tikets as $ticket) {
           if (!empty($ticket['pilih_tiket']) && is_object($ticket['pilih_tiket'])) {
           $harga_tiket_pty = get_field('harga_tiket_pty', $ticket['pilih_tiket']->ID);
           if (!empty($harga_tiket_pty)) {
              foreach ($harga_tiket_pty as $sub) {
                $harga_asli = $sub['harga_tiket_rep'];
                // Cek apakah ada potongan harga
                if (!empty($sub['potongan_harga'])) {
                    $potonganHarga = $sub['potongan_harga'] . '%';
                        if (preg_match('/^\d+%$/', $potonganHarga)) {
                            $potongan_nilai = str_replace('%', '', $potonganHarga) / 100;
                            $harga_potongan = $harga_asli - ($harga_asli * $potongan_nilai);
                            $allticket[] = $harga_potongan; // Tambahkan harga setelah potongan
                            }
                        }

                        // Tambahkan harga asli juga untuk dibandingkan
                        $allticket[] = $harga_asli;
                  }
                }
               }
              }
            }

            // Cari nilai terkecil dari semua harga (baik yang potongan maupun asli)
            if (!empty($allticket)) {
                $valuemin = min($allticket);
            } else {
                $valuemin = 'IDR. 0';
            }

            // Tampilkan nilai harga terkecil
            echo '<span class="harga-from"><br>From ' . esc_html(moneyChanger($valuemin)) . '</span>';
            echo '</button>';
            echo '</li>';

        }
        
        echo '</ul>'; // Menutup tag <ul>
        echo '</div>'; // Menutup div.tabs
        echo '<div class="data-tour"></div>';
        echo '<div class="data-tiket"></div>';
        
        // Skrip JavaScript di bawah
        ?>
 <script>
jQuery(document).ready(function($) {
    // Fungsi untuk memuat tiket berdasarkan tanggal dan post_id
    function loadTickets(tanggal, postId) {
        $('.data-tiket').html(''); // Kosongkan konten sebelum memuat tiket baru
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'load_tickets',
                tanggal: tanggal,
                post_id: postId
            },
            beforeSend: function() {
                $('.data-tour').html('<div class="loading-container"><img src="https://adeevatours.com/wp-content/uploads/2024/09/loading-gif.gif" class="loading-gif"></div>'); // Tampilkan pesan loading
            },
            success: function(response) {
                $('.data-tour').html(response); // Tampilkan hasil di dalam div dengan class 'data-tour'

                // Secara otomatis pilih baris pertama dan muat detail tiket
                var firstRow = $('.data-tour tr[data-ticket-id]').first();
                if (firstRow.length) {
                    firstRow.addClass('selected'); // Tandai baris pertama sebagai dipilih
                    firstRow.find('input[name="ticket_option"]').prop('checked', true); // Pilih radio button di baris pertama

                    var ticketId = firstRow.data('ticket-id');

                    // Muat detail tiket pertama setelah data tiket dimuat
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'get_ticket_details',
                            ticket_id: ticketId
                        },
                        beforeSend: function() {
                            $('.data-tiket').html('<div class="loading-container"><img src="https://adeevatours.com/wp-content/uploads/2024/09/loading-gif.gif" class="loading-gif"></div>');
                        },
                        success: function(detailResponse) {
                            $('.data-tiket').html(detailResponse); // Tampilkan detail tiket

                            // Inisialisasi slider setelah konten tiket dimuat
                            initializeSlider();

                            // Inisialisasi smooth scroll setelah konten dimuat
                            smoothScroll();
                        },
                        error: function() {
                            $('.data-tiket').html('<p>Error loading ticket details.</p>');
                        }
                    });
                }
            },
            error: function() {
                $('.data-tour').html('<p>Error loading tickets.</p>');
            }
        });
    }

    // Event handler untuk klik pada tombol tab tanggal
    $('.date-button').on('click', function() {
        $('.date-button').removeClass('active'); // Hapus kelas active dari semua tombol
        $(this).addClass('active'); // Tambahkan kelas active pada tombol yang diklik

        var tanggal = $(this).data('tanggal');
        var postId = $(this).data('postid'); // Ambil post ID dari data attribute
        
        loadTickets(tanggal, postId); // Panggil fungsi untuk memuat tiket
    });

    // Pilih tanggal terbaru secara otomatis saat halaman dimuat
    var dateButtons = $('.date-button');
    if (dateButtons.length) {
        // Mengambil tanggal terbaru
        var latestDateButton = dateButtons.first(); // Tombol terbaru
        latestDateButton.addClass('active'); // Tandai tombol terbaru sebagai active
        
        var latestDate = latestDateButton.data('tanggal');
        var latestPostId = latestDateButton.data('postid');

        // Muat tiket berdasarkan tanggal terbaru secara otomatis
        loadTickets(latestDate, latestPostId);
    }

    // Event handler untuk klik pada baris <tr>
    $(document).on('click', 'tr[data-ticket-id]', function() {
        var ticketId = $(this).data('ticket-id');
        
        // Pilih radio button yang berada di dalam baris <tr> yang diklik
        $(this).find('input[name="ticket_option"]').prop('checked', true);

        // Tambahkan efek visual untuk menandai baris yang dipilih
        $('tr').removeClass('selected');
        $(this).addClass('selected');

        // Jalankan AJAX untuk mendapatkan detail tiket saat baris diklik
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_ticket_details',
                ticket_id: ticketId
            },
            beforeSend: function() {
                $('.data-tiket').html('<div class="loading-container"><img src="https://adeevatours.com/wp-content/uploads/2024/09/loading-gif.gif" class="loading-gif"></div>');
            },
            success: function(response) {
                $('.data-tiket').html(response);             

                // Scroll hanya jika elemen ticket-content ada
                if ($("#ticket-content").length) {
                    $('html, body').animate({
                        scrollTop: $("#ticket-content").offset().top
                    }, 800);
                } else {
                    console.warn('Element #ticket-content not found for scrolling.');
                }
                
                // Panggil fungsi slider setelah konten dimuat
                initializeSlider();
                
                // Panggil fungsi smooth scroll setelah konten dimuat
                smoothScroll();
            },
            error: function() {
                $('.data-tiket').html('<p>Error loading ticket details.</p>');
            }
        });
    });
    
    // Fungsi smooth scroll
    function smoothScroll() {
        jQuery($ => {
            const speed = 1000; // Kecepatan scroll dalam milidetik

            $('a[href*="#"]')
                .filter((i, a) => a.getAttribute('href').startsWith('#') || a.href.startsWith(`${location.href}#`))
                .unbind('click.smoothScroll')
                .bind('click.smoothScroll', event => {
                    const targetId = event.currentTarget.getAttribute('href').split('#')[1];
                    const targetElement = document.getElementById(targetId);

                    if (targetElement) {
                        event.preventDefault();
                        $('html, body').animate({ scrollTop: $(targetElement).offset().top }, speed);
                    }
                });
        });
    }
    
    // Fungsi untuk menginisialisasi slider
    function initializeSlider() {
        let slideIndex = 1;
        showSlides(slideIndex);

        function showSlides(n) {
            let i;
            let slides = document.getElementsByClassName("mySlides");
            let dots = document.getElementsByClassName("dot");
            if (n > slides.length) { slideIndex = 1 }
            if (n < 1) { slideIndex = slides.length }
            for (i = 0; i < slides.length; i++) {
                slides[i].style.display = "none";
            }
            for (i = 0; i < dots.length; i++) {
                dots[i].className = dots[i].className.replace(" active", "");
            }
            slides[slideIndex - 1].style.display = "block";
            dots[slideIndex - 1].className += " active";
        }

        function plusSlides(n) {
            showSlides(slideIndex += n);
        }

        function currentSlide(n) {
            showSlides(slideIndex = n);
        }

        // Inisialisasi event listener untuk tombol prev/next
        document.querySelector('.prev').addEventListener('click', function () {
            plusSlides(-1);
        });

        document.querySelector('.next').addEventListener('click', function () {
            plusSlides(1);
        });

        // Set event listener untuk dots
        let dots = document.getElementsByClassName('dot');
        for (let i = 0; i < dots.length; i++) {
            dots[i].addEventListener('click', function () {
                currentSlide(i + 1);
            });
        }
    }
});

</script>



        <?php
    } else {
        echo '<p>No dates available.</p>';
    }
}
add_shortcode('day_sc', 'dayPerDay');



function load_tickets_ajax() {
    if (isset($_POST['tanggal']) && isset($_POST['post_id'])) {
        $tanggal_input = sanitize_text_field($_POST['tanggal']);
        $post_id = intval($_POST['post_id']); // Ambil post ID
        
        // Ambil field repeater dari ACF
        $dayperday = get_field('masukan_tanggal', $post_id); // Menggunakan post ID yang dikirim
        
        if ($dayperday && is_array($dayperday)) {
            // Cari data tiket berdasarkan tanggal yang dikirimkan
            $found = false;
            foreach ($dayperday as $day) {
                if ($day['tanggal_tour'] === $tanggal_input) {
                    $tickets = $day['informasi_tiket'];
                    if (!empty($tickets) && is_array($tickets)) {
                        echo '<table>';
                        echo '<thead>';
                        echo '<th></th>';
                        echo '<th>Ticket</th>';
                        echo '<th>Company</th>';
                        echo '<th>Price</th>';
                        echo '<th>Action</th>';
                        echo '</thead>';
                        
                        foreach ($tickets as $tiket) {
                            // Memastikan bahwa $tiket['pilih_tiket'] adalah objek yang valid
                            if (isset($tiket['pilih_tiket']) && is_object($tiket['pilih_tiket'])) {
                                $ticket_id = $tiket['pilih_tiket']->ID;
                                echo '<tr class="baris-selector" data-ticket-id="' . esc_attr($ticket_id) . '">';
                                echo '<td class="radio-but"><input type="radio" name="ticket_option" value="' . esc_attr($ticket_id) . '" class="ticket-radio" style="display: inline;"></td>';
                                echo '<td class="ticket-title">' . esc_html($tiket['pilih_tiket']->post_title); 
                                
                                if (!empty(get_field('harga_tiket_pty', $ticket_id))) {
                                    foreach (get_field('harga_tiket_pty', $ticket_id) as $harga) {
                                        if (!empty($harga['potongan_harga'])) {
                                            // Tambahkan label "Sale" jika ada potongan harga
                                            echo '<br><span class="sale-label" style="color: red;">Sale !</span>';
                                            break; // Keluar dari loop setelah menemukan potongan harga
                                        }
                                    }
                                }
                                echo '</td>';
                                
                                // Mengambil informasi perusahaan dengan pengecekan validitas
                                $company = get_field('company_tiket', $ticket_id);
                                echo '<td class="ticket-company">' . esc_html($company) . '</td>';
                                
                                echo '<td class="ticket-price">';
                                if (!empty(get_field('harga_tiket_pty', $ticket_id))) {
                                    foreach (get_field('harga_tiket_pty', $ticket_id) as $harga) {
                                        if (!empty($harga['potongan_harga'])) {
                                            $potonganHarga = $harga['potongan_harga'] . '%';
                                            if (preg_match('/^\d+%$/', $potonganHarga)) {
                                                $potongan_nilai = str_replace('%', '', $potonganHarga) / 100;
                                                $potongan_harga = $harga['harga_tiket_rep'] * $potongan_nilai;
                                                $harga_setelah_potongan = $harga['harga_tiket_rep'] - $potongan_harga;
                                                echo '<del>' . esc_html(moneyChanger($harga['harga_tiket_rep'])) . '</del> - ' . esc_html(moneyChanger($harga_setelah_potongan)) . '<br>';
                                            } else {
                                                echo esc_html(moneyChanger($harga['harga_tiket_rep'])) . '<br>';
                                            }
                                        } else {
                                            echo esc_html(moneyChanger($harga['harga_tiket_rep'])) . '<br>';
                                        }
                                    }
                                } else {
                                    echo 'IDR. 0';
                                }
                                echo '</td>';
                                
                                // Memeriksa apakah $tiket['pilih_tiket'] ada dan memiliki properti ID
                                $url_booking = get_field('url_booking', $ticket_id);
                                $url_booking = $url_booking ? $url_booking : '#';
                                
                                echo '<td class="ticket-action">
                                        <a href="#ticket-content" class="content-detail">Details</a>
                                        <a href="' . esc_url($url_booking) . '" class="booking-button" target="_blank"> Book</a>
                                      </td>';
                                
                                echo '<input type="radio" name="ticket_option" value="' . esc_attr($ticket_id) . '" style="display: none;">';
                                echo '</tr>';
                            } else {
                                // Jika $tiket['pilih_tiket'] tidak valid, tampilkan row kosong atau sesuai kebutuhan Anda
                                echo '<tr class="baris-selector">';
                                echo '<td class="radio-but"><input type="radio" name="ticket_option" value="" class="ticket-radio" style="display: inline;"></td>';
                                echo '<td class="ticket-title">No ticket available</td>';
                                echo '<td class="ticket-company">N/A</td>';
                                echo '<td class="ticket-price">IDR. 0</td>';
                                echo '<td class="ticket-action">
                                        <a href="#ticket-content" class="content-detail">Details</a>
                                        <a href="#" class="booking-button" target="_blank"> Book</a>
                                      </td>';
                                echo '</tr>';
                            }
                        }
                        echo '</table>';
                    } else {
                        echo '<p>No tickets available for this date.</p>';
                    }
                    $found = true;
                    break;
                }
            }
            
            // Jika tanggal tidak ditemukan
            if (!$found) {
                echo '<p>No tickets found for the selected date.</p>';
            }
        } else {
            echo '<p>No date data found in ACF.</p>';
        }
    } else {
        echo '<p>No date received.</p>';
    }

    wp_die(); // Menghentikan eksekusi AJAX
}

add_action('wp_ajax_load_tickets', 'load_tickets_ajax');
add_action('wp_ajax_nopriv_load_tickets', 'load_tickets_ajax');


function get_ticket_details_ajax() {
    if ( isset($_POST['ticket_id']) ) {
        $ticket_id = intval( $_POST['ticket_id']); // Ambil ID tiket
        
        // Query untuk mendapatkan post dari post_type 'ticket'
        $ticket = get_post($ticket_id);
        
        if ($ticket && $ticket->post_type === 'ticket') {
            // Ambil judul dan konten
            $title   = get_the_title($ticket);
            $content = apply_filters('the_content', $ticket->post_content);
            
            // Ambil gambar unggulan
            $image_url = get_the_post_thumbnail_url($ticket_id, 'full');

            echo '<div id="ticket-content" class="ticket-content"></div>';

            // KONTEN START DARI SINI
            echo '<div class="glob-container">';
            echo '<div class="section-content">';
            
            // GAMBAR GALERI
            
            $gallery_images = get_field('galeri_tiket', $ticket_id);
            if ($gallery_images) {
                echo '<div class="slideshow-container">';
                $jum = count($gallery_images);
                $num = 1;
                foreach ($gallery_images as $imgGalery) {
                    ?>
                    <div class="mySlides fade">
                        <div class="numbertext"><?= $num . ' / ' . $jum ?></div>
                        <img src="<?= esc_url($imgGalery['url']) ?>" style="width:100%">
                    </div>
                    <?php
                    $num++;
                }
                echo '<a class="prev" onclick="plusSlides(-1)">❮</a>';
                echo '<a class="next" onclick="plusSlides(1)">❯</a>';
                echo '</div>'; // slideshow-container

                echo '<div style="text-align:center" class="dot-slides-uhui">';
                $nums = 1;
                foreach ($gallery_images as $scroar) {
                    ?>
                    <span class="dot" onclick="currentSlide(<?= $nums ?>)"></span>
                    <?php
                    $nums++;
                }
                echo '</div>'; // dots
            } 

            // JUDUL KONTEN
            echo '<h3 class="judul-konten">' . esc_html($title) . '</h3>';

            // ISI KONTEN
            echo '<div>' . $content . '</div>';
            
            //TOMBOL BOOKING
            $bookingButton = get_field('url_booking', $ticket_id);
            if($bookingButton) {
                echo '<a href="'.$bookingButton.'" class="book-now" target="_blank"> Booking Tour Now </a>';
            } else {
                echo '<a href="#tabs-anchor" class="book-now"> Booking Tour Now </a>';
            }
            
            
            
            
            echo '</div>'; // section-content
            
            
            
            // KONTEN SIDE DISINI
            echo '<div class="side-content">';
            
            // About payment
            $optionPay = get_field('pilih_info_payment', $ticket_id);
            if($optionPay) {
                if ($optionPay == 'Global') {
                    $aboutPayment = get_field('about_payment','option');
                    if (!$aboutPayment) { // Jika tidak ada data global yang diambil
                        $aboutPayment = 'Informasi Pembayaran Tidak Ditemukan';
                    }
                } elseif ($optionPay == 'Spesifik') {
                    $aboutPayment = get_field('about_payment_spesific', $ticket_id);
                    if (!$aboutPayment) { // Jika tidak ada data spesifik yang diambil
                        $aboutPayment = get_field('about_payment','option');
                    }
                } else {
                    $aboutPayment = get_field('about_payment','option');
                }

            echo '<div class="sub-col">';
            echo '<h3 class="sub-title">About Payment</h3>';
            echo '<hr>';
            echo '<div class="about-payment">'.$aboutPayment.'</div>';
            echo '</div>'; // sub-col   
            }
            //end About Payment
            
            
            //The tour includes
            $tourIncludes = get_field('tiket_termaksud', $ticket_id);
            if ($tourIncludes && is_array($tourIncludes)) {
            echo '<div class="sub-col">';
            echo '<h3 class="sub-title">Tour Includes</h3>';
            echo '<hr>';
            echo '<ul class="urgent-list">';
                foreach ($tourIncludes as $inc) {
                    echo '<li>'.$inc['sub_termaksud'].'</li>';
                }
            
            echo '</ul>';
            echo '</div>'; // sub-col
            }
            //The tour includes end
            
            
            //Extra Charge
            $extraCharge = get_field('extra_charge', $ticket_id);
            if ($extraCharge && is_array($extraCharge)) {
            echo '<div class="sub-col">';
            echo '<h3 class="sub-title">Extra Charge</h3>';
            echo '<hr>';
            echo '<ul class="urgent-list">';
                foreach ($extraCharge as $ext) {
                    echo '<li>'.$ext['extra_charge_information'].'</li>';
                }
            
            echo '</ul>';
            echo '</div>'; // sub-col
            }
            //Extra Charge End
            
            //Remember To Pack
            $rememberPack = get_field('remember_pack', $ticket_id);
            if ($rememberPack && is_array($rememberPack)) {
            echo '<div class="sub-col">';
            echo '<h3 class="sub-title">Remember To Pack</h3>';
            echo '<hr>';
            echo '<ul class="urgent-list">';
                foreach ($rememberPack as $rem) {
                    echo '<li>'.$rem['informasi_bawaan_barang'].'</li>';
                }
            
            echo '</ul>';
            echo '</div>'; // sub-col
            }
            //Remember To Pack End
            
            //Please Note
            $pleaseNote = get_field('catatan_tambahan', $ticket_id);
            if ($pleaseNote && is_array($pleaseNote)) {
            echo '<div class="sub-col">';
            echo '<h3 class="sub-title">Please Note</h3>';
            echo '<hr>';
            echo '<ul class="urgent-list">';
                foreach ($pleaseNote as $ple) {
                    echo '<li>'.$ple['catatan_tambahan_sub'].'</li>';
                }
            
            echo '</ul>';
            echo '</div>'; // sub-col
            }
            //Please Note End

            
            
            
            
            echo '</div>'; // side-content
            echo '</div>'; // glob-container
        } else {
            echo '<p>No ticket found.</p>';
        }
    } else {
        echo '<p>No ticket ID received.</p>';
    }

    wp_die(); // Menghentikan eksekusi AJAX
}

add_action( 'wp_ajax_get_ticket_details', 'get_ticket_details_ajax' );
add_action( 'wp_ajax_nopriv_get_ticket_details', 'get_ticket_details_ajax' );



function enqueue_custom_ajax_scripts() {
    // Daftarkan custom-ajax.js terlebih dahulu
    wp_enqueue_script('custom-ajax-script', get_template_directory_uri() . '/js/custom-ajax.js', array('jquery'), null, true);

    // Daftarkan custom-ajax-v2.js
    wp_enqueue_script('custom-ajax-script-v2', get_template_directory_uri() . '/js/custom-ajax-v2.js', array('jquery'), null, true);

    // Mengirimkan ajaxurl ke kedua skrip, dengan satu panggilan
    wp_localize_script('custom-ajax-script', 'ajaxurl', admin_url('admin-ajax.php')); 
    wp_localize_script('custom-ajax-script-v2', 'ajaxurl', admin_url('admin-ajax.php'));
}
add_action('wp_enqueue_scripts', 'enqueue_custom_ajax_scripts');








 ?>