/**
 * Frontend JavaScript
 *
 * @package Multiside_Aroi_Integration
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Validate pickup time on checkout
        $('form.checkout').on('checkout_place_order', function() {
            var pickupTime = $('#hentes_kl').val();

            if (!pickupTime || pickupTime === '') {
                alert('Vennligst velg en hentetid for bestillingen.');
                return false;
            }

            return true;
        });

        // Add visual feedback for department cards
        $('.department-card').on('mouseenter', function() {
            $(this).addClass('hover');
        }).on('mouseleave', function() {
            $(this).removeClass('hover');
        });
    });

})(jQuery);
