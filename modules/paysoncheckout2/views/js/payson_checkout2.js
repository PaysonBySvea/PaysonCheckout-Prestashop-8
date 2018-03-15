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

    $('.payson-click-trigger').each(function() {
        var el = $(this);
        var elTarget = el.parent().parent().find('.pco-target');
        el.click(function() {
            el.toggleClass('payson-click-trigger--inactive');
            elTarget.fadeToggle(150);
        });
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

    function updateCheckout(callData, updateCart, updateCheckout) {
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
                        setTimeout(function() {
                            if ($('#paysonpaymentwindow').length) {
                                $('#paysonpaymentwindow').height('auto');
                            }
                        }, 500);
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
    }

    function sendLockDown() {
        if ($('#paysonIframe').length) {
            document.getElementById('paysonIframe').contentWindow.postMessage('lock', '*');
            //if ($('#paysonpaymentwindow').length) {
                // To prevent height flash when iframe reload
                //$('#paysonpaymentwindow').height($('#paysonIframe').height());
            //}
        }
    }

    function sendRelease() {
        if ($('#paysonIframe').length) {
            document.getElementById('paysonIframe').contentWindow.postMessage('release', '*');
        }
    }

    setTimeout(function() {
        if ($('#paysonpaymentwindow').length) {
            $('#paysonpaymentwindow').height('auto');
        }
    }, 500);
    
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
                    setTimeout(function() {
                        if ($('#paysonpaymentwindow').length) {
                            $('#paysonpaymentwindow').height('auto');
                        }
                    }, 500);
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
    
    // IE11 poly for custom event, no need for this
//    (function () {
//        if ( typeof window.CustomEvent === "function" ) return false; //If not IE
//
//        function CustomEvent ( event, params ) {
//                params = params || { bubbles: false, cancelable: false, detail: undefined };
//                var evt = document.createEvent( 'CustomEvent' );
//                evt.initCustomEvent( event, params.bubbles, params.cancelable, params.detail );
//                return evt;
//        }
//        CustomEvent.prototype = window.Event.prototype;
//        window.CustomEvent = CustomEvent;
//    })();
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
