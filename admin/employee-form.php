<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$id = (int) input('id');
$existing = $id ? one('SELECT * FROM employees WHERE id = ?', [$id]) : null;
if ($id && !$existing) {
    flash('danger', 'That employee does not exist.');
    redirect('admin/employees.php');
}

$fields = ['emp_no', 'name', 'email', 'nic', 'epf_no', 'gender', 'dob', 'phone', 'department_id', 'designation',
           'join_date', 'basic_salary', 'fixed_allowance', 'other_allowance', 'bank_name', 'bank_account'];

if ($existing) {
    $data = array_intersect_key($existing, array_flip($fields));
} else {
    $next = (int) scalar('SELECT COUNT(*) FROM employees') + 1;
    do {
        $empNo = sprintf('EMP%03d', $next++);
    } while (scalar('SELECT 1 FROM employees WHERE emp_no = ?', [$empNo]));
    $data = array_fill_keys($fields, '');
    $data['emp_no'] = $empNo;
    $data['fixed_allowance'] = '3500';
    $data['other_allowance'] = '0';
    $data['join_date'] = date('Y-m-d');
}
$errors = [];

if (is_post()) {
    verify_csrf();
    foreach ($fields as $f) {
        $data[$f] = input($f);
    }
    $data['email'] = mb_strtolower($data['email']);
    $data['nic'] = strtoupper(str_replace(' ', '', $data['nic']));
    $password = (string) ($_POST['password'] ?? '');

    if ($data['name'] === '') $errors['name'] = 'Enter the full name.';
    if ($data['emp_no'] === '') $errors['emp_no'] = 'Enter an employee number.';
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email address.';
    if ($data['nic'] !== '' && !preg_match('/^(\d{9}[VX]|\d{12})$/', $data['nic'])) {
        $errors['nic'] = 'NIC must be 9 digits + V/X (old) or 12 digits (new).';
    }
    foreach (['dob', 'join_date'] as $d) {
        if ($data[$d] !== '' && !valid_date($data[$d])) $errors[$d] = 'Enter a valid date.';
    }
    foreach (['basic_salary', 'fixed_allowance', 'other_allowance'] as $m) {
        if ($data[$m] === '') $data[$m] = '0';
        if (!is_numeric($data[$m]) || (float) $data[$m] < 0) $errors[$m] = 'Enter an amount of 0 or more.';
    }
    if ((float) $data['basic_salary'] <= 0 && !isset($errors['basic_salary'])) $errors['basic_salary'] = 'Enter the basic salary.';
    if (!$existing && strlen($password) < 8) $errors['password'] = 'Set a password of at least 8 characters.';
    if ($existing && $password !== '' && strlen($password) < 8) $errors['password'] = 'Passwords must be at least 8 characters.';
    if (!$errors && scalar('SELECT 1 FROM employees WHERE email = ? AND id <> ?', [$data['email'], $id])) {
        $errors['email'] = 'Another employee already uses this email.';
    }
    if (!$errors && scalar('SELECT 1 FROM employees WHERE emp_no = ? AND id <> ?', [$data['emp_no'], $id])) {
        $errors['emp_no'] = 'This employee number is already taken.';
    }

    if (!$errors) {
        $row = $data;
        foreach (['nic', 'epf_no', 'gender', 'dob', 'phone', 'designation', 'join_date', 'bank_name', 'bank_account'] as $nullable) {
            $row[$nullable] = $row[$nullable] === '' ? null : $row[$nullable];
        }
        $row['department_id'] = $row['department_id'] === '' ? null : (int) $row['department_id'];
        if ($password !== '') {
            $row['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }
        if ($existing) {
            update('employees', $row, $id);
            flash('success', "{$data['name']}'s details were saved. Changes to pay apply from the next payroll run.");
        } else {
            $row['status'] = 'active';
            $row['created_at'] = date('Y-m-d H:i:s');
            insert('employees', $row);
            flash('success', "{$data['name']} was added. They can now sign in with their email and password.");
        }
        redirect('admin/employees.php');
    }
}

$departments = all('SELECT id, name FROM departments ORDER BY name');
$rates = payroll_rates();

function field_error(array $errors, string $key): string
{
    return isset($errors[$key]) ? '<div class="invalid-feedback d-block">' . e($errors[$key]) . '</div>' : '';
}
function invalid(array $errors, string $key): string
{
    return isset($errors[$key]) ? ' is-invalid' : '';
}

render_header($existing ? 'Edit employee' : 'Add employee', 'admin', 'employees', $existing ? $existing['name'] . ' · ' . $existing['emp_no'] : 'Create the employee record and their login.');
?>
<form method="post" novalidate>
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-xl-8 d-grid gap-3">
            <div class="card-app">
                <div class="card-header-app"><h2><i class="bi bi-person me-1"></i> Personal details</h2></div>
                <div class="card-body-app row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Full name *</label>
                        <input class="form-control<?= invalid($errors, 'name') ?>" name="name" value="<?= e($data['name']) ?>" required>
                        <?= field_error($errors, 'name') ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email (used to sign in) *</label>
                        <input class="form-control<?= invalid($errors, 'email') ?>" type="email" name="email" value="<?= e($data['email']) ?>" required>
                        <?= field_error($errors, 'email') ?>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">NIC number</label>
                        <input class="form-control<?= invalid($errors, 'nic') ?>" name="nic" value="<?= e($data['nic']) ?>" placeholder="199712345678">
                        <?= field_error($errors, 'nic') ?>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date of birth</label>
                        <input class="form-control<?= invalid($errors, 'dob') ?>" type="date" name="dob" value="<?= e($data['dob']) ?>">
                        <?= field_error($errors, 'dob') ?>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Gender</label>
                        <select class="form-select" name="gender">
                            <option value="">—</option>
                            <?php foreach (['Male', 'Female', 'Other'] as $g): ?>
                                <option <?= $data['gender'] === $g ? 'selected' : '' ?>><?= $g ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Phone</label>
                        <input class="form-control" name="phone" value="<?= e($data['phone']) ?>" placeholder="077 123 4567">
                    </div>
                </div>
            </div>

            <div class="card-app">
                <div class="card-header-app"><h2><i class="bi bi-briefcase me-1"></i> Job</h2></div>
                <div class="card-body-app row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Employee no *</label>
                        <input class="form-control<?= invalid($errors, 'emp_no') ?>" name="emp_no" value="<?= e($data['emp_no']) ?>" required>
                        <?= field_error($errors, 'emp_no') ?>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">EPF member no</label>
                        <input class="form-control" name="epf_no" value="<?= e($data['epf_no']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Designation</label>
                        <input class="form-control" name="designation" value="<?= e($data['designation']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Department</label>
                        <select class="form-select" name="department_id">
                            <option value="">Unassigned</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= (int) $d['id'] ?>" <?= (string) $data['department_id'] === (string) $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Join date</label>
                        <input class="form-control<?= invalid($errors, 'join_date') ?>" type="date" name="join_date" value="<?= e($data['join_date']) ?>">
                        <?= field_error($errors, 'join_date') ?>
                    </div>
                </div>
            </div>

            <div class="card-app">
                <div class="card-header-app"><h2><i class="bi bi-cash me-1"></i> Monthly pay</h2></div>
                <div class="card-body-app row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Basic salary (Rs.) *</label>
                        <input class="form-control<?= invalid($errors, 'basic_salary') ?>" type="number" min="0" step="0.01" name="basic_salary" value="<?= e($data['basic_salary']) ?>" required>
                        <?= field_error($errors, 'basic_salary') ?>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">EPF-liable allowance</label>
                        <input class="form-control<?= invalid($errors, 'fixed_allowance') ?>" type="number" min="0" step="0.01" name="fixed_allowance" value="<?= e($data['fixed_allowance']) ?>">
                        <div class="form-text">e.g. budgetary relief allowance (Rs. 3,500)</div>
                        <?= field_error($errors, 'fixed_allowance') ?>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Other allowances</label>
                        <input class="form-control<?= invalid($errors, 'other_allowance') ?>" type="number" min="0" step="0.01" name="other_allowance" value="<?= e($data['other_allowance']) ?>">
                        <div class="form-text">Travel, fuel etc. (no EPF/ETF)</div>
                        <?= field_error($errors, 'other_allowance') ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Bank</label>
                        <input class="form-control" name="bank_name" value="<?= e($data['bank_name']) ?>" placeholder="Commercial Bank">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Account number</label>
                        <input class="form-control" name="bank_account" value="<?= e($data['bank_account']) ?>">
                    </div>
                </div>
            </div>

            <div class="card-app">
                <div class="card-header-app"><h2><i class="bi bi-key me-1"></i> Login</h2></div>
                <div class="card-body-app">
                    <label class="form-label"><?= $existing ? 'New password (leave blank to keep the current one)' : 'Password *' ?></label>
                    <input class="form-control<?= invalid($errors, 'password') ?>" type="password" name="password" autocomplete="new-password" minlength="8">
                    <?= field_error($errors, 'password') ?>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card-app position-sticky" style="top:1rem" data-salary-preview data-rates='<?= e(json_encode($rates)) ?>'>
                <div class="card-header-app"><h2><i class="bi bi-calculator me-1"></i> Monthly estimate</h2></div>
                <div class="card-body-app">
                    <table class="table table-sm mb-3">
                        <tr><td>Gross pay</td><td class="num" data-k="gross"></td></tr>
                        <tr><td>EPF 8% (employee)</td><td class="num text-danger" data-k="epf8"></td></tr>
                        <tr><td>APIT</td><td class="num text-danger" data-k="apit"></td></tr>
                        <tr class="fw-bold"><td>Take-home</td><td class="num text-success" data-k="net"></td></tr>
                        <tr class="small text-body-secondary"><td>EPF 12% (employer)</td><td class="num" data-k="epf12"></td></tr>
                        <tr class="small text-body-secondary"><td>ETF 3% (employer)</td><td class="num" data-k="etf"></td></tr>
                        <tr class="small fw-semibold"><td>Cost to company</td><td class="num" data-k="cost"></td></tr>
                    </table>
                    <p class="small text-body-secondary mb-3">Before overtime, no-pay leave and other deductions. Updates as you type.</p>
                    <button class="btn btn-primary w-100" type="submit"><?= $existing ? 'Save changes' : 'Add employee' ?></button>
                    <a class="btn btn-link w-100 mt-1" href="<?= e(url('admin/employees.php')) ?>">Cancel</a>
                </div>
            </div>
        </div>
    </div>
</form>
<?php render_footer(); ?>
