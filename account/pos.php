<?php
session_start();
include "config.php";

if (!isset($_SESSION['cashier_id'])) {
    header("Location: login.php");
    exit();
}

$cashier_id     = $_SESSION['cashier_id'] ?? 0;
$cashier_name   = $_SESSION['cashier_name'] ?? 'Cashier';
$restaurant_id  = $_SESSION['restaurant_id'] ?? 0;

// Fetch restaurant info (now includes tax_rate)
$restQuery = $conn->prepare("SELECT restaurant_name, logo, phone, address, service_charge_rate, table_charge, tax_rate FROM restaurants WHERE id = ?");
$restQuery->bind_param("i", $restaurant_id);
$restQuery->execute();
$restaurant = $restQuery->get_result()->fetch_assoc();
$restQuery->close();

$restaurant_name     = $restaurant['restaurant_name'] ?? 'My Restaurant';
$restaurant_logo     = $restaurant['logo'] ?? 'default_logo.png';
$restaurant_phone    = $restaurant['phone'] ?? 'N/A';
$restaurant_address  = $restaurant['address'] ?? 'N/A';
$service_charge_rate = $restaurant['service_charge_rate'] ?? 10.00; 
$table_charge_amount = $restaurant['table_charge'] ?? 0.00;
$tax_rate            = $restaurant['tax_rate'] ?? 5.00; // ✅ default 5%

