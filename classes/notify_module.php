<?php

class Notify_Module extends Core_Module_Base
{

	protected function set_module_info()
	{
		return new Core_Module_Detail(
			"Notify",
			"Notification engine",
			"PHP Road",
			"http://phproad.com/"
		);
	}

	public function build_user_preferences($host)
	{
		$host->add_preference_field($this, 'block_notify', 'Block notifications', false, 'full')->display_as(frm_checkbox)->comment('Prevent all notifications from being sent by this site')->tab('Notifications');
	}

	public function build_admin_settings($settings)
	{
		$settings->add('/notify/providers', 'Notification Settings', 'Set up notification providers and settings', '/modules/notify/assets/images/notify_config.png', 25);
		$settings->add('/notify/templates', 'Notification Templates', 'Messages sent to users, staff and external sites', '/modules/notify/assets/images/template_config.png', 25);
	}

}
