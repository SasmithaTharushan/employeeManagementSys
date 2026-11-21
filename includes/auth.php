<?php
declare(strict_types=1);

const MAX_LOGIN_ATTEMPTS = 5;
const LOGIN_LOCK_SECONDS = 60;

function admin_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $id = $_SESSION['admin_id'] ?? null;
        $user = $id ? one('SELECT id, name, email, created_at FROM admins WHERE id = ?', [$id]) : null;
    }
    return $user;
}

function employee_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $id = $_SESSION['employee_id'] ?? null;
        $user = $id ? one(
            "SELECT e.*, d.name AS department
               FROM employees e LEFT JOIN departments d ON d.id = e.department_id
              WHERE e.id = ? AND e.status = 'active'",
            [$id]
        ) : null;
    }
    return $user;
}

function require_admin(): array
{
    $user = admin_user();
    if (!$user) {
        redirect('admin/login.php');
    }
    return $user;
}

function require_employee(): array
{
    $user = employee_user();
    if (!$user) {
        redirect('employee/login.php');
    }
    return $user;
}

/** Seconds left before another login attempt is allowed (0 = allowed). */
function login_lock_remaining(): int
{
    $until = $_SESSION['login_locked_until'] ?? 0;
    return max(0, $until - time());
}

function attempt_login(string $role, string $email, string $password): bool
{
    $table = $role === 'admin' ? 'admins' : 'employees';
    $extra = $role === 'admin' ? '' : ', status';
    $row = one("SELECT id, password_hash$extra FROM $table WHERE email = ?", [mb_strtolower($email)]);

    $ok = $row && password_verify($password, $row['password_hash'])
        && ($role === 'admin' || $row['status'] === 'active');

    if (!$ok) {
        $_SESSION['login_failures'] = ($_SESSION['login_failures'] ?? 0) + 1;
        if ($_SESSION['login_failures'] >= MAX_LOGIN_ATTEMPTS) {
            $_SESSION['login_locked_until'] = time() + LOGIN_LOCK_SECONDS;
            $_SESSION['login_failures'] = 0;
        }
        return false;
    }

    if (password_needs_rehash($row['password_hash'], PASSWORD_DEFAULT)) {
        update($table, ['password_hash' => password_hash($password, PASSWORD_DEFAULT)], (int) $row['id']);
    }

    session_regenerate_id(true);
    unset($_SESSION['admin_id'], $_SESSION['employee_id'], $_SESSION['login_failures'], $_SESSION['login_locked_until']);
    $_SESSION[$role . '_id'] = (int) $row['id'];
    return true;
}

function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/**
 * Shared "change password" handler. Returns an error message, or null on success.
 */
function change_password(string $table, int $id, string $current, string $new, string $confirm): ?string
{
    $hash = scalar("SELECT password_hash FROM $table WHERE id = ?", [$id]);
    if (!$hash || !password_verify($current, $hash)) {
        return 'Your current password is incorrect.';
    }
    if (strlen($new) < 8) {
        return 'The new password must be at least 8 characters.';
    }
    if ($new !== $confirm) {
        return 'The new passwords do not match.';
    }
    update($table, ['password_hash' => password_hash($new, PASSWORD_DEFAULT)], $id);
    return null;
}
