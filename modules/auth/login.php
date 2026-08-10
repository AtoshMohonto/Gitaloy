<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

if (isset($_GET['logout'])) {
    logoutUser();
}

if (!empty($_SESSION['user_id'])) {
    header('Location: ' . appBaseUrl() . '/modules/dashboard/index.php');
    exit;
}

$error = null;
$settings = getSettings();
$loginTitle = $settings['login_title'] ?? 'Welcome to Gitaloy';
$loginSubtitle = $settings['login_subtitle'] ?? 'Village student management for free education.';
$loginButton = $settings['login_button'] ?? 'Sign in';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!validateCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid request. Please refresh and try again.';
    } elseif ($login === '' || $password === '') {
        $error = 'Username/email and password are required.';
    } else {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE (username = ? OR email = ?) LIMIT 1');
        $stmt->execute([$login, $login]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $error = 'Invalid username/email or password.';
        } elseif ((int) $user['is_active'] !== 1) {
            $error = 'This account is disabled or still pending admin approval. Contact the administrator.';
        } else {
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role_id'] = (int) $user['role_id'];
            $_SESSION['role_name'] = roleName((int) $user['role_id']);
            logActivity('Logged in', 'auth');
            header('Location: ' . appBaseUrl() . '/modules/dashboard/index.php');
            exit;
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>
<main class="flex-1 flex items-center justify-center px-4 py-16">
    <div class="w-full max-w-md rounded-2xl border border-emerald-100 bg-white p-8 shadow-sm">
        <div class="text-center">
            <span class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-emerald-700 text-2xl text-white">🌾</span>
            <h1 class="mt-4 text-2xl font-bold text-slate-900"><?= htmlspecialchars($loginTitle) ?></h1>
            <p class="mt-2 text-sm text-slate-600"><?= htmlspecialchars($loginSubtitle) ?></p>
        </div>

        <?php if ($error !== null): ?>
            <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form class="mt-6 space-y-4" method="post" action="<?= appBaseUrl() ?>/modules/auth/login.php">
            <?= csrfField() ?>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Username or email</label>
                <input type="text" name="login" class="w-full rounded-xl border border-emerald-200 px-3 py-2" required autofocus>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Password</label>
                <input type="password" name="password" class="w-full rounded-xl border border-emerald-200 px-3 py-2" required>
            </div>
            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-900 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700"><i data-lucide="log-in" class="h-4 w-4"></i><?= htmlspecialchars($loginButton) ?></button>
        </form>

        <p class="mt-4 text-center text-sm text-slate-600">Students use their student ID as username.</p>
        <p class="mt-2 text-center text-sm text-slate-600">
            New here? <a href="<?= appBaseUrl() ?>/modules/auth/register.php" class="font-semibold text-emerald-700 hover:underline">Create an account</a>
        </p>
    </div>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
