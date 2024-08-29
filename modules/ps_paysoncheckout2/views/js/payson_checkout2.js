/*
* 2019 Payson AB
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
*
*  @author    Payson AB <integration@payson.se>
*  @copyright 2019 Payson AB
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*/

var reqInProcess = false;
$(document).ready(function() {
    // React on cart change
    prestashop.on('updateCart',function (event) {
        if (event.resp !== 'orderChange') {
            sendLockDown();
            updateCheckout({pco_update: '1'}, false, true);
            refreshCarriers();
        }
    });

    // Check terms
    if (sessionStorage.conditions_to_approve_checkbox === 'true') {
        $('.conditions_to_approve_checkbox').prop('checked', true);
    }
    
    // Check newsletter optin
    if (sessionStorage.newsletter_optin_checkbox === 'true') {
        $('.newsletter_optin_checkbox').prop('checked', true);
    }

    // Show/hide cards
    $('.payson-click-trigger').each(function() {
        var el = $(this);
        var elTarget = el.parent().parent().find('.pco-target');
        el.click(function() {
            el.toggleClass('payson-click-trigger--inactive');
            elTarget.fadeToggle(150);
        });
    });
    
    // Show terms
    $('.js-terms a').on('click', function(event) {
        event.preventDefault();
        var url = $(event.target).attr('href');
        if (url) {
            url += '?content_only=1';
            $.get(url, function (content) {
              $('#modal').find('.modal-body').html($(content).find('.page-cms').contents());
            });
        }
        $('#modal').modal('show');
    });
    
    // Approve terms
    $('.conditions_to_approve_checkbox').bind('change', function() {
        sessionStorage.setItem('conditions_to_approve_checkbox', $(this).prop('checked'));
        updateCheckout({pco_update: '1'}, false, true);
    });
    
    // Optin newsletter
    $('.newsletter_optin_checkbox').bind('change', function() {
        sessionStorage.setItem('newsletter_optin_checkbox', $(this).prop('checked'));
        updateCheckout({pco_update: '1', newsletter_sub: $(this).prop('checked')}, false, false);
    });
    
    // Change carrier
    $('.payson-select-list__item').each(function() {
        var el = $(this);
        el.click(function() {
            if (termsChecked()) {
                if (!el.hasClass('selected')) {
                    var deliveryId = el.find('input[type=radio]').val();
                    el.siblings().removeClass('selected');
                    el.addClass('selected');
                    sendLockDown();
                    updateCheckout({pco_update: '1', delivery_option: {0:deliveryId}}, true,  true);
                }
            } else {
                $('#modal').find('.modal-body').html(acceptTermsMessage);
                $('#modal').modal('show');
            }
        });
    });
    
    // Order message
    $('#savemessagebutton').click(function() {
        if (termsChecked()) {
            sendLockDown();
            updateCheckout({pco_update: '1', message: $('#message').val()}, true, true);
        } else {
            $('#modal').find('.modal-body').html(acceptTermsMessage);
            $('#modal').modal('show');
        }
    });
    
    // Gift wrapping
    $('#savegiftbutton').click(function() {
        if (termsChecked()) {
            var gift = 0;
            if ($('#gift').is(':checked')) {
                gift = 1;
            }
            sendLockDown();
            updateCheckout({pco_update: '1', gift_message: $('#gift_message').val(), gift: gift}, true, true);
        } else {
            $('#modal').find('.modal-body').html(acceptTermsMessage);
            $('#modal').modal('show');
        }
    });

    // If terms approved
    function termsChecked() {
	var is_ok = true;
	$(".conditions_to_approve_checkbox").each(function() {
            if (!$(this).prop('checked')) {
                is_ok = false;
            }
	});
	return is_ok;
    }

    // Update checkout
    function updateCheckout(callData, updateCart, updateCheckout) {
        if (!reqInProcess && (typeof pcourl !== typeof undefined)) {
            reqInProcess = true;
            if (termsChecked()) {
                if (!$('#paysonIframe').length) {
                    $('#paysonpaymentwindow').html('');
                    $('#paysonpaymentwindow').height('519px');
                }

                $.ajax({
                    type: 'GET',
                    url: pcourl,
                    async: true,
                    cache: false,
                    data: callData,
                    success: function(returnData) {
                        if (updateCheckout === true) {
                            if (returnData === 'reload') {
                                location.href = pcourl;
                            } else {
                                if ($('#paysonIframe').length && returnData.indexOf("paysonContainer") !== -1) {
                                    sendUpdate();
                                } else {
                                    $("#paysonpaymentwindow").html(returnData);
                                }
                            }
                        }
                        if (updateCart === true) {
                            prestashop.emit('updateCart', {
                                resp: 'orderChange'  //DU-598
                            });
                        }
                        sendRelease();
                    },
                    error: function() {
                        sendRelease();
                    }
                });
            } else {
               sendRelease();
               $("#paysonpaymentwindow").html(acceptTermsMessage);
            }
            reqInProcess = false;
        }
    }

    // Refresh carriers
    function refreshCarriers() {
        if (!reqInProcess && (typeof pcourl !== typeof undefined)) {
            reqInProcess = true;
            
            $.ajax({
                type: 'GET',
                url: pcourl,
                async: true,
                cache: false,
                data: {refresh_carriers: '1'},
                success: function(returnData) {
                    if (returnData !== 'no_update') {
                        jsonData = JSON.parse(returnData);
                        for(var i = 0; i < jsonData.length; i++) {
                            if (!$(".payson-carrier-card-block .li-delivery-option-" + jsonData[i].id).length) {
                                // Carrier has been added or removed, reload page
                                location.href = pcourl;
                            }
                        }
                        
                        for(var i = 0; i < jsonData.length; i++) {
                            // Update carrier price
                            $(".payson-carrier-card-block .li-delivery-option-" + jsonData[i].id + " .payson.carrier-price").html(jsonData[i].price);
                        }
                    }
                },
                error: function() {
                    
                }
            });
           
            reqInProcess = false;
        }
    }

    // Lock iframe
    function sendLockDown() {
        if ($('#paysonIframe').length) {
            document.getElementById('paysonIframe').contentWindow.postMessage('lock', '*');
            // Prevent height flash
            if ($('#paysonpaymentwindow').length) {
                $('#paysonpaymentwindow').height($('#paysonIframe').height());
            }
        }
    }

    // Release iframe
    function sendRelease() {
        if ($('#paysonIframe').length) {
            document.getElementById('paysonIframe').contentWindow.postMessage('release', '*');
        }
        // Reset height
        var heightInterval = setInterval(function() {
            if ($('#paysonpaymentwindow').length) {
                $('#paysonpaymentwindow').height('auto');
                clearInterval(heightInterval);
            }
        }, 100);
    }
    
    // Update iframe
    function sendUpdate() {
        if ($('#paysonIframe').length) {
            document.getElementById('paysonIframe').contentWindow.postMessage('updatePage', '*');
        }
    }
    
    // Validate order
    function validateOrder(callData) {
        if (!reqInProcess) {
            reqInProcess = true;
            $.ajax({
                type: 'GET',
                url: validateurl,
                async: true,
                cache: false,
                data: callData,
                success: function(returnData) {
                    if (returnData === 'reload') {
                        location.href = pcourl;
                    } else {
                        sendRelease();
                    }
                },
                error: function() {
                    sendRelease();
                }
            });
            reqInProcess = false;
        }
    }
    
    // Listen for address change
    document.addEventListener('PaysonEmbeddedAddressChanged', function() {
        sendLockDown();
        validateOrder({validate_order: '1', checkout: pco_checkout_id, id_cart: id_cart});
    }, true);
    
    // Initial trigger
    updateCheckout({pco_update: '1'}, false, true);
});

// Adjust order of cards on small screens
$(window).resize(function() {
    if (window.matchMedia('(max-width: 975px)').matches) {
        if ($('#payson_cart_summary_wrapp .right-col').length) {
            $('#payson_cart_summary_wrapp .right-col').insertBefore($('.card-payson-pay'));
        }
    } else {
        if ($('#payson_cart_summary_wrapp .left-col .right-col').length) {
            $('#payson_cart_summary_wrapp').append($('#payson_cart_summary_wrapp .left-col .right-col'));
        }
    }
});
$(window).trigger('resize');
