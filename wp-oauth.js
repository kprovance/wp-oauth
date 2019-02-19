/* global wpoa, wpoa_cvars, jQuery */

( function( $ ) {
	'use strict';

	var timeout_idle_time = 0;
	var wp_media_dialog_field; // Field to populate after the admin selects an image using the wordpress media dialog.
	var timeout_interval;
	var msg;

	window.wpoa = window.wpoa || {};

	$( document ).ready(
		function() {
			wpoa.init();
		}
	);

	wpoa.init = function() {

		// Store the client's GMT offset (timezone) for converting server time into local time on a per-client basis
		// (this makes the time at which a provider was linked more accurate to the specific user).
		var d = new Date();

		var gmtoffset   = d.getTimezoneOffset() / 60;
		document.cookie = 'gmtoffset=' + gmtoffset;

		// Handle accordion sections.
		$( '.wpoa-settings h3' ).click(
			function() {
				$( this ).parent().find( '.form-padding' ).slideToggle();
			}
		);

		// Handle help tip buttons.
		$( '.tip-button' ).click(
			function( e ) {
				e.preventDefault();
				$( this ).parents( '.has-tip' ).find( '.tip-message' ).fadeToggle();
			}
		);

		// Automatically show warning tips when the user enters a sensitive form field.
		$( '.wpoa-settings input, .wpoa-settings select' ).focus(
			function( e ) {
				var tip_warning = $( this ).parents( '.has-tip' ).find( '.tip-warning, .tip-info' );

				e.preventDefault();

				if ( tip_warning.length > 0 ) {
					tip_warning.fadeIn();
					$( this ).parents( '.has-tip' ).find( '.tip-message' ).fadeIn();
				}
			}
		);

		// Handle global togglers.
		$( '#wpoa-settings-sections-on' ).click(
			function( e ) {
				e.preventDefault();
				$( '.wpoa-settings h3' ).parent().find( '.form-padding' ).slideDown();
			}
		);

		$( '#wpoa-settings-sections-off' ).click(
			function( e ) {
				e.preventDefault();
				$( '.wpoa-settings h3' ).parent().find( '.form-padding' ).slideUp();
			}
		);

		$( '#wpoa-settings-tips-on' ).click(
			function( e ) {
				e.preventDefault();
				$( '.tip-message' ).fadeIn();
			}
		);

		$( '#wpoa-settings-tips-off' ).click(
			function( e ) {
				e.preventDefault();
				$( '.tip-message' ).fadeOut();
			}
		);

		// New design button.
		$( '#wpoa-login-form-new' ).click(
			function() {

				// Show the edit design sub-section and hide the design selector.
				$( '#wpoa-login-form-design' ).parents( 'tr' ).hide();
				$( '#wpoa-login-form-design-form' ).addClass( 'new-design' );
				$( '#wpoa-login-form-design-form input' ).not( ':button' ).val( '' ); // Clears the form field values.
				$( '#wpoa-login-form-design-form h4' ).text( 'New Design' );
				$( '#wpoa-login-form-design-form' ).show();
			}
		);

		// Edit design button.
		$( '#wpoa-login-form-edit' ).click(
			function() {
				var design_name  = $( '#wpoa-login-form-design :selected' ).text();
				var form_designs = $( '[name=wpoa_login_form_designs]' ).val();
				var designs;
				var design;

				form_designs = decodeURIComponent( form_designs );
				designs      = JSON.parse( form_designs );
				design       = designs[design_name];

				if ( design ) {

					// Pull the design into the form fields for editing.
					// TODO: don't hard code these, we want to add new fields in the future without having to update this function...
					$( '[name=wpoa_login_form_design_name]' ).val( design_name );
					$( '[name=wpoa_login_form_icon_set]' ).val( design.icon_set );
					$( '[name=wpoa_login_form_show_login]' ).val( design.show_login );
					$( '[name=wpoa_login_form_show_logout]' ).val( design.show_logout );
					$( '[name=wpoa_login_form_layout]' ).val( design.layout );
					$( '[name=wpoa_login_form_button_prefix]' ).val( design.button_prefix );
					$( '[name=wpoa_login_form_logged_out_title]' ).val( design.logged_out_title );
					$( '[name=wpoa_login_form_logged_in_title]' ).val( design.logged_in_title );
					$( '[name=wpoa_login_form_logging_in_title]' ).val( design.logging_in_title );
					$( '[name=wpoa_login_form_logging_out_title]' ).val( design.logging_out_title );

					// Show the edit design sub-section and hide the design selector.
					$( '#wpoa-login-form-design' ).parents( 'tr' ).hide();
					$( '#wpoa-login-form-design-form' ).removeClass( 'new-design' );
					$( '#wpoa-login-form-design-form h4' ).text( 'Edit Design' );
					$( '#wpoa-login-form-design-form' ).show();
				}
			}
		);

		// Delete design button.
		$( '#wpoa-login-form-delete' ).click(
			function() {
				var designs;
				var old_design_name;

				// Get the designs.
				var form_designs = $( '[name=wpoa_login_form_designs]' ).val();

				form_designs = decodeURIComponent( form_designs );
				designs      = JSON.parse( form_designs );

				// Get the old design name (the design we'll be deleting).
				old_design_name = $( '#wpoa-login-form-design :selected' ).text();

				$( '#wpoa-login-form-design option:contains("' + old_design_name + '")' ).remove();
				delete designs[old_design_name];

				// Update the designs array for POST.
				$( '[name=wpoa_login_form_designs]' ).val( encodeURIComponent( JSON.stringify( designs ) ) );
			}
		);

		// Edit design ok button.
		$( '#wpoa-login-form-ok' ).click(
			function() {

				// Applies changes to the current design by updating the designs array stored as JSON in a hidden form field...
				// Get the design name being proposed.
				var new_design_name    = $( '[name=wpoa_login_form_design_name]' ).val();
				var validation_warning = '';
				var form_designs;
				var designs;
				var old_design_name;

				// Remove any validation error from a previous failed attempt.
				$( '#wpoa-login-form-design-form .validation-warning' ).remove();

				// Make sure the design name is not empty.
				if ( ! $( '#wpoa-login-form-design-name' ).val() ) {
					validation_warning = '<p id="validation-warning" class="validation-warning">Design name cannot be empty.</span>';
					$( '#wpoa-login-form-design-name' ).parent().append( validation_warning );

					return;
				}

				// This is either a NEW design or MODIFIED design, handle accordingly.
				if ( $( '#wpoa-login-form-design-form' ).hasClass( 'new-design' ) ) {

					// NEW DESIGN, add it...
					// Make sure the design name doesn't already exist.
					if ( -1 !== $( '#wpoa-login-form-design option' ).text().indexOf( new_design_name ) ) {

						// Design name already exists, notify the user and abort.
						validation_warning = '<p id="validation-warning" class="validation-warning">Design name already exists! Please choose a different name.</span>';
						$( '#wpoa-login-form-design-name' ).parent().append( validation_warning );

						return;
					} else {

						// Get the designs array which contains all of our designs.
						form_designs = $( '[name=wpoa_login_form_designs]' ).val();
						form_designs = decodeURIComponent( form_designs );
						designs      = JSON.parse( form_designs );

						// Add a design to the designs array.
						// TODO: don't hard code these, we want to add new fields in the future without having to update this function...
						designs[new_design_name]                  = {};
						designs[new_design_name].icon_set         = $( '[name=wpoa_login_form_icon_set]' ).val();
						designs[new_design_name].show_login       = $( '[name=wpoa_login_form_show_login]' ).val();
						designs[new_design_name].show_logout      = $( '[name=wpoa_login_form_show_logout]' ).val();
						designs[new_design_name].layout           = $( '[name=wpoa_login_form_layout]' ).val();
						designs[new_design_name].button_prefix    = $( '[name=wpoa_login_form_button_prefix]' ).val();
						designs[new_design_name].logged_out_title = $(
							'[name=wpoa_login_form_logged_out_title]'
						).val();

						designs[new_design_name].logged_in_title  = $( '[name=wpoa_login_form_logged_in_title]' ).val();
						designs[new_design_name].logging_in_title = $(
							'[name=wpoa_login_form_logging_in_title]'
						).val();

						designs[new_design_name].logging_out_title = $(
							'[name=wpoa_login_form_logging_out_title]'
						).val();

						// Update the select box to include this new design.
						$( '#wpoa-login-form-design' ).append(
							$( '<option></option>' ).text( new_design_name ).attr( 'selected', 'selected' )
						);

						// Select the design in the selector.
						// update the designs array for POST.
						$( '[name=wpoa_login_form_designs]' ).val( encodeURIComponent( JSON.stringify( designs ) ) );

						// Hide the design editor and show the select box.
						$( '#wpoa-login-form-design' ).parents( 'tr' ).show();
						$( '#wpoa-login-form-design-form' ).hide();
					}
				} else {

					// MODIFIED DESIGN, add it and remove the old one...
					// Get the designs array which contains all of our designs.
					form_designs = $( '[name=wpoa_login_form_designs]' ).val();
					form_designs = decodeURIComponent( form_designs );
					designs      = JSON.parse( form_designs );

					// Remove the old design.
					old_design_name = $( '#wpoa-login-form-design :selected' ).text();
					$( '#wpoa-login-form-design option:contains("' + old_design_name + '")' ).remove();

					delete designs[old_design_name];

					// Add the modified design.
					// TODO: don't hard code these, we want to add new fields in the future without having to update this function...
					designs[new_design_name]                   = {};
					designs[new_design_name].icon_set          = $( '[name=wpoa_login_form_icon_set]' ).val();
					designs[new_design_name].show_login        = $( '[name=wpoa_login_form_show_login]' ).val();
					designs[new_design_name].show_logout       = $( '[name=wpoa_login_form_show_logout]' ).val();
					designs[new_design_name].layout            = $( '[name=wpoa_login_form_layout]' ).val();
					designs[new_design_name].button_prefix     = $( '[name=wpoa_login_form_button_prefix]' ).val();
					designs[new_design_name].logged_out_title  = $( '[name=wpoa_login_form_logged_out_title]' ).val();
					designs[new_design_name].logged_in_title   = $( '[name=wpoa_login_form_logged_in_title]' ).val();
					designs[new_design_name].logging_in_title  = $( '[name=wpoa_login_form_logging_in_title]' ).val();
					designs[new_design_name].logging_out_title = $( '[name=wpoa_login_form_logging_out_title]' ).val();

					// Update the select box to include this new design.
					$( '#wpoa-login-form-design' ).append(
						$( '<option></option>' ).text( new_design_name ).attr( 'selected', 'selected' )
					);

					// Update the designs array for POST.
					$( '[name=wpoa_login_form_designs]' ).val( encodeURIComponent( JSON.stringify( designs ) ) );

					// Hide the design editor and show the design selector.
					$( '#wpoa-login-form-design' ).parents( 'tr' ).show();
					$( '#wpoa-login-form-design-form' ).hide();
				}
			}
		);

		// Cancels the changes to the current design.
		$( '#wpoa-login-form-cancel' ).click(
			function() {
				$( '#wpoa-login-form-design' ).parents( 'tr' ).show();
				$( '#wpoa-login-form-design-form' ).hide();
			}
		);

		// Login redirect sub-settings.
		$( '[name=wpoa_login_redirect]' ).change(
			function() {
				var val = $( this ).val();

				$( '[name=wpoa_login_redirect_url]' ).hide();
				$( '[name=wpoa_login_redirect_page]' ).hide();

				if ( 'specific_page' === val ) {
					$( '[name=wpoa_login_redirect_page]' ).show();
				} else if ( 'custom_url' === val ) {
					$( '[name=wpoa_login_redirect_url]' ).show();
				}
			}
		);

		// Logout redirect sub-settings.
		$( '[name=wpoa_login_redirect]' ).change();

		$( '[name=wpoa_logout_redirect]' ).change(
			function() {
				var val = $( this ).val();

				$( '[name=wpoa_logout_redirect_url]' ).hide();
				$( '[name=wpoa_logout_redirect_page]' ).hide();

				if ( 'specific_page' === val ) {
					$( '[name=wpoa_logout_redirect_page]' ).show();
				} else if ( 'custom_url' === val ) {
					$( '[name=wpoa_logout_redirect_url]' ).show();
				}
			}
		);

		$( '[name=wpoa_logout_redirect]' ).change();

		// Show the wordpress media dialog for selecting a logo image.
		$( '#wpoa_logo_image_button' ).click(
			function( e ) {
				e.preventDefault();
				wp_media_dialog_field = $( '#wpoa_logo_image' );
				wpoa.selectMedia();
			}
		);

		// Show the wordpress media dialog for selecting a bg image.
		$( '#wpoa_bg_image_button' ).click(
			function( e ) {
				e.preventDefault();
				wp_media_dialog_field = $( '#wpoa_bg_image' );
				wpoa.selectMedia();
			}
		);

		$( '#wpoa-paypal-button' ).hover(
			function() {
				$( '#wpoa-heart' ).css( 'opacity', '1' );
			},
			function() {
				$( '#wpoa-heart' ).css( 'opacity', '0' );
			}
		);

		// Attach unlink button click events.
		$( '.wpoa-unlink-account' ).click(
			function( event ) {
				var btn               = $( this );
				var wpoa_identity_row = btn.data( 'wpoa-identity-row' );
				var nonce             = btn.data( 'nonce' );
				var post_data         = {};

				event.preventDefault();

				btn.hide();
				btn.after( '<span> Please wait...</span>' );

				post_data = {
					action: 'wpoa_unlink_account',
					wpoa_identity_row: wpoa_identity_row,
					nonce: nonce
				};

				$.ajax(
					{
						type: 'POST',
						url: wpoa_cvars.ajaxurl,
						data: post_data,
						success: function( json_response ) {

							var oresponse = JSON.parse( json_response );

							if ( 1 === oresponse.result ) {
								btn.parent().fadeOut(
									1000,
									function() {
										btn.parent().remove();
									}
								);
							}
						}
					}
				);
			}
		);

		// Handle login button click.
		$( '.wpoa-login-button' ).click(
			function( event ) {
				var logging_in_title;

				event.preventDefault();

				window.location = $( this ).attr( 'href' );

				// Fade out the WordPress login form.
				$( '#login #loginform' ).fadeOut();	// The WordPress username/password form.
				$( '#login #nav' ).fadeOut();          // The WordPress 'Forgot my password' link.
				$( '#login #backtoblog' ).fadeOut();   // The WordPress '<- Back to blog' link.
				$( '.message' ).fadeOut();             // The WordPress messages (e.g. 'You are now logged out.').

				// Toggle the loading style.
				$( '.wpoa-login-form .wpoa-login-button' ).not( this ).addClass( 'loading-other' );
				$( '.wpoa-login-form .wpoa-logout-button' ).addClass( 'loading-other' );
				$( this ).addClass( 'loading' );

				logging_in_title = $( this ).parents( '.wpoa-login-form' ).data( 'logging-in-title' );
				$( '.wpoa-login-form #wpoa-title' ).text( logging_in_title );
			}
		);

		// Handle logout button click.
		$( '.wpoa-logout-button' ).click(
			function() {
				var logging_out_title;

				// Fade out the login form.
				$( '#login #loginform' ).fadeOut();
				$( '#login #nav' ).fadeOut();
				$( '#login #backtoblog' ).fadeOut();

				// Toggle the loading style.
				$( this ).addClass( 'loading' );
				$( '.wpoa-login-form .wpoa-logout-button' ).not( this ).addClass( 'loading-other' );
				$( '.wpoa-login-form .wpoa-login-button' ).addClass( 'loading-other' );

				logging_out_title = $( this ).parents( '.wpoa-login-form' ).data( 'logging-out-title' );
				$( '.wpoa-login-form #wpoa-title' ).text( logging_out_title );
			}
		);

		// Show or log the client's login result which includes success or error messages.
		msg = $( '#wpoa-result' ).html();

		// Var msg = wpoa_cvars.login_message; // TODO: this method doesn't work that well since we don't clear the session variable at the server...
		if ( msg ) {
			if ( wpoa_cvars.show_login_messages ) {

				// Notify the client of the login result with a visible, short-lived message at the top of the screen.
				wpoa.notify( msg );
			} else {

				// Log the message to the dev console; useful for client support, troubleshooting and debugging if the admin has turned off the visible messages.
				console.log( msg );
			}
		}

		// Create the login session timeout if the admin enabled this setting.
		if ( '1' === wpoa_cvars.logged_in && '0' !== wpoa_cvars.logout_inactive_users ) {

			// Bind mousemove, keypress events to reset the timeout.
			$( document ).mousemove(
				function() {
					timeout_idle_time = 0;
				}
			);

			$( document ).keypress(
				function() {
					timeout_idle_time = 0;
				}
			);

			// Start a timer to keep track of each minute that passes.
			timeout_interval = setInterval( wpoa.timeoutIncrement, 60000 );
		}

		// Hide the login form if the admin enabled this setting.
		// TODO: consider .remove() as well...maybe too intrusive though...and remember that bots don't use javascript
		// so this won't remove it for bots and those bots can still spam the login form...
		if ( 1 === wpoa_cvars.hide_login_form ) {
			$( '#login #loginform' ).hide();
			$( '#login #nav' ).hide();
			$( '#login #backtoblog' ).hide();
		}

		// Show custom logo and bg if the admin enabled this setting.
		if ( document.URL.indexOf( 'wp-login' ) >= 0 ) {
			if ( wpoa_cvars.logo_image ) {
				$( '.login h1 a' ).css( 'background-image', 'url(' + wpoa_cvars.logo_image + ')' );
			}
			if ( wpoa_cvars.bg_image ) {
				$( 'body' ).css( 'background-image', 'url(' + wpoa_cvars.bg_image + ')' );
				$( 'body' ).css( 'background-size', 'cover' );
			}
		}
	};

	// Handle idle timeout.
	wpoa.timeoutIncrement = function() {
		var duration = wpoa_cvars.logout_inactive_users;
		if ( timeout_idle_time === duration - 1 ) {

			// Warning reached, next time we logout:.
			timeout_idle_time += 1;
			wpoa.notify( 'Your session will expire in 1 minute due to inactivity.' );
		} else if ( timeout_idle_time === duration ) {

			// Idle duration reached, logout the user:.
			wpoa.notify( 'Logging out due to inactivity...' );
			wpoa.processLogout();
		}
	};

	// Shows the associated tip message for a setting.
	wpoa.showTip = function( id ) {
		$( id ).parents( 'tr' ).find( '.tip-message' ).fadeIn();
	};

	// Shows the default wordpress media dialog for selecting or uploading an image.
	wpoa.selectMedia = function() {
		var custom_uploader;

		if ( custom_uploader ) {
			custom_uploader.open();
			return;
		}

		custom_uploader = wp.media.frames.file_frame = wp.media(
			{
				title: 'Choose Image',
				button: {
					text: 'Choose Image'
				},
				multiple: false
			}
		);

		custom_uploader.on(
			'select',
			function() {
				var attachment = custom_uploader.state().get( 'selection' ).first().toJSON();

				wp_media_dialog_field.val( attachment.url );
			}
		);

		custom_uploader.open();
	};

	// Displays a short-lived notification message at the top of the screen.
	wpoa.notify = function( msg ) {
		var h = '';

		$( '.wpoa-login-message' ).remove();

		h += '<div class="wpoa-login-message"><span>' + msg + '</span></div>';

		$( 'body' ).prepend( h );
		$( '.wpoa-login-message' ).fadeOut( 5000 );
	};

	// Logout.
	wpoa.processLogout = function() {
		var data = {
			'action': 'wpoa_logout'
		};

		$.ajax(
			{
				url: wpoa_cvars.ajaxurl,
				data: data,
				success: function() {
					window.location = wpoa_cvars.url + '/';
				}
			}
		);
	};
} )( jQuery );
