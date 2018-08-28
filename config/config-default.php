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

/**
 * Wether to run in debug mode, shows all text output if true, only errors otherwise
 * Values: true | false
 * Default: false
 */
$config['FHC-Core-Nextcloud']['debugmode'] = false;
