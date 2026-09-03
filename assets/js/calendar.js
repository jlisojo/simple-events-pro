(function () {
    'use strict';

    function navigateMonth(container, month) {
        var request = new XMLHttpRequest();
        request.open('POST', window.simpleEventsProCalendar.ajaxUrl, true);
        request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        request.onload = function () {
            if (request.status !== 200) {
                return;
            }

            var replacement = document.createElement('div');
            replacement.innerHTML = request.responseText;
            var calendar = replacement.querySelector('.se-pro-calendar');
            var current = container.querySelector('.se-pro-calendar');

            if (calendar && current) {
                current.parentNode.replaceChild(calendar, current);
                container.dataset.month = month;
            }
        };
        request.send(
            'action=simple_events_pro_calendar_month&month=' + encodeURIComponent(month) +
            '&nonce=' + encodeURIComponent(window.simpleEventsProCalendar.nonce)
        );
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('.se-pro-calendar__prev, .se-pro-calendar__next');
        if (!button) {
            return;
        }

        var container = button.closest('.se-pro-calendar-container');
        if (!container || !button.dataset.date) {
            return;
        }

        event.preventDefault();
        navigateMonth(container, button.dataset.date);
    });
})();
