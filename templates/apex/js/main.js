$(function() {
    // ═══════════ NAVBAR SCROLL ═══════════
    $(window).on('scroll', function() {
        if ($(window).scrollTop() > 50) {
            $('#mainNavbar').addClass('scrolled');
        } else {
            $('#mainNavbar').removeClass('scrolled');
        }
    });

    // ═══════════ MOBILE TOGGLE ═══════════
    $('#mobileToggle').on('click', function() {
        $('#navbarMenu').toggleClass('open');
    });

    // Close menu on link click (mobile)
    $('#navbarMenu a').on('click', function() {
        $('#navbarMenu').removeClass('open');
    });

    // ═══════════ HERO PARTICLES ═══════════
    var particlesContainer = document.getElementById('heroParticles');
    if (particlesContainer) {
        for (var i = 0; i < 35; i++) {
            var p = document.createElement('div');
            p.className = 'particle';
            p.style.left = Math.random() * 100 + '%';
            p.style.animationDuration = (8 + Math.random() * 12) + 's';
            p.style.animationDelay = Math.random() * 10 + 's';
            var size = (1 + Math.random() * 2) + 'px';
            p.style.width = size;
            p.style.height = size;
            particlesContainer.appendChild(p);
        }
    }

    // ═══════════ SERVER TIME ═══════════
    if (document.getElementById('tServerTime')) {
        serverTime.init("tServerTime", "tLocalTime", "tServerDate", "tLocalDate");
    }

    // ═══════════ BOOTSTRAP TOOLTIPS ═══════════
    $('[data-toggle="tooltip"]').tooltip();

    // ═══════════ PAYPAL ═══════════
    if ($('#paypal_conversion_rate_value').length) {
        var paypal_cr = parseInt($('#paypal_conversion_rate_value').html());
        if ($('#amount').length) {
            document.getElementById('amount').onkeyup = function(ev) {
                var num = 0;
                var c = 0;
                var event = window.event || ev;
                var code = (event.keyCode) ? event.keyCode : event.charCode;
                for (num = 0; num < this.value.length; num++) {
                    c = this.value.charCodeAt(num);
                    if (c < 48 || c > 57) {
                        document.getElementById('result').innerHTML = '0';
                        document.getElementById('amount').value = '';
                        return false;
                    }
                }
                num = parseInt(this.value);
                if (isNaN(num)) {
                    document.getElementById('result').innerHTML = '0';
                } else {
                    var result = (paypal_cr * num).toString();
                    document.getElementById('result').innerHTML = result;
                }
            }
        }
    }

    // ═══════════ SMOOTH SCROLL ═══════════
    $('a[href^="#"]').on('click', function(e) {
        var target = $(this.getAttribute('href'));
        if (target.length) {
            e.preventDefault();
            $('html, body').animate({ scrollTop: target.offset().top - 80 }, 600);
        }
    });
});

// ═══════════ AUTH MODAL ═══════════
function openAuthModal(tab) {
    if (!$('#authModal').length) return;
    $('#authModalBackdrop').addClass('active');
    $('#authModal').addClass('active');
    switchAuthTab(tab || 'login');
    $('body').css('overflow', 'hidden');
}

function closeAuthModal() {
    $('#authModalBackdrop').removeClass('active');
    $('#authModal').removeClass('active');
    $('body').css('overflow', '');
}

function switchAuthTab(tab) {
    var $loginForm = $('#loginForm');
    var $registerForm = $('#registerForm');

    if (tab === 'login') {
        $('#tabLogin').addClass('active');
        $('#tabRegister').removeClass('active');
        $('#tabLogin').attr('aria-selected', 'true');
        $('#tabRegister').attr('aria-selected', 'false');
        $loginForm.attr('aria-hidden', 'false');
        $registerForm.attr('aria-hidden', 'true');

        if (!$loginForm.is(':visible')) {
            $registerForm.stop(true, true).fadeOut(120, function() {
                $loginForm.stop(true, true).fadeIn(140, function() {
                    $loginForm.find('input[name="webengineLogin_user"]').trigger('focus');
                });
            });
        } else {
            $loginForm.find('input[name="webengineLogin_user"]').trigger('focus');
        }
    } else {
        $('#tabLogin').removeClass('active');
        $('#tabRegister').addClass('active');
        $('#tabLogin').attr('aria-selected', 'false');
        $('#tabRegister').attr('aria-selected', 'true');
        $loginForm.attr('aria-hidden', 'true');
        $registerForm.attr('aria-hidden', 'false');

        if (!$registerForm.is(':visible')) {
            $loginForm.stop(true, true).fadeOut(120, function() {
                $registerForm.stop(true, true).fadeIn(140, function() {
                    $registerForm.find('input[name="webengineRegister_user"]').trigger('focus');
                });
            });
        } else {
            $registerForm.find('input[name="webengineRegister_user"]').trigger('focus');
        }
    }
}

