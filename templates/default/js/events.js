$(function() {
    loadEventSchedule();
});

// ═══════════ FLOATING EVENTS POPUP ═══════════
function toggleEventsPopup() {
    $('#eventsPopup').toggleClass('open');
}
// Close popup when clicking outside
$(document).on('click', function(e) {
    if (!$(e.target).closest('#eventsPopup, #eventsToggle').length) {
        $('#eventsPopup').removeClass('open');
    }
});

function loadEventSchedule() {
    $.getJSON(baseUrl + "api/events.php", function(data) {
        var popupHtml = '';
        var hasEvents = false;

        $.each(data, function(key, val) {
            hasEvents = true;
            // Build popup row
            popupHtml += '<div class="event-row" id="popup_row_' + key + '">';
            popupHtml += '<div>';
            popupHtml += '<div class="event-name" id="popup_' + key + '_name">' + val.event + '</div>';
            popupHtml += '<div class="event-meta">Next: <span id="popup_' + key + '_next">' + val.nextF + '</span></div>';
            popupHtml += '</div>';
            popupHtml += '<div class="event-timer"><div class="event-countdown" id="popup_' + key + '">--:--:--</div></div>';
            popupHtml += '</div>';

            // Also update inline table if it exists (kept for compatibility)
            if ($('#' + key).length) {
                eventSchedule(key, val.opentime, val.duration, val.offset, val.timeleft, true);
                document.getElementById(key + '_name').innerHTML = val.event;
                document.getElementById(key + '_next').innerHTML = val.nextF;
            } else {
                // Only popup mode
                eventSchedule(key, val.opentime, val.duration, val.offset, val.timeleft, false);
            }
        });

        if (hasEvents) {
            $('#eventsPopupBody').html(popupHtml);
        } else {
            $('#eventsPopupBody').html('<div style="padding:20px;text-align:center;color:var(--text-muted);font-size:13px;">No events configured.</div>');
        }
    }).fail(function() {
        $('#eventsPopupBody').html('<div style="padding:20px;text-align:center;color:var(--text-muted);font-size:13px;">Could not load events.</div>');
    });
}

function eventSchedule(eventId, openTime, duration, offset, timeLeft, hasInline) {
    var eHours = null, eMinutes = null, eSeconds = null, eDays = null;

    function init() {
        setInterval(function() { update(); }, 1000);
    }

    function reloadEventInfo() {
        $.getJSON(baseUrl + "api/events.php?event=" + eventId, function(data) {
            openTime = data.opentime;
            duration = data.duration;
            offset = data.offset;
            timeLeft = data.timeleft;
            document.getElementById(eventId + '_name').innerHTML = data.event;
            document.getElementById(eventId + '_next').innerHTML = data.nextF;
        });
    }

    function update() {
        if (timeLeft >= 1) {
            var days_module = timeLeft % 86400;
            eDays = (timeLeft - days_module) / 86400;
            var hours_module = days_module % 3600;
            eHours = (days_module - hours_module) / 3600;
            var minutes_module = hours_module % 60;
            eMinutes = (hours_module - minutes_module) / 60;
            eSeconds = minutes_module;

            if (eMinutes < 10) eMinutes = '0' + eMinutes;
            if (eSeconds < 10) eSeconds = '0' + eSeconds;
        } else {
            eDays = '0'; eHours = '0'; eMinutes = '00'; eSeconds = '00';
            reloadEventInfo();
        }

        if (openTime > 0) {
            if (offset - timeLeft < openTime) {
                var openHtml = '<span style="color:var(--accent-green);font-weight:600">Open</span>';
                if (hasInline && document.getElementById(eventId)) document.getElementById(eventId).innerHTML = openHtml;
                var popupElO = document.getElementById('popup_' + eventId);
                if (popupElO) popupElO.innerHTML = openHtml;
                timeLeft--;
                return;
            }
        } else {
            if (duration > 0) {
                if (offset - timeLeft < duration) {
                    var inProgHtml = '<span style="color:var(--accent-blue);font-weight:600">In Progress</span>';
                    if (hasInline && document.getElementById(eventId)) document.getElementById(eventId).innerHTML = inProgHtml;
                    var popupElP = document.getElementById('popup_' + eventId);
                    if (popupElP) popupElP.innerHTML = inProgHtml;
                    timeLeft--;
                    return;
                }
            }
        }

        var timeStr;
        if (eHours == '00' && eMinutes == '00') {
            timeStr = eSeconds + " sec";
        } else if (eDays > 0) {
            timeStr = eDays + "d " + eHours + "h " + eMinutes + "m " + eSeconds + "s";
        } else {
            timeStr = eHours + "h " + eMinutes + "m " + eSeconds + "s";
        }

        // Update inline table (if present)
        if (hasInline && document.getElementById(eventId)) {
            document.getElementById(eventId).innerHTML = timeStr;
        }
        // Update popup
        var popupEl = document.getElementById('popup_' + eventId);
        if (popupEl) popupEl.innerHTML = timeStr;

        timeLeft--;
    }

    init();
}