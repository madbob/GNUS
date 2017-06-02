$(document).ready(function() {
	$('#adding').submit(function(e) {
		e.preventDefault();
		var form = $(this);
		var url = form.find('input:text[name=url]').val();
		if (url == '') {
			alert('You have to specify a valid URL for the feed to add');
			return false;
		}
		
		form.find('button').attr('disabled', 'disabled').addClass('is-loading');

		$.ajax({
			method: $(this).attr('method'),
			url: $(this).attr('action'),
			dataType: 'json',

			data: {
				action: 'add',
				url: url
			},

			success: function(data) {
				var feedback;
				var b = form.find('button');
				
				if (data.status == 'error') {
					feedback = $('<div class="notification is-danger">' + data.message + '</div>');
					b.removeAttr('disabled').removeClass('is-loading').parent();
				}
				else {
					feedback = $('<div class="notification is-success">' + data.message + '</div>');
					location.reload();
				}
				
				b.after(feedback);
			},
			
			error: function() {
				var b = form.find('button');
				b.removeAttr('disabled').removeClass('is-loading').parent();

				feedback = $('<div class="notification is-danger">An unspecificed error occourred</div>');
				b.after(feedback);
			}
		});

		return false;
	});
});

