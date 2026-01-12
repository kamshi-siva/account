<?php
session_start();
include "config.php";

// 🔒 Only logged-in cashiers
if (!isset($_SESSION['cashier_id'])) {
    header("Location: login.php");
    exit();
}

$cashier_id = $_SESSION['cashier_id'];
$restaurant_id = $_SESSION['restaurant_id'];

// Get cashier name for greeting
$cashier_name = $conn->query("SELECT name FROM cashiers WHERE id=$cashier_id")->fetch_assoc()['name'] ?? "Cashier";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>POS System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
.btn-gradient { background: linear-gradient(135deg, #1a9db178, #42dbe392); border: none; color: #000; transition: transform 0.2s, box-shadow 0.2s; }
.btn-gradient:hover { transform: scale(1.05); box-shadow: 0 4px 12px rgba(0,0,0,0.2); color: #000; }
.product-card { cursor: pointer; padding: 10px; margin: 5px 0; border-radius: 6px; background-color: #ffffffd1; transition: transform 0.2s, box-shadow 0.2s; }
.product-card:hover { transform: scale(1.02); box-shadow: 0 2px 10px rgba(0,0,0,0.15); }
</style>
</head>
<body>

<div class="row p-3">
  <div class="col-lg-8 products shadow-sm rounded" style="background-color: #dce9eec8;">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="text-primary fw-bold">Food Menu</h2>
      <a href="logout.php" class="btn btn-sm btn-danger">Logout</a>
    </div>

    <div class="filter-btns mb-3">
      <button class="btn btn-gradient btn-sm me-1" onclick="filterCategory('all')">All</button>
      <?php
      // Get categories for this restaurant
      $catResult = $conn->query("SELECT * FROM categories WHERE restaurant_id=$restaurant_id ORDER BY id ASC");
      if ($catResult && $catResult->num_rows > 0) {
          while ($cat = $catResult->fetch_assoc()) {
              $catName = htmlspecialchars($cat['name']);
              $catSlug = strtolower($catName);
              echo "<button class='btn btn-gradient btn-sm me-1' onclick=\"filterCategory('{$catSlug}')\">{$catName}</button> ";
          }
      }
      ?>
    </div>

    <input type="text" id="searchInput" class="form-control" placeholder="Search food...">

    <div id="productList" class="mt-3">
      <?php
      // Get products for this restaurant
      $result = $conn->query("SELECT * FROM products WHERE restaurant_id=$restaurant_id ORDER BY id DESC");
      if($result && $result->num_rows > 0){
        while($row = $result->fetch_assoc()){
          $safeName = htmlspecialchars($row['product_name'], ENT_QUOTES);
          $price = number_format((float)$row['price'], 2, '.', '');
          echo "<div class='product-card' data-category='all' onclick=\"addToCart('{$safeName}',{$price})\">
                  <span class='d-block fw-bold'>{$safeName}</span>
                  <span class='text-success fw-semibold'>Rs {$price}</span>
                </div>";
        }
      } else {
        echo "<p class='text-muted'>No food items available. <a href='add_food.php'>Add food</a></p>";
      }
      ?>
    </div>
  </div>

  <div class="col-lg-4 cart shadow-sm rounded" style="background-color: #dce9eec8;">
    <span class="me-3 fw-bold">Hello, <?= htmlspecialchars($cashier_name) ?></span>
    <h2 class="mb-4 text-primary fw-bold">Cart</h2>
    <form id="checkoutForm" method="POST" action="checkout.php">
      <table class="table table-bordered">
        <thead class="table-light">
          <tr><th>Item</th><th>Qty</th><th>Total</th></tr>
        </thead>
        <tbody id="cartTableBody"></tbody>
      </table>
      <div class="text-end fw-bold">Subtotal: Rs <span id="subtotal">0.00</span></div>
      <div class="text-end fw-bold">Tax (5%): Rs <span id="tax">0.00</span></div>
      <div class="text-end fs-5 fw-bold">Total: Rs <span id="total">0.00</span></div>

      <input type="hidden" name="cartData" id="cartData">
      <input type="hidden" name="payment_method" id="payment_method">
      <input type="hidden" name="paid_amount" id="paid_amount">
      <input type="hidden" name="card_number" id="card_number">
      <input type="hidden" name="card_expiry" id="card_expiry">
      <input type="hidden" name="card_cvv" id="card_cvv">

      <div class="d-inline-flex gap-1 mt-2">
          <button type="button" class="payment-btn cash btn btn-outline-primary" onclick="openPayment('Cash')">Cash</button>
          <button type="button" class="payment-btn card btn btn-outline-success" onclick="openPayment('Card')">Card</button>
      </div>

      <button type="submit" class="checkout-btn mt-3 btn btn-primary w-100">Checkout & Pay</button>
    </form>
  </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
  <div class="modal-header">
    <h5 class="modal-title">Complete Payment (<span id="payMethod"></span>)</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
  </div>
  <div class="modal-body">
    <div class="mb-3">
      <label class="form-label">Total Amount (Rs)</label>
      <input type="text" id="modalTotal" class="form-control" readonly>
    </div>
    <div class="mb-3">
      <label class="form-label">Paid Amount (Rs)</label>
      <input type="number" id="paidAmount" class="form-control" oninput="calcBalance()">
    </div>
    <div class="mb-3">
      <label class="form-label">Balance (Rs)</label>
      <input type="text" id="balanceAmount" class="form-control" readonly>
    </div>

    <div id="cardDetails" style="display:none;">
      <hr>
      <h6>Card Details</h6>
      <div class="mb-3">
        <label class="form-label">Card Number</label>
        <input type="text" id="cardNumberInput" class="form-control" maxlength="16" placeholder="XXXX XXXX XXXX XXXX">
      </div>
      <div class="mb-3">
        <label class="form-label">Expiry Date</label>
        <input type="text" id="cardExpiryInput" class="form-control" placeholder="MM/YY">
      </div>
      <div class="mb-3">
        <label class="form-label">CVV</label>
        <input type="password" id="cardCVVInput" class="form-control" maxlength="3" placeholder="***">
      </div>
    </div>
  </div>
  <div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
    <button type="button" class="btn btn-success" onclick="confirmPayment()">Confirm</button>
  </div>
</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let cart=[], subtotal=0, tax=0, total=0, selectedPayment="";

function addToCart(name,price){ 
  price=parseFloat(price)||0; 
  let existing=cart.find(i=>i.name===name); 
  if(existing) existing.qty++; 
  else cart.push({name,price,qty:1}); 
  calculateTotal(); 
  renderCart(); 
}
function increaseQty(i){ cart[i].qty++; calculateTotal(); renderCart(); }
function decreaseQty(i){ if(cart[i].qty>1) cart[i].qty--; else cart.splice(i,1); calculateTotal(); renderCart(); }

function calculateTotal(){ 
  subtotal=cart.reduce((a,i)=>a+i.price*i.qty,0); 
  tax=+(subtotal*0.05).toFixed(2); 
  total=subtotal+tax; 
}
function renderCart(){ 
  const tbody=document.getElementById('cartTableBody'); 
  tbody.innerHTML=''; 
  cart.forEach((i,index)=>{
    tbody.innerHTML+=`<tr>
      <td>${i.name}</td>
      <td>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="decreaseQty(${index})">-</button> ${i.qty} 
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="increaseQty(${index})">+</button>
      </td>
      <td>Rs ${(i.price*i.qty).toFixed(2)}</td>
    </tr>`; 
  }); 
  document.getElementById('subtotal').innerText=subtotal.toFixed(2); 
  document.getElementById('tax').innerText=tax.toFixed(2); 
  document.getElementById('total').innerText=total.toFixed(2); 
  document.getElementById('cartData').value=JSON.stringify(cart); 
}

document.getElementById('searchInput').addEventListener('input',function(){ 
  const f=this.value.toLowerCase(); 
  document.querySelectorAll('#productList .product-card').forEach(p=>{
    p.style.display=p.querySelector('.fw-bold').innerText.toLowerCase().includes(f)?'':'none'; 
  });
});

function filterCategory(cat){ 
  document.querySelectorAll('#productList .product-card').forEach(p=>{
    const c=p.getAttribute('data-category'); 
    p.style.display=(cat==='all'||c===cat)?'':'none'; 
  });
}

function openPayment(method){
  selectedPayment=method;
  document.getElementById('payMethod').innerText=method;
  document.getElementById('modalTotal').value=total.toFixed(2);
  document.getElementById('paidAmount').value='';
  document.getElementById('balanceAmount').value='';

  document.getElementById('cardDetails').style.display = (method === 'Card') ? 'block' : 'none';

  new bootstrap.Modal(document.getElementById('paymentModal')).show();
}

function calcBalance(){ 
  let paid=parseFloat(document.getElementById('paidAmount').value)||0; 
  document.getElementById('balanceAmount').value=(paid-total).toFixed(2); 
}

function confirmPayment(){
  let paid=parseFloat(document.getElementById('paidAmount').value)||0;
  if(paid<total){ alert("Paid amount is less than total!"); return; }

  document.getElementById('payment_method').value=selectedPayment;
  document.getElementById('paid_amount').value=paid.toFixed(2);

  if(selectedPayment === "Card"){
    document.getElementById('card_number').value = document.getElementById('cardNumberInput').value;
    document.getElementById('card_expiry').value = document.getElementById('cardExpiryInput').value;
    document.getElementById('card_cvv').value = document.getElementById('cardCVVInput').value;
  }

  bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
  alert("Payment confirmed. Now click 'Checkout & Pay' to finalize.");
}
</script>

</body>
</html>
