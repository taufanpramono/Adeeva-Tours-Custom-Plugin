<?php 

//==================================
// Version : 1.1
// Build : Php Enggine & WP Hook
// Author : Taufan Pramono
// usage : backend Repeater
//==================================





function filter_pilih_tiket_post_object_query( $args, $field, $post_id ) {
    // Tidak ada pengecualian pada query, kita hanya memastikan bekerja di post type tours.
    if ( get_post_type($post_id) !== 'tours' ) {
        return $args;
    }

    // Mengambil semua data tiket tanpa pengecualian.
    return $args;
}
add_filter('acf/fields/post_object/query/name=pilih_tiket', 'filter_pilih_tiket_post_object_query', 10, 3);

function acf_repeater_disable_selected_tickets_script() {
    ?>
    <script type="text/javascript">
    (function($) {
        // Fungsi untuk menonaktifkan pilihan yang sudah dipilih di semua repeater
        function disableSelectedTickets() {
            var selectedTickets = [];

            // Loop setiap row di repeater 'informasi_tiket' dan kumpulkan tiket yang dipilih
            $('select[name^="acf[field_66ec193fdc114]"]').each(function() {
                var selectedValue = $(this).val();
                if (selectedValue) {
                    selectedTickets.push(selectedValue);
                }
            });

            console.log('Tiket yang sudah dipilih:', selectedTickets);

            // Loop setiap select dan disable option yang sudah dipilih
            $('select[name^="acf[field_66ec193fdc114]"]').each(function() {
                var $select = $(this);

                // Enable semua opsi terlebih dahulu
                $select.find('option').each(function() {
                    $(this).prop('disabled', false); // Aktifkan semua opsi
                });

                // Disable opsi yang sudah dipilih di row lain
                $.each(selectedTickets, function(index, value) {
                    $select.find('option[value="' + value + '"]').prop('disabled', true);
                });
            });
        }

        // Jalankan ketika row baru di repeater ditambahkan
        $(document).on('acf/add_row', function(e, $el) {
            disableSelectedTickets(); // Panggil fungsi untuk disable tiket yang sudah dipilih
        });

        // Jalankan ketika halaman pertama kali dimuat
        $(document).ready(function() {
            disableSelectedTickets(); // Panggil fungsi untuk disable tiket yang sudah dipilih
        });

    })(jQuery);
    </script>
    <?php
}
add_action('acf/input/admin_footer', 'acf_repeater_disable_selected_tickets_script');



 ?>