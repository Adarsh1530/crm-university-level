<?php
/**
 * Global Helper Functions
 * University-Level Student CRM Demo
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Returns the base URL for the application
 * Automatically adjusts for root or subfolder installation
 *
 * @param string $path
 * @return string
 */
function base_url($path = '') {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $scriptDir = str_replace('\\', '/', $scriptDir);
    
    $knownSubdirs = [
        '/dashboard', '/data', '/leads', '/customers', '/admissions',
        '/tasks', '/finance', '/documents', '/organization', '/archive',
        '/notifications', '/reports', '/system', '/staff', '/settings',
        '/followups', '/students', '/config', '/includes'
    ];
    
    $base = $scriptDir;
    foreach ($knownSubdirs as $sub) {
        if (substr($base, -strlen($sub)) === $sub) {
            $base = substr($base, 0, -strlen($sub));
            break;
        }
    }
    
    $base = rtrim($base, '/');
    $path = ltrim($path, '/');
    
    return ($base === '' ? '' : $base) . '/' . $path;
}

/**
 * Redirect to a given URL
 *
 * @param string $url
 * @return void
 */
function redirect($url) {
    // If relative path without scheme or slash, prefix with base_url
    if (!preg_match('#^https?://#', $url) && substr($url, 0, 1) !== '/') {
        $url = base_url($url);
    }
    header("Location: $url");
    exit;
}

/**
 * Sanitize string input
 *
 * @param mixed $input
 * @return mixed
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim((string)$input), ENT_QUOTES, 'UTF-8');
}

/**
 * Set flash message
 *
 * @param string $type success|danger|warning|info
 * @param string $message
 * @return void
 */
function set_flash($type, $message) {
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Clear all flash messages
 *
 * @return void
 */
function clear_flashes() {
    unset($_SESSION['flash_messages']);
}

/**
 * Retrieve and clear flash messages
 *
 * @return array
 */
function get_flashes() {
    $flashes = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);

    // If user is currently authenticated, never display pre-login "Please sign in" warnings
    if (!empty($_SESSION['user_id'])) {
        $flashes = array_values(array_filter($flashes, function($f) {
            return stripos($f['message'], 'Please sign in') === false;
        }));
    }

    return $flashes;
}

/**
 * CSRF token generation
 *
 * @return string
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF hidden input field
 *
 * @return string
 */
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

/**
 * Validate CSRF token
 *
 * @param string|null $token
 * @return bool
 */
function validate_csrf($token = null) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? '';
    }
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Verify CSRF token or terminate request with security notice
 *
 * @param string|null $token
 * @return bool
 */
function verify_csrf_token($token = null) {
    if (!validate_csrf($token)) {
        if (!headers_sent()) {
            http_response_code(403);
        }
        set_flash('danger', 'Security validation failed (Invalid or expired session token). Please try submitting again.');
        $referrer = $_SERVER['HTTP_REFERER'] ?? base_url('dashboard/index.php');
        redirect($referrer);
        exit;
    }
    return true;
}

/**
 * Generate CSRF token alias
 *
 * @return string
 */
function generate_csrf_token() {
    return csrf_token();
}

/**
 * Log an internal CRM activity
 *
 * @param int|null $userId
 * @param int|null $studentId
 * @param string $action
 * @param string $details
 * @return bool
 */