$(function() {
    $('#authModal form').on('submit', function(e) {
        var $form = $(this);
        if ($form.data('submitting')) {
            e.preventDefault();
            return false;
        }

        $form.data('submitting', true);
        $form.find('button[type="submit"]').addClass('is-loading').prop('disabled', true);
    });
});

// Close modal on ESC
$(document).on('keydown', function(e) {
    if (e.keyCode === 27) closeAuthModal();
});

// ═══════════ SERVER TIME ═══════════
var serverTime = {
    weekDays: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
    monthNames: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
    serverDate: null, localDate: null, dateOffset: null, nowDate: null,
    eleServer: null, eleLocal: null, eleServerDate: null, eleLocalDate: null,
    init: function(e, c, s, l) {
        var f = this;
        f.eleServer = e; f.eleLocal = c; f.eleServerDate = s; f.eleLocalDate = l;
        $.getJSON(baseUrl + "api/servertime.php", function(a) {
            f.serverDate = new Date(a.ServerTime);
            f.localDate = new Date();
            f.dateOffset = f.serverDate - f.localDate;
            document.getElementById(f.eleServer).innerHTML = f.dateTimeFormat(f.serverDate);
            document.getElementById(f.eleLocal).innerHTML = f.dateTimeFormat(f.localDate);
            document.getElementById(f.eleServerDate).innerHTML = f.dateFormat(f.serverDate);
            document.getElementById(f.eleLocalDate).innerHTML = f.dateFormat(f.localDate);
            setInterval(function() { f.update() }, 1000);
        });
    },
    update: function() {
        var b = this;
        b.nowDate = new Date();
        document.getElementById(b.eleLocal).innerHTML = b.dateTimeFormat(b.nowDate);
        b.nowDate.setTime(b.nowDate.getTime() + b.dateOffset);
        document.getElementById(b.eleServer).innerHTML = b.dateTimeFormat(b.nowDate);
    },
    dateTimeFormat: function(e) {
        var c = this;
        return c.digit(e.getHours()) + ":" + c.digit(e.getMinutes()) + ":" + c.digit(e.getSeconds());
    },
    dateFormat: function(e) {
        var c = this;
        return c.weekDays[e.getDay()] + " " + c.monthNames[e.getMonth()] + " " + e.getDate();
    },
    digit: function(b) { b = String(b); return b.length == 1 ? "0" + b : b; }
};

// ═══════════ RANKINGS FILTER ═══════════
function rankingsFilterByClass() {
    var delay = 500;
    var classList = Array.from(arguments);
    if ($(".rankings-table").length) {
        $(".rankings-table").fadeOut().delay(delay).fadeIn();
        setTimeout(function() {
            $(".rankings-table tr").each(function() {
                if ($(this).attr("data-class-id") == null) return true;
                if (!classList.includes(parseInt($(this).attr("data-class-id")))) {
                    $(this).hide();
                } else {
                    $(this).show();
                }
            });
        }, delay);
    }
}

function rankingsFilterRemove() {
    var delay = 500;
    $(".rankings-table").fadeOut().delay(delay).fadeIn();
    setTimeout(function() {
        $(".rankings-table tr").each(function() { $(this).fadeIn(); });
    }, delay);
}

function rankingsApplyClassFilter(selectElement) {
    if (!selectElement) {
        rankingsFilterRemove();
        return;
    }

    var rawValue = (selectElement.value || '').trim();
    if (rawValue === '') {
        rankingsFilterRemove();
        return;
    }

    var classList = rawValue.split(',').map(function(item) {
        return parseInt(item, 10);
    }).filter(function(item) {
        return !isNaN(item);
    });

    if (classList.length === 0) {
        rankingsFilterRemove();
        return;
    }

    rankingsFilterByClass.apply(null, classList);
}

$(function() {
    if ($('#rankingsClassFilter').length) {
        $('#rankingsClassFilter').on('change', function() {
            rankingsApplyClassFilter(this);
        });
    }

    if ($(".rankings-class-filter-selection").length) {
        $('a.rankings-class-filter-selection').click(function() {
            $('a.rankings-class-filter-selection').addClass("rankings-class-filter-grayscale");
            $(this).removeClass("rankings-class-filter-grayscale");
        });
    }
});