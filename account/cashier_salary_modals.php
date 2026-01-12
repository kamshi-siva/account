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
    <form method="POST" class="modal-content">
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
      </div>
      <div class="modal-footer">
        <button type="submit" name="add_salary" class="btn btn-primary"><i class="bi bi-save"></i> Save Salary</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Allowance Rules Modal -->
<div class="modal fade" id="allowanceRulesModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title"><i class="bi bi-gear"></i> Allowance Rules</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
            <label class="form-label">Default Allowance</label>
            <input type="number" name="default_allowance" step="0.01" class="form-control" value="<?= $current_rules['default_allowance'] ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Year-End Bonus (December)</label>
            <input type="number" name="year_end_bonus" step="0.01" class="form-control" value="<?= $current_rules['year_end_bonus'] ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Sales Threshold for Extra Allowance</label>
            <input type="number" name="sales_threshold" step="0.01" class="form-control" value="<?= $current_rules['sales_threshold'] ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Extra Allowance if Sales Exceed Threshold</label>
            <input type="number" name="extra_allowance" step="0.01" class="form-control" value="<?= $current_rules['extra_allowance'] ?>" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" name="save_allowance_rules" class="btn btn-warning"><i class="bi bi-save"></i> Save Rules</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div>
</div>
