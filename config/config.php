<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * URL to Nextcloud instance
 */
$config['FHC-Core-Nextcloud']['url'] = 'https://cloud.example.com/';

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
