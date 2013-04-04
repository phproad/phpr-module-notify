<?php

class Notify_Providers extends Admin_Controller
{
	public $implement = 'Db_List_Behavior, Db_Form_Behavior';
	public $list_model_class = 'Notify_Provider';
	public $list_record_url = null;
	public $list_reuse_model = false;
	public $list_no_sorting = true;

	public $form_preview_title = 'Notify Provider';
	public $form_create_title = 'New Notify Provider';
	public $form_edit_title = 'Edit Notify Provider';
	public $form_model_class = 'Notify_Provider';
	public $form_not_found_message = 'Notify provider not found';
	public $form_redirect = null;
	public $form_flash_id = 'form_flash';

	public $form_edit_save_flash = 'The notify provider has been successfully saved';
	public $form_create_save_flash = 'The notify provider has been successfully added';
	public $form_edit_delete_flash = 'The notify provider has been successfully deleted';
	
	protected $required_permissions = array('notify:manage_providers');
	public $global_handlers = array('on_test');

	public function __construct()
	{
		parent::__construct();
		$this->app_menu = 'notify';
		$this->app_page = 'providers';
		$this->app_module_name = 'Notify';

		$this->list_record_url = url('notify/providers/edit');
		$this->form_redirect = url('notify/providers');
	}

	public function index()
	{
		$this->app_page_title = 'Providers';
	}

	public function edit_form_before_display($model)
	{
		$model->before_config_ui_render($model, $this);
	}

	public function create_form_before_display($model)
	{
		$model->before_config_ui_render($model, $this);
		$model->code = $model->get_code();
	}

	public function form_create_model_object()
	{
		$model = Notify_Provider::create();

		$class_name = Phpr::$router->param('param1');

		if (!Phpr::$class_loader->load($class_name))
			throw new Phpr_ApplicationException('Class '.$class_name.' not found');

		$model->class_name = $class_name;
		$model->init_columns();
		$model->init_form_fields();
		$model->code = $model->get_code();

		return $model;
	}

	protected function index_on_load_add_popup()
	{
		try
		{
			$provider_list = Notify_Manager::get_providers();
			usort($provider_list, array('Notify_Providers', 'provider_compare'));
			$this->view_data['provider_list'] = $provider_list;
		}
		catch (Exception $ex)
		{
			$this->handle_page_error($ex);
		}

		$this->display_partial('add_provider_form');
	}

	public static function provider_compare($a, $b)
	{
		return strcasecmp($a->get_name(), $b->get_name());
	}

	protected function on_test($id)
	{
		try
		{
			$obj = strlen($id) ? $this->form_find_model_object($id) : $this->form_create_model_object();
			$obj->validate_data(post($this->form_model_class, array()));
			if ($obj->send_test_message($this->active_user))
				echo Admin_Html::flash_message('The test message has been successfully sent.');
			else
				throw new Exception("Unable to send test message, this provider does not support this action");
		}
		catch (Exception $ex)
		{
			Phpr::$response->ajax_report_exception($ex, true, true);
		}
	}
	
	public function list_get_row_class($model)
	{
		return $model->is_enabled ? null : 'disabled';
	}
}

