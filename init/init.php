<?php

// Add social providers to class loader
Phpr::$class_loader->add_module_directory('drivers/notify_providers');
Phpr::$class_loader->add_module_directory('drivers/notify_templates');
