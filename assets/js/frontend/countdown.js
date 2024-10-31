(function($) {
    'use strict';

    $(document).ready(function() {
        try {
            if (revenue_countdown) {
                let countDownData = Object.keys(revenue_countdown.data);

                countDownData.forEach((campaignID) => {
                    let startTime = revenue_countdown.data[campaignID].start_time ? new Date(revenue_countdown.data[campaignID].start_time).getTime() : null;
                    let endTime = new Date(revenue_countdown.data[campaignID].end_time).getTime();
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
                        $(`#revx-campaign-countdown-${campaignID} .revx-day`).text(days);
                        $(`#revx-campaign-countdown-${campaignID} .revx-hour`).text(hours);
                        $(`#revx-campaign-countdown-${campaignID} .revx-minute`).text(minutes);
                        $(`#revx-campaign-countdown-${campaignID} .revx-second`).text(seconds);


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
    });
})(jQuery);
