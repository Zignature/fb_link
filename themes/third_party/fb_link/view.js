//
// ACS Bridge 1.5
// http://www.hicksondesign.com
//
// Copyright 2012, Ron Hickson
//

(function ($) {
	$.fn.extend({
		acsView: function (settings) {
			// Options and default values
			var defaults = {
				placeId: "acs_events"
			};
			var settings = $.extend(defaults, settings),
				divId = '#' + this.attr('id'),
				divH = this.height(),
				divW = this.width(),
				winH,
				winW,
				dialogH,
				dialogW,
				docH,
				docW,
				loaderShow,
				position,
				fetch;
				
			if (typeof settings.template !== 'undefined') {

				// Get data
				fetch = function () {
					$.ajaxSetup({
						beforeSend: function(xhr) {
        					xhr.setRequestHeader('Cache-Control', 'max-age=600');
        					xhr.setRequestHeader('Pragma', 'cache');
        				}
					});

					$.ajax({
						url: settings.template,
						dataType: "html",
						success: function (data) {
							$('#' + settings.placeId).hide().html(data).fadeTo('slow', 1);

							$('.' + settings.prev).on('click', function () {
								$('#' + settings.placeId).fadeTo('slow', 0.2);
								fetch(prevTime);
							});
							$('.' + settings.next).on('click', function () {
								$('#' + settings.placeId).fadeTo('slow', 0.2);
								fetch(nextTime);
							});
							return false;
						}
					});
				};

				// Create the loading div on the page
				fetch(settings);
				$(window).resize(function () {
					position();
				});
			}
		}	
	});

}(jQuery));