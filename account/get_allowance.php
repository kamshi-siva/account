<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once "config.php";

if (!isset($_SESSION['restaurant_admin_id'])) {
    header("Location: index.php");
    exit();
}

$restaurant_id = $_SESSION['restaurant_id'];
$cashier_id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT name, phone, ic_no, address, id_image FROM cashiers WHERE id=? AND restaurant_id=?");
$stmt->bind_param("ii", $cashier_id, $restaurant_id);
$stmt->execute();
$cashier = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$cashier) die("Cashier not found.");

$errors = [];
$success = '';

$months = [
    'January'=>1,'February'=>2,'March'=>3,'April'=>4,
    'May'=>5,'June'=>6,'July'=>7,'August'=>8,
    'September'=>9,'October'=>10,'November'=>11,'December'=>12
];

// Give Advance
if (isset($_POST['give_advance'])) {
    $amount = floatval($_POST['amount']);
    $reason = trim($_POST['reason']);
    $given_date = $_POST['given_date'];

    if ($amount <= 0 || !$given_date) {
        $errors[] = "Please enter a valid amount and date.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO cashier_advance (cashier_id, restaurant_id, amount, reason, given_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iidss", $cashier_id, $restaurant_id, $amount, $reason, $given_date);

        if ($stmt->execute()) {
            $success = "Advance of Rs. " . number_format($amount, 2) . " given successfully.";
        } else {
            $errors[] = "Error giving advance: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Add Salary
if (isset($_POST['add_salary'])) {
    $month = trim($_POST['month']);
    $year = intval($_POST['year']);
    $basic_salary = floatval($_POST['basic_salary']);
    $bonus = floatval($_POST['bonus']);
    $deductions = floatval($_POST['deductions']);
    $allowance = floatval($_POST['allowance']);

    if (!$month || !$year || $basic_salary <= 0) {
        $errors[] = "Month, Year, and Basic Salary are required.";
    }

    if (empty($errors)) {
        $month_num = $months[$month] ?? 0;
        if ($month_num == 0) $errors[] = "Invalid month selected.";

        if (empty($errors)) {
            // Total advance for this month
            $stmt_adv = $conn->prepare("
                SELECT IFNULL(SUM(amount),0) AS total_advance
                FROM cashier_advance
                WHERE cashier_id=? AND restaurant_id=? 
                  AND MONTH(given_date)=? AND YEAR(given_date)=?
            ");
            $stmt_adv->bind_param("iiii", $cashier_id, $restaurant_id, $month_num, $year);
            $stmt_adv->execute();
            $adv_result = $stmt_adv->get_result()->fetch_assoc();
            $total_advance = $adv_result['total_advance'] ?? 0;
            $stmt_adv->close();

            $total_deductions = $deductions + $total_advance;
            $total_salary = $basic_salary + $bonus + $allowance - $total_deductions;

            $stmt = $conn->prepare("
                INSERT INTO cashier_salary 
                (cashier_id, restaurant_id, month, year, basic_salary, bonus, allowance, deductions, total_salary) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("iissddddd", $cashier_id, $restaurant_id, $month, $year, $basic_salary, $bonus, $allowance, $total_deductions, $total_salary);

            if ($stmt->execute()) {
                $success = "Salary added successfully. Advance deducted: Rs. " . number_format($total_advance, 2);
            } else {
                $errors[] = "Database error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Fetch salary records
$stmt2 = $conn->prepare("
    SELECT month, year, basic_salary, bonus, allowance, deductions, total_salary 
    FROM cashier_salary 
    WHERE cashier_id=? 
    ORDER BY year DESC, FIELD(month,
        'December','November','October','September','August','July',
        'June','May','April','March','February','January')
");
$stmt2->bind_param("i", $cashier_id);
$stmt2->execute();
$salary = $stmt2->get_result();
$stmt2->close();

// Fetch advances
$stmt3 = $conn->prepare("
    SELECT amount, reason, given_date 
    FROM cashier_advance 
    WHERE cashier_id=? AND restaurant_id=? 
    ORDER BY given_date DESC
");
$stmt3->bind_param("ii", $cashier_id, $restaurant_id);
$stmt3->execute();
$advances = $stmt3->get_result();
$stmt3->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cashier Salary - <?= htmlspecialchars($cashier['name']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { background:#f5f7fa; font-family:'Inter',sans-serif; overflow-x:hidden; }
.img-thumb { width:90px; height:90px; border-radius:10px; object-fit:cover; border:2px solid #ddd; }
.salary-table th, .salary-table td { vertical-align:middle; text-align:center; }
.main-content { margin-left:180px; padding:80px 20px; transition:margin-left 0.3s; }
.sidebar { width:180px; position:fixed; top:56px; left:0; height:100%; background:#fff; border-right:1px solid #e3e6f0; padding:1rem; transition:all 0.3s; }
.sidebar ul.nav li a { color:#495057; text-decoration:none; display:block; padding:12px 20px; border-left:4px solid transparent; border-radius:0 8px 8px 0; margin:4px 0; font-weight:500; }
.sidebar ul.nav li a:hover, .sidebar ul.nav li a.active { background:linear-gradient(90deg,#dbe4ff,#fff); border-left:4px solid #0d6efd; color:#0d6efd; }
.sidebar.collapsed { margin-left:-180px; }
.navbar { position:fixed; top:0; width:100%; z-index:1000; }
.main-content.expanded { margin-left:0; }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <button class="btn btn-primary me-2" id="sidebarToggle"><i class="bi bi-list"></i></button>
    <a class="navbar-brand">Restaurant Dashboard</a>
    <div class="ms-auto">
      <a href="cashiers.php" class="btn btn-outline-light btn-sm">Back</a>
      <a href="logout_restaurant.php" class="btn btn-outline-light btn-sm ms-2">Logout</a>
    </div>
  </div>
</nav>

<div class="sidebar" id="sidebar">
    <ul class="nav flex-column">
        <li><a href="restaurant_dashboard.php"><i class="bi bi-house-door me-2"></i>Dashboard</a></li>
        <li><a href="orders.php"><i class="bi bi-receipt me-2"></i>Orders</a></li>
        <li><a href="products.php"><i class="bi bi-box-seam me-2"></i>Products</a></li>
        <li><a href="categories.php"><i class="bi bi-tags me-2"></i>Categories</a></li>
        <li><a href="cashiers.php" class="active"><i class="bi bi-people me-2"></i>Cashiers</a></li>
    </ul>
</div>

<div class="main-content" id="mainContent">
    <?php if ($errors): ?>
        <div class="alert alert-danger"><?= implode('<br>', $errors) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body d-flex align-items-center">
            <?php if ($cashier['id_image']): ?>
                <img src="uploads/cashiers/<?= htmlspecialchars($cashier['id_image']) ?>" class="img-thumb me-3" alt="Cashier ID">
            <?php endif; ?>
            <div>
                <h4 class="mb-1"><?= htmlspecialchars($cashier['name']) ?></h4>
                <p class="mb-0"><i class="bi bi-telephone"></i> <?= htmlspecialchars($cashier['phone']) ?></p>
                <p class="mb-0"><i class="bi bi-person-vcard"></i> <?= htmlspecialchars($cashier['ic_no']) ?></p>
                <p class="mb-0"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($cashier['address']) ?></p>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end mb-3">
        <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#giveAdvanceModal">
            <i class="bi bi-cash-coin"></i> Give Advance
        </button>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSalaryModal">
            <i class="bi bi-wallet2"></i> Add Salary
        </button>
    </div>

    <!-- Salary Table -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="bi bi-wallet2"></i> Salary Details</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped salary-table">
                    <thead class="table-light">
                        <tr>
                            <th>Month</th>
                            <th>Year</th>
                            <th>Basic Salary</th>
                            <th>Bonus</th>
                            <th>Allowance</th>
                            <th>Deductions</th>
                            <th>Total Salary</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($salary->num_rows > 0): ?>
                            <?php while($row = $salary->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['month']) ?></td>
                                    <td><?= htmlspecialchars($row['year']) ?></td>
                                    <td><?= number_format($row['basic_salary'], 2) ?></td>
                                    <td><?= number_format($row['bonus'], 2) ?></td>
                                    <td><?= number_format($row['allowance'], 2) ?></td>
                                    <td><?= number_format($row['deductions'], 2) ?></td>
                                    <td><strong><?= number_format($row['total_salary'], 2) ?></strong></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center">No salary records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Advance Table -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0"><i class="bi bi-cash-stack"></i> Advance History</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped salary-table">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($advances->num_rows > 0): ?>
                            <?php while($row = $advances->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['given_date']) ?></td>
                                    <td><?= number_format($row['amount'], 2) ?></td>
                                    <td><?= htmlspecialchars($row['reason']) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="text-center">No advance records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
<!-- Give Advance Modal -->
<div class="modal fade" id="giveAdvanceModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title"><i class="bi bi-cash-coin"></i> Give Advance to <?= htmlspecialchars($cashier['name']) ?></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
            <label class="form-label">Amount</label>
            <input type="number" name="amount" step="0.01" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Reason</label>
            <input type="text" name="reason" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Date</label>
            <input type="date" name="given_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" name="give_advance" class="btn btn-success"><i class="bi bi-send"></i> Give Advance</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Add Salary Modal -->
<div class="modal fade" id="addSalaryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" class="modal-content" id="addSalaryForm">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title"><i class="bi bi-wallet2"></i> Add Salary for <?= htmlspecialchars($cashier['name']) ?></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
            <label class="form-label">Month</label>
            <input type="text" name="month" class="form-control" placeholder="e.g. October" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Year</label>
            <input type="number" name="year" class="form-control" value="<?= date('Y') ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Basic Salary</label>
            <input type="number" name="basic_salary" step="0.01" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Bonus</label>
            <input type="number" name="bonus" step="0.01" class="form-control" value="0">
        </div>
        <div class="mb-3">
            <label class="form-label">Other Deductions</label>
            <input type="number" name="deductions" step="0.01" class="form-control" value="0">
        </div>
        <div class="mb-3">
            <label class="form-label">Estimated Allowance</label>
            <input type="text" id="estimated_allowance" class="form-control" readonly value="0.00">
        </div>
        <div class="mb-3">
            <label class="form-label">Total Salary</label>
            <input type="text" id="total_salary" class="form-control" readonly value="0.00">
        </div>
        <input type="hidden" name="allowance" id="hidden_allowance">
      </div>
      <div class="modal-footer">
        <button type="submit" name="add_salary" class="btn btn-primary"><i class="bi bi-save"></i> Save Salary</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const sidebar = document.getElementById('sidebar');
const mainContent = document.getElementById('mainContent');
document.getElementById('sidebarToggle').addEventListener('click', ()=>{
    sidebar.classList.toggle('collapsed');
    mainContent.classList.toggle('expanded');
});

// Add Salary Modal logic
const monthInput = document.querySelector('input[name="month"]');
const yearInput = document.querySelector('input[name="year"]');
const basicInput = document.querySelector('input[name="basic_salary"]');
const bonusInput = document.querySelector('input[name="bonus"]');
const deductionsInput = document.querySelector('input[name="deductions"]');
const estimatedAllowance = document.getElementById('estimated_allowance');
const hiddenAllowance = document.getElementById('hidden_allowance');
const totalSalaryInput = document.getElementById('total_salary');

function fetchAllowance() {
    const month = monthInput.value.trim();
    const year = yearInput.value.trim();
    if (!month || !year) return;

    fetch(`get_allowance.php?cashier_id=<?= $cashier_id ?>&month=${month}&year=${year}`)
        .then(res => res.json())
        .then(data => {
            let allowance = 0;
            if (data.allowance !== undefined) {
                allowance = data.allowance;
            }
            estimatedAllowance.value = "Rs. " + allowance;
            hiddenAllowance.value = allowance;
            updateTotalSalary();
        });
}

function updateTotalSalary() {
    const basic = parseFloat(basicInput.value) || 0;
    const bonus = parseFloat(bonusInput.value) || 0;
    const deductions = parseFloat(deductionsInput.value) || 0;
    const allowance = parseFloat(hiddenAllowance.value) || 0;

    const total = basic + bonus + allowance - deductions;
    totalSalaryInput.value = total.toFixed(2);
}

// Event listeners
monthInput.addEventListener('change', fetchAllowance);
yearInput.addEventListener('change', fetchAllowance);
basicInput.addEventListener('input', updateTotalSalary);
bonusInput.addEventListener('input', updateTotalSalary);
deductionsInput.addEventListener('input', updateTotalSalary);

</script>
</body>
</html>
