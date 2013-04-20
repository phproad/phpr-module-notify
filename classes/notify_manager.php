<?php

class Notify_Manager 
{
	public static function get_templates()
	{
		return Phpr_Driver_Manager::get_drivers('Notify_Template_Base');
	}

	public static function get_template($code)
	{
		return Phpr_Driver_Manager::get_driver('Notify_Template_Base', $code);
	}

	public static function get_providers()
	{
		return Phpr_Driver_Manager::get_drivers('Notify_Provider_Base');
	}

	public static function get_provider($code)
	{
		return Phpr_Driver_Manager::get_driver('Notify_Provider_Base', $code);
	}

	public static function auto_create_templates() 
	{
		$drivers = self::get_templates();

		foreach ($drivers as $driver)
		{
			self::create_template_from_code($driver->get_code());
		}
	}

	public static function create_template_from_code($code) 
	{
		$driver = self::get_template($code);
		
		if ($driver->get_code() != $code) 
			throw new Exception("Unable to find a Notification class with code: ".$code);

		// @todo Cache this
		$template = Notify_Template::create()->find_by_code($driver->get_code());
		if ($template)
			return $template;
		
		$template = Notify_Template::create();
		$template->is_enabled = true;

		$template->name = $driver->get_name();
		$template->description = $driver->get_description();
		$template->code = $driver->get_code();
		$template->class_name = get_class($driver);

		$template->save();
		$template->init_template_extension();
		return $template;
	}
}