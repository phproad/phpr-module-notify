<?php

$table = Db_Structure::table('notify_providers');
	$table->primary_key('id');
	$table->column('code', db_varchar, 100)->index();
	$table->column('class_name', db_varchar, 100)->index();
	$table->column('config_data', db_text);
	$table->column('is_enabled', db_bool);

$table = Db_Structure::table('notify_templates');
	$table->primary_key('id');
	$table->column('name', db_varchar);
	$table->column('code', db_varchar, 100)->index();
	$table->column('class_name', db_varchar, 100)->index();
	$table->column('description', db_text);
	$table->column('is_enabled', db_bool);
	$table->column('config_data', db_text);
