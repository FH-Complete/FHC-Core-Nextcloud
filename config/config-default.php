<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Connection protocol to Nextcloud instance
 */
$config['FHC-Core-Nextcloud']['protocol'] = 'https';

/**
 * URL to Nextcloud instance
 */
$config['FHC-Core-Nextcloud']['host'] = 'cloud.example.com';

/**
 * Path to Nextcloud API
 */
$config['FHC-Core-Nextcloud']['path'] = 'ocs/v1.php/cloud';

/**
 * Admin User for Nextcloud instance
 */
$config['FHC-Core-Nextcloud']['username'] = 'username';

/**
 * Admin Password for Nextcloud instance
 */
$config['FHC-Core-Nextcloud']['password'] = 'password';

/**
 * Verify the peer SSL Certificate
 * Values: true | false
 * Default: true
 */
$config['FHC-Core-Nextcloud']['verifyssl'] = true;

/**
 * Whether to run in debug mode, shows all text output if true, only errors otherwise
 * Values: true | false
 * Default: false
 */
$config['FHC-Core-Nextcloud']['debugmode'] = false;
