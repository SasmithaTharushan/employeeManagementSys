<?php
declare(strict_types=1);

function config(string $key, $default = null)
{
    $value = $GLOBALS['config'] ?? [];
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

/** Escape a value for HTML output. */
function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Base URL path of the app, e.g. "/" or "/employeeManagementSys/". */
function base_path(): string
{
    static $base = null;
    if ($base === null) {
        $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        if (in_array(basename($dir), ['admin', 'employee'], true)) {
            $dir = dirname($dir);
        }
        $base = rtrim(str_replace('\\', '/', $dir), '/') . '/';
    }
    return $base;
}

function url(string $path = ''): string
{
    return base_path() . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function money($amount, bool $prefix = true): string
{
    return ($prefix ? 'Rs. ' : '') . number_format((float) $amount, 2);
}

function input(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function period_label(string $period): string
{
    return date('F Y', strtotime($period . '-01'));
}

function valid_date(string $value): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $value);
    return $d !== false && $d->format('Y-m-d') === $value;
}

function fmt_date(?string $value): string
{
    return $value ? date('d M Y', strtotime($value)) : '—';
}

/** Inclusive number of calendar days between two Y-m-d dates. */
function days_between(string $start, string $end): int
{
    return (int) (new DateTime($start))->diff(new DateTime($end))->days + 1;
}

/* ---------- CSRF protection ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $sent = $_POST['_csrf'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('Your session expired. Go back, refresh the page and try again.');
    }
}

/* ---------- Display helpers ---------- */

function leave_types(): array
{
    return ['annual' => 'Annual', 'casual' => 'Casual', 'sick' => 'Sick', 'nopay' => 'No-pay'];
}

function status_badge(string $status): string
{
    $map = [
        'pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'cancelled' => 'secondary',
        'draft' => 'warning', 'finalized' => 'success', 'active' => 'success', 'resigned' => 'secondary',
    ];
    $tone = $map[$status] ?? 'secondary';
    return '<span class="badge badge-soft badge-' . $tone . '">' . e(ucfirst($status)) . '</span>';
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $letters = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        $letters .= mb_strtoupper(mb_substr($p, 0, 1));
    }
    return $letters ?: '?';
}
