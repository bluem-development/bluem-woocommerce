/* (C) Bluem Plugin Support, for
Bluem 2021
*/
// bluem_woocommerce_bic_retriever

jQuery(function() {
    jQuery('body')
        .on('updated_checkout', function() {
            updateBICs();

            jQuery('input[name="payment_method"]').change(function() {
                console.log("payment method changed");
                updateBICs();

            });
        });
});

function updateBICs() {
    jQuery("#BICselector").hide();

    console.log("Current gateway ID: ");
    var method_context = jQuery("input[name='payment_method']:checked").val();
    console.log(method_context);

    var nonce = jQuery("#bluem_ajax_nonce").val();
    if (method_context == 'bluem_mandates') {
        console.log("Using my gateway");

        console.log("Calling " + myAjax.ajaxurl)
        jQuery.ajax({
            type: "get",
            dataType: "json",
            url: myAjax.ajaxurl,
            data: {
                action: "bluem_retrieve_bics_ajax",
                context: method_context,
                nonce: nonce
            },
            success: function(response) {
                console.log("Response");
                console.log(response);
                if (response.length > 0) {
                    refillBICInput(response);
                    jQuery("#BICselector").show();
                }
            }
        });

        // nonce = jQuery(this).attr("data-nonce");

        //Etc etc
    } else {
        console.log("Not using my gateway. Proceed as usual");
    }
}

function refillBICInput(BICs) {
    jQuery("#BICInput").html('');
    BICs.forEach(BIC => {

        var issuerID = BIC.issuerID
        var issuerName = BIC.issuerName
        jQuery('#BICInput').append('<option value="' + issuerID + '">' + issuerName + '</option>');
    });
    // for (var i = 0; i <= BICs.length; i++) {
    // console.log()
    // }

}