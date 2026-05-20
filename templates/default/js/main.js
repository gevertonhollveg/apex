$(function() {
	// Initiate Server Time
	serverTime.init("tServerTime", "tLocalTime", "tServerDate", "tLocalDate");
	
	// Initiate bootstrap tooltips
	$('[data-toggle="tooltip"]').tooltip();
	
	// PayPal Buy Credits
	if($('#paypal_conversion_rate_value').length) {
		var paypal_cr = parseInt($('#paypal_conversion_rate_value').html());
		if($('#amount').length) {
			document.getElementById('amount').onkeyup = function(ev) {
				var num = 0;
				var c = 0;
				var event = window.event || ev;
				var code = (event.keyCode) ? event.keyCode : event.charCode;
				for(num=0;num<this.value.length;num++) {
					c = this.value.charCodeAt(num);
					if(c<48 || c>57) {
						document.getElementById('result').innerHTML = '0';
						document.getElementById('amount').value = '';
						return false;
					}
				}
				num = parseInt(this.value);
				if(isNaN(num)) {
					document.getElementById('result').innerHTML = '0';
				} else {
					var result = (paypal_cr*num).toString();
					document.getElementById('result').innerHTML = result;
				}
			}
		}
	}
});

var serverTime = {
	weekDays: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
	monthNames: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
	serverDate: null,
	localDate: null,
	dateOffset: null,
	nowDate: null,
	eleServer: null,
	eleLocal: null,
	eleServerDate: null,
	eleLocalDate: null,
	init: function(e, c, s, l) {
		var f = this;
		f.eleServer = e;
		f.eleLocal = c;
		f.eleServerDate = s;
		f.eleLocalDate = l;
		$.getJSON(baseUrl + "api/servertime.php", function(a) {
			f.serverDate = new Date(a.ServerTime);
			f.localDate = new Date();
			f.dateOffset = f.serverDate - f.localDate;
			document.getElementById(f.eleServer).innerHTML = f.dateTimeFormat(f.serverDate);
			document.getElementById(f.eleLocal).innerHTML = f.dateTimeFormat(f.localDate);
			document.getElementById(f.eleServerDate).innerHTML = f.dateFormat(f.serverDate);
			document.getElementById(f.eleLocalDate).innerHTML = f.dateFormat(f.localDate);
			
			setInterval(function() {
				f.update()
			}, 1000)
		})
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
		var f = [];
		f.push(c.digit(e.getHours()));
		f.push(":");
		f.push(c.digit(e.getMinutes()));
		f.push(":");
		f.push(c.digit(e.getSeconds()));
		return f.join("")
	},
	dateFormat: function(e) {
		var c = this;
		var f = [];
		f.push(c.weekDays[e.getDay()]);
		f.push(" ");
		f.push(c.monthNames[e.getMonth()]);
		f.push(" ");
        f.push(e.getDate());
		return f.join("")
	},
	digit: function(b) {
		b = String(b);
		b = b.length == 1 ? "0" + b : b;
		return b
	}
};

function rankingsFilterByClass() {
	var delay = 500; // milliseconds
	var classList = new Array();
	
	for(var i = 0; i < arguments.length; i++) {
		classList[i] = arguments[i];
	}
	
	if($(".rankings-table").length) {
		$(".rankings-table").fadeOut().delay(delay).fadeIn();
		setTimeout(function() {
			$(".rankings-table tr").each(function() {
				if($(this).attr("data-class-id") == null) { return true; }
				if(classList.includes(parseInt($(this).attr("data-class-id"))) == false) {
					$(this).hide();
				} else {
					$(this).show();
				}
			});
		}, delay);
	}
}

function rankingsFilterRemove() {
	var delay = 500; // milliseconds
	
	$(".rankings-table").fadeOut().delay(delay).fadeIn();
	setTimeout(function() {
		if($(".rankings-table").length) {
			$(".rankings-table tr").each(function() {
					$(this).fadeIn();
				}
			);
		}
	}, delay);
}

$(function() {
	if($(".rankings-class-filter-selection").length) {
		$('a.rankings-class-filter-selection').click(function(){
			$('a.rankings-class-filter-selection').addClass("rankings-class-filter-grayscale");
			$(this).removeClass("rankings-class-filter-grayscale");
		});
	}
});