// Fetch categories & products
$catResult = $conn->query("SELECT * FROM categories WHERE restaurant_id = $restaurant_id ORDER BY id ASC");
$prodResult = $conn->query("
    SELECT p.*, c.name AS category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.restaurant_id = $restaurant_id
    ORDER BY p.id DESC
");


// Low stock products
$stmt = $conn->prepare("SELECT id, product_name, quantity FROM products WHERE restaurant_id=? AND quantity<=10 ORDER BY quantity ASC");
$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$lowStockProducts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>POS System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.btn-gradient { background: linear-gradient(135deg,#1a9db178,#42dbe392); border: none; color: #000; transition: transform .2s,box-shadow .2s; }
.btn-gradient:hover { transform: scale(1.05); box-shadow: 0 4px 12px rgba(0,0,0,0.2); color:#000; }
#productList { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
.product-card { cursor: pointer; height: 120px; padding: 10px; border-radius: 8px; background-color: #abdbe3a9; box-shadow: 0 1px 4px rgba(0,0,0,0.1); transition: transform 0.2s, box-shadow 0.2s; display: flex; flex-direction: column; justify-content: space-between; text-align: flex-start; gap: 2px; }
.product-card:hover { transform: scale(1.02); box-shadow: 0 2px 6px rgba(0,0,0,0.15); }
.products{max-height:75vh;overflow-y:auto;}
.filter-btns .btn{border-radius:20px;padding:5px 12px;font-size:.85rem;font-weight:500;}
#searchInput{border-radius:20px;padding:8px 15px;margin-bottom:15px;box-shadow:0 2px 6px rgba(0,0,0,0.1);border:1px solid #ccc;}
.header-info img{width:50px;height:50px;border-radius:50%;object-fit:cover;}
.payment-form { background:#fff; border-radius:10px; padding:10px 15px; box-shadow:0 2px 8px rgba(0,0,0,0.1); margin-top:10px; }
.payment-form label{font-weight:600;}
</style>
</head>
<body>

<div class="row p-3">
  <!-- Products Section -->
  <div class="col-lg-8 products shadow-sm rounded" style="background-color:#ffff;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="text-primary fw-bold">Food Menu</h2>
        <div class="dropdown">
            <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="border-radius:25px; padding:6px 15px; font-weight:500;">
                <?= htmlspecialchars($cashier_name) ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" style="border-radius:12px; min-width:150px;">
                <li><a class="dropdown-item text-danger" href="logout.php" style="font-weight:500;">Logout</a></li>
                <li><a class="dropdown-item text-warning" href="#" onclick="openOutOfStockModal()" style="font-weight:500;">Out of Stock</a></li>
            </ul>
        </div>
    </div>

    <!-- Category Filter + Search -->
    <div class="d-flex flex-column flex-md-row align-items-center justify-content-between mb-3 gap-2">
      <div class="filter-btns d-flex align-items-center flex-nowrap overflow-auto w-100 gap-2 p-1" 
           style="white-space:nowrap; scrollbar-width:thin;">
        <button class="btn btn-gradient btn-sm" onclick="filterCategory('all')">All</button>
        <?php if($catResult && $catResult->num_rows > 0): 
          while($cat = $catResult->fetch_assoc()): ?>
            <button class="btn btn-gradient btn-sm" 
                    onclick="filterCategory('<?= strtolower(htmlspecialchars($cat['name'])) ?>')">
              <?= htmlspecialchars($cat['name']) ?>
            </button>
        <?php endwhile; endif; ?>
      </div>
    </div>

    <input type="text" id="searchInput" class="form-control" placeholder="🔍Search food...">

    <div id="productList" class="mt-3">
      <?php
      if($prodResult && $prodResult->num_rows > 0){
          while($row = $prodResult->fetch_assoc()){
                $name_js = json_encode($row['product_name']); // safe for JS
                $name = htmlspecialchars($row['product_name'], ENT_QUOTES);
                $price = number_format((float)$row['price'],2,'.','');
                $cat = strtolower($row['category_name']);
                $qty = (int)$row['quantity'];
                echo "<div class='product-card' 
                            data-category='{$cat}' 
                            data-stock='{$qty}' 
                            onclick='addToCart({$name_js}, {$price}, [], {$qty})'>
                        <span class='fw-bold'>{$name}</span>
                        <span class='text-success fw-semibold'>Rs {$price}</span>
                        <span class='text-muted small'>Stock: <span class='stock-count'>{$qty}</span></span>
                      </div>";
            }
      }
      ?>
    </div>
  </div>

 <!-- Cart Section -->
<div class="col-lg-4 cart shadow-sm rounded" style="background-color:#dce9eec8;">
    <div class="text-center py-3 border-bottom">
        <img src="<?= htmlspecialchars($restaurant_logo) ?>" class="rounded-circle mb-2" style="width:70px;height:70px;object-fit:cover;">
        <div class="fw-bold fs-5"><?= htmlspecialchars($restaurant_name) ?></div>
    </div>
    <div class="d-flex justify-content-between align-items-start p-2">
        <div>
            <div class="small text-muted">Phone: <?= htmlspecialchars($restaurant_phone) ?></div>
            <div class="small text-muted">Address: <?= htmlspecialchars($restaurant_address) ?></div>
        </div>
        <div class="text-end">
            <div id="currentTime" class="fw-bold"></div>
            <div id="currentDate" class="text-muted small"></div>
        </div>
    </div>

    <!-- Checkout Form -->
    <form id="checkoutForm" method="POST" action="checkout.php">
        <div class="mb-3">
            <label class="fw-bold">Customer Phone</label>
            <input type="text" name="customer_phone" id="customer_phone" class="form-control" placeholder="Enter phone number" value="+94">
        </div>

        <table class="table table-bordered">
            <thead class="table-light"><tr><th>Item</th><th>Qty</th><th>Total</th></tr></thead>
            <tbody id="cartTableBody"></tbody>
        </table>

        <div class="text-end fw-bold">Subtotal: Rs <span id="subtotal">0.00</span></div>
        <div class="text-end fw-bold">Tax (<span id="taxRateLabel"><?= $tax_rate ?></span>%): Rs <span id="tax">0.00</span></div>
        <div class="text-end fw-bold">Service Charge (<span id="serviceRateLabel"><?= $service_charge_rate ?></span>%): Rs <span id="serviceCharge">0.00</span></div>
        <div class="text-end fw-bold">Table Charge: Rs <span id="tableChargeLabel"><?= number_format($table_charge_amount,2) ?></span></div>
        <div class="text-end fs-5 fw-bold">Total: Rs <span id="total">0.00</span></div>

        <!-- Hidden fields -->
        <input type="hidden" name="cartData" id="cartData">
        <input type="hidden" name="payment_method" id="payment_method">
        <input type="hidden" name="paid_amount" id="paid_amount">
        <input type="hidden" name="service_charge" id="service_charge">
        <input type="hidden" name="service_rate" id="service_rate">
        <input type="hidden" name="table_charge" id="table_charge">
        <input type="hidden" id="serviceRate" value="<?= $service_charge_rate ?>">
        <input type="hidden" id="tableCharge" value="<?= $table_charge_amount ?>">
        <input type="hidden" id="taxRate" value="<?= $tax_rate ?>">


      <div class="d-inline-flex gap-1 mt-2">
        <button type="button" class="btn btn-outline-primary" onclick="openCashModal()">Cash</button>
        <button type="button" class="btn btn-outline-success" onclick="openCardModal()">Card</button>
      </div>
      <button type="submit" class="checkout-btn mt-3 btn btn-primary w-100">Checkout & Pay</button>
    </form>
  </div>
</div>

<!-- Extras Modal, Cash Modal, Card Modal, Out of Stock Modal remain same with fixes applied (extra qty default = 1) -->
<!-- Extras Modal -->
<div class="modal fade" id="extraModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title">
          Add Extras for <span id="extraItemName" class="text-warning"></span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body" id="extraBody" style="max-height: 350px; overflow-y: auto;"></div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" onclick="saveExtras()">Add Selected Extras</button>
      </div>
    </div>
  </div>
</div>

<!-- Cash Modal -->
<div class="modal fade" id="cashModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Cash Payment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <div class="mb-3"><label>Total Amount (Rs)</label><input type="text" id="cashTotal" class="form-control" readonly></div>
      <div class="mb-3"><label>Received Amount (Rs)</label><input type="number" id="cashReceived" class="form-control" oninput="calculateChange()"></div>
      <div class="mb-3"><label>Change (Rs)</label><input type="text" id="cashChange" class="form-control" readonly></div>
    </div>
    <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" onclick="confirmCashPayment()">Confirm</button></div>
  </div></div>
</div>

<!-- Card Modal -->
<div class="modal fade" id="cardModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Card Payment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <div class="mb-3"><label>Card Number</label><input type="text" id="cardNumber" class="form-control" placeholder="xxxx-xxxx-xxxx-xxxx"></div>
      <div class="mb-3"><label>Card Holder Name</label><input type="text" id="cardHolder" class="form-control" placeholder="Name on Card"></div>
      <div class="mb-3"><label>Amount (Rs)</label><input type="text" id="cardTotal" class="form-control" readonly></div>
    </div>
    <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" onclick="confirmCardPayment()">Confirm Payment</button></div>
  </div></div>
</div>

<!-- Out of Stock Modal -->
<div class="modal fade" id="outOfStockModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Low / Out of Stock Products</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <?php if(count($lowStockProducts) > 0): ?>
          <table class="table table-bordered">
            <thead>
              <tr><th>#</th><th>Product</th><th>Quantity</th><th>Status</th></tr>
            </thead>
            <tbody>
              <?php foreach($lowStockProducts as $i=>$p): ?>
                <tr>
                  <td><?= $i+1 ?></td>
                  <td><?= htmlspecialchars($p['product_name']) ?></td>
                  <td><?= htmlspecialchars($p['quantity']) ?></td>
                  <td>
                    <?= ($p['quantity']==0) 
                        ? "<span class='text-danger fw-bold'>Out of Stock</span>" 
                        : "<span class='text-warning fw-bold'>Low Stock</span>" ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <div class="alert alert-success">All products are in stock 🎉</div>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// ---------------------
// GLOBAL VARIABLES
// ---------------------
let cart = [];
let subtotal = 0, total = 0;
let selectedIndex = null;
let lowStockAlerted = new Set();
let tempExtras = {}; // store extras temporarily by cart index

// ---------------------
// DATE & TIME
// ---------------------
function updateDateTime(){
    const now = new Date();
    document.getElementById("currentTime").textContent = now.toLocaleTimeString();
    document.getElementById("currentDate").textContent = now.toLocaleDateString();
}
setInterval(updateDateTime, 1000);
updateDateTime();

// ---------------------
// ADD TO CART
// ---------------------
function addToCart(name, price, extras = [], initialQty = 0){
    const productCard = [...document.querySelectorAll(".product-card")]
        .find(c => c.querySelector("span").textContent === name);

    let stock = parseInt(productCard?.dataset.stock) || 0;
    if(stock === 0){
        alert(`Sorry! "${name}" is out of stock and cannot be added.`);
        return;
    }

    const key = name + JSON.stringify(extras);
    let existing = cart.find(i => i.key === key);

    if(existing){
        if(existing.qty + 1 > stock){
            alert(`Sorry! Only ${stock} units available for "${name}".`);
            return;
        }
        existing.qty++;
    } else {
        cart.push({ key, name, price: parseFloat(price), qty: 1 });
    }

    if(stock <= 10 && !lowStockAlerted.has(name)){
        alert(`⚠ Warning: "${name}" is low stock! Only ${stock} left.`);
        lowStockAlerted.add(name);
    }

    renderCart();
}

// ---------------------
// INCREASE / DECREASE
// ---------------------
function increaseQty(index){
    const item = cart[index];
    const card = [...document.querySelectorAll(".product-card")]
        .find(c => c.querySelector("span").textContent === item.name);
    let maxQty = parseInt(card?.dataset.stock) || 0;
    if(item.qty + 1 > maxQty){
        alert(`Cannot add more. Only ${maxQty} units available for "${item.name}".`);
        return;
    }
    item.qty++;
    renderCart();
}

function decreaseQty(index){
    cart[index].qty--;
    if(cart[index].qty <= 0){
        if(confirm(`Remove ${cart[index].name}?`)) cart.splice(index, 1);
        else cart[index].qty = 1;
    }
    renderCart();
}

// ---------------------
// EXTRAS MODAL
// ---------------------
// --- Extras modal handlers ---
function openExtrasModal(index){
    selectedIndex = index;
    const item = cart[index];
    document.getElementById("extraItemName").innerText = item.name;
    document.getElementById("extraBody").innerHTML = `
    <div id="extrasList">
        ${(item.extras||[]).map(x=>`
        <div class="d-flex mb-2 extra-row">
            <input type="text" class="form-control form-control-sm me-1 extra-name" value="${x.name}">
            <input type="number" class="form-control form-control-sm me-1 extra-price" value="${x.price}">
            <input type="number" class="form-control form-control-sm me-1 extra-qty" value="${x.qty}" min="1">
            <button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.remove()">X</button>
        </div>`).join('')}
        <div class="d-flex mb-2 extra-row">
            <input type="text" class="form-control form-control-sm me-1 extra-name" placeholder="Extra name">
            <input type="number" class="form-control form-control-sm me-1 extra-price" placeholder="Price">
            <input type="number" class="form-control form-control-sm me-1 extra-qty" placeholder="Qty" value="#" min="1">
            <button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.remove()">X</button>
        </div>
    </div>
    <button type="button" class="btn btn-sm btn-primary mt-2" onclick="addExtraRow()">Add Another</button>`;
    new bootstrap.Modal(document.getElementById("extraModal")).show();
}

function addExtraRow(){
    const c=document.getElementById("extrasList");
    const r=document.createElement("div");
    r.classList.add("d-flex","mb-2","extra-row");
    r.innerHTML=`<input type="text" class="form-control form-control-sm me-1 extra-name" placeholder="Extra name">
    <input type="number" class="form-control form-control-sm me-1 extra-price" placeholder="Price">
    <input type="number" class="form-control form-control-sm me-1 extra-qty" placeholder="Qty" value="1" min="1">
    <button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.remove()">X</button>`;
    c.appendChild(r);
}

function saveExtras(){
    if(selectedIndex===null) return;
    const rows = document.querySelectorAll("#extrasList .extra-row");
    let extras = [];
    rows.forEach(r=>{
        const name = r.querySelector(".extra-name").value.trim();
        const price = parseFloat(r.querySelector(".extra-price").value) || 0;
        const qty   = parseInt(r.querySelector(".extra-qty").value) || 1;
        if(name && price>0) extras.push({name,price,qty});
    });
    cart[selectedIndex].extras = extras;
    renderCart();
    bootstrap.Modal.getInstance(document.getElementById("extraModal")).hide();
}

// ---------------------
// CALCULATE TOTALS
// ---------------------
function calculateTotal(){
    subtotal = 0;
    cart.forEach(item => {
        let extrasCost = item.extras ? item.extras.reduce((s,e)=>s+(e.price*e.qty),0) : 0;
        subtotal += (item.price*item.qty) + extrasCost;
    });

    let taxRate = parseFloat(document.getElementById("taxRate").value)||0;
    let serviceRate = parseFloat(document.getElementById("serviceRate").value)||0;
    let tableCharge = parseFloat(document.getElementById("tableCharge").value)||0;

    let tax = subtotal*(taxRate/100);
    let serviceCharge = subtotal*(serviceRate/100);

    total = subtotal + tax + serviceCharge + tableCharge;

    document.getElementById("subtotal").innerText = subtotal.toFixed(2);
    document.getElementById("tax").innerText = tax.toFixed(2);
    document.getElementById("serviceCharge").innerText = serviceCharge.toFixed(2);
    document.getElementById("tableChargeLabel").innerText = tableCharge.toFixed(2);
    document.getElementById("total").innerText = total.toFixed(2);

    document.getElementById("cartData").value = JSON.stringify(cart);
}

// ---------------------
// RENDER CART
// ---------------------
function renderCart(){
    const tbody = document.getElementById("cartTableBody");
    tbody.innerHTML = "";

    cart.forEach((item,index)=>{
        let extrasHtml = "";
        if(item.extras?.length>0){
            extrasHtml = item.extras.map(e=>`<div class='text-muted small'>+ ${e.qty} x ${e.name} (Rs ${e.price} = Rs ${(e.price*e.qty).toFixed(2)})</div>`).join("");
        }

        const rowTotal = (
            item.price*item.qty +
            (item.extras?.reduce((s,e)=>s+(e.price*e.qty),0)||0)
        ).toFixed(2);

        tbody.innerHTML += `
<tr data-index="${index}" style="cursor:pointer" onclick="openExtrasModal(${index})">
  <td>${item.name}<br>${extrasHtml}</td>
  <td class="text-center">
    <button class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation(); decreaseQty(${index})">−</button>
    <span class="mx-2 fw-bold">${item.qty}</span>
    <button class="btn btn-sm btn-outline-success" onclick="event.stopPropagation(); increaseQty(${index})">+</button>
  </td>
  <td>Rs ${rowTotal}</td>
</tr>`;
    });

    calculateTotal();
}

// ---------------------
// CHECKOUT FORM
// ---------------------
document.getElementById("checkoutForm").addEventListener("submit", function(e){
    for(let idx in tempExtras){
        cart[idx].extras = tempExtras[idx];
    }
    calculateTotal();
});

// ---------------------
// CASH & CARD PAYMENT
// ---------------------
function openCashModal(){ if(cart.length===0){alert("Cart empty");return;} calculateTotal(); document.getElementById("cashTotal").value=total.toFixed(2); new bootstrap.Modal(document.getElementById("cashModal")).show(); }
function calculateChange(){ let received=parseFloat(document.getElementById("cashReceived").value)||0; let change=received-total; document.getElementById("cashChange").value=(change>0?change:0).toFixed(2); }
function confirmCashPayment(){
    let received = parseFloat(document.getElementById("cashReceived").value) || 0;

    if(received < total){
        alert("Received amount is less than total!");
        return;
    }

    document.getElementById("paid_amount").value = received.toFixed(2);
    document.getElementById("payment_method").value = "Cash"; // ✅ ADD THIS

    bootstrap.Modal.getInstance(document.getElementById("cashModal")).hide();
}

function openCardModal(){ if(cart.length===0){alert("Cart empty");return;} calculateTotal(); document.getElementById("cardTotal").value=total.toFixed(2); new bootstrap.Modal(document.getElementById("cardModal")).show(); }
function confirmCardPayment(){
    const number = document.getElementById("cardNumber").value.trim();
    const name   = document.getElementById("cardHolder").value.trim();

    if(number.length < 12){
        alert("Invalid card number");
        return;
    }
    if(name.length < 3){
        alert("Invalid card holder");
        return;
    }

    document.getElementById("paid_amount").value = total.toFixed(2);
    document.getElementById("payment_method").value = "Card"; // ✅ ADD THIS

    bootstrap.Modal.getInstance(document.getElementById("cardModal")).hide();
}

// ---------------------
// SEARCH & FILTER
// ---------------------
function filterCategory(cat){ document.querySelectorAll(".product-card").forEach(card=>{ card.style.display = (cat==="all"||card.dataset.category===cat)?"flex":"none"; }); }
document.getElementById("searchInput").addEventListener("input", function(){ const val=this.value.toLowerCase(); document.querySelectorAll(".product-card").forEach(card=>{ const name=card.querySelector("span").textContent.toLowerCase(); card.style.display=name.includes(val)?"flex":"none"; }); });

// ---------------------
// LOW STOCK MODAL
// ---------------------
function updateLowStockModal(){
    const tbody = document.querySelector("#outOfStockModal tbody");
    tbody.innerHTML = "";
    const lowStock=[...document.querySelectorAll(".product-card")].map(c=>({name:c.querySelector("span").textContent,stock:parseInt(c.dataset.stock)||0})).filter(p=>p.stock<=10);
    if(lowStock.length===0){tbody.innerHTML=`<tr><td colspan="4" class="text-center">All products are in stock 🎉</td></tr>`;return;}
    lowStock.forEach((p,i)=>{
        tbody.innerHTML+=`<tr>
            <td>${i+1}</td>
            <td>${p.name}</td>
            <td>${p.stock}</td>
            <td>${p.stock===0? "<span class='text-danger fw-bold'>Out of Stock</span>":"<span class='text-warning fw-bold'>Low Stock</span>"}</td>
        </tr>`;
    });
}
function openOutOfStockModal(){ updateLowStockModal(); new bootstrap.Modal(document.getElementById("outOfStockModal")).show(); }
setInterval(updateLowStockModal,3000);

// ---------------------
// CLEAR CART
// ---------------------
function clearCart() {
    if(confirm("Clear the cart?")) {
        cart = [];
        tempExtras = {};
        renderCart();
    }
}

console.log("POS JS loaded, cart ready:", cart);
</script>


</body>
</html>