var tmpshippingvalue = "";
$(document).ready(function() {
    prestashop.on(
      'updateCart',
      function (event) {
          sendLockDown();
          updateCheckout();
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
    
    $('.payson-select-list__item').each(function() {
        var el = $(this);
        el.click(function(){
            el.siblings().removeClass('selected');
            el.addClass('selected');
        });
    });

    function updateCheckout() {
        $.ajax({
                type: 'GET',
                url: pcourl,
                async: true,
                cache: false,
                data: 'pco_update=1',
                success: function(jsonData)
                {
                    $("#paysonpaymentwindow").html(jsonData);

                    setTimeout(function() {
                        if ($('#paysonpaymentwindow').length) {
                            $('#paysonpaymentwindow').height('auto');
                        }
                    }, 1000);
                },
                error: function(XMLHttpRequest, textStatus, errorThrown) {
                        //alert(jsonData);
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
    
    setTimeout(function() {
        if ($('#paysonpaymentwindow').length) {
            $('#paysonpaymentwindow').height('auto');
        }
    }, 1000);
    
});

$(window).resize(function() {
        if ($('#paysonIframe').length && $('#paysonpaymentwindow').length) {
            //$('#paysonpaymentwindow').height($('#paysonIframe').height());
            //$('#paysonpaymentwindow').height('auto');
        }

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
