<?php require_once BASE_PATH . '/includes/header.php'; ?>

<div class="page-header">
    <h1>🚩 डैशबोर्ड (Dashboard)</h1>
</div>
<div class="card">
    <div class="card-header">Welcome to the New MVC Architecture, <?= htmlspecialchars($user['name']) ?>!</div>
    <div style="padding: 16px;">
        <p><strong>Shakha:</strong> <?= htmlspecialchars($shakha['name'] ?? 'N/A') ?></p>
        <p>This page is now powered by the <code>DashboardController</code> and rendered via the <code>View</code> engine.</p>
        <br>
        <a href="/pages/dashboard.php" class="btn btn-outline">Back to Old Dashboard</a>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
