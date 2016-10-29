(function($) {
	$(document).ready( function() {

		$('.comment-sticky-link').click( function(e) {

			e.preventDefault();

			var comment_link = $(this);

			var comment_status = comment_link.data('status');
console.log(comment_status);
			comment_link.text(jp_sticky_comments.strings.wait);

			var data = {
					action: 'jp_sticky_comments_stick_comment',
					comment_id: comment_link.data('id'),
					check: jp_sticky_comments.stick_comment_nonce,
					comment_status: comment_status
			};

			$.ajax({
				data: data,
				type: "POST",
				url: jp_sticky_comments.ajaxurl,
				success: function(response) {
					if (1 == response.result) {
						var data_status = 99999 == response.karma ? 'sticky' : 'notsticky';
						console.log('data_status ' + data_status);
						comment_link.attr('data-status',data_status);
						var comment_link_text = 'sticky' === data_status ? jp_sticky_comments.strings.stuck : jp_sticky_comments.strings.unstuck;
						comment_link.text(comment_link_text);
					}
					if ( 0 == response.result || 0 == response) {
						comment_link.text(jp_sticky_comments.strings.error);
					}
				},
				error: function(response) {
					console.dir(response);
					comment_link.text(jp_sticky_comments.strings.error);
				}
			});
		});
	});
})(jQuery);