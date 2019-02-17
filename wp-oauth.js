/* global wpoa, wpoa_cvars */
var $j = jQuery.noConflict();

// After the document has loaded, we hook up our events and initialize any other js functionality.
$j(function(){
	wpoa.init();
});

// Namespace the wpoa functions to prevent global conflicts, using the 'immediately invoked function expression' pattern.
(function( wpoa ) {
	var timeout_idle_time = 0;
	var wp_media_dialog_field; // Field to populate after the admin selects an image using the wordpress media dialog.
	var timeout_interval;
	var msg;

	// Init the client-side wpoa functionality.
	wpoa.init = function() {

		// Store the client's GMT offset (timezone) for converting server time into local time on a per-client basis
		// (this makes the time at which a provider was linked more accurate to the specific user).
		var d = new Date();

		var gmtoffset   = d.getTimezoneOffset() / 60;
		document.cookie = 'gmtoffset=' + gmtoffset;

		// Handle accordion sections.
		jQuery( '.wpoa-settings h3' ).click(
			function() {
				jQuery( this ).parent().find( '.form-padding' ).slideToggle();
			}
		);

		// Handle help tip buttons.
		jQuery( '.tip-button' ).click(
			function( e ) {
				e.preventDefault();
				jQuery( this ).parents( '.has-tip' ).find( '.tip-message' ).fadeToggle();
			}
		);

		// Automatically show warning tips when the user enters a sensitive form field.
		jQuery( '.wpoa-settings input, .wpoa-settings select' ).focus(
			function( e ) {
				var tip_warning = jQuery( this ).parents( '.has-tip' ).find( '.tip-warning, .tip-info' );

				e.preventDefault();

				if ( tip_warning.length > 0 ) {
					tip_warning.fadeIn();
					jQuery( this ).parents( '.has-tip' ).find( '.tip-message' ).fadeIn();
				}
			}
		);

		// Handle global togglers.
		jQuery( '#wpoa-settings-sections-on' ).click(
			function( e ) {
				e.preventDefault();
				jQuery( '.wpoa-settings h3' ).parent().find( '.form-padding' ).slideDown();
			}
		);

		jQuery( '#wpoa-settings-sections-off' ).click(
			function( e ) {
				e.preventDefault();
				jQuery( '.wpoa-settings h3' ).parent().find( '.form-padding' ).slideUp();
			}
		);

		jQuery( '#wpoa-settings-tips-on' ).click(
			function( e ) {
				e.preventDefault();
				jQuery( '.tip-message' ).fadeIn();
			}
		);

		jQuery( '#wpoa-settings-tips-off' ).click(
			function( e ) {
				e.preventDefault();
				jQuery( '.tip-message' ).fadeOut();
			}
		);

		// New design button.
		jQuery( '#wpoa-login-form-new' ).click(
			function() {

				// Show the edit design sub-section and hide the design selector.
				jQuery( '#wpoa-login-form-design' ).parents( 'tr' ).hide();
				jQuery( '#wpoa-login-form-design-form' ).addClass( 'new-design' );
				jQuery( '#wpoa-login-form-design-form input' ).not( ':button' ).val( '' ); // Clears the form field values.
				jQuery( '#wpoa-login-form-design-form h4' ).text( 'New Design' );
				jQuery( '#wpoa-login-form-design-form' ).show();
			}
		);

		// Edit design button.
		jQuery( '#wpoa-login-form-edit' ).click(
			function() {
				var design_name  = jQuery( '#wpoa-login-form-design :selected' ).text();
				var form_designs = jQuery( '[name=wpoa_login_form_designs]' ).val();
				var designs      = JSON.parse( form_designs );
				var design       = designs[design_name];

				if ( design ) {

					// Pull the design into the form fields for editing.
					// TODO: don't hard code these, we want to add new fields in the future without having to update this function...
					jQuery( '[name=wpoa_login_form_design_name]' ).val( design_name );
					jQuery( '[name=wpoa_login_form_icon_set]' ).val( design.icon_set );
					jQuery( '[name=wpoa_login_form_show_login]' ).val( design.show_login );
					jQuery( '[name=wpoa_login_form_show_logout]' ).val( design.show_logout );
					jQuery( '[name=wpoa_login_form_layout]' ).val( design.layout );
					jQuery( '[name=wpoa_login_form_button_prefix]' ).val( design.button_prefix );
					jQuery( '[name=wpoa_login_form_logged_out_title]' ).val( design.logged_out_title );
					jQuery( '[name=wpoa_login_form_logged_in_title]' ).val( design.logged_in_title );
					jQuery( '[name=wpoa_login_form_logging_in_title]' ).val( design.logging_in_title );
					jQuery( '[name=wpoa_login_form_logging_out_title]' ).val( design.logging_out_title );

					// Show the edit design sub-section and hide the design selector.
					jQuery( '#wpoa-login-form-design' ).parents( 'tr' ).hide();
					jQuery( '#wpoa-login-form-design-form' ).removeClass( 'new-design' );
					jQuery( '#wpoa-login-form-design-form h4' ).text( 'Edit Design' );
					jQuery( '#wpoa-login-form-design-form' ).show();
				}
			}
		);

		// Delete design button.
		jQuery( '#wpoa-login-form-delete' ).click(
			function() {

				// Get the designs.
				var form_designs = jQuery( '[name=wpoa_login_form_designs]' ).val();
				var designs      = JSON.parse( form_designs );

				// Get the old design name (the design we'll be deleting).
				var old_design_name = jQuery( '#wpoa-login-form-design :selected' ).text();

				jQuery( '#wpoa-login-form-design option:contains("' + old_design_name + '")' ).remove();
				delete designs[old_design_name];

				// Update the designs array for POST.
				jQuery( '[name=wpoa_login_form_designs]' ).val( JSON.stringify( designs ) );
			}
		);

		// Edit design ok button.
		jQuery( '#wpoa-login-form-ok' ).click(
			function() {

				// Applies changes to the current design by updating the designs array stored as JSON in a hidden form field...
				// Get the design name being proposed.
				var new_design_name    = jQuery( '[name=wpoa_login_form_design_name]' ).val();
				var validation_warning = '';
				var form_designs;
				var designs;
				var old_design_name;

				// Remove any validation error from a previous failed attempt.
				jQuery( '#wpoa-login-form-design-form .validation-warning' ).remove();

				// Make sure the design name is not empty.
				if ( ! jQuery( '#wpoa-login-form-design-name' ).val() ) {
					validation_warning = '<p id="validation-warning" class="validation-warning">Design name cannot be empty.</span>';
					jQuery( '#wpoa-login-form-design-name' ).parent().append( validation_warning );

					return;
				}

				// This is either a NEW design or MODIFIED design, handle accordingly.
				if ( jQuery( '#wpoa-login-form-design-form' ).hasClass( 'new-design' ) ) {

					// NEW DESIGN, add it...
					// Make sure the design name doesn't already exist.
					if ( -1 !== jQuery( '#wpoa-login-form-design option' ).text().indexOf( new_design_name ) ) {

						// Design name already exists, notify the user and abort.
						validation_warning = '<p id="validation-warning" class="validation-warning">Design name already exists! Please choose a different name.</span>';
						jQuery( '#wpoa-login-form-design-name' ).parent().append( validation_warning );

						return;
					} else {

						// Get the designs array which contains all of our designs.
						form_designs = jQuery( '[name=wpoa_login_form_designs]' ).val();
						designs      = JSON.parse( form_designs );

						// Add a design to the designs array.
						// TODO: don't hard code these, we want to add new fields in the future without having to update this function...
						designs[new_design_name]                  = {};
						designs[new_design_name].icon_set         = jQuery( '[name=wpoa_login_form_icon_set]' ).val();
						designs[new_design_name].show_login       = jQuery( '[name=wpoa_login_form_show_login]' ).val();
						designs[new_design_name].show_logout      = jQuery( '[name=wpoa_login_form_show_logout]' ).val();
						designs[new_design_name].layout           = jQuery( '[name=wpoa_login_form_layout]' ).val();
						designs[new_design_name].button_prefix    = jQuery( '[name=wpoa_login_form_button_prefix]' ).val();
						designs[new_design_name].logged_out_title = jQuery(
							'[name=wpoa_login_form_logged_out_title]'
						).val();

						designs[new_design_name].logged_in_title  = jQuery( '[name=wpoa_login_form_logged_in_title]' ).val();
						designs[new_design_name].logging_in_title = jQuery(
							'[name=wpoa_login_form_logging_in_title]'
						).val();

						designs[new_design_name].logging_out_title = jQuery(
							'[name=wpoa_login_form_logging_out_title]'
						).val();

						// Update the select box to include this new design.
						jQuery( '#wpoa-login-form-design' ).append(
							jQuery( '<option></option>' ).text( new_design_name ).attr( 'selected', 'selected' )
						);

						// Select the design in the selector.
						// update the designs array for POST.
						jQuery( '[name=wpoa_login_form_designs]' ).val( JSON.stringify( designs ) );

						// Hide the design editor and show the select box.
						jQuery( '#wpoa-login-form-design' ).parents( 'tr' ).show();
						jQuery( '#wpoa-login-form-design-form' ).hide();
					}
				} else {

					// MODIFIED DESIGN, add it and remove the old one...
					// Get the designs array which contains all of our designs.
					form_designs = jQuery( '[name=wpoa_login_form_designs]' ).val();
					designs      = JSON.parse( form_designs );

					// Remove the old design.
					old_design_name = jQuery( '#wpoa-login-form-design :selected' ).text();
					jQuery( '#wpoa-login-form-design option:contains("' + old_design_name + '")' ).remove();

					delete designs[old_design_name];

					// Add the modified design.
					// TODO: don't hard code these, we want to add new fields in the future without having to update this function...
					designs[new_design_name]                   = {};
					designs[new_design_name].icon_set          = jQuery( '[name=wpoa_login_form_icon_set]' ).val();
					designs[new_design_name].show_login        = jQuery( '[name=wpoa_login_form_show_login]' ).val();
					designs[new_design_name].show_logout       = jQuery( '[name=wpoa_login_form_show_logout]' ).val();
					designs[new_design_name].layout            = jQuery( '[name=wpoa_login_form_layout]' ).val();
					designs[new_design_name].button_prefix     = jQuery( '[name=wpoa_login_form_button_prefix]' ).val();
					designs[new_design_name].logged_out_title  = jQuery( '[name=wpoa_login_form_logged_out_title]' ).val();
					designs[new_design_name].logged_in_title   = jQuery( '[name=wpoa_login_form_logged_in_title]' ).val();
					designs[new_design_name].logging_in_title  = jQuery( '[name=wpoa_login_form_logging_in_title]' ).val();
					designs[new_design_name].logging_out_title = jQuery( '[name=wpoa_login_form_logging_out_title]' ).val();

					// Update the select box to include this new design.
					jQuery( '#wpoa-login-form-design' ).append(
						jQuery( '<option></option>' ).text( new_design_name ).attr( 'selected', 'selected' )
					);

					// Update the designs array for POST.
					jQuery( '[name=wpoa_login_form_designs]' ).val( JSON.stringify( designs ) );

					// Hide the design editor and show the design selector.
					jQuery( '#wpoa-login-form-design' ).parents( 'tr' ).show();
					jQuery( '#wpoa-login-form-design-form' ).hide();
				}
			}
		);

		// Cancels the changes to the current design.
		jQuery( '#wpoa-login-form-cancel' ).click(
			function() {
				jQuery( '#wpoa-login-form-design' ).parents( 'tr' ).show();
				jQuery( '#wpoa-login-form-design-form' ).hide();
			}
		);

		// Login redirect sub-settings.
		jQuery( '[name=wpoa_login_redirect]' ).change(
			function() {
				var val = jQuery( this ).val();

				jQuery( '[name=wpoa_login_redirect_url]' ).hide();
				jQuery( '[name=wpoa_login_redirect_page]' ).hide();

				if ( 'specific_page' === val ) {
					jQuery( '[name=wpoa_login_redirect_page]' ).show();
				} else if ( 'custom_url' === val ) {
					jQuery( '[name=wpoa_login_redirect_url]' ).show();
				}
			}
		);

		// Logout redirect sub-settings.
		jQuery( '[name=wpoa_login_redirect]' ).change();

		jQuery( '[name=wpoa_logout_redirect]' ).change(
			function() {
				var val = jQuery( this ).val();

				jQuery( '[name=wpoa_logout_redirect_url]' ).hide();
				jQuery( '[name=wpoa_logout_redirect_page]' ).hide();

				if ( 'specific_page' === val ) {
					jQuery( '[name=wpoa_logout_redirect_page]' ).show();
				} else if ( 'custom_url' === val ) {
					jQuery( '[name=wpoa_logout_redirect_url]' ).show();
				}
			}
		);

		jQuery( '[name=wpoa_logout_redirect]' ).change();

		// Show the wordpress media dialog for selecting a logo image.
		jQuery( '#wpoa_logo_image_button' ).click(
			function( e ) {
				e.preventDefault();
				wp_media_dialog_field = jQuery( '#wpoa_logo_image' );
				wpoa.selectMedia();
			}
		);

		// Show the wordpress media dialog for selecting a bg image.
		jQuery( '#wpoa_bg_image_button' ).click(
			function( e ) {
				e.preventDefault();
				wp_media_dialog_field = jQuery( '#wpoa_bg_image' );
				wpoa.selectMedia();
			}
		);

		jQuery( '#wpoa-paypal-button' ).hover(
			function() {
				jQuery( '#wpoa-heart' ).css( 'opacity', '1' );
			},
			function() {
				jQuery( '#wpoa-heart' ).css( 'opacity', '0' );
			}
		);

		// Attach unlink button click events.
		jQuery( '.wpoa-unlink-account' ).click(
			function( event ) {
				var btn               = jQuery( this );
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

				jQuery.ajax(
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
		jQuery( '.wpoa-login-button' ).click(
			function( event ) {
				var logging_in_title;

				event.preventDefault();

				window.location = jQuery( this ).attr( 'href' );

				// Fade out the WordPress login form.
				jQuery( '#login #loginform' ).fadeOut();	// The WordPress username/password form.
				jQuery( '#login #nav' ).fadeOut();          // The WordPress 'Forgot my password' link.
				jQuery( '#login #backtoblog' ).fadeOut();   // The WordPress '<- Back to blog' link.
				jQuery( '.message' ).fadeOut();             // The WordPress messages (e.g. 'You are now logged out.').

				// Toggle the loading style.
				jQuery( '.wpoa-login-form .wpoa-login-button' ).not( this ).addClass( 'loading-other' );
				jQuery( '.wpoa-login-form .wpoa-logout-button' ).addClass( 'loading-other' );
				jQuery( this ).addClass( 'loading' );

				logging_in_title = jQuery( this ).parents( '.wpoa-login-form' ).data( 'logging-in-title' );
				jQuery( '.wpoa-login-form #wpoa-title' ).text( logging_in_title );
			}
		);

		// Handle logout button click.
		jQuery( '.wpoa-logout-button' ).click(
			function() {
				var logging_out_title;

				// Fade out the login form.
				jQuery( '#login #loginform' ).fadeOut();
				jQuery( '#login #nav' ).fadeOut();
				jQuery( '#login #backtoblog' ).fadeOut();

				// Toggle the loading style.
				jQuery( this ).addClass( 'loading' );
				jQuery( '.wpoa-login-form .wpoa-logout-button' ).not( this ).addClass( 'loading-other' );
				jQuery( '.wpoa-login-form .wpoa-login-button' ).addClass( 'loading-other' );

				logging_out_title = jQuery( this ).parents( '.wpoa-login-form' ).data( 'logging-out-title' );
				jQuery( '.wpoa-login-form #wpoa-title' ).text( logging_out_title );
			}
		);

		// Show or log the client's login result which includes success or error messages.
		msg = jQuery( '#wpoa-result' ).html();

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
			jQuery( document ).mousemove(
				function() {
					timeout_idle_time = 0;
				}
			);

			jQuery( document ).keypress(
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
			jQuery( '#login #loginform' ).hide();
			jQuery( '#login #nav' ).hide();
			jQuery( '#login #backtoblog' ).hide();
		}

		// Show custom logo and bg if the admin enabled this setting.
		if ( document.URL.indexOf( 'wp-login' ) >= 0 ) {
			if ( wpoa_cvars.logo_image ) {
				jQuery( '.login h1 a' ).css( 'background-image', 'url(' + wpoa_cvars.logo_image + ')' );
			}
			if ( wpoa_cvars.bg_image ) {
				jQuery( 'body' ).css( 'background-image', 'url(' + wpoa_cvars.bg_image + ')' );
				jQuery( 'body' ).css( 'background-size', 'cover' );
			}
		}
	}; // END of wpoa.init()

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
		jQuery( id ).parents( 'tr' ).find( '.tip-message' ).fadeIn();
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

		jQuery( '.wpoa-login-message' ).remove();

		h += '<div class="wpoa-login-message"><span>' + msg + '</span></div>';

		jQuery( 'body' ).prepend( h );
		jQuery( '.wpoa-login-message' ).fadeOut( 5000 );
	};

	// Logout.
	wpoa.processLogout = function() {
		var data = {
			'action': 'wpoa_logout'
		};

		jQuery.ajax(
			{
				url: wpoa_cvars.ajaxurl,
				data: data,
				success: function() {
					window.location = wpoa_cvars.url + '/';
				}
			}
		);
	};

	// Check to evaluate whether 'wpoa' exists in the global namespace - if not, assign window.wpoa an object literal.
} )( window.wpoa = window.wpoa || {} );
