<?php

/**
 * CodeIgniter Front Controller for Cloudways deployment.
 * Forwards requests from public_html/ to public/index.php
 */

// Set the front controller path to the public directory
$_SERVER['SCRIPT_NAME'] = '/index.php';

// Change to the public directory and load from there
chdir(__DIR__ . '/public');

// Load the real front controller
require __DIR__ . '/public/index.php';
