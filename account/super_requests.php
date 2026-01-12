<?php
// super_requests.php
session_start();
require_once 'config.php';

// NOTE: In production, protect this page: check that the logged-in user is a super admin.
// e.g., if (!isset($_SESSION['super_admin_id'])) { header('Location: login_super.php'); exit; }

$requests = $conn->query("SELECT * FROM restaurant_requests ORDER BY created_at DESC");
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Super Admin - Requests</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
  <h3>Restaurant Registration Requests</h3>
  <table class="table table-striped mt-3">
    <thead>
      <tr>
        <th>#</th>
        <th>Logo</th>
        <th>Name</th>
        <th>Phone</th>
        <th>Address</th>
        <th>Status</th>
        <th>Submitted</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($r = $requests->fetch_assoc()): ?>
        <tr>
          <td><?= $r['id'] ?></td>
          <td>
            <?php if ($r['logo']): ?>
              <img src="<?=htmlspecialchars($r['logo'])?>" style="height:40px;">
            <?php endif; ?>
          </td>
          <td><?=htmlspecialchars($r['restaurant_name'])?></td>
          <td><?=htmlspecialchars($r['phone'])?></td>
          <td style="max-width:240px;"><?=nl2br(htmlspecialchars($r['address']))?></td>
          <td><?=htmlspecialchars($r['status'])?></td>
          <td><?=htmlspecialchars($r['created_at'])?></td>
          <td>
            <?php if ($r['status'] === 'Pending'): ?>
              <a class="btn btn-sm btn-success" href="approve_request.php?id=<?=$r['id']?>" onclick="return confirm('Approve this request?')">Approve</a>
              <a class="btn btn-sm btn-danger" href="reject_request.php?id=<?=$r['id']?>" onclick="return confirm('Reject this request?')">Reject</a>
            <?php else: ?>
              <small class="text-muted">Processed</small>
            <?php endif; ?>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</body>
</html>
