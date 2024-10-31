(function ($) {
    'use strict';

    function showToast(message, duration = 3000) {
        // Check if toast container exists, otherwise create it
        var $toastContainer = $('.toast-container');
        if ($toastContainer.length === 0) {
            $toastContainer = $('<div class="revx-toaster-container"></div>');
            $('body').append($toastContainer);
        }

        // Create a new toast element as a jQuery object
        var $toast = $(`
            <div class="revx-toaster revx-justify-space revx-toaster-lg revx-toaster__success">
                <div class="revx-paragraph--xs revx-align-center-xs">
                    ${message}
                </div>
                <div class="revx-paragraph--xs revx-align-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16px" height="16px" fill="none" viewBox="0 0 16 16" class="revx-toaster__close-icon revx-toaster__icon">
                    <path stroke="#fff" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.2" d="m12 4-8 8M4 4l8 8"></path>
                    </svg>
                </div>
            </div>
        `);

        // Add close button functionality
        $toast.find('.revx-toaster__close-icon').on('click', function () {
            $toast.fadeOut(400, function () {
                $(this).remove(); // Remove the toast from DOM
            });
        });

        // Append the toast to the toast container
        $toastContainer.append($toast);

        // Show the toast
        $toast.fadeIn(400);

        // Set timeout to hide the toast after the specified duration
        setTimeout(function () {
            $toast.fadeOut(400, function () {
                $(this).remove(); // Remove the toast from DOM
            });
        }, duration);
    }

    function calculateCurrentPrice(offerData, productId, quantity) {
        const product = offerData[productId];

        if (!product) {
            throw new Error("Product not found");
        }

        let regularPrice = parseFloat(product.regular_price);
        let currentPrice = regularPrice;

        if (product.offer && Array.isArray(product.offer)) {
            for (let i = 0; i < product.offer.length; i++) {
                const offer = product.offer[i];
                const minQty = parseInt(offer.qty, 10);

				if(offer.type=='free') {
					if (quantity <= minQty) {


						switch (offer.type) {
							case 'free':
								currentPrice = 0;


								break;

							default:
								break;
						}
						// Add more offer types if needed (e.g., fixed discount)
					}
				} else {
					if (quantity >= minQty) {


						switch (offer.type) {
							case 'percentage':
								currentPrice = regularPrice - (parseFloat(offer.value) / 100) * regularPrice;;
								break;
							case 'amount':
							case 'fixed_discount':
								currentPrice = regularPrice - parseFloat(offer.value);

								break;
							case 'fixed_price':
								currentPrice = parseFloat(offer.value);


								break;
							case 'no_discount':
								currentPrice = regularPrice;


								break;
							case 'free':
								currentPrice = 0;


								break;

							default:
								break;
						}
						// Add more offer types if needed (e.g., fixed discount)
					}
				}
            }
        }

        return parseFloat(currentPrice * quantity);
    }

    if (typeof revenue_campaign == 'object') {
        $(".revx-campaign-item__title").on('click', function (e) {
            const item = $(e.target).parent();
            const datasets = item.dataset;

            const campaignID = datasets.campaignId;
            const productID = datasets.productId;
            const productURL = datasets.productUrl;
            if ("redirect_to_product_page" === revenue_campaign[campaignID]) {
                window.location.href = productURL;
                return;
            }

            if ("details_on_popup" === revenue_campaign[campaignID]) {

            }



        });
    }

    // Spending Goal

    // Progress Bar
    $('.revx-progress-close-icon').on('click', function () {
        $(this).closest('.revx-spending-goal-view').fadeOut(500, function () {
            $(this).remove();
        });
    });


    const formatPrice = (price) => {
        const currencyFormat = revenue_campaign?.currency_format;
        const currencySymbol = revenue_campaign?.currency_format_symbol;
        const decimalSeparator = revenue_campaign?.currency_format_decimal_sep;
        const thousandSeparator = revenue_campaign?.currency_format_thousand_sep;
        const numDecimals = revenue_campaign?.currency_format_num_decimals;

        // Ensure the price is a number and fix to the specified number of decimals
        const fixedPrice = parseFloat(price).toFixed(numDecimals);

        // Split the price into whole and decimal parts
        const parts = fixedPrice.split('.');
        let integerPart = parts[0];
        const decimalPart = parts[1];

        // Add thousand separators to the integer part
        integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, thousandSeparator);

        // Combine the integer and decimal parts
        const formattedPrice = integerPart + decimalSeparator + decimalPart;

        // Apply the currency format
        return currencyFormat.replace('%1$s', currencySymbol).replace('%2$s', formattedPrice);
    }

    // Volume Discount
    $('.revx-volume-discount .revx-campaign-item').on('click', function (e) {
        // Remove selected style from all items
        e.stopPropagation();
        var that = $(this);
        $('.revx-volume-discount .revx-campaign-item').each(function () {
            var item = $(this).find('.revx-volume-discount__tag');
            var defaultStyle = item.data('default-style');
            $(item).attr('style', defaultStyle);
            $(this).attr('data-revx-selected', false);

        });
        var clickedItem = that.find('.revx-volume-discount__tag');

        // Apply selected style to the clicked item
        var selectedStyle = clickedItem.data('selected-style');
        clickedItem.attr('style', selectedStyle);
        that.attr('data-revx-selected', true);
    });



    $('select.revx-productAttr-wrapper__field').on('change', function () {
		const attributeData = $('.variations_form').data('product_variations');

        const attributeName = $(this).data('attribute_name');

        const parentWrapper = $(this).closest('.revx-productAttr-wrapper');
        const fieldsInParent = parentWrapper.find('.revx-productAttr-wrapper__field');



        let allFieldsHaveValue = true;
        const values = {};
        fieldsInParent.each(function () {
            if ($(this).val() === "") {
                allFieldsHaveValue = false;
                return false; // Break the loop
            } else {
                values[$(this).data('attribute_name')] = $(this).val();
            }
        });

        let selectedVariation = false;

        if (allFieldsHaveValue) {

            attributeData.forEach(element => {
                if (JSON.stringify(element.attributes) == JSON.stringify(values)) {
                    selectedVariation = element;
                }
            });
        }

        $('.revx-campaign-item').removeAttr('data-product-id');
        if (selectedVariation) {
            const parent = $(this).closest('.revx-campaign-item');
            parent.attr('data-product-id', selectedVariation['variation_id']);


            parent.parent().parent().find(".revx-campaign-add-to-cart-btn").attr('data-product-id', selectedVariation['variation_id']);

            parent.find('input[data-name=revx_quantity]').trigger('change');

        }

    });


    $(".revx-bundle-discount .revx-builder__quantity input[data-name=revx_quantity]").on('change', function () {
        const parent = $(this).closest('.revx-campaign-container__wrapper');
        const bundle_products = $(this).closest('.revx-campaign-container__wrapper').data('bundle_products');

        if (bundle_products.length == 0) {
            return;
        }

        const quantity = $(this).val();

        parent.find(".revenuex-campaign-add-bundle-to-cart").attr('data-quantity', quantity);

        let campaignId = $(this).data('campaign-id'); // Get data-campaign-id attribute value

        let offerData = $(`input[name=revx-offer-data-${campaignId}]`);
        offerData = offerData[0].value;
        let jsonData = JSON.parse(offerData);

        let totalRegularPrice = 0;
        let totalSalePrice = 0;

        bundle_products.forEach((product) => {
            let productId = product.item_id;
            // let quantity = product.quantity;

            if (jsonData[productId]) {
                totalRegularPrice += parseFloat((jsonData[productId]['regular_price'] * quantity).toFixed(2));
                totalSalePrice += parseFloat(calculateCurrentPrice(jsonData, productId,product.quantity* quantity).toFixed(2));
            }





        });




        if (totalRegularPrice != totalSalePrice) {
            $(parent).find('.revx-total-price__offer-price .revx-campaign-item__sale-price').html(formatPrice(totalSalePrice));
            $(parent).find('.revx-total-price__offer-price .revx-campaign-item__regular-price').html(formatPrice(totalRegularPrice));
        } else {
            $(parent).find('.revx-total-price__offer-price .revx-campaign-item__regular-price').html(formatPrice(totalRegularPrice));
        }
    });


    $(".revx-volume-discount .revx-builder__quantity input[data-name=revx_quantity]").on('change', function () {
        const parent = $(this).closest('.revx-campaign-item');
        const product_id = $(this).closest('.revx-campaign-item').data('product-id');

        if (!product_id) {
            return;
        }

        const quantity = $(this).val();

        parent.parent().parent().find(".revx-campaign-add-to-cart-btn").attr('data-quantity', quantity);

        let campaignId = $(this).data('campaign-id'); // Get data-campaign-id attribute value


        let offerData = $(`input[name=revx-offer-data-${campaignId}]`);
        offerData = offerData[0].value;
        let jsonData = JSON.parse(offerData);

        let regularPrice = (jsonData[product_id]['regular_price'] * quantity).toFixed(2);
        let salePrice = calculateCurrentPrice(jsonData, product_id, quantity).toFixed(2);

        if (salePrice != regularPrice) {
            $(parent).find('.revx-campaign-item__sale-price').html(formatPrice(salePrice));
            $(parent).find('.revx-campaign-item__regular-price').html(formatPrice(regularPrice));
        } else {
            $(parent).find('.revx-campaign-item__regular-price').html(formatPrice(salePrice));
        }
    });

    $(".revx-normal-discount .revx-builder__quantity input[data-name=revx_quantity]").on('change', function () {
        const parent = $(this).closest('.revx-campaign-item');
        const product_id = $(this).closest('.revx-campaign-item').data('product-id');

        if (!product_id) {
            return;
        }

        const quantity = $(this).val();

        parent.parent().parent().find(".revx-campaign-add-to-cart-btn").attr('data-quantity', quantity);

        let campaignId = $(this).data('campaign-id'); // Get data-campaign-id attribute value

        let offerData = $(`input[name=revx-offer-data-${campaignId}]`);
        offerData = offerData[0].value;
        let jsonData = JSON.parse(offerData);


        let inRP = (jsonData[product_id]['regular_price']);


        let regularPrice = (jsonData[product_id]['regular_price'] * quantity).toFixed(2);

        let salePrice = calculateCurrentPrice(jsonData, product_id, quantity).toFixed(2);

        let inSP = (salePrice/quantity).toFixed(2);


        if (salePrice != regularPrice) {
            $(parent).find('.revx-campaign-item__sale-price').html(`${quantity} x `+formatPrice(inSP));
            $(parent).find('.revx-campaign-item__regular-price').html(`${quantity} x `+formatPrice(inRP));
        } else {
            $(parent).find('.revx-campaign-item__sale-price').html(`${quantity} x `+formatPrice(inRP));
        }
    });


    $(".revx-quantity-minus").on('click', function (e) {
        e.preventDefault();
        e.stopPropagation(); // Stop event propagation to parent elements
        var $input = $(this).siblings('input[type="number"]');

        if (!$input) {
            return;
        }
        $input.focus(); // Focus on the input field after updating its value

        var currentValue = parseInt($input.val(), 10);

        let min = $input.attr('min');
        if (min && (currentValue - 1) >= min) {

            if (!isNaN(currentValue) && currentValue > 0) {
                $input.val(currentValue - 1);
            }
        }

        $input.trigger("change");

    });

    $(".revx-quantity-plus").on('click', function (e) {
        e.preventDefault();
        e.stopPropagation(); // Stop event propagation to parent elements

        var $input = $(this).siblings('input[type="number"]');
        if (!$input.length) {
            return;
        }
        $input.focus(); // Focus on the input field after updating its value

        var currentValue = parseInt($input.val(), 10);
        var maxValue = parseInt($input.attr('max'), 10); // Get the max attribute value

        if (!isNaN(currentValue)) {
            // Check if current value is less than the max value
            if (!isNaN(maxValue) && currentValue < maxValue) {
                $input.val(currentValue + 1);
            } else if (isNaN(maxValue)) {
                $input.val(currentValue + 1); // No max set, just increment
            }
        } else {
            $input.val(1); // Set default value if current value is not a number
        }

        $input.trigger("change");
    });



	$('input[data-name=revx_quantity]').on('input', function (e) {
        let minVal = parseInt($(this).attr('min')) || 1; // Default to 1 if min is not set
        let maxVal = parseInt($(this).attr('max')); // Parse max value from the attribute
        let val = parseInt($(this).val()); // Get the current value of the input

        // If the current value is less than min, set it to min
        if (val < minVal) {
            $(this).val(minVal);
        }

        // If the current value is greater than max, set it to max
        if (!isNaN(maxVal) && val > maxVal) {
            $(this).val(maxVal);
        }

        // Trigger change event after updating the value
        $(this).trigger('change');
    });

    function getCookie(cname) {
        let name = 'revx_' + cname + "=";
        let decodedCookie = decodeURIComponent(document.cookie);
        let ca = decodedCookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) == ' ') {
                c = c.substring(1);
            }
            if (c.indexOf(name) == 0) {
                return c.substring(name.length, c.length);
            }
        }
        return "";
    }

    // Function to set the cookie
    function setCookie(name, value, days) {
        let date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        let expires = "expires=" + date.toUTCString();
        document.cookie = 'revx_' + name + "=" + encodeURIComponent(value) + ";" + expires + ";path=/";
    }

    // Mix Match
    $(".revx-mix-match button.revx-builder-add-btn").on('click', function (e) {
        e.preventDefault();
        const campaign_id = $(this).data('campaign-id');
        const product_id = $(this).data('product-id');
        const item = $(this).closest('.revx-campaign-item');

        const quantity = item.find(`input[data-name="revx_quantity"]`).val() ?? 1;


        let offerData = $(`input[name=revx-offer-data-${campaign_id}]`).val();
        let jsonData = JSON.parse(offerData);

        let qtyData = $(`input[name=revx-qty-data-${campaign_id}]`).val();
        let jsonQtyData = JSON.parse(qtyData);

        let data = {
            'id': product_id,
            productName: jsonData[product_id]['item_name'],
            regularPrice: jsonData[product_id]['regular_price'],
            thumbnail: jsonData[product_id]['thumbnail'],
            'quantity': quantity
        };

        let cookieName = `mix_match_${campaign_id}`;
        let prevData = getCookie(cookieName);

        let prevSelectedItems = $(`input[name=revx-selected-items-${campaign_id}]`).val();

        prevSelectedItems = prevSelectedItems? JSON.parse(prevSelectedItems):{} ;

        prevData = prevData? JSON.parse(prevData) : {};

		if(Object.keys(prevData)==0) {
			prevData = {...prevSelectedItems};
		}


        if (prevData[product_id]) {
            prevData[product_id]['quantity'] = parseInt(prevData[product_id]['quantity']) + parseInt(quantity);

            $(`.revx-selected-item[data-campaign-id=${campaign_id}][data-product-id=${product_id}] .revx-selected-item__product-price`).html(`${prevData[product_id]['quantity']} x ${formatPrice(data['regularPrice'])}`);

        } else {
			const container = $(`.revx-campaign-${campaign_id} .revx-selected-product-container`);


            if(container.hasClass('revx-d-none')) {
                container.removeClass('revx-d-none');
            }
            if(container.hasClass('revx-empty-selected-items')) {
                container.removeClass('revx-empty-selected-items');
            }


			$(`.revx-campaign-${campaign_id} .revx-empty-mix-match`).addClass('revx-d-none');


            prevData[product_id] = data;

            let placeholderItem = $(`.revx-selected-item.revx-d-none[data-campaign-id=${campaign_id}]`);
            let clonedItem = placeholderItem.clone();
            clonedItem.find('.revx-selected-item__product-title').html(data['productName']);
            clonedItem.find('.revx-campaign-item__image img').attr('src',data['thumbnail']);
            clonedItem.find('.revx-campaign-item__image img').attr('alt',data['productName']);
            clonedItem.find('.revx-selected-item__product-price').html(`${quantity} x ${formatPrice(data['regularPrice'])}`);
            clonedItem.removeClass('revx-d-none');
            clonedItem.attr('data-product-id',product_id);
            placeholderItem.before(clonedItem);

        }


        $(`input[name=revx-selected-items-${campaign_id}]`).val(JSON.stringify(prevData));

        setCookie(cookieName, JSON.stringify(prevData), 7);

        updateMixMatchHeaderAndPrices(campaign_id, prevData, jsonQtyData);

        $(this).parent().find(`input[data-name="revx_quantity"]`).val(1);
    });

    function removeMixMatchSelectedItem (e) {
        const product_id = $(this).closest('[data-product-id]').data('product-id');
        const campaign_id = $(this).closest('[data-campaign-id]').data('campaign-id');
        const item = $(`.revx-selected-item[data-campaign-id=${campaign_id}][data-product-id=${product_id}]`);

        item.remove();

		let cookieName = `mix_match_${campaign_id}`;
        let prevData = getCookie(cookieName);

        let prevSelectedItems = $(`input[name=revx-selected-items-${campaign_id}]`).val();
        prevSelectedItems = prevSelectedItems? JSON.parse(prevSelectedItems):{} ;

        prevData = prevData? JSON.parse(prevData) : {};

		if(Object.keys(prevData)==0) {
			prevData = prevSelectedItems;
		}


        delete prevData[product_id];
        setCookie(cookieName, JSON.stringify(prevData), 7);
        $(`input[name=revx-selected-items-${campaign_id}]`).val(JSON.stringify(prevData));

        let qtyData = $(`input[name=revx-qty-data-${campaign_id}]`).val();
        let jsonQtyData = JSON.parse(qtyData);

        if(Object.keys(prevData).length==0) {
            $(`.revx-campaign-${campaign_id} .revx-empty-selected-products`).removeClass('revx-d-none');
            $(`.revx-campaign-${campaign_id} .revx-selected-product-container`).addClass('revx-empty-selected-items');
            $(`.revx-campaign-${campaign_id} .revx-empty-mix-match`).removeClass('revx-d-none');

        }

        updateMixMatchHeaderAndPrices(campaign_id, prevData, jsonQtyData);
    }

    $(".revx-mix-match").on('click','.revx-remove-selected-item', removeMixMatchSelectedItem);

    function updateMixMatchHeaderAndPrices(campaign_id, prevData, jsonQtyData={}) {
        const header = $(`.revx-campaign-${campaign_id} .revx-selected-product-header`);
        const item_counts = Object.keys(prevData).length;
        let qtyData = $(`input[name=revx-qty-data-${campaign_id}]`).val();
        jsonQtyData = JSON.parse(qtyData);

        if (item_counts !== 0 && header.hasClass('revx-d-none')) {
            header.removeClass('revx-d-none');
        } else if(item_counts==0) {
            header.addClass('revx-d-none');
        }
        // const addToCart = $(`.revx-campaign-add-to-cart-btn[data-campaign-id=${campaign_id}]`);
        // if(item_counts==0 && !addToCart.hasClass('revx-d-none') ) {
        //     $(`.revx-campaign-add-to-cart-btn[data-campaign-id=${campaign_id}]`).addClass('revx-d-none');
        // } else if(item_counts>0) {
        //     $(`.revx-campaign-add-to-cart-btn[data-campaign-id=${campaign_id}]`).removeClass('revx-d-none');
        // }
        header.find('.revx-selected-product-count').html(item_counts);

        let totalRegularPrice = 0;
        let totalSalePrice = 0;

        Object.values(prevData).forEach(item => {
            totalRegularPrice += parseFloat(item['regularPrice']) * parseInt(item['quantity']);
        });

        let selectedIndex = -1;
        jsonQtyData.forEach((item, idx) => {

			if (item_counts >= item['quantity']) {
				selectedIndex = idx;

				switch (item.type) {
					case 'percentage':
						totalSalePrice = totalRegularPrice * (1 - (item['value'] / 100));
						break;
					case 'fixed_discount':
						totalSalePrice = Math.max(0,parseFloat(totalRegularPrice) - parseFloat(item['value']*item?.quantity));


						break;
					case 'no_discount':
						totalSalePrice = totalRegularPrice;

						break;

					default:
						break;
				}
			 }


        });

        $(`.revx-mix-match-${campaign_id} .revx-mixmatch-quantity div[data-index=${selectedIndex}]`).attr('style', $(`.revx-mix-match-${campaign_id} .revx-mixmatch-quantity div[data-index=${selectedIndex}]`).data('selected-style'));

        const that = $(`.revx-campaign-${campaign_id} .revx-mixmatch-quantity`);
        that.each(function () {
            var item = $(this).find('.revx-mixmatch-regular-quantity');
            var defaultStyle = item.data('default-style');
            $(item).attr('style', defaultStyle);

            $(item).find('.revx-builder-checkbox').addClass('revx-d-none');

        });

        var clickedItem = that.find(`div[data-index=${selectedIndex}]`);
        var selectedStyle = clickedItem.data('selected-style');
        clickedItem.attr('style', selectedStyle);
        $(clickedItem).find('.revx-builder-checkbox').removeClass('revx-d-none');

        if(totalSalePrice==0) {
            header.find('.revx-campaign-item__sale-price').html(formatPrice(totalRegularPrice));
        } else {
			if(totalSalePrice && header.find('.revx-campaign-item__sale-price').hasClass('revx-d-none')) {
				header.find('.revx-campaign-item__sale-price').removeClass('revx-d-none');
			}
			if(totalRegularPrice && header.find('.revx-campaign-item__regular-price').hasClass('revx-d-none')) {
				header.find('.revx-campaign-item__regular-price').removeClass('revx-d-none');
			}

            header.find('.revx-campaign-item__sale-price').html(formatPrice(totalSalePrice));
            header.find('.revx-campaign-item__regular-price').html(formatPrice(totalRegularPrice));
        }

    }


    // Frequently Bought Together

    // Function to update the styles based on selection
    function updateStyles($checkbox, selected) {
        var selectedStyles = $checkbox.data('selected-style');
        var defaultStyles = $checkbox.data('default-style');
        if (selected) {
            $checkbox.attr('style', selectedStyles);
        } else {
            $checkbox.attr('style', defaultStyles);
        }
    }
    $(".revx-frequently-bought-together").on('click','.revx-item-options .revx-item-option',function(e){
        e.preventDefault();

        var $this = $(this);
        if($this.hasClass('revx-item-required')) {
            return;
        }
        var $checkbox = $this.find('.revx-builder-checkbox');

        const parent =  $this.closest('.revx-campaign-container__wrapper');


        const campaign_id = parent.data('campaign-id');
        const cookieName =  `campaign_${campaign_id}`;
        var selectedProducts = getCookie(cookieName);
        let prevSelectedItems = $(`input[name=revx-fbt-selected-items-${campaign_id}]`).val();
        prevSelectedItems = prevSelectedItems? JSON.parse(prevSelectedItems):{} ;
        selectedProducts = selectedProducts? JSON.parse(selectedProducts) : {};
        if(Object.keys(selectedProducts)==0) {
			selectedProducts = {...prevSelectedItems};
		}



        var productId = $this.data('product-id');



        // Toggle the selected state
        if (selectedProducts[productId]) {
            // selectedProducts = selectedProducts.filter(id => id !== productId);
            delete selectedProducts[productId];
            updateStyles($checkbox, false);
        } else {

            const quantityInput =  $(`input[name=revx-quantity-${campaign_id}-${productId}]`).val() ?? $this.data('min-quantity');

            selectedProducts[productId] = quantityInput;
            updateStyles($checkbox, true);
        }
        $(`input[name=revx-fbt-selected-items-${campaign_id}]`).val(JSON.stringify(selectedProducts));


        // Update the cookie
        setCookie(cookieName, JSON.stringify(selectedProducts), 1);

        fbtCalculation(parent,campaign_id);
    });


    const fbtCalculation = (parent,campaign_id)=>{

        const cookieName =  `campaign_${campaign_id}`;
        var selectedProducts = getCookie(cookieName);
        let prevSelectedItems = $(`input[name=revx-fbt-selected-items-${campaign_id}]`).val();
        prevSelectedItems = prevSelectedItems? JSON.parse(prevSelectedItems):{} ;
        selectedProducts = selectedProducts? JSON.parse(selectedProducts) : {};
        if(Object.keys(selectedProducts)==0) {
			selectedProducts = {...prevSelectedItems};
		}

        const calculateSalePrice = (data,qty=1)=>{
            if(!data?.type) {
                return data.regular_price*qty;
            }
            let total = 0;
            switch (data.type) {
                case 'percentage':
                    total = parseFloat(data.regular_price) * (1 - (data['value'] / 100));

                    break;
                case 'amount':
                case 'fixed_discount':
                    total = Math.max(0,parseFloat(data.regular_price) - parseFloat(data['value']));


                    break;
                case 'fixed_price':
                    total =  parseFloat(data['value']);

                    break;
                case 'no_discount':
                    total =  parseFloat(data.regular_price);

                    break;
                case 'free':
                    total =  0;

                    break;

                default:
                    break;
            }


            return parseFloat(total) * parseInt(qty);
        }
        let offerData = $(`input[name=revx-offer-data-${campaign_id}]`).val();

        offerData = JSON.parse(offerData);


        let totalRegularPrice = 0;
        let totalSalePrice = 0;

        Object.keys(selectedProducts).forEach((id)=>{

            totalRegularPrice+= (parseFloat(offerData[id].regular_price) * parseInt(selectedProducts[id]));
            totalSalePrice+= parseFloat(calculateSalePrice(offerData[id],parseInt(selectedProducts[id])));
        });


		if(totalRegularPrice != totalSalePrice) {
			parent.find(`.revx-triggerProduct .revx-campaign-item__regular-price`).html(formatPrice(totalRegularPrice));
			parent.find(`.revx-triggerProduct .revx-campaign-item__sale-price`).html(formatPrice(totalSalePrice));
		} else {
			parent.find(`.revx-triggerProduct .revx-campaign-item__sale-price`).html(formatPrice(totalSalePrice));
			parent.find(`.revx-triggerProduct .revx-campaign-item__regular-price`).html('');
		}
       parent.find(`.revx-triggerProduct .revx-selected-product-count`).html(Object.keys(selectedProducts).length);

    }

    $(".revx-frequently-bought-together").on('change','input[data-name=revx_quantity]',function(e){
        e.preventDefault();
        const parent =  $(this).closest('.revx-campaign-container__wrapper');


        let quantity = $(this).val();


        const campaign_id = parent.data('campaign-id');


        // addFbtRequiredProductsIfNotAdded(campaign_id,false);
        const product_id = $(this).data('product-id');
        const cookieName =  `campaign_${campaign_id}`;




        var selectedProducts = getCookie(cookieName);
        let prevSelectedItems = $(`input[name=revx-fbt-selected-items-${campaign_id}]`).val();
        prevSelectedItems = prevSelectedItems? JSON.parse(prevSelectedItems):{} ;
        selectedProducts = selectedProducts? JSON.parse(selectedProducts) : {};
        if(Object.keys(selectedProducts)==0) {
			selectedProducts = {...prevSelectedItems};
		}


        if(selectedProducts[product_id]) {
            selectedProducts[product_id] = quantity;


            setCookie(cookieName, JSON.stringify(selectedProducts), 1);
            fbtCalculation(parent,campaign_id);
        }

        $(`input[name=revx-fbt-selected-items-${campaign_id}]`).val(JSON.stringify(selectedProducts));




    });

    $(".revx-buyx-gety").on('change','input[data-name=revx_quantity]',function(e){
        e.preventDefault();
        const parent =  $(this).closest('.revx-campaign-container');




        let quantity = $(this).val();


        const campaign_id = parent.data('campaign-id');

        const product_id = $(this).data('product-id');


        let offerData = $(`input[name=revx-offer-data-${campaign_id}]`);


        offerData = offerData[0].value;
        let jsonData = JSON.parse(offerData);

        let regularPrice = (jsonData[product_id]['regular_price'] * quantity).toFixed(2);
        let salePrice = calculateCurrentPrice(jsonData, product_id, quantity).toFixed(2);




        const item =  $(this).closest('.revx-campaign-item__content');

        item.find('.revx-campaign-item__regular-price').text(formatPrice(regularPrice));
        item.find('.revx-campaign-item__sale-price').text(formatPrice(salePrice));

        if (regularPrice == salePrice) {
			if(!item.find('.revx-campaign-item__regular-price').hasClass('revx-d-none')) {
				item.find('.revx-campaign-item__regular-price').addClass('revx-d-none');
			}
        } else {
			if(item.find('.revx-campaign-item__regular-price').hasClass('revx-d-none')) {
				item.find('.revx-campaign-item__regular-price').removeClass('revx-d-none');
			}
        }



        let totalRegularPrice = 0;
        let totalSalePrice= 0;

        Object.keys(jsonData).forEach((pid)=>{
            let qty = $(`input[name=revx-quantity-${campaign_id}-${pid}]`).val();
            let rp = (jsonData[pid]['regular_price'] * qty).toFixed(2);
            let sp = parseFloat(calculateCurrentPrice(jsonData, pid, qty).toFixed(2));
            totalRegularPrice += parseFloat(rp);
            totalSalePrice +=  parseFloat(sp);

        });

        parent.find('.revx-total-price .revx-campaign-item__regular-price ').html(formatPrice(totalRegularPrice));
        parent.find('.revx-total-price .revx-campaign-item__sale-price ').html(formatPrice(totalSalePrice));

    });

    $(".revx-campaign-add-to-cart-btn").on('click', function (e) {
        e.preventDefault();

        const that = $(this);

        // Gather necessary data
        let qty = e.target.dataset?.quantity ?? 0;
        // if (!qty) {
        //     qty = $(`input[name=revx-quantity-${e.target.dataset.campaignId}-${e.target.dataset.productId}]`).val() ?? 1;
        // }

        let campaignId = e.target.dataset.campaignId;
        let productId = e.target.dataset.productId;
        let campaignType = $(this).data('campaign-type');
        let requiredProduct = e.target.dataset.requiredProduct;
        let campaignSrcPage = $(this).data('campaign_source_page');



		let offer_index = that.data('offer-index')?? 0;



        // Prepare data object for AJAX request
        let data = {
            action: 'revenue_add_to_cart',
            productId: productId,
            campaignId: campaignId,
            _wpnonce: revenue_campaign.nonce,
            quantity: qty,
			campaignSourcePage: campaignSrcPage
        };

        if (campaignType === 'buy_x_get_y') {
            // Adjust data structure if campaign type is 'buy_x_get_y'
            // data['quantity'] = {[productId]: 1};
			data['productId'] = $('.single_add_to_cart_button').val();
        }
        if (campaignType === 'volume_discount') {
            // Adjust data structure if campaign type is 'buy_x_get_y'
            // data['quantity'] = {[productId]: 1};

			let volume_quantity = $(this).closest('.revx-volume-discount').find('.revx-campaign-item[data-revx-selected=true]').data('quantity');


			data['quantity'] = volume_quantity;
        }

        if (campaignType === 'frequently_bought_together') {

            // Add requiredProduct to data if campaign type is 'frequently_bought_together'
            data['requiredProduct'] = productId;

            let cookieName = `campaign_${campaignId}`;

            let fbtData = JSON.parse(getCookie(cookieName));

            data['fbt_data'] = fbtData;

        }

        if(campaignType=='mix_match') {


			let cookieName = `mix_match_${campaignId}`;
			let prevData = getCookie(cookieName);

			let prevSelectedItems = $(`input[name=revx-selected-items-${campaignId}]`).val();
			prevSelectedItems = prevSelectedItems? JSON.parse(prevSelectedItems):{} ;

			prevData = prevData? JSON.parse(prevData) : {};

			if(Object.keys(prevData)==0) {
				prevData = prevSelectedItems;
			}

            let mixMatchData = prevData;
            let mixMatchProducts = {};
            Object.values(mixMatchData).forEach((item) =>{
                mixMatchProducts[item['id']]=item['quantity'];
            })

            data['mix_match_data'] = mixMatchProducts;

        }



        if(campaignType=='buy_x_get_y') {
            let offerData = $(`input[name=revx-offer-data-${campaignId}]`);
            offerData = offerData[0].value;
            let jsonData = JSON.parse(offerData);

            let bxgy_data ={};



            Object.keys(jsonData).forEach((pid)=>{
                let qty = $(`input[name=revx-quantity-${campaignId}-${pid}]`).val();
                bxgy_data[pid] = qty;
            });

            data['bxgy_data'] = bxgy_data;


        }

        // Add revx-loading class to button to show loading state
        $(this).addClass('revx-loading').text('Adding...');

        // Make AJAX request
        $.ajax({
            url: revenue_campaign.ajax,
            data: data,
            type: 'POST',
            success: function (response) {
				$(`.revx-campaign-${campaignId}`).trigger("revx_added_to_cart");
                // Handle success response
                if (response && response.data && response.data.on_cart_action === 'hide_products') {
                    const target = $(`#revenue-campaign-item-${productId}-${campaignId}`);
                    const parent = target.parent();
                    target.hide('slow', function () {
                        target.remove();
                        if (parent.length === 1) {
                            parent.parent().remove();
                        }
                    });
                }

                if(campaignType=='mix_match') {
					let cookieName = `mix_match_${campaignId}`;

					$(`input[name=revx-selected-items-${campaignId}]`).val(JSON.stringify({}));
					setCookie(cookieName, JSON.stringify({}), 7);
					updateMixMatchHeaderAndPrices(campaignId, {});
					$(`.revx-campaign-${campaignId}`).find('.revx-selected-item').each(function(){
						if(!$(this).hasClass('revx-d-none')) {
							$(this).remove();
						}
					});

					$(`.revx-campaign-${campaignId} .revx-empty-selected-products`).removeClass('revx-d-none');
					$(`.revx-campaign-${campaignId} .revx-selected-product-container`).addClass('revx-empty-selected-items');
					$(`.revx-campaign-${campaignId} .revx-empty-mix-match`).removeClass('revx-d-none');
					// $(`.revx-campaign-${campaignId} .revx-selected-product-container`).addClass('revx-d-none');

                }
                if(campaignType=='frequently_bought_together') {

					let hasRequired = false;

                    const parent =  $(that).closest('.revx-campaign-container');

                    $(`.revx-campaign-${campaignId}`).find('.revx-builder-checkbox').each(function() {
                       if(!$(this).parent().hasClass('revx-item-required')) {
                           updateStyles($(this), false);
                       } else {
							hasRequired=true;
					   }
                    });

					if(hasRequired) {
						$(`input[name=revx-fbt-selected-items-${campaignId}]`).val(JSON.stringify({[productId]:1}));
						setCookie(`campaign_${campaignId}`, JSON.stringify({[productId]:1}), 1);
					} else {
						$(`input[name=revx-fbt-selected-items-${campaignId}]`).val(JSON.stringify({}));
						setCookie(`campaign_${campaignId}`, JSON.stringify({}), 1);
					}
					fbtCalculation(parent,campaignId);
                }

				if(that.hasClass('revx-builder-atc-skip')) {
					// Remove revx-loading class and update button text to 'Added to Cart'
					window.location.replace(revenue_campaign.checkout_page_url);

				} else {
					// Remove revx-loading class and update button text to 'Added to Cart'
					// that.removeClass('revx-loading').text('Added to Cart');
					showToast('Added to cart');
                    setTimeout(()=>{
                        that.removeClass('revx-loading').text('Add to Cart');
                    },100);
				}

				if(response?.data?.is_reload) {
					location.reload();
				}
            },
            error: function (jqXHR, textStatus, errorThrown) {
                // Handle error
                console.error('Error:', textStatus, errorThrown);
                that.removeClass('revx-loading').text('Add to Cart');
            }
        });
    });

    $(".revenue-campaign-add-bundle-to-cart").on('click', function (e) {
        e.preventDefault();
        const that = $(this);

        let qty = $(this).data("quantity");

		let quantitySelector = $(this).parent().find('input[data-name="revx_quantity"]').val();

		if(quantitySelector) {
			qty = quantitySelector ?? 1;
		}
        if (e.target.dataset?.campaignSrc === 'popup') {

        }

		let campaignSrcPage = $(this).data('campaign_source_page');


        $(this).addClass('revx-loading').text('Adding...');

		let campaign_id = e.target.dataset.campaignId;

		let trigger_product_id = $('.single_add_to_cart_button').val();

		if(!trigger_product_id) {
			trigger_product_id = $(`input[name=revx-trigger-product-id-${campaign_id}]`).val();
		}

        $.ajax({
            url: revenue_campaign.ajax,
            data: {
                action: 'revenue_add_bundle_to_cart',
                campaignId: campaign_id,
                _wpnonce: revenue_campaign.nonce,
				trigger_product_id: trigger_product_id,
                quantity: qty,
				campaignSrcPage: campaignSrcPage
            },
            type: 'POST',
            success: function (response) {

				$(`.revx-campaign-${campaign_id}`).trigger("revx_added_to_cart");

                // if('hide_products'==response.data.on_cart_action) {
                // 	const target = $(e.target).parent();
                // 	target.hide('slow', function(){ target.remove(); });
                // }
				if(that.hasClass('revx-builder-atc-skip')) {
					// Remove revx-loading class and update button text to 'Added to Cart'
					window.location.replace(revenue_campaign.checkout_page_url);

				} else {

					// Remove revx-loading class and update button text to 'Added to Cart'
					// that.removeClass('revx-loading').text('Added to Cart');
					showToast('Added to cart');
                    setTimeout(()=>{
                        that.removeClass('revx-loading').text('Add to Cart');
                    },100);
				}

				if(response?.data?.is_reload) {
					location.reload();
				}

            },
            error: function (jqXHR, textStatus, errorThrown) {
                that.removeClass('revx-loading').text('Add to Cart');

            },
        });
    });


	const padWithZero = (num) => num.toString().padStart(2, '0');


	// Countdown Timer-----------------

	// Declaration

	const countdown = ()=>{
		try {
            if (revenue_campaign) {
                let countDownData = Object.keys(revenue_campaign.data);



                countDownData.forEach((campaignID) => {
					let _data = revenue_campaign?.data?.[campaignID]?.['countdown_data'];

                    let startTime = _data?.start_time ? new Date(_data.start_time).getTime() : null;
                    let endTime = _data?.end_time? new Date(_data.end_time).getTime():null;
                    let now = new Date().getTime();

                    if (startTime && startTime > now) {
                        return; // Skip if the campaign hasn't started yet
                    }

                    if (endTime < now) {
                        return; // Skip if the campaign has already ended
                    }

                    // Function to update the countdown timer
                    let updateCountdown = function() {
                        now = new Date().getTime();
                        let distance = endTime - now;

                        if (distance < 0) {
                            clearInterval(interval);
							$(`#revx-countdown-timer-${campaignID}`).addClass('revx-d-none'); // Hide the element
                            return;
                        }

                        // Calculate days, hours, minutes, and seconds
                        let days = Math.floor(distance / (1000 * 60 * 60 * 24));
                        let hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                        let minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                        let seconds = Math.floor((distance % (1000 * 60)) / 1000);

                        // Update the HTML elements
                        $(`#revx-countdown-timer-${campaignID} .revx-days`).text(padWithZero(days));
                        $(`#revx-countdown-timer-${campaignID} .revx-hours`).text(padWithZero(hours));
                        $(`#revx-countdown-timer-${campaignID} .revx-minutes`).text(padWithZero(minutes));
                        $(`#revx-countdown-timer-${campaignID} .revx-seconds`).text(padWithZero(seconds));


                    };

                    // Call the updateCountdown function initially to set the first values
                    updateCountdown();

                    // Update the countdown every second
                    let interval = setInterval(updateCountdown, 1000);

                     // Show the countdown timer only after the initial values are set
                     $(`#revx-countdown-timer-${campaignID}`).removeClass('revx-d-none');
                });
            }
        } catch (error) {
        }
	}

	// Call
	countdown();

	//--------------- Countdown Timer


	// Slider -----------------------


	function checkOverflow(container) {
		$(container).each(function () {
			var $this = $(this);

			var isOverflowing = $this[0].scrollWidth > $this[0].offsetWidth;

			if (isOverflowing) {
				$(this).find('.revx-builderSlider-icon').addClass('revx-has-overflow');

			} else {
				$(this).find('.revx-builderSlider-icon').removeClass('revx-has-overflow');
			}
		});
	}



    function initializeSlider($sliderContainer, $containerSelector='.revx-inpage-container',$campaign_type='') {

		const $container = $sliderContainer.closest($containerSelector);
		const containerElement = $container.get(0);
		const computedStyle    = getComputedStyle(containerElement);
		let gridColumnValue    = computedStyle.getPropertyValue('--revx-grid-column').trim();
		let itemGap 		   = parseInt(computedStyle.getPropertyValue('gap').trim());


        if(!itemGap) {
            itemGap = 16;
        }

        const $slides = $sliderContainer.find('.revx-campaign-item');
        const minSlideWidth = 100; // 12rem in pixels (assuming 1rem = 16px)

		let containerWidth = $sliderContainer.parent().width();


		if($campaign_type=='mix_match') {
			containerWidth = $sliderContainer.closest('.revx-slider-items-wrapper').innerWidth();

		}
		if($campaign_type=='bundle_discount') {
			containerWidth = $sliderContainer.closest('.revx-slider-items-wrapper').outerWidth();
            itemGap=0;


		}
		if($campaign_type=='fbt') {
			containerWidth = $container.find(".revx-slider-items-wrapper").innerWidth();
		}
		if($campaign_type=='normal_discount') {
			containerWidth = $container.closest(".revx-slider-items-wrapper").innerWidth();
            itemGap=0;



		}

        let slidesVisible =Math.min(gridColumnValue,Math.floor(containerWidth / minSlideWidth)) ; // Calculate initial slides visible


        let slideWidth = containerWidth / slidesVisible;
        slideWidth -= itemGap;



		if($campaign_type=='bundle_discount') {
			slideWidth-= $container.find('.revx-builder__middle_element').width();
		}


        const totalSlides = $slides.length;
        let slideIndex = 0;

        function updateSlideWidth() {

            containerWidth = $sliderContainer.closest('.revx-slider-items-wrapper').innerWidth();

			slidesVisible =Math.min(gridColumnValue,Math.floor(containerWidth / minSlideWidth)); // Recalculate slides visible
            slideWidth = containerWidth / slidesVisible;
            slideWidth -= itemGap;

			if($campaign_type=='bundle_discount') {
				slideWidth-= $sliderContainer.find('.revx-builder__middle_element').width();
			}

            $slides.css('width', slideWidth + 'px');

            moveToSlide(slideIndex);
        }

        setTimeout(()=>{
            updateSlideWidth();
        });


        function moveToSlide(index) {
            let tempWidth = slideWidth;
            if($campaign_type=='fbt') {
				tempWidth+= $sliderContainer.find('.revx-product-bundle').width();
			}
			if($campaign_type=='bundle_discount') {
				tempWidth+= $sliderContainer.find('.revx-builder__middle_element').width();
			}
			if($campaign_type=='mix_match') {
				tempWidth+=itemGap;
			}
            const offset = -tempWidth * index;

            $sliderContainer.css({
                'transition': 'transform 0.5s ease-in-out',
                'transform': `translateX(${offset}px)`
            });
        }

        function moveToNextSlide() {
            slideIndex++;

            if (slideIndex > totalSlides - slidesVisible) {
                slideIndex = 0;
            }

            moveToSlide(slideIndex);
        }

        function moveToPrevSlide() {
            slideIndex--;

            if (slideIndex < 0) {
                slideIndex = totalSlides - slidesVisible;
            }

            moveToSlide(slideIndex);
        }

        $sliderContainer.siblings('.revx-builderSlider-right').click(function () {
            if (!$sliderContainer.is(':animated')) {
                moveToNextSlide();
            }
        });

        $sliderContainer.siblings('.revx-builderSlider-left').click(function () {
            if (!$sliderContainer.is(':animated')) {
                moveToPrevSlide();
            }
        });

        setTimeout(() => {
            // // const initialWidth = $sliderContainer.width();
            // $sliderContainer.width(containerWidth + 1); // Increase width by 1px
            // $sliderContainer.width(containerWidth); // Reset to original width
            $sliderContainer.parent().width(containerWidth); // Reset to original width
            $sliderContainer.parent().width(containerWidth+1); // Reset to original width
            $(window).trigger('resize'); // Trigger window resize
        });

        $(window).resize(function () {
            updateSlideWidth();
        });

		$sliderContainer.closest('.revx-inpage-container').css('visibility', 'visible');
    }

    function buxXGetYSlider() {
        $('.revx-inpage-container.revx-buyx-gety-grid').each(function () {

            const $container = $(this).find('.revx-campaign-container__wrapper');
            const containerElement = $container.get(0);
            const computedStyle = getComputedStyle(containerElement);

            let gridColumnValue = parseInt(computedStyle.getPropertyValue('--revx-grid-column').trim());
            const minSlideWidth = 132; // 12rem in pixels (assuming 1rem = 16px)

            const $triggerItemContainer = $container.find('.revx-bxgy-trigger-items');
            const $offerItemContainer = $container.find('.revx-bxgy-offer-items');

            let triggerItemColumn = parseInt(getComputedStyle($triggerItemContainer.get(0)).getPropertyValue('--revx-grid-column').trim());
            let offerItemColumn = parseInt(getComputedStyle($offerItemContainer.get(0)).getPropertyValue('--revx-grid-column').trim());

            let containerWidth = $container.width();

			let seperatorWidth = $container.find('.revx-product-bundle').width();

			containerWidth -=seperatorWidth;


            gridColumnValue = Math.min(gridColumnValue, Math.floor(containerWidth / minSlideWidth));
            triggerItemColumn = Math.min($triggerItemContainer.find('.revx-campaign-item').length, triggerItemColumn);
            offerItemColumn = Math.min($offerItemContainer.find('.revx-campaign-item').length, offerItemColumn);

            // Ensure the total columns for trigger and offer items do not exceed the available grid columns
            if (triggerItemColumn + offerItemColumn > gridColumnValue) {
                const excessColumns = (triggerItemColumn + offerItemColumn) - gridColumnValue;

                // Adjust columns proportionally to ensure total columns match gridColumnValue
                const triggerAdjustment = Math.floor((triggerItemColumn / (triggerItemColumn + offerItemColumn)) * excessColumns);
                const offerAdjustment = excessColumns - triggerAdjustment;

                triggerItemColumn -= triggerAdjustment;
                offerItemColumn -= offerAdjustment;
            }


            let slideWidth = (containerWidth / gridColumnValue);


            initializeSubSlider($triggerItemContainer, triggerItemColumn, slideWidth,'trigger');
            initializeSubSlider($offerItemContainer, offerItemColumn, slideWidth,'offer');

            $(this).css('visibility', 'visible');
        });
    }

    function initializeSubSlider($sliderContainer, itemColumn, slideWidth,type) {

		const $container = $sliderContainer.find('.revx-slider-container');
		let itemGap = parseInt(getComputedStyle($container.get(0)).getPropertyValue('gap').trim());

		// slideWidth -=itemGap;
		slideWidth -=itemGap;
		let containerWidth = itemColumn*(slideWidth);
		$sliderContainer.width(containerWidth);



		$sliderContainer = $container;


        const $slides = $sliderContainer.find('.revx-campaign-item');
		$slides.css({'width': slideWidth + 'px'});

        const totalSlides = $slides.length;
        let slideIndex = 0; // Start at the first slide

        function moveToSlide(index) {

			let tempWidth = slideWidth;
			tempWidth +=(itemGap);
			tempWidth+=index;

			if(itemColumn==1) {
				tempWidth+=itemGap;
			}

            let offset = -tempWidth * index;

            $sliderContainer.css({
                'transition': 'transform 0.5s ease-in-out',
                'transform': `translateX(${offset}px)`
            });
        }

        function moveToNextSlide() {
            slideIndex++;
            if (slideIndex > totalSlides - itemColumn) {
                slideIndex = 0;
            }
            moveToSlide(slideIndex);
        }

        function moveToPrevSlide() {
            slideIndex--;
            if (slideIndex < 0) {
                slideIndex = totalSlides - itemColumn;
            }
            moveToSlide(slideIndex);
        }

        $sliderContainer.siblings('.revx-builderSlider-right').click(function () {
            if (!$sliderContainer.is(':animated')) {
                moveToNextSlide();
            }
        });

        $sliderContainer.siblings('.revx-builderSlider-left').click(function () {
            if (!$sliderContainer.is(':animated')) {
                moveToPrevSlide();
            }
        });

        $(window).resize(function () {
            moveToSlide(slideIndex);
        });

        moveToSlide(slideIndex);
    }

    buxXGetYSlider();

    $(window).resize(function () {
        buxXGetYSlider();
    });


	$('.revx-inpage-container.revx-normal-discount-grid .revx-slider-container').each(function () {
		initializeSlider($(this),'.revx-campaign-view__items','normal_discount');
	});
    $('.revx-inpage-container.revx-mix-match-grid .revx-slider-container').each(function () {
        initializeSlider($(this),'.revx-campaign-view__items','mix_match');
    });
    $('.revx-inpage-container.revx-bundle-discount-grid .revx-slider-container').each(function () {
        initializeSlider($(this),'.revx-campaign-view__items','bundle_discount');
    });
    $('.revx-inpage-container.revx-frequently-bought-together-grid .revx-slider-container').each(function () {
        initializeSlider($(this),'.revx-inpage-container','fbt');
    });


	$(window).on('load resize', function() {
		checkOverflow('.revx-slider');
	});


	// ---------------- Slider

})(jQuery);



