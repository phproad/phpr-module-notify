<?php

class Notify_Template extends Db_ActiveRecord 
{
	public $table_name = 'notify_templates';
	public $implement = 'Db_Model_Dynamic';
	
	public $recipients = array();
	public $internal_recipients = array();
	public $enabled = true;
	protected $added_fields = array();

	public static function create($values = null) 
	{
		return new self($values);
	}
	
	public function define_columns($context = null)
	{
		$this->define_column('name', 'Name')->validation()->fn('trim')->required('Please specify the template name.');
		$this->define_column('description', 'Description')->validation()->fn('trim')->required('Please provide the template description.');
		$this->define_column('is_enabled', 'Enabled')->order('desc')->default_invisible();
		$this->define_column('code', 'Code')->order('asc')->validation()->fn('trim')->required('Please specify the template code.')->unique('Code %s is already in use. Please specify another code.')->regexp('/^[a-z_0-9:]*$/i', 'Template code can only contain latin characters, numbers, colons and underscores.');
	}

	public function define_form_fields($context = null)
	{
		// Prevent duplication
		if ($this->form_fields_defined) return false; 
		$this->form_fields_defined = true;

		$this->has_template_extension();

		$this->add_form_field('is_enabled')->tab('Message');

		if ($context == 'create') {
			$this->add_form_field('name', 'left')->tab('Message');
			$this->add_form_field('code', 'right')->tab('Message');
			$this->add_form_field('description')->size('tiny')->tab('Message');
		} else {
			$this->add_form_field('name')->tab('Message');
			$this->add_form_field('description')->size('tiny')->tab('Message');
		}

		// Allow providers to extend template form
		$active_providers = Notify_Provider::find_all_active_providers();
		foreach ($active_providers as $provider) {
			$provider->build_template_ui($this, $context);
			$provider->init_template_data($this);
		}
	}

	// Events
	// 

	public function after_fetch()
	{
		$this->init_form_fields();
	}

	public function before_create($session_key = null)
	{
		// Prevent duplication
		if (strlen($this->code))
		{
			Db_Helper::query("delete from email_templates where code=:code", array(
				'code' => $this->code
			));
		}
	}

	public function before_delete($id = null)
	{
		Phpr::$events->fire_event('notify:on_delete_template', $this);
	}
	
	// Service methods
	// 

	public function has_template_extension()
	{
		if (!strlen($this->class_name))
			return false;

		// Mixin class
		if ($this->class_name && !$this->is_extended_with($this->class_name))
			$this->extend_with($this->class_name);

		return true;
	}

	public function send($providers = null)
	{
		if ($providers === null || !is_array($providers))
			$providers = Notify_Provider::find_all_active_providers();

		$sent_any = false;
		
		foreach ($providers as $provider)
		{
			if ($provider->send_notification($this))
				$sent_any = true;
		}

		return $sent_any;
	}

	public function set_vars($params=array(), $html_encode_values=true)
	{
		$params['site_name'] = c('site_name');
		$params['site_link'] = root_url(' ',true);
		$params['current_date'] = Phpr_DateTime::now()->to_long_date_format();

		foreach ($this->added_fields as $field_name => $field_obj) {
			$this->{$field_name} = Phpr_Parser::parse_text(
				$this->{$field_name}, 
				$params, 
				array('encode_html'=>$html_encode_values)
			);
		}

		return $this;
	}

	// Recipient handling
	// 

	public function add_recipient($recipient, $internal = false)
	{
		if ($internal)
			$this->internal_recipients[] = $recipient;
		else 
			$this->recipients[] = $recipient;

		return $this;
	}

	public function add_recipients($recipients, $internal = false)
	{
		if (!is_array($recipients))
			$this->add_recipient($recipients, $internal);

		foreach ($recipients as $recipient)
			$this->add_recipient($recipient, $internal);

		return $this;
	}

	public function get_recipients($internal = false)
	{
		if ($internal)
			return $this->internal_recipients;
		else 
			return $this->recipients;
	}

	// Dynamic model
	// 

	public function add_field($code, $title, $side = 'full', $type = db_text)
	{
		$form_column = $this->define_dynamic_column($code, $title, $type)->validation();
		$form_field = $this->add_dynamic_form_field($code, $side)->tab('Message');
		$this->added_fields[$code] = $form_field;
		return $form_field;
	}

}

