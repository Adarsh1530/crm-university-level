<?php
/**
 * Redirect removed legacy college data to CRM Dashboard
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
redirect('dashboard/index.php');
