<?php
/**
 * Converted / Customer Activation Redirect
 * In Edroots CRM, leads are activated to Customers via finance/create_invoice.php
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$leadId = (int)($_GET['id'] ?? 0);
if ($leadId > 0) {
    redirect('leads/view.php?id=' . $leadId);
} else {
    redirect('leads/index.php');
}