function log_activity($userId, $studentId, $action, $details) {
    global $pdo;
    if (!$pdo) return false;
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, student_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        return $stmt->execute([$userId, $studentId, $action, $details, $ip]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Create a new notification
 *
 * @param int|null $userId (null for all staff)
 * @param string $title
 * @param string $message
 * @param string $type
 * @param string|null $link
 * @return bool
 */
function add_notification($userId, $title, $message, $type, $link = null) {
    global $pdo;
    if (!$pdo) return false;
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, link, is_read, created_at) VALUES (?, ?, ?, ?, ?, 0, NOW())");
        return $stmt->execute([$userId, $title, $message, $type, $link]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get unread notification count for user (Status: Not Attended)
 *
 * @param int $userId
 * @return int
 */
function get_unread_notifications_count($userId) {
    global $pdo;
    if (!$pdo) return 0;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE (user_id = ? OR user_id IS NULL) AND status = 'Not Attended'");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Get recent notifications for dropdown
 *
 * @param int $userId
 * @param int $limit
 * @return array
 */
function get_recent_notifications($userId, $limit = 5) {
    global $pdo;
    if (!$pdo) return [];
    try {
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE (user_id = ? OR user_id IS NULL) ORDER BY created_at DESC LIMIT ?");
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Render Priority Badge HTML
 *
 * @param string $priority
 * @return string
 */
function priority_badge($priority) {
    $class = match (strtolower($priority)) {
        'high' => 'badge-priority-high',
        'moderate', 'medium' => 'badge-priority-moderate',
        'low' => 'badge-priority-low',
        default => 'badge-secondary',
    };
    $icon = match (strtolower($priority)) {
        'high' => '<i class="fa-solid fa-arrow-up-right-dots"></i> ',
        'moderate', 'medium' => '<i class="fa-solid fa-minus"></i> ',
        'low' => '<i class="fa-solid fa-arrow-down-long"></i> ',
        default => '',
    };
    return '<span class="badge ' . $class . '">' . $icon . htmlspecialchars($priority) . '</span>';
}

/**
 * Render Status Badge HTML
 *
 * @param string $status
 * @return string
 */
function status_badge($status) {
    $lower = strtolower($status);
    $class = match ($lower) {
        'new lead', 'pending' => 'badge-status-pending',
        'in progress', 'counselling', 'contacted' => 'badge-status-progress',
        'interested', 'application started' => 'badge-status-info',
        'documents submitted', 'application processing' => 'badge-status-purple',
        'confirmed', 'converted', 'completed' => 'badge-status-success',
        'overdue', 'lost', 'rejected', 'admission lost' => 'badge-status-danger',
        'on hold', 'not interested', 'rescheduled' => 'badge-status-warning',
        'active' => 'badge-status-success',
        'inactive' => 'badge-status-secondary',
        default => 'badge-secondary',
    };
    return '<span class="badge ' . $class . '">' . htmlspecialchars($status) . '</span>';
}

/**
 * Render Admission Stage Badge HTML
 *
 * @param string $stageName
 * @param int $stepNumber
 * @return string
 */
function stage_badge($stageName, $stepNumber = null) {
    $stepPrefix = $stepNumber ? '<span class="stage-step-num">' . $stepNumber . '</span> ' : '';
    return '<span class="badge badge-stage stage-step-' . ($stepNumber ?? 'default') . '">' . $stepPrefix . htmlspecialchars($stageName) . '</span>';
}

/**
 * Format date for friendly display
 *
 * @param string|null $date
 * @param string $format
 * @return string
 */
function format_date($date, $format = 'M d, Y') {
    if (!$date || $date === '0000-00-00') return '-';
    $d = new DateTime($date);
    return $d->format($format);
}

/**
 * Format datetime
 *
 * @param string|null $datetime
 * @param string $format
 * @return string
 */
function format_datetime($datetime, $format = 'M d, Y h:i A') {
    if (!$datetime || $datetime === '0000-00-00 00:00:00') return '-';
    $d = new DateTime($datetime);
    return $d->format($format);
}

/**
 * Human friendly time ago string
 *
 * @param string|null $datetime
 * @return string
 */
function time_ago($datetime) {
    if (!$datetime) return '';
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;
    
    if ($difference < 60) {
        return 'just now';
    } elseif ($difference < 3600) {
        $minutes = round($difference / 60);
        return $minutes . ' min' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($difference < 86400) {
        $hours = round($difference / 3600);
        return $hours . ' hr' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($difference < 604800) {
        $days = round($difference / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M d, Y', $timestamp);
    }
}

/**
 * Get CRM institutional settings
 *
 * @param string|null $key
 * @param mixed $default
 * @return mixed
 */
function get_crm_settings($key = null, $default = null) {
    $file = __DIR__ . '/crm_settings.json';
    $defaults = [
        'university_name' => 'Apex Global University',
        'portal_title' => 'University Student Admissions CRM',
        'campus_location' => 'London & International Campuses',
        'academic_intake' => 'Fall 2026 / Spring 2027',
        'default_currency' => 'USD ($)',
        'system_timezone' => 'UTC+05:30 (Asia/Kolkata)',
        'date_format' => 'M d, Y',
        'lead_assignment' => 'Caseload Balancing',
        'auto_assign_enquiries' => '1',
        'priority_score_threshold' => '75',
        'followup_sla_hours' => '24',
        'max_caseload_per_counsellor' => '30',
        'notify_new_enquiry' => '1',
        'notify_whatsapp_alerts' => '1',
        'escalate_overdue_hours' => '48',
        'session_timeout_mins' => '60',
        'audit_log_retention_days' => '365'
    ];

    if (file_exists($file)) {
        $saved = json_decode(file_get_contents($file), true);
        if (is_array($saved)) {
            $defaults = array_merge($defaults, $saved);
        }
    }

    if ($key === null) {
        return $defaults;
    }

    return $defaults[$key] ?? $default;
}

/**
 * Save CRM institutional settings
 *
 * @param array $settings
 * @return bool
 */
function save_crm_settings(array $settings) {
    $file = __DIR__ . '/crm_settings.json';
    $current = get_crm_settings();
    $merged = array_merge($current, $settings);
    return (bool)file_put_contents($file, json_encode($merged, JSON_PRETTY_PRINT));
}

/**
 * Canonical Phone Normalization
 * Removes spaces, hyphens, parentheses, +91, leading 91, leading 0
 *
 * @param string|null $phone
 * @return string
 */
function normalize_phone($phone) {
    if (empty($phone)) return '';
    $digits = preg_replace('/[^0-9]/', '', (string)$phone);
    if (empty($digits)) return '';
    
    // Indian standard 10-digit mobile
    if (strlen($digits) === 12 && substr($digits, 0, 2) === '91') {
        return substr($digits, 2);
    }
    if (strlen($digits) === 11 && substr($digits, 0, 1) === '0') {
        return substr($digits, 1);
    }
    if (strlen($digits) === 10) {
        return $digits;
    }
    return $digits;
}

/**
 * Check if a normalized phone number already exists in active leads or customers
 *
 * @param string $phone
 * @param int|null $excludeLeadId
 * @return array|false Returns match details or false if unique
 */
function check_phone_duplicate($phone, $excludeLeadId = null) {
    global $pdo;
    $norm = normalize_phone($phone);
    if (empty($norm)) return false;

    $sql = "
        SELECT l.id, l.lead_code, l.full_name, l.mobile_number, l.is_customer, l.customer_code,
               b.name AS branch_name, u.name AS counselor_name
        FROM leads l
        LEFT JOIN branches b ON l.branch_id = b.id
        LEFT JOIN users u ON l.assigned_counselor_id = u.id
        WHERE l.phone_normalized = ? AND l.is_archived = 0
    ";
    $params = [$norm];
    if ($excludeLeadId) {
        $sql .= " AND l.id != ?";
        $params[] = (int)$excludeLeadId;
    }
    $sql .= " LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $match = $stmt->fetch();
    return $match ?: false;
}

/**
 * Format Indian / General Currency
 *
 * @param float|int $amount
 * @param string $symbol
 * @return string
 */
function format_currency($amount, $symbol = '₹') {
    return $symbol . ' ' . number_format((float)$amount, 2);
}

/**
 * Generate 1 or 2 letter uppercase initials from user name
 * (Handles honorifics like Dr., Prof., Mr., Ms., etc.)
 *
 * @param string $name
 * @return string
 */
function user_initials($name) {
    if (empty($name)) return 'U';
    $cleanName = preg_replace('/^(Dr\.|Prof\.|Mr\.|Mrs\.|Ms\.|Doctor|Professor)\s+/i', '', trim($name));
    $parts = preg_split('/\s+/', trim($cleanName));
    if (count($parts) >= 2) {
        return strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));
    }
    return strtoupper(substr($cleanName, 0, min(2, strlen($cleanName))));
}

/**
 * Log Permanent Deletion with User Justification
 *
 * @param string $recordType
 * @param int $recordId
 * @param string $recordIdentifier
 * @param string $justification
 * @return bool
 */
function log_permanent_deletion($recordType, $recordId, $recordIdentifier, $justification) {
    global $pdo;
    $user = current_user();
    if (!$pdo || !$user) return false;
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $pdo->prepare("
            INSERT INTO deletion_logs 
            (user_id, record_type, record_id, record_identifier, justification, ip_address, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        return $stmt->execute([
            $user['id'],
            $recordType,
            (int)$recordId,
            $recordIdentifier,
            $justification,
            $ip
        ]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Clean phone number to digits only
 *
 * @param string|null $phone
 * @return string
 */
function clean_phone_number($phone) {
    return normalize_phone($phone);
}

/**
 * Generate Direct WhatsApp Link with pre-filled greeting
 *
 * @param string $phone
 * @param string $name
 * @param string $context
 * @return string
 */
function whatsapp_url($phone, $name = '', $context = 'Admissions Follow-up') {
    $clean = clean_phone_number($phone);
    if (empty($clean)) return '#';
    
    $settings = get_crm_settings();
    $uniName = $settings['university_name'] ?? 'Apex Global University';
    
    $greeting = "Hello " . (!empty($name) ? $name : "there") . "! ";
    $greeting .= "This is from {$uniName} Admissions. I am following up regarding your academic inquiry. Are you available for a brief conversation?";
    
    return 'https://api.whatsapp.com/send?phone=' . urlencode($clean) . '&text=' . urlencode($greeting);
}

/**
 * Generate Telephone link
 *
 * @param string $phone
 * @return string
 */
function tel_url($phone) {
    $clean = clean_phone_number($phone);
    return !empty($clean) ? 'tel:+' . $clean : '#';
}

/**
 * Render quick contact action buttons (WhatsApp, Phone, Email)
 *
 * @param string $phone
 * @param string $name
 * @param string $email
 * @return string
 */
function render_contact_actions($phone, $name = '', $email = '') {
    if (empty($phone) && empty($email)) return '<span class="text-muted">-</span>';
    
    $html = '<div class="contact-actions-wrap" style="display:inline-flex; align-items:center; gap:5px;">';
    
    if (!empty($phone)) {
        $wa = whatsapp_url($phone, $name);
        $tel = tel_url($phone);
        $html .= '<a href="' . htmlspecialchars($wa) . '" target="_blank" rel="noopener noreferrer" class="btn-contact-action btn-wa" title="WhatsApp ' . htmlspecialchars($name) . '"><i class="fa-brands fa-whatsapp"></i></a>';
        $html .= '<a href="' . htmlspecialchars($tel) . '" class="btn-contact-action btn-tel" title="Call ' . htmlspecialchars($phone) . '"><i class="fa-solid fa-phone"></i></a>';
    }
    
    if (!empty($email)) {
        $html .= '<a href="mailto:' . htmlspecialchars($email) . '" class="btn-contact-action btn-mail" title="Email ' . htmlspecialchars($email) . '"><i class="fa-regular fa-envelope"></i></a>';
    }
    
    $html .= '</div>';
    return $html;
}

/**
 * Award Reward / Incentive Points
 *
 * @param string $targetType 'student' or 'staff'
 * @param int $targetId
 * @param int $points
 * @param string $reason
 * @param int|null $createdBy
 * @return bool
 */
function award_points($targetType, $targetId, $points, $reason, $createdBy = null) {
    global $pdo;
    if (!$pdo || (int)$points <= 0 || (int)$targetId <= 0) return false;
    try {
        $stmt = $pdo->prepare("INSERT INTO points_transactions (target_type, target_id, points, type, category, description, created_by, created_at) VALUES (?, ?, ?, 'Earned', 'Activity Reward', ?, ?, NOW())");
        $stmt->execute([$targetType, (int)$targetId, (int)$points, $reason, $createdBy]);
        
        if ($targetType === 'student') {
            $upd = $pdo->prepare("UPDATE students SET points_balance = points_balance + ? WHERE id = ?");
            $upd->execute([(int)$points, (int)$targetId]);
        }
        return true;
    } catch (Exception $e) {
        return false;
    }
}

