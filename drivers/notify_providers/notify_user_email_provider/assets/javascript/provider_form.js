function set_authorization_status() {
	var smtp_auth_checked = !(jQuery('#Notify_Provider_smtp_authorization').is(':checked'));
	jQuery('#Notify_Provider_smtp_user').attr('disabled', smtp_auth_checked);
	jQuery('#Notify_Provider_smtp_password').attr('disabled', smtp_auth_checked);
}

function set_email_method() {
	var send_mode_value = jQuery('#Notify_Provider_send_mode').val();

	switch (send_mode_value)
	{
		case 'smtp':
			jQuery('#tab_smtp').admin_show();
			jQuery('#tab_sendmail').admin_hide();
		break;
		case 'mail':
			jQuery('#tab_smtp').admin_hide();
			jQuery('#tab_sendmail').admin_hide();
		break;
		case 'sendmail':
			jQuery('#tab_sendmail').admin_show();
			jQuery('#tab_smtp').admin_hide();
		break;
	}
}

jQuery(document).ready(function($) { 
	set_authorization_status();
	set_email_method();
	$('#Notify_Provider_smtp_authorization').bind('click', set_authorization_status);
	$('#Notify_Provider_send_mode').bind('change', set_email_method);
});