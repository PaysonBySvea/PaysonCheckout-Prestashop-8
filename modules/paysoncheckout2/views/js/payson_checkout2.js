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
        el.click(function(){
            el.toggleClass('payson-click-trigger--inactive');
            elTarget.fadeToggle(150);
        });
    });
    
    // Change carrier
    $('.payson-select-list__item').each(function() {
        var el = $(this);
        el.click(function(){
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
        $.ajax({
            type: 'GET',
            url: pcourl,
            async: true,
            cache: false,
            data: callData,
            success: function(returnData)
            {
                if (updateCheckout === true) {
                    $("#paysonpaymentwindow").html(returnData);
                    setTimeout(function() {
                        if ($('#paysonpaymentwindow').length) {
                            $('#paysonpaymentwindow').height('auto');
                        }
                    }, 1000);
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
            }
        });
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
    }

    setTimeout(function() {
        if ($('#paysonpaymentwindow').length) {
            $('#paysonpaymentwindow').height('auto');
        }
    }, 1000);
    
    function reloadOnError() {
        $.ajax({
            type: 'GET',
            url: pcourl,
            async: true,
            cache: false,
            data: 'chkorder=chk',
            success: function(returnData)
            {
                if (returnData == 'reload') {
                    sendLockDown();
                    location.href = pcourl;
                }
            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                    //console.log(returnData);
            }
        });
    }
    
    setInterval(function () {
        reloadOnError();
    }, 3000);
    
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
