$(document).ready(function(){
    if(document.getElementById('opc_payment_methods')) 
    {
        var amount = 0;
        amount = $("#total_price").text();
        $intervalToShowPayson = 1;
        
        /*if (document.getElementById("new_account_form")) {
            document.getElementById("new_account_form").style.display = ("none");
        }*/
        
        /*document.getElementById("new_account_form").style.display = ("none");
        $getElementHPayment = document.getElementById("HOOK_PAYMENT");
        $getElementHPChildren = $getElementHPayment.children[0];
        $getElementHPCClass = $getElementHPChildren.className;*/

        /*if($getElementHPCClass === 'warning'){
            $("#HOOK_PAYMENT").each(function() {
                $("#HOOK_PAYMENT").hide();   
            });
            $("#opc_payment_methods").append("<div id=iframepayson></div>"); 

        }else{
            $("#opc_new_account").append("<div id=iframepayson></div>"); 
        }*/

        setInterval(function() {
            $isPayson = document.getElementById("paysonTracker");
            if ($isPayson) {
                $paysonTrackerId = $isPayson.children[0].id;
                if ($paysonTrackerId) {
                    if(amount !== $("#total_price").text() || ($intervalToShowPayson === 1 && document.getElementById("payment_" + $paysonTrackerId).checked === true)){
                        /*if (document.getElementById("new_account_form")) {
                            document.getElementById("new_account_form").style.display = ("none");
                        }*/
                       
                        if(document.getElementById('iframepayson')) {
                            document.getElementById('iframepayson').style.display = ("block");
                        }
                        
                        /*$(".confirm_button_div").each(function() {
                            $(".confirm_button_div").hide();   
                        });*/

                        sendLockDown();
                        displaySnippet();

                        /*$(".confirm_button_div").each(function() {
                            $(".confirm_button_div").hide();   
                        });*/

                        $("#offer_password").each(function() {
                            $("#offer_password").hide(); 

                        });
                       $intervalToShowPayson = 0;
                       amount = $("#total_price").text();
                    }
                    if(document.getElementById("payment_" + $paysonTrackerId).checked === false){
                        if(document.getElementById('iframepayson')) {
                            document.getElementById('iframepayson').style.display = ("none");
                        }
                       
                        /*if (document.getElementById("new_account_form")) {
                        document.getElementById("new_account_form").style.display = ("block");
                        }*/
                        
                        $(".confirm_button_div").each(function() {
                            $(".confirm_button_div").show();   
                        });
                        
                        $intervalToShowPayson = 1;
                    }
                }
            }
        }, 500);  
    displaySnippet();     

    }

    function displaySnippet() {
        $.ajax({
           url: baseDir + 'modules/paysonCheckout2/redirect.php?type=checkPayson',
           success:function (data) {
            $("#iframepayson").html(data);
                sendRelease();
           }, cache: false
        });
    }

    document.addEventListener("PaysonEmbeddedAddressChanged",function(evt) {
        var address = evt.detail;
        updatCartAddress(address);
    });

    function updatCartAddress(address) {
        $.ajax({
           url: baseDir  + "modules/paysonCheckout2/redirect.php?address_data="+JSON.stringify(address),
           success:function (data) {
            //$("#iframepayson").html(data);
           }, cache: false
        });
    }

    function sendLockDown() {
        if(document.getElementById('paysonIframe')) {
            document.getElementById('paysonIframe').contentWindow.postMessage('lock', '*');
        }
    }

    function sendRelease() {
        if(document.getElementById('paysonIframe')) {
            document.getElementById('paysonIframe').contentWindow.postMessage('release', '*');
        }
    }
});
