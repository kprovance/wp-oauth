/* global wpoa, wpoa_cvars, jQuery */

( function( $ ) {
	'use strict';

	var timeoutIdleTime = 0;
	var wpMediaDialogField; // Field to populate after the admin selects an image using the wordpress media dialog.
	var timeoutInterval;
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
				var tipWarning = $( this ).parents( '.has-tip' ).find( '.tip-warning, .tip-info' );

				e.preventDefault();

				if ( tipWarning.length > 0 ) {
					tipWarning.fadeIn();
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
				var designName = $( '#wpoa-login-form-design :selected' ).text();
				var formDesign = $( '[name=wpoa_login_form_designs]' ).val();
				var designs;
				var design;

				formDesign = decodeURIComponent( formDesign );
				designs    = JSON.parse( formDesign );
				design     = designs[designName];

				if ( design ) {

					// Pull the design into the form fields for editing.
					// TODO: don't hard code these, we want to add new fields in the future without having to update this function...
					$( '[name=wpoa_login_form_design_name]' ).val( designName );
					$( '[name=wpoa_login_form_icon_set]' ).val( design.icon_set );
					$( '[name=wpoa_login_form_show_login]' ).val( design.show_login );
					$( '[name=wpoa_login_form_show_logout]' ).val( design.show_logout );
					$( '[name=wpoa_login_form_layout]' ).val( design.layout );
					$( '[name=wpoa_login_form_button_prefix]' ).val( design.button_prefix );
					$( '[name=wpoa_login_form_logged_out_title]' ).val( design.logged_out_title );
					$( '[name=wpoa_login_form_logged_in_title]' ).val( design.logged_in_title );
					$( '[name=wpoa_login_form_loggingInTitle]' ).val( design.loggingInTitle );
					$( '[name=wpoa_login_form_loggingOutTitle]' ).val( design.loggingOutTitle );

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
				var oldDesignName;

				// Get the designs.
				var formDesign = $( '[name=wpoa_login_form_designs]' ).val();

				formDesign = decodeURIComponent( formDesign );
				designs    = JSON.parse( formDesign );

				// Get the old design name (the design we'll be deleting).
				oldDesignName = $( '#wpoa-login-form-design :selected' ).text();

				$( '#wpoa-login-form-design option:contains("' + oldDesignName + '")' ).remove();
				delete designs[oldDesignName];

				// Update the designs array for POST.
				$( '[name=wpoa_login_form_designs]' ).val( encodeURIComponent( JSON.stringify( designs ) ) );
			}
		);

		// Edit design ok button.
		$( '#wpoa-login-form-ok' ).click(
			function() {

				// Applies changes to the current design by updating the designs array stored as JSON in a hidden form field...
				// Get the design name being proposed.
				var newDesignName    = $( '[name=wpoa_login_form_design_name]' ).val();
				var validationWarning = '';
				var formDesign;
				var designs;
				var oldDesignName;

				// Remove any validation error from a previous failed attempt.
				$( '#wpoa-login-form-design-form .validation-warning' ).remove();

				// Make sure the design name is not empty.
				if ( ! $( '#wpoa-login-form-design-name' ).val() ) {
					validationWarning = '<p id="validation-warning" class="validation-warning">Design name cannot be empty.</span>';
					$( '#wpoa-login-form-design-name' ).parent().append( validationWarning );

					return;
				}

				// This is either a NEW design or MODIFIED design, handle accordingly.
				if ( $( '#wpoa-login-form-design-form' ).hasClass( 'new-design' ) ) {

					// NEW DESIGN, add it...
					// Make sure the design name doesn't already exist.
					if ( -1 !== $( '#wpoa-login-form-design option' ).text().indexOf( newDesignName ) ) {

						// Design name already exists, notify the user and abort.
						validationWarning = '<p id="validation-warning" class="validation-warning">Design name already exists! Please choose a different name.</span>';
						$( '#wpoa-login-form-design-name' ).parent().append( validationWarning );

						return;
					} else {

						// Get the designs array which contains all of our designs.
						formDesign = $( '[name=wpoa_login_form_designs]' ).val();
						formDesign = decodeURIComponent( formDesign );
						designs    = JSON.parse( formDesign );

						// Add a design to the designs array.
						// TODO: don't hard code these, we want to add new fields in the future without having to update this function...
						designs[newDesignName]                  = {};
						designs[newDesignName].icon_set         = $( '[name=wpoa_login_form_icon_set]' ).val();
						designs[newDesignName].show_login       = $( '[name=wpoa_login_form_show_login]' ).val();
						designs[newDesignName].show_logout      = $( '[name=wpoa_login_form_show_logout]' ).val();
						designs[newDesignName].layout           = $( '[name=wpoa_login_form_layout]' ).val();
						designs[newDesignName].button_prefix    = $( '[name=wpoa_login_form_button_prefix]' ).val();
						designs[newDesignName].logged_out_title = $(
							'[name=wpoa_login_form_logged_out_title]'
						).val();

						designs[newDesignName].logged_in_title = $( '[name=wpoa_login_form_logged_in_title]' ).val();
						designs[newDesignName].loggingInTitle  = $(
							'[name=wpoa_login_form_loggingInTitle]'
						).val();

						designs[newDesignName].loggingOutTitle = $(
							'[name=wpoa_login_form_loggingOutTitle]'
						).val();

						// Update the select box to include this new design.
						$( '#wpoa-login-form-design' ).append(
							$( '<option></option>' ).text( newDesignName ).attr( 'selected', 'selected' )
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
					formDesign = $( '[name=wpoa_login_form_designs]' ).val();
					formDesign = decodeURIComponent( formDesign );
					designs    = JSON.parse( formDesign );

					// Remove the old design.
					oldDesignName = $( '#wpoa-login-form-design :selected' ).text();
					$( '#wpoa-login-form-design option:contains("' + oldDesignName + '")' ).remove();

					delete designs[oldDesignName];

					// Add the modified design.
					// TODO: don't hard code these, we want to add new fields in the future without having to update this function...
					designs[newDesignName]                  = {};
					designs[newDesignName].icon_set         = $( '[name=wpoa_login_form_icon_set]' ).val();
					designs[newDesignName].show_login       = $( '[name=wpoa_login_form_show_login]' ).val();
					designs[newDesignName].show_logout      = $( '[name=wpoa_login_form_show_logout]' ).val();
					designs[newDesignName].layout           = $( '[name=wpoa_login_form_layout]' ).val();
					designs[newDesignName].button_prefix    = $( '[name=wpoa_login_form_button_prefix]' ).val();
					designs[newDesignName].logged_out_title = $( '[name=wpoa_login_form_logged_out_title]' ).val();
					designs[newDesignName].logged_in_title  = $( '[name=wpoa_login_form_logged_in_title]' ).val();
					designs[newDesignName].loggingInTitle   = $( '[name=wpoa_login_form_loggingInTitle]' ).val();
					designs[newDesignName].loggingOutTitle  = $( '[name=wpoa_login_form_loggingOutTitle]' ).val();

					// Update the select box to include this new design.
					$( '#wpoa-login-form-design' ).append(
						$( '<option></option>' ).text( newDesignName ).attr( 'selected', 'selected' )
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
				wpMediaDialogField = $( '#wpoa_logo_image' );
				wpoa.selectMedia();
			}
		);

		// Show the wordpress media dialog for selecting a bg image.
		$( '#wpoa_bg_image_button' ).click(
			function( e ) {
				e.preventDefault();
				wpMediaDialogField = $( '#wpoa_bg_image' );
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
				var btn             = $( this );
				var wpoaIdentityRow = btn.data( 'wpoa-identity-row' );
				var nonce           = btn.data( 'nonce' );
				var postData        = {};

				event.preventDefault();

				btn.hide();
				btn.after( '<span> Please wait...</span>' );

				postData = {
					action: 'wpoa_unlink_account',
					wpoa_identity_row: wpoaIdentityRow,
					nonce: nonce
				};

				$.ajax(
					{
						type: 'POST',
						url: wpoa_cvars.ajaxurl,
						data: postData,
						success: function( json_response ) {
							var oresponse = JSON.parse( json_response );
							var linkButton;

							if ( 1 === oresponse.result ) {
								btn.parent().fadeOut(
									1000,
									function() {
										btn.parent().remove();
									}
								);

								linkButton = $( '.wpoa-login-button.' + wpoaIdentityRow );

								if ( linkButton.hasClass( 'disabled' ) ) {
									linkButton.removeClass( 'disabled' );
								}
							}
						}
					}
				);
			}
		);

		// Handle login button click.
		$( '.wpoa-login-button' ).click(
			function( event ) {
				var loggingInTitle;

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

				loggingInTitle = $( this ).parents( '.wpoa-login-form' ).data( 'logging-in-title' );
				$( '.wpoa-login-form #wpoa-title' ).text( loggingInTitle );
			}
		);

		// Handle logout button click.
		$( '.wpoa-logout-button' ).click(
			function() {
				var loggingOutTitle;

				// Fade out the login form.
				$( '#login #loginform' ).fadeOut();
				$( '#login #nav' ).fadeOut();
				$( '#login #backtoblog' ).fadeOut();

				// Toggle the loading style.
				$( this ).addClass( 'loading' );
				$( '.wpoa-login-form .wpoa-logout-button' ).not( this ).addClass( 'loading-other' );
				$( '.wpoa-login-form .wpoa-login-button' ).addClass( 'loading-other' );

				loggingOutTitle = $( this ).parents( '.wpoa-login-form' ).data( 'logging-out-title' );
				$( '.wpoa-login-form #wpoa-title' ).text( loggingOutTitle );
			}
		);

		// Show or log the client's login result which includes success or error messages.
		msg = $( '#wpoa-result' ).html();

		// Var msg = wpoa_cvars.login_message; // TODO: this method doesn't work that well since we don't clear the session variable at the server...
		if ( msg ) {
			if ( '1' === wpoa_cvars.show_login_messages ) {

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
					timeoutIdleTime = 0;
				}
			);

			$( document ).keypress(
				function() {
					timeoutIdleTime = 0;
				}
			);

			// Start a timer to keep track of each minute that passes.
			timeoutInterval = setInterval( wpoa.timeoutIncrement, 60000 );
		}

		// Hide the login form if the admin enabled this setting.
		// TODO: consider .remove() as well...maybe too intrusive though...and remember that bots don't use javascript
		// so this won't remove it for bots and those bots can still spam the login form...
		if ( '1' === wpoa_cvars.hide_login_form ) {
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
		var duration = parseInt( wpoa_cvars.logout_inactive_users );
		if ( timeoutIdleTime === ( duration - 1 ) ) {

			// Warning reached, next time we logout:.
			timeoutIdleTime += 1;
			wpoa.notify( 'Your session will expire in 1 minute due to inactivity.' );
		} else if ( timeoutIdleTime === duration ) {

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
		var customUploader;

		if ( customUploader ) {
			customUploader.open();
			return;
		}

		customUploader = wp.media.frames.file_frame = wp.media(
			{
				title: 'Choose Image',
				button: {
					text: 'Choose Image'
				},
				multiple: false
			}
		);

		customUploader.on(
			'select',
			function() {
				var attachment = customUploader.state().get( 'selection' ).first().toJSON();

				wpMediaDialogField.val( attachment.url );
			}
		);

		customUploader.open();
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
