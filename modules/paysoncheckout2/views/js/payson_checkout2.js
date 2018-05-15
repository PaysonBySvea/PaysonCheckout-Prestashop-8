/*
* 2018 Payson AB
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
*
*  @author    Payson AB <integration@payson.se>
*  @copyright 2018 Payson AB
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*/

$(document).ready(function() {
    prestashop.on(
      'updateCart',
      function (event) {
          if (event.reason !== 'orderChange') {
            sendLockDown();
            var callData = {pco_update: '1'};
            updateCheckout(callData, false, true);
        }
      }
    );

    if (sessionStorage.conditions_to_approve_checkbox === 'true') {
        $('.conditions_to_approve_checkbox').prop('checked', true);
    }

    $('.payson-click-trigger').each(function() {
        var el = $(this);
        var elTarget = el.parent().parent().find('.pco-target');
        el.click(function() {
            el.toggleClass('payson-click-trigger--inactive');
            elTarget.fadeToggle(150);
        });
    });
    
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
    
    // Change carrier
    $('.payson-select-list__item').each(function() {
        var el = $(this);
        el.click(function() {
            if (!el.hasClass('selected')) {
                var deliveryId = el.find('input[type=radio]').val();
                var callData = {pco_update: '1', delivery_option: {0:deliveryId}};
                el.siblings().removeClass('selected');
                el.addClass('selected');
                sendLockDown();
                updateCheckout(callData, true,  true);
            }
        });
    });
    
    // Order message
    $('#savemessagebutton').click(function() {
        var message = $('#message').val();
        var callData = {pco_update: '1', message: message};
        sendLockDown();
        updateCheckout(callData, true, false);
    });
    
    // Gift wrapping
    $('#savegiftbutton').click(function() {
        var gift = 0;
        if ($('#gift').is(':checked')) {
            gift = 1;
        }
        var gift_message = $('#gift_message').val();
        var callData = {pco_update: '1', gift_message: gift_message, gift: gift};
        sendLockDown();
        updateCheckout(callData, true, true);
    });

    function termsChecked() {
	var is_ok = true;
	$(".conditions_to_approve_checkbox").each(function() {
            if (!$(this).prop('checked')) {
                    is_ok = false;
            }
	});
	
	return is_ok;
    }

    function updateCheckout(callData, updateCart, updateCheckout) {
        if (termsChecked()) {
            if (!$('#paysonIframe').length) {
                $('#paysonpaymentwindow').html('');
                $('#paysonpaymentwindow').height('519px');
            }

            upReq = null;
            upReq = $.ajax({
                type: 'GET',
                url: pcourl,
                async: true,
                cache: false,
                data: callData,
                beforeSend: function()
                { 
                    if (upReq !== null) {
                        upReq.abort();
                    }
                },
                success: function(returnData)
                {
                    if (updateCheckout === true) {
                        if (returnData === 'reload') {
                            location.href = pcourl;
                        } else {
                            $("#paysonpaymentwindow").html(returnData);
                        }
                    }
                    if (updateCart === true) {
                        prestashop.emit('updateCart', {
                            reason: 'orderChange'
                        });
                    }

                    sendRelease();
                },
                error: function(XMLHttpRequest, textStatus, errorThrown) {
                    //console.log(returnData);
                    sendRelease();
                }
            });
        } else {
            sendRelease();
            $("#paysonpaymentwindow").html(acceptTermsMessage);
        }
    }

    function sendLockDown() {
        if ($('#paysonIframe').length) {
            document.getElementById('paysonIframe').contentWindow.postMessage('lock', '*');
            if ($('#paysonpaymentwindow').length) {
                // To prevent height flash when iframe reload
                $('#paysonpaymentwindow').height($('#paysonIframe').height());
            }
        }
    }

    function sendRelease() {
        if ($('#paysonIframe').length) {
            document.getElementById('paysonIframe').contentWindow.postMessage('release', '*');
        }
        setTimeout(function() {
            if ($('#paysonpaymentwindow').length) {
                $('#paysonpaymentwindow').height('auto');
            }
        }, 500);
    }
    
    // Validate order on PaysonEmbeddedAddressChanged event
    function validateOrder(callData) {
        valReq = null;
        valReq = $.ajax({
            type: 'GET',
            url: validateurl,
            async: true,
            cache: false,
            data: callData,
            beforeSend: function()
            { 
                if (valReq !== null) {
                    valReq.abort();
                }
            },
            success: function(returnData)
            {
                if (returnData === 'reload') {
                    //sendLockDown();
                    location.href = pcourl;
                } else {
                    sendRelease();
                }
            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                //console.log(returnData);
                sendRelease();
            }
        });
    }
    
    document.addEventListener('PaysonEmbeddedAddressChanged', function() {
        sendLockDown();
        var callData = {validate_order: '1', checkout: pco_checkout_id, id_cart: id_cart};
        validateOrder(callData);
    }, true);
    
    updateCheckout({pco_update: '1'}, false, true);
});

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
