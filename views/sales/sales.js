let allOrders = [];
let selectedOrderId = null;

async function loadOrders() {
    try {
        const response = await fetch('../../api/orders_api.php?action=list');
        const data = await response.json();

        if (!data.success) {
            alert('Failed to load orders');
            return;
        }

        allOrders = data.orders;
        renderOrders(allOrders);

    } catch (error) {
        console.error('Error loading orders:', error);
        document.getElementById('orders-table').innerHTML = '<p style="color: red;">Failed to load orders</p>';
    }
}

function renderOrders(orders) {
    const container = document.getElementById('orders-table');
    
    if (orders.length === 0) {
        container.innerHTML = '<p style="color: #999; text-align: center; padding: 40px;">No orders found.</p>';
        return;
    }

    container.innerHTML = `
        <div class="orders-header">
            <div class="col-id">Order ID</div>
            <div class="col-customer">Customer</div>
            <div class="col-total">Total</div>
            <div class="col-status">Status</div>
            <div class="col-date">Date</div>
            <div class="col-action">Action</div>
        </div>
    `;

    orders.forEach(order => {
        const row = document.createElement('div');
        row.className = 'order-row';
        row.innerHTML = `
            <div class="col-id">#${order.order_id}</div>
            <div class="col-customer">
                <strong>${escapeHtml(order.username)}</strong><br>
                <small>${escapeHtml(order.email)}</small>
            </div>
            <div class="col-total">MWK ${Number(order.total_amount).toLocaleString()}</div>
            <div class="col-status">
                <span class="status-badge status-${order.status.toLowerCase().replace(/\s+/g, '-')}">
                    ${order.status}
                </span>
            </div>
            <div class="col-date">${new Date(order.order_date).toLocaleDateString()}</div>
            <div class="col-action">
                <button class="btn-view" onclick="viewOrderDetails(${order.order_id})">View</button>
            </div>
        `;
        container.appendChild(row);
    });
}

function filterOrders() {
    const filterValue = document.getElementById('status-filter').value;
    
    if (filterValue === '') {
        renderOrders(allOrders);
    } else {
        const filtered = allOrders.filter(o => o.status === filterValue);
        renderOrders(filtered);
    }
}

function viewOrderDetails(orderId) {
    const order = allOrders.find(o => o.order_id === orderId);
    if (!order) return;

    selectedOrderId = orderId;

    const itemsHtml = (order.items || []).map(item => `
        <tr>
            <td>${item.menu_item_id}</td>
            <td>${item.quantity}</td>
            <td>MWK ${Number(item.price).toLocaleString()}</td>
            <td>MWK ${Number(item.subtotal).toLocaleString()}</td>
        </tr>
    `).join('');

    document.getElementById('modal-body').innerHTML = `
        <div class="order-detail-info">
            <p><strong>Order ID:</strong> ${order.order_id}</p>
            <p><strong>Customer:</strong> ${escapeHtml(order.username)} (${escapeHtml(order.email)})</p>
            <p><strong>Phone:</strong> ${escapeHtml(order.phone_num)}</p>
            <p><strong>Delivery Address:</strong> ${escapeHtml(order.delivery_address)}</p>
            <p><strong>Date:</strong> ${new Date(order.order_date).toLocaleDateString()}</p>
            <p><strong>Current Status:</strong> <span class="status-badge status-${order.status.toLowerCase().replace(/\s+/g, '-')}">${order.status}</span></p>
            <hr>
            <h5>Order Items:</h5>
            <table class="table table-sm">
                <thead>
                    <tr><th>Item ID</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr>
                </thead>
                <tbody>
                    ${itemsHtml}
                </tbody>
            </table>
            <hr>
            <p><strong>Total Amount:</strong> MWK ${Number(order.total_amount).toLocaleString()}</p>
        </div>
    `;

    document.getElementById('status-dropdown').value = order.status;
    document.getElementById('order-detail-modal').classList.add('show');
}

async function updateOrderStatus() {
    if (selectedOrderId === null) return;

    const newStatus = document.getElementById('status-dropdown').value;
    if (!newStatus) {
        alert('Please select a status');
        return;
    }

    try {
        const response = await fetch('../../api/orders_api.php?action=update_status', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ order_id: selectedOrderId, status: newStatus })
        });

        const data = await response.json();

        if (data.success) {
            alert('Order status updated successfully');
            closeModal();
            loadOrders();
        } else {
            alert('Error: ' + (data.message || 'Failed to update'));
        }
    } catch (error) {
        console.error('Error updating status:', error);
        alert('Error updating order status');
    }
}

function closeModal() {
    document.getElementById('order-detail-modal').classList.remove('show');
    selectedOrderId = null;
}

function escapeHtml(text) {
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return text.replace(/[&<>"']/g, m => map[m]);
}

document.addEventListener('DOMContentLoaded', loadOrders);
document.addEventListener('click', (e) => {
    if (e.target.id === 'order-detail-modal') closeModal();
});
