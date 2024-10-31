(function ($) {
	'use strict';

	/**
	 * All of the code for your public-facing JavaScript source
	 * should reside in this file.
	 */


    $(".revenue-campaign-add-to-cart").on('click',function(e){
        let qty = e.target.dataset?.quantity ?? 0;
        if(!qty) {
            qty = $(`revx-product-qty-${e.target.dataset.productId}-${e.target.dataset.campaignId}`).val() ?? 1;
        }
		if(e.target.dataset?.campaignSrc==='popup') {

		}

        $.ajax( {
            url: revenue_add_to_cart.ajax,
            data: {
                action: 'revenue_add_to_cart',
                productId:e.target.dataset.productId,
                campaignId:e.target.dataset.campaignId,
				_wpnonce: revenue_add_to_cart.nonce,
                quantity: qty
            },
            type: 'POST',
            success: function ( response ) {
				if('hide_products'==response.data.on_cart_action) {
					const target = $(e.target).parent();
					target.hide('slow', function(){ target.remove(); });
				}
            },
            error: function ( jqXHR, textStatus, errorThrown ) {

            },
        } );
    });

    $(".revenue-campaign-add-bundle-to-cart").on('click',function(e){
        let qty = e.currentTarget.dataset?.quantity ?? 0;
		const bundleIdx = e.currentTarget.dataset?.bundleIdx;
		const productId = e.currentTarget.dataset?.productId;
        if(!qty) {
            if(bundleIdx) {
                qty = $(`revx-product-qty-${e.target.dataset.campaignId}-${bundleIdx}`).val() ?? 1;
                } else {
                qty = $(`revx-product-qty-${e.target.dataset.campaignId}`).val() ?? 1;
            }
        }
		if(e.target.dataset?.campaignSrc==='popup') {

		}
        $.ajax( {
            url: revenue_add_to_cart.ajax,
            data: {
                action: 'revenue_add_bundle_to_cart',
                bundleIdx:bundleIdx,
                campaignId:e.target.dataset.campaignId,
                productId:productId,
				_wpnonce: revenue_add_to_cart.nonce,
                quantity: qty
            },
            type: 'POST',
            success: function ( response ) {
				// if('hide_products'==response.data.on_cart_action) {
				// 	const target = $(e.target).parent();
				// 	target.hide('slow', function(){ target.remove(); });
				// }
            },
            error: function ( jqXHR, textStatus, errorThrown ) {

            },
        } );
    });



	if(typeof revenue_campaign =='object') {
		$(".revx-campaign-item__title").on('click',function(e){
			const item  = $(e.target).parent();
			const datasets = item.dataset;

			const campaignID = datasets.campaignId;
			const productID = datasets.productId;
			const productURL = datasets.productUrl;
			if("redirect_to_product_page" === revenue_campaign[campaignID]) {
				window.location.href = productURL;
				return;
			}

			if("details_on_popup" === revenue_campaign[campaignID]) {

			}



		});
	}



})(jQuery);
