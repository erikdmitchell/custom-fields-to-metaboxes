jQuery(document).ready(function($) {

	// step one submit - take post type and generate metabox list
	$('#step_1_submit').click(function (e) {
		e.preventDefault();

		var postType=$('#get-post-type input[type="radio"]:checked').val();
		var data={
			'action' : 'get_metaboxes',
			'post_type' : postType
		};

		if (typeof postType==='undefined')
			return false;

		$.post(ajaxurl,data,function(response) {
			var data=$.parseJSON(response);

			// clear debug fields and hide //
			$('#debug_notices #notices .notices-wrap').text('');
			$('#debug_notices').removeClass('active');

			$('#cftmb-admin-notices').text(''); // clear our admin notices
			$('.admin-options').removeClass('active'); // hide options after main page
			$('#step-1').removeClass('active'); // hide step 1
			$('#step-2').addClass('active'); // show step 2

			$('#metabox-list').text(''); // clear list
			$('#metabox-list').append(data.metabox_list_html); // append metaboxes
			$('#metabox-list').append(data.post_type_html); // append post type
		});
	});

	// step two submit - take post type, metabox and generate our matching fields stuff
	$('#step_2_submit').click(function (e) {
		e.preventDefault();

		var metaboxID=$('#get-metabox input[type="radio"]:checked').val();
		var postType=$('#step-2 #post_type').val();
		var data={
			'action' : 'get_fields_to_map',
			'post_type' : postType,
			'metabox_id' : metaboxID
		};

		if (typeof metaboxID==='undefined')
			return false;

		$.post(ajaxurl,data,function(response) {
			var data=$.parseJSON(response);

			$('#step-2').removeClass('active');
			$('#step-3').addClass('active');

			$('#step-3 > .form-table').text(''); // clear table
			$('#step-3 > .form-table').append(data.table_html);
			$('#step-3').append(data.hidden_fields);
		});
	});

	// step three submit - process this thing and display home stuff
	$('#step_3_submit').click(function (e) {
		e.preventDefault();

		var data={
			'action' : 'process_custom_fields_to_metabox',
			'form' : $('#step-3').serialize()
		};

		$.post(ajaxurl,data,function(response) {
			var data=$.parseJSON(response);

			// if debug enabled, proccess //
			if (options.debug==1) {
				var today = new Date();
				$('#debug_notices #notices .notices-wrap').append(today+'<br>'); // add todays date and time

				if (!data) {
					$('#debug_notices #notices .notices-wrap').append('<div class="cftmb-debug-error">There was an error. Please try again.</div>');
				} else if ($.isArray(data)) {
					// cycle through and ouput notices //
					for (var i in data) {
						for (var x in data[i]) {
							$('#debug_notices #notices .notices-wrap').append(data[i][x]);
						}
					}
				}

				$('#debug_notices').addClass('active');
			} else {
				// regular notices output //
				if (data==false) {
					data='<div class="error">There was an error, please try again.</div>';
				}

				$('#cftmb-admin-notices').append(data);
			}

			$('#step-3').removeClass('active');
			$('#step-1').addClass('active');

			// clear selected radio button //
			$('#get-post-type input[type="radio"]').each(function () {
				$(this).prop('checked',false);
			});
		});
	});

	// our clear debug notices function //
	$('#debug_notices #clear_notices').click(function (e) {
		e.preventDefault();

		$('#debug_notices #notices .notices-wrap').text('');
	});

});