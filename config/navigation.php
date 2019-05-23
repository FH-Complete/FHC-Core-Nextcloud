<?php
// Add Menu-Entry to Main Page
$config['navigation_header']['*']['Administration']['children']['Nextcloud'] = array(
		'link' => base_url('index.ci.php/extensions/FHC-Core-Nextcloud/Nextcloud'),
		'sort' => 20,
		'description' => 'Nextcloud',
		'expand' => false
);

// Add Menu-Entry to Extension Page
$config['navigation_menu']['extensions/FHC-Core-Nextcloud/*'] = array(
	'Back' => array(
		'link' => site_url(),
		'description' => 'Zurück',
		'icon' => 'angle-left'
	)
);
