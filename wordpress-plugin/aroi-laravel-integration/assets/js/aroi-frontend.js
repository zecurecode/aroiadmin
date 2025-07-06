/**
 * Aroi Laravel Integration Frontend JavaScript
 */

(function($) {
    'use strict';

    // Initialize on document ready
    $(document).ready(function() {
        
        // Auto-refresh status every 5 minutes
        if ($('.aroi-location-status').length > 0) {
            setInterval(refreshLocationStatus, 300000); // 5 minutes
        }
        
        // Update pickup time options based on current time
        if ($('#hentes_kl').length > 0) {
            updatePickupTimes();
            setInterval(updatePickupTimes, 60000); // Update every minute
        }
        
        // Handle dynamic status updates
        $('.aroi-check-status').on('click', function(e) {
            e.preventDefault();
            checkLocationStatus($(this).data('site-id'));
        });
        
    });
    
    /**
     * Refresh location status via AJAX
     */
    function refreshLocationStatus() {
        var siteId = aroi_ajax.site_id;
        
        $.ajax({
            url: aroi_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'aroi_check_open_status',
                nonce: aroi_ajax.nonce,
                site_id: siteId
            },
            success: function(response) {
                if (response.success) {
                    updateStatusDisplay(response.data);
                }
            }
        });
    }
    
    /**
     * Check specific location status
     */
    function checkLocationStatus(siteId) {
        $.ajax({
            url: aroi_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'aroi_check_open_status',
                nonce: aroi_ajax.nonce,
                site_id: siteId
            },
            beforeSend: function() {
                showLoader();
            },
            success: function(response) {
                hideLoader();
                if (response.success) {
                    updateStatusDisplay(response.data);
                } else {
                    showError(response.data.message || 'Error checking status');
                }
            },
            error: function() {
                hideLoader();
                showError('Connection error');
            }
        });
    }
    
    /**
     * Update status display
     */
    function updateStatusDisplay(data) {
        // Update simple status
        $('.aroi-status').each(function() {
            var $status = $(this);
            $status.removeClass('open closed unknown');
            $status.addClass(data.is_open ? 'open' : 'closed');
            $status.text(data.message);
        });
        
        // Update detailed status
        $('.aroi-location-status').each(function() {
            var $container = $(this);
            
            $container.removeClass('is-open is-closed');
            $container.addClass(data.is_open ? 'is-open' : 'is-closed');
            
            $container.find('.status-text').text(data.message);
            
            if (data.open_time && data.close_time) {
                $container.find('.hours-info').html(
                    '<i class="far fa-clock"></i> ' + 
                    data.open_time + ' - ' + data.close_time
                );
            }
        });
        
        // Update opening hours display
        $('.aroi-opening-hours .status').each(function() {
            var $status = $(this);
            $status.removeClass('open closed');
            $status.addClass(data.is_open ? 'open' : 'closed');
            
            if (data.is_open) {
                $status.html('<span style="color:green;">Åpen for henting i dag</span>');
            } else {
                $status.html('<span style="color:red;">Åpner klokken ' + data.open_time + ' i dag. Du kan fortsatt bestille for henting innen åpningstiden.</span>');
            }
        });
    }
    
    /**
     * Update pickup time options
     */
    function updatePickupTimes() {
        var $select = $('#hentes_kl');
        if (!$select.length) return;
        
        var currentTime = new Date();
        var currentHour = currentTime.getHours();
        var currentMinute = currentTime.getMinutes();
        
        // Remove options that are in the past
        $select.find('option').each(function() {
            var timeStr = $(this).val();
            if (timeStr) {
                var parts = timeStr.split(':');
                var optionHour = parseInt(parts[0]);
                var optionMinute = parseInt(parts[1]);
                
                if (optionHour < currentHour || (optionHour === currentHour && optionMinute <= currentMinute)) {
                    $(this).remove();
                }
            }
        });
        
        // If no options left, disable the select
        if ($select.find('option').length === 0) {
            $select.prop('disabled', true);
            $select.after('<p class="aroi-pickup-notice">Ingen tilgjengelige hentetider i dag</p>');
        }
    }
    
    /**
     * Show loader
     */
    function showLoader() {
        if (!$('.aroi-loader').length) {
            $('body').append('<div class="aroi-loader"><div class="spinner"></div></div>');
        }
        $('.aroi-loader').fadeIn();
    }
    
    /**
     * Hide loader
     */
    function hideLoader() {
        $('.aroi-loader').fadeOut();
    }
    
    /**
     * Show error message
     */
    function showError(message) {
        var $error = $('<div class="aroi-error">' + message + '</div>');
        $('body').append($error);
        
        setTimeout(function() {
            $error.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    /**
     * Format time
     */
    function formatTime(hours, minutes) {
        return ('0' + hours).slice(-2) + ':' + ('0' + minutes).slice(-2);
    }
    
    /**
     * Parse time string
     */
    function parseTime(timeStr) {
        var parts = timeStr.split(':');
        return {
            hours: parseInt(parts[0]),
            minutes: parseInt(parts[1])
        };
    }

})(jQuery);

// Add CSS for loader and error messages
(function() {
    var style = document.createElement('style');
    style.textContent = `
        .aroi-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            z-index: 9999;
        }
        .aroi-loader .spinner {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #8a3794;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }
        .aroi-error {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #dc3232;
            color: white;
            padding: 15px 20px;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 10000;
        }
        .aroi-pickup-notice {
            color: #dc3232;
            font-style: italic;
            margin-top: 10px;
        }
    `;
    document.head.appendChild(style);
})();