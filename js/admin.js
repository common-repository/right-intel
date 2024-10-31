(function($) { "use strict";
	function setupConfirmMessages() {
		// setup confirm messages
		$('.ri-confirm').on('click', function(evt) {
			if (!confirm(this.getAttribute('data-confirm-msg') || 'Are you sure?')) {
				evt.preventDefault();
			}
		});
	}
	function setupPostPreview() {
		var $frame = $('#FramePostPreview');
		function updatePreview() {
			var src = $frame.prop('src').split('?');
			var base = src[0];
			var query = src[1].replace(/(\w+)=(.+?)(&|$)/g, function($0, $1, $2, $3) {
				if ($1 == 'cachebust') {
					return 'cachebust=' + (+new Date);
				}
				var $input = $('[name="' + $1 + '"]');
				if ($input.length === 0) {
					return $0;
				}
				return $1 + '=' + encodeURIComponent($input.val()) + $3;
			});			
			$frame.prop('src', base + '?' + query);
		}
		if ($frame.length > 0) {
			$('#FormStylingOptions select').on('change', updatePreview);
			$('#FormStylingOptions input').on('blur paste', updatePreview);
			updatePreview();
		}
	}
	$(setupConfirmMessages);
	$(setupPostPreview);
}(jQuery));
