<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * URL to Nextcloud instance
 */
$config['FHC-Core-Nextcloud']['url'] = 'https://cloud12.technikum-wien.at/';

/**
 * Admin User for Nextcloud instance
 */
$config['FHC-Core-Nextcloud']['username'] = 'sysentw';

/**
 * Admin Password for Nextcloud instance
 */
$config['FHC-Core-Nextcloud']['password'] = 'sys3ntw12345';

/**
 * Verify the peer SSL Certificate
 * Values: true | false
 * Default: true
 */
$config['FHC-Core-Nextcloud']['verifyssl'] = true;
