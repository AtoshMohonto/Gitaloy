<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../users/user_db.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: ' . appBaseUrl() . '/modules/dashboard/index.php');
    exit;
}

$error = null;
$success = null;

$formData = [
    'name' => '',
    'username' => '',
    'email' => '',
    'phone' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['name'] = trim($_POST['name'] ?? '');
    $formData['username'] = trim($_POST['username'] ?? '');
    $formData['email'] = trim($_POST['email'] ?? '');
    $formData['phone'] = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($formData['name'] === '' || $formData['username'] === '' || $password === '') {
        $error = 'Full name, username, and password are required.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (userUsernameExists($formData['username'])) {
        $error = 'That username is already taken.';
    } elseif ($formData['email'] !== '' && userEmailExists($formData['email'])) {
        $error = 'That email is already registered.';
    } else {
        $newUserId = createUser([
            'name' => $formData['name'],
            'username' => $formData['username'],
            'email' => $formData['email'],
            'password' => $password,
            'role_id' => ROLE_STUDENT,
            'phone' => $formData['phone'],
            'scope_type' => '',
            'scope_id' => null,
            'center_id' => null,
            'is_active' => 0,
        ]);
        logActivity(
            'New self-registration: ' . $formData['name'] . ' (@' . $formData['username'] . ')',
            'auth',
            $newUserId,
            $formData['name'],
            ROLE_STUDENT
        );
        $success = 'Account created! An administrator needs to approve your account before you can sign in.';
        $formData = ['name' => '', 'username' => '', 'email' => '', 'phone' => ''];
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>
<main class="flex-1 flex items-center justify-center px-4 py-16">
    <div class="w-full max-w-md rounded-2xl border border-emerald-100 bg-white p-8 shadow-sm">
        <div class="text-center">
            <span class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-emerald-700 text-2xl text-white">🌾</span>
            <h1 class="mt-4 text-2xl font-semibold text-slate-900">Create an account</h1>
            <p class="mt-2 text-sm text-slate-600">Sign up for Gitaloy. An admin will review and approve your account.</p>
        </div>

        <?php if ($success !== null): ?>
            <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"><?= htmlspecialchars($success) ?></div>
            <p class="mt-4 text-center text-sm text-slate-600">
                <a href="<?= appBaseUrl() ?>/modules/auth/login.php" class="font-semibold text-emerald-700 hover:underline">Back to sign in</a>
            </p>
        <?php else: ?>
            <?php if ($error !== null): ?>
                <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form class="mt-6 space-y-4" method="post" action="<?= appBaseUrl() ?>/modules/auth/register.php">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Full name</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($formData['name']) ?>" class="w-full rounded-xl border border-emerald-200 px-3 py-2" required autofocus>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Username</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($formData['username']) ?>" class="w-full rounded-xl border border-emerald-200 px-3 py-2" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Email (optional)</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($formData['email']) ?>" class="w-full rounded-xl border border-emerald-200 px-3 py-2">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Phone (optional)</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($formData['phone']) ?>" class="w-full rounded-xl border border-emerald-200 px-3 py-2">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Password</label>
                    <input type="password" name="password" class="w-full rounded-xl border border-emerald-200 px-3 py-2" required minlength="6">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Confirm password</label>
                    <input type="password" name="confirm_password" class="w-full rounded-xl border border-emerald-200 px-3 py-2" required minlength="6">
                </div>
                <button class="w-full rounded-full bg-emerald-700 px-4 py-3 font-semibold text-white hover:bg-emerald-800">Create account</button>
            </form>

            <p class="mt-4 text-center text-sm text-slate-600">
                Already have an account? <a href="<?= appBaseUrl() ?>/modules/auth/login.php" class="font-semibold text-emerald-700 hover:underline">Sign in</a>
            </p>
        <?php endif; ?>
    </div>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
