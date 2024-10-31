jQuery(document).ready(function($) {

    // Floating Behavior type
    // on scroll, delay

    var floatingElements = $('.revx-floating-main');
    var currentIndex = 0;

    function showFloatingElement(index) {
        if (index >= floatingElements.length) return;

        var $element = $(floatingElements[index]);
        var scrollPercentage = parseInt($element.data('floating-scroll')) || 0;
        var spendingTime = parseInt($element.data('spending-time')) || 0;
        var autoCloseTime = parseInt($element.data('auto-close')) || 0;
        var positionClass = $element.data('position-class') || 'revx-floating-bottom-right';
        var delay = $element.data('animation-delay') || 0;

		function handleVisibility() {

			$element.addClass('floating-visible');
			$element.addClass(positionClass);
			$element.css('visibility','visible');

			$('.revx-floating.revx-normal-discount-grid .revx-slider-container').each(function () {
				initializeSlider($(this), 'normal_discount');
			});

			$('.revx-floating.revx-bundle-discount-grid .revx-slider-container').each(function () {
				initializeSlider($(this), 'bundle_discount');
			});

			$('.revx-floating.revx-frequently-bought-together-grid .revx-slider-container').each(function () {
				initializeSlider($(this), 'fbt');
			});

			$('.revx-floating.revx-mix-match .revx-slider-container').each(function () {
				initializeSlider($(this), 'mix_match');
			});

			buxXGetYSlider();

				$(window).resize(function () {
					buxXGetYSlider();
				});

			checkOverflow('.revx-floating .revx-slider');

			// Check on window resize
			$(window).resize(function () {
				checkOverflow('.revx-floating .revx-slider');
			});

			if (autoCloseTime > 0) {
				var autoCloseTimeout = setTimeout(function () {
					closeFloatingElement();
				}, autoCloseTime * 1000);
			}

			$element.find('.revx-campaign-close').on('click', function () {
				clearTimeout(autoCloseTimeout); // Cancel auto-close if user clicks the close icon
				closeFloatingElement();
			});
			$element.find('.revx-builder-noThanks-btn').on('click', function () {
				clearTimeout(autoCloseTimeout); // Cancel auto-close if user clicks the close icon
				closeFloatingElement();
			});
		}


        function closeFloatingElement() {
            $element.removeClass(positionClass);
            $element.removeClass('floating-visible');
            $element.hide(100);
            currentIndex++;
            setTimeout(function() {
                showFloatingElement(currentIndex);
            }, 5000); // 5s delay before showing the next element
        }

        setTimeout(function () {
            if (scrollPercentage > 0) {
                $(window).on('scroll.floatingElement' + index, function () {
                    var scrollPosition = $(window).scrollTop();
                    var documentHeight = $(document).height() - $(window).height();
                    var currentScroll = (scrollPosition / documentHeight) * 100;

                    if (currentScroll >= scrollPercentage) {
                        $(window).off('scroll.floatingElement' + index);
                        setTimeout(handleVisibility, spendingTime * 1000);
                    }
                });
            } else {
                setTimeout(handleVisibility, spendingTime * 1000);
            }
        }, delay * 1000);

    }

    // Start with the first floating element
    showFloatingElement(currentIndex);


	function checkOverflow(container) {
		$(container).each(function () {
			var $this = $(this);


			// var isOverflowing = $this[0].scrollWidth > $this[0].offerWidth;

			var isOverflowing = $this[0].scrollWidth > $this[0].offsetWidth;

			if (isOverflowing) {
				$(this).closest('.revx-floating').find('.revx-builderSlider-icon').addClass('revx-has-overflow');
			} else {
				$(this).closest('.revx-floating').find('.revx-builderSlider-icon').removeClass('revx-has-overflow');
			}
		});
	}

	function initializeSlider($sliderContainer,type) {

		const $container = $sliderContainer.closest('.revx-floating');

		const containerElement = $container.get(0);

		const computedStyle = getComputedStyle(containerElement);
		let gridColumnValue = computedStyle.getPropertyValue('--revx-grid-column').trim();

		if(!gridColumnValue) {
			gridColumnValue = 3;
		}

        const $slides = $sliderContainer.find('.revx-campaign-item');
        let minSlideWidth = 10 * 16; // 12rem in pixels (assuming 1rem = 16px)

		if(type=='fbt') {
			minSlideWidth = 8 * 16;
		}
        let containerWidth = $sliderContainer.parent().width();

		if(type=='normal_discount') {

		} else if(type=='bundle_discount') {
			containerWidth = $sliderContainer.closest('.revx-campaign-container__wrapper').innerWidth();
		} else if(type=='fbt') {
			containerWidth = $container.find(".revx-regular-product").width();

		}
        let slidesVisible =Math.min(gridColumnValue,Math.floor(containerWidth / minSlideWidth)) ; // Calculate initial slides visible
        let slideWidth = containerWidth / slidesVisible;
		if(type=='fbt') {
            slideWidth-= $container.find('.revx-product-bundle').width();
		}
        const totalSlides = $slides.length;
        let slideIndex = 0; // Start at the first slide

        // Function to update the slide width based on the container width
        function updateSlideWidth() {
            containerWidth = $sliderContainer.parent().width();

			if(type=='normal_discount') {

			} else if(type=='bundle_discount') {
				containerWidth = $sliderContainer.closest('.revx-campaign-container__wrapper').innerWidth();
			} else if(type=='fbt') {
				containerWidth = $container.find(".revx-regular-product").width();

			}
            slidesVisible =Math.min(gridColumnValue,Math.floor(containerWidth / minSlideWidth)); // Recalculate slides visible
            slideWidth = containerWidth / slidesVisible;

			if(type=='fbt') {
                slideWidth-= $sliderContainer.find('.revx-product-bundle').width();

                // $container.find('.revx-fbt-options').css('width',slideWidth + 'px');
            }
            $slides.css('width', slideWidth + 'px');
            moveToSlide(slideIndex); // Adjust the current slide position based on the new width
        }

        // Set the initial width of each slide
        updateSlideWidth();

        function moveToSlide(index) {
			let tempWidth = slideWidth;
            if(type=='fbt') {
                tempWidth+= $sliderContainer.find('.revx-product-bundle').width();
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

        // Event listeners for the navigation buttons
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

        // Event listener for window resize to update slide width
        $(window).resize(function () {
            updateSlideWidth();
        });


    }

	function buxXGetYSlider() {
		$('.revx-floating.revx-buyx-gety-grid').each(function () {

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

			$container.css('visibility', 'visible');
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
});
