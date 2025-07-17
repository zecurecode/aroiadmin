/**
 * Aroi Catering JavaScript
 */
(function($) {
    'use strict';

    var AroiCatering = {
        selectedSiteId: null,
        cateringSettings: null,
        selectedProducts: [],
        totalAmount: 0,

        init: function() {
            this.bindEvents();
            this.initDatepicker();
        },

        bindEvents: function() {
            var self = this;

            // Location selection
            $('.select-location').on('click', function() {
                self.selectedSiteId = $(this).data('site-id');
                self.loadCateringSettings();
            });

            // Continue to products
            $('#continue-to-products').on('click', function() {
                if (self.validateDetailsForm()) {
                    self.loadProducts();
                }
            });

            // Back to details
            $('#back-to-details').on('click', function() {
                self.showStep('details');
            });

            // Submit order
            $('#submit-catering-order').on('click', function() {
                self.submitOrder();
            });

            // Product quantity changes
            $(document).on('change', '.product-quantity', function() {
                self.calculateTotal();
            });

            // Date change - check availability
            $('#delivery_date').on('change', function() {
                self.checkDateAvailability($(this).val());
            });
        },

        initDatepicker: function() {
            $('#delivery_date').datepicker({
                dateFormat: aroi_catering.date_format,
                minDate: aroi_catering.min_date,
                beforeShowDay: function(date) {
                    // Check if date is blocked
                    var dateString = $.datepicker.formatDate('yy-mm-dd', date);
                    if (AroiCatering.cateringSettings && 
                        AroiCatering.cateringSettings.blocked_dates &&
                        AroiCatering.cateringSettings.blocked_dates.includes(dateString)) {
                        return [false, 'blocked-date', aroi_catering.texts.date_unavailable];
                    }
                    return [true, ''];
                }
            });
        },

        loadCateringSettings: function() {
            var self = this;
            
            $.ajax({
                url: aroi_catering.ajax_url,
                type: 'GET',
                data: {
                    action: 'aroi_get_catering_settings',
                    nonce: aroi_catering.nonce,
                    site_id: self.selectedSiteId
                },
                beforeSend: function() {
                    self.showLoader();
                },
                success: function(response) {
                    if (response.success) {
                        self.cateringSettings = response.data.settings;
                        self.updateSettingsDisplay();
                        self.showStep('details');
                    } else {
                        self.showError(response.data.message || aroi_catering.texts.error);
                    }
                },
                error: function() {
                    self.showError(aroi_catering.texts.error);
                },
                complete: function() {
                    self.hideLoader();
                }
            });
        },

        updateSettingsDisplay: function() {
            $('#min-guests-text').text(aroi_catering.texts.min_guests + this.cateringSettings.min_guests);
            $('#number_of_guests').attr('min', this.cateringSettings.min_guests);
            $('#min-amount-text').text(aroi_catering.texts.min_amount + aroi_catering.currency + this.cateringSettings.min_order_amount);
            
            // Update datepicker min date based on advance notice
            var minDate = '+' + this.cateringSettings.advance_notice_days + 'd';
            $('#delivery_date').datepicker('option', 'minDate', minDate);
        },

        checkDateAvailability: function(date) {
            var self = this;
            
            $.ajax({
                url: aroi_catering.ajax_url,
                type: 'POST',
                data: {
                    action: 'aroi_check_catering_availability',
                    nonce: aroi_catering.nonce,
                    site_id: self.selectedSiteId,
                    date: date
                },
                success: function(response) {
                    if (!response.success || !response.available) {
                        alert(response.message || aroi_catering.texts.date_unavailable);
                        $('#delivery_date').val('');
                    }
                }
            });
        },

        validateDetailsForm: function() {
            var form = $('#aroi-catering-details-form')[0];
            if (!form.checkValidity()) {
                form.reportValidity();
                return false;
            }

            var guests = parseInt($('#number_of_guests').val());
            if (guests < this.cateringSettings.min_guests) {
                alert(aroi_catering.texts.min_guests + this.cateringSettings.min_guests);
                return false;
            }

            return true;
        },

        loadProducts: function() {
            var self = this;
            
            $.ajax({
                url: aroi_catering.ajax_url,
                type: 'POST',
                data: {
                    action: 'aroi_get_catering_products',
                    nonce: aroi_catering.nonce,
                    site_id: self.selectedSiteId
                },
                beforeSend: function() {
                    self.showLoader();
                },
                success: function(response) {
                    if (response.success) {
                        self.displayProducts(response.data.products);
                        self.showStep('products');
                    } else {
                        self.showError(response.data || aroi_catering.texts.error);
                    }
                },
                error: function() {
                    self.showError(aroi_catering.texts.error);
                },
                complete: function() {
                    self.hideLoader();
                }
            });
        },

        displayProducts: function(products) {
            var html = '<div class="row">';
            
            products.forEach(function(product) {
                html += '<div class="col-md-4 mb-3">';
                html += '<div class="product-card">';
                if (product.image) {
                    html += '<img src="' + product.image + '" alt="' + product.name + '" class="product-image">';
                }
                html += '<h4>' + product.name + '</h4>';
                html += '<p>' + product.description + '</p>';
                html += '<p class="price">' + aroi_catering.currency + product.price + '</p>';
                html += '<div class="quantity-selector">';
                html += '<label>Antall:</label>';
                html += '<input type="number" class="product-quantity" data-product-id="' + product.id + '" ';
                html += 'data-product-price="' + product.price + '" data-product-name="' + product.name + '" ';
                html += 'min="0" value="0" step="' + (product.min_quantity || 1) + '">';
                html += '</div>';
                html += '</div>';
                html += '</div>';
            });
            
            html += '</div>';
            
            $('#catering-products-container').html(html);
        },

        calculateTotal: function() {
            var self = this;
            self.selectedProducts = [];
            self.totalAmount = 0;
            
            $('.product-quantity').each(function() {
                var quantity = parseInt($(this).val()) || 0;
                if (quantity > 0) {
                    var productId = $(this).data('product-id');
                    var productPrice = parseFloat($(this).data('product-price'));
                    var productName = $(this).data('product-name');
                    
                    self.selectedProducts.push({
                        id: productId,
                        name: productName,
                        quantity: quantity,
                        price: productPrice
                    });
                    
                    self.totalAmount += quantity * productPrice;
                }
            });
            
            $('#catering-total-amount').text(aroi_catering.currency + self.totalAmount.toFixed(2));
            
            // Check minimum amount
            if (self.totalAmount < self.cateringSettings.min_order_amount) {
                $('#submit-catering-order').prop('disabled', true);
                $('#min-amount-text').addClass('text-danger');
            } else {
                $('#submit-catering-order').prop('disabled', false);
                $('#min-amount-text').removeClass('text-danger');
            }
        },

        submitOrder: function() {
            var self = this;
            
            if (self.selectedProducts.length === 0) {
                alert('Vennligst velg minst ett produkt.');
                return;
            }
            
            if (self.totalAmount < self.cateringSettings.min_order_amount) {
                alert(aroi_catering.texts.min_amount + aroi_catering.currency + self.cateringSettings.min_order_amount);
                return;
            }
            
            var formData = {
                action: 'aroi_submit_catering_order',
                nonce: aroi_catering.nonce,
                site_id: self.selectedSiteId,
                delivery_date: $('#delivery_date').val(),
                delivery_time: $('#delivery_time').val(),
                delivery_address: $('#delivery_address').val(),
                number_of_guests: $('#number_of_guests').val(),
                contact_name: $('#contact_name').val(),
                contact_phone: $('#contact_phone').val(),
                contact_email: $('#contact_email').val(),
                special_requirements: $('#special_requirements').val(),
                catering_notes: $('#catering_notes').val(),
                products: JSON.stringify(self.selectedProducts),
                total_amount: self.totalAmount
            };
            
            $.ajax({
                url: aroi_catering.ajax_url,
                type: 'POST',
                data: formData,
                beforeSend: function() {
                    self.showLoader();
                    $('#submit-catering-order').prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        self.showStep('confirmation');
                        // Redirect to checkout after 3 seconds
                        setTimeout(function() {
                            window.location.href = response.data.checkout_url;
                        }, 3000);
                    } else {
                        self.showError(response.data || aroi_catering.texts.error);
                        $('#submit-catering-order').prop('disabled', false);
                    }
                },
                error: function() {
                    self.showError(aroi_catering.texts.error);
                    $('#submit-catering-order').prop('disabled', false);
                },
                complete: function() {
                    self.hideLoader();
                }
            });
        },

        showStep: function(step) {
            $('.aroi-catering-step').hide();
            $('#step-' + step).show();
        },

        showLoader: function() {
            // Implementation depends on your loader design
            $('body').append('<div class="aroi-loader">' + aroi_catering.texts.loading + '</div>');
        },

        hideLoader: function() {
            $('.aroi-loader').remove();
        },

        showError: function(message) {
            alert(message); // You can implement a better error display
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        AroiCatering.init();
    });

})(jQuery);