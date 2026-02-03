<div>
	<h2>Settings for ChurchTools Calendar Sync</h2>
	<div>Just modify the fields below:</div>
	<div>
		<form method="post" class="ctwpsync_settings" action="" data-action="save_ctwpsync_settings">
		<br>ChurchTools-URL (Including https://)<br>
		<input type="text" size="30" name="ctwpsync_url" id="ctwpsync_url" class="text_box" placeholder="https://yourchurch.church.tools/" value="<?php echo $saved_data ? $saved_data['url'] : '' ; ?>" required>
		<br>ChurchTools API token<br>
		<input type="password" size="30" name="ctwpsync_apitoken" id="ctwpsync_apitoken" class="text_box" placeholder="my login token" value="<?php echo $saved_data ? $saved_data['apitoken'] : '' ; ?>">
		<button type="button" id="ctwpsync_validate_connection" class="button" style="margin-left: 10px;">Validate Connection</button>
		<span id="ctwpsync_validation_result" style="margin-left: 10px;"></span>

		<h3>Calendars to Sync</h3>
		<p>Select which calendars to sync and optionally assign a category to each:</p>
		<button type="button" id="ctwpsync_load_calendars" class="button">Load Calendars from ChurchTools</button>
		<div id="ctwpsync_calendars_container" style="margin-top: 10px;">
			<?php if ($saved_data && !empty($saved_data['calendars']) && is_array($saved_data['calendars'])): ?>
				<table class="wp-list-table widefat fixed striped" style="max-width: 600px;">
					<thead>
						<tr>
							<th style="width: 40px;">Sync</th>
							<th>Calendar</th>
							<th>Category (optional)</th>
						</tr>
					</thead>
					<tbody id="ctwpsync_calendars_list">
						<?php foreach ($saved_data['calendars'] as $index => $cal): ?>
							<tr>
								<td><input type="checkbox" checked></td>
								<td>
									<?php echo esc_html($cal['name'] ?: 'Calendar ID: ' . $cal['id']); ?>
									<input type="hidden" name="ctwpsync_calendars[<?php echo $index; ?>][id]" value="<?php echo esc_attr($cal['id']); ?>">
									<input type="hidden" name="ctwpsync_calendars[<?php echo $index; ?>][name]" value="<?php echo esc_attr($cal['name']); ?>">
								</td>
								<td>
									<input type="text" name="ctwpsync_calendars[<?php echo $index; ?>][category]" value="<?php echo esc_attr($cal['category']); ?>" placeholder="Category name" style="width: 100%;">
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else: ?>
				<p><em>No calendars configured. Click "Load Calendars from ChurchTools" after entering URL and API token.</em></p>
			<?php endif; ?>
		</div>

		<h3>Sync Settings</h3>
		<br>Calendar sync past days<br>
		<input type="text" size="30" name="ctwpsync_import_past" id="ctwpsync_import_past" class="text_box" placeholder="0" value="<?php echo $saved_data ? $saved_data['import_past'] : '' ; ?>" required>
		<br>Calendar sync future days<br>
		<input type="text" size="30" name="ctwpsync_import_future" id="ctwpsync_import_future" class="text_box" placeholder="380" value="<?php echo $saved_data ? $saved_data['import_future'] : '' ; ?>" required>

		<h3>Category Options</h3>
		<br>Resource type for categories:<br>
		<select name="ctwpsync_resourcetype_for_categories" id="ctwpsync_resourcetype_for_categories" style="min-width: 200px;">
			<option value="-1" <?php echo (isset($saved_data['resourcetype_for_categories']) && $saved_data['resourcetype_for_categories'] == -1) ? 'selected' : ''; ?>>Disabled</option>
			<?php if (isset($saved_data['resourcetype_for_categories']) && $saved_data['resourcetype_for_categories'] > 0): ?>
				<option value="<?php echo esc_attr($saved_data['resourcetype_for_categories']); ?>" selected>
					Resource Type ID: <?php echo esc_html($saved_data['resourcetype_for_categories']); ?>
				</option>
			<?php endif; ?>
		</select>
		<button type="button" id="ctwpsync_load_resource_types" class="button" style="margin-left: 10px;">Load Resource Types</button>
		<span id="ctwpsync_resource_types_result" style="margin-left: 10px;"></span>

		<br><br>
		<input type="checkbox" name="ctwpsync_enable_tag_categories" id="ctwpsync_enable_tag_categories" <?php echo ($saved_data && !empty($saved_data['enable_tag_categories'])) ? 'checked' : ''; ?>>
		<label for="ctwpsync_enable_tag_categories">Sync ChurchTools appointment tags as event categories</label>

		<h3>Image Settings</h3>
		<br>Name of a custom attribute in Events Manager. When set, this plugin will not download event images, but directly embed them from ChurchTools.<br>
		Must be defined in the <a href="https://wp-events-plugin.com/documentation/event-attributes/#enablingactivating">Events Manager settings</a><br>
		<input type="text" size="30" name="ctwpsync_em_image_attr" id="ctwpsync_em_image_attr" class="text_box" placeholder="disabled" value="<?php echo $saved_data ? $saved_data['em_image_attr'] : '' ; ?>">

		<br><br>
		<input type="submit" value="Save" class="button button-primary">
		<p><strong>Last updated:</strong> <?php echo $lastupdated; ?></p>
		<p><strong>Sync duration:</strong> <?php echo $lastsyncduration; ?></p>
	</div>

	<hr>
	<h3>Events Manager 7.1+ Compatibility</h3>
	<?php
	$migration_completed = get_option('ctwpsync_em71_migration_completed');
	if ($migration_completed) {
		echo '<p style="color: green;">&#10003; Migration to Events Manager 7.1+ completed</p>';
		echo '<p>All existing events have been updated with the correct event_type and post_status for Events Manager 7.1+</p>';
	} else {
		echo '<p style="color: orange;">&#9888; Migration pending</p>';
		echo '<p>Existing events need to be migrated for Events Manager 7.1+ compatibility.</p>';
		echo '<p>The migration will run automatically on the next plugin load or sync cycle.</p>';
		echo '<p>To manually trigger the migration now, reload this page.</p>';
	}
	?>
</div>

<script>
jQuery(document).ready(function($) {
	var nonce = '<?php echo wp_create_nonce("ctwpsync_validate"); ?>';

	// Manual validation button
	$('#ctwpsync_validate_connection').click(function() {
		validateConnection();
	});

	// Load calendars button
	$('#ctwpsync_load_calendars').click(function() {
		loadCalendars();
	});

	// Load resource types button
	$('#ctwpsync_load_resource_types').click(function() {
		loadResourceTypes();
	});

	// Intercept form submission to validate first
	$('form.ctwpsync_settings').submit(function(e) {
		var url = $('#ctwpsync_url').val();
		var token = $('#ctwpsync_apitoken').val();

		// If URL or token is empty, let HTML5 validation handle it
		if (!url || !token) {
			return true;
		}

		// Warn if no calendars are selected (but allow saving)
		if ($('input[name^="ctwpsync_calendars"]').length === 0 && $('[name^="ctwpsync_calendars"]').length === 0) {
			if (!confirm('No calendars selected. The sync will not work without calendars. Save anyway?')) {
				e.preventDefault();
				return false;
			}
		}

		// Prevent form submission until validation passes
		e.preventDefault();

		$('#ctwpsync_validation_result').html('<span style="color:blue;">Validating before save...</span>');
		$('input[type="submit"]').prop('disabled', true);

		$.post(ajaxurl, {
			action: 'ctwpsync_validate_connection',
			url: url,
			token: token,
			nonce: nonce
		}, function(response) {
			if (response.success) {
				$('#ctwpsync_validation_result').html('<span style="color:green;">&#10003; ' + response.data + ' - Saving...</span>');
				// Validation passed, submit the form
				$('form.ctwpsync_settings').off('submit').submit();
			} else {
				$('#ctwpsync_validation_result').html('<span style="color:red;">&#10007; ' + response.data + ' - Save cancelled</span>');
				$('input[type="submit"]').prop('disabled', false);
			}
		}).fail(function() {
			$('#ctwpsync_validation_result').html('<span style="color:red;">&#10007; Validation request failed - Save cancelled</span>');
			$('input[type="submit"]').prop('disabled', false);
		});
	});

	function validateConnection() {
		var url = $('#ctwpsync_url').val();
		var token = $('#ctwpsync_apitoken').val();

		if (!url || !token) {
			$('#ctwpsync_validation_result').html('<span style="color:red;">Please enter URL and API token first</span>');
			return;
		}

		$('#ctwpsync_validation_result').html('<span style="color:blue;">Checking...</span>');
		$('#ctwpsync_validate_connection').prop('disabled', true);

		$.post(ajaxurl, {
			action: 'ctwpsync_validate_connection',
			url: url,
			token: token,
			nonce: nonce
		}, function(response) {
			$('#ctwpsync_validate_connection').prop('disabled', false);
			if (response.success) {
				$('#ctwpsync_validation_result').html('<span style="color:green;">&#10003; ' + response.data + '</span>');
			} else {
				$('#ctwpsync_validation_result').html('<span style="color:red;">&#10007; ' + response.data + '</span>');
			}
		}).fail(function() {
			$('#ctwpsync_validate_connection').prop('disabled', false);
			$('#ctwpsync_validation_result').html('<span style="color:red;">&#10007; Request failed</span>');
		});
	}

	function loadCalendars() {
		var url = $('#ctwpsync_url').val();
		var token = $('#ctwpsync_apitoken').val();

		if (!url || !token) {
			alert('Please enter URL and API token first');
			return;
		}

		$('#ctwpsync_load_calendars').prop('disabled', true).text('Loading...');

		$.post(ajaxurl, {
			action: 'ctwpsync_get_calendars',
			url: url,
			token: token,
			nonce: nonce
		}, function(response) {
			$('#ctwpsync_load_calendars').prop('disabled', false).text('Load Calendars from ChurchTools');
			if (response.success) {
				renderCalendarTable(response.data);
			} else {
				alert('Failed to load calendars: ' + response.data);
			}
		}).fail(function() {
			$('#ctwpsync_load_calendars').prop('disabled', false).text('Load Calendars from ChurchTools');
			alert('Request failed');
		});
	}

	function renderCalendarTable(calendars) {
		// Get currently selected calendar IDs and their categories
		var currentSelections = {};
		$('input[name^="ctwpsync_calendars"]').each(function() {
			var match = $(this).attr('name').match(/ctwpsync_calendars\[(\d+)\]\[id\]/);
			if (match) {
				var idx = match[1];
				var id = $(this).val();
				var category = $('input[name="ctwpsync_calendars[' + idx + '][category]"]').val() || '';
				currentSelections[id] = category;
			}
		});

		var html = '<table class="wp-list-table widefat fixed striped" style="max-width: 600px;">' +
			'<thead><tr>' +
			'<th style="width: 40px;">Sync</th>' +
			'<th>Calendar</th>' +
			'<th>Category (optional)</th>' +
			'</tr></thead><tbody id="ctwpsync_calendars_list">';

		calendars.forEach(function(cal, index) {
			var isSelected = currentSelections.hasOwnProperty(cal.id);
			var category = isSelected ? currentSelections[cal.id] : '';
			var checked = isSelected ? 'checked' : '';

			html += '<tr>' +
				'<td><input type="checkbox" class="calendar-checkbox" data-index="' + index + '" ' + checked + '></td>' +
				'<td>' + escapeHtml(cal.name) +
				'<input type="hidden" class="calendar-id" data-index="' + index + '" value="' + cal.id + '">' +
				'<input type="hidden" class="calendar-name" data-index="' + index + '" value="' + escapeHtml(cal.name) + '">' +
				'</td>' +
				'<td><input type="text" class="calendar-category" data-index="' + index + '" value="' + escapeHtml(category) + '" placeholder="Category name" style="width: 100%;"></td>' +
				'</tr>';
		});

		html += '</tbody></table>';
		$('#ctwpsync_calendars_container').html(html);

		// Update hidden form fields when checkboxes change
		updateCalendarFormFields();
		$('.calendar-checkbox, .calendar-category').on('change keyup', function() {
			updateCalendarFormFields();
		});
	}

	function updateCalendarFormFields() {
		// Remove old hidden fields
		$('.ctwpsync-calendar-field').remove();

		var index = 0;
		$('.calendar-checkbox:checked').each(function() {
			var dataIndex = $(this).data('index');
			var id = $('.calendar-id[data-index="' + dataIndex + '"]').val();
			var name = $('.calendar-name[data-index="' + dataIndex + '"]').val();
			var category = $('.calendar-category[data-index="' + dataIndex + '"]').val();

			$('form.ctwpsync_settings').append(
				'<input type="hidden" class="ctwpsync-calendar-field" name="ctwpsync_calendars[' + index + '][id]" value="' + id + '">' +
				'<input type="hidden" class="ctwpsync-calendar-field" name="ctwpsync_calendars[' + index + '][name]" value="' + escapeHtml(name) + '">' +
				'<input type="hidden" class="ctwpsync-calendar-field" name="ctwpsync_calendars[' + index + '][category]" value="' + escapeHtml(category) + '">'
			);
			index++;
		});
	}

	function loadResourceTypes() {
		var url = $('#ctwpsync_url').val();
		var token = $('#ctwpsync_apitoken').val();

		if (!url || !token) {
			alert('Please enter URL and API token first');
			return;
		}

		$('#ctwpsync_load_resource_types').prop('disabled', true).text('Loading...');
		$('#ctwpsync_resource_types_result').html('<span style="color:blue;">Loading...</span>');

		$.post(ajaxurl, {
			action: 'ctwpsync_get_resource_types',
			url: url,
			token: token,
			nonce: nonce
		}, function(response) {
			$('#ctwpsync_load_resource_types').prop('disabled', false).text('Load Resource Types');
			if (response.success) {
				var currentValue = $('#ctwpsync_resourcetype_for_categories').val();
				var html = '<option value="-1">Disabled</option>';
				response.data.forEach(function(rt) {
					var selected = (rt.id == currentValue) ? 'selected' : '';
					html += '<option value="' + rt.id + '" ' + selected + '>' + escapeHtml(rt.name) + '</option>';
				});
				$('#ctwpsync_resourcetype_for_categories').html(html);
				$('#ctwpsync_resource_types_result').html('<span style="color:green;">&#10003; Loaded ' + response.data.length + ' resource types</span>');
			} else {
				$('#ctwpsync_resource_types_result').html('<span style="color:red;">&#10007; ' + response.data + '</span>');
			}
		}).fail(function() {
			$('#ctwpsync_load_resource_types').prop('disabled', false).text('Load Resource Types');
			$('#ctwpsync_resource_types_result').html('<span style="color:red;">&#10007; Request failed</span>');
		});
	}

	function escapeHtml(text) {
		if (!text) return '';
		var div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}

	// Initialize calendar form fields for existing selections
	if ($('.calendar-checkbox').length > 0) {
		updateCalendarFormFields();
		$('.calendar-checkbox, .calendar-category').on('change keyup', function() {
			updateCalendarFormFields();
		});
	}
});
</script>
