<?php
/**
 * Root Entry Point
 * University-Level Student CRM Demo
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect('dashboard/index.php');
} else {
    redirect('login.php');
}
