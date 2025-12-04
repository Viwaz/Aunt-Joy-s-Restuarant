// Orders management for customers
async function loadOrders() {
    try {
        const response = await fetch('../../../api/orders_api.php?action=list');
        const data = await response.json();

        if (!data.success) {
            alert('Failed to load orders');
            return;
        }

        const currentOrders = data.orders.filter(o =>
            ['pending','en route']
        );
        const historyOrders = data.orders.filter(o => o.status === 'delivered');

        renderOrders('current-orders', currentOrders);
        renderOrders('history-orders', historyOrders);

    } catch (error) {
        console.error('Error loading orders:', error);
    }
}

function renderOrders(containerId, orders) {
    const container = document.getElementById(containerId);
    
    if (orders.length === 0) {
        container.innerHTML = '<p style="color: #999;">No orders yet.</p>';
        return;
    }

    container.innerHTML = orders.map(order => `
        <div class="order-card">
            <div class="order-header">
                <div>
                    <p class="order-id">Order #${order.order_id}</p>
                    <p class="order-date">${new Date(order.order_date).toLocaleDateString()}</p>
                </div>
                <div>
                    <span class="order-status status-${order.status.toLowerCase().replace(/\s+/g, '-')}">${order.status}</span>
                </div>
            </div>
            <div class="order-body">
                <p><strong>Total:</strong> MWK ${Number(order.total_amount).toLocaleString()}</p>
                <p><strong>Delivery:</strong> ${escapeHtml(order.delivery_address)}</p>
                <button class="btn-view-details" onclick="showOrderDetails(${order.order_id})">View Details</button>
            </div>
        </div>
    `).join('');
}

function showOrderDetails(orderId) {
    // Fetch full order details and display in modal
    fetch('../../../api/orders_api.php?action=list')
        .then(r => r.json())
        .then(data => {
            const order = data.orders.find(o => o.order_id === orderId);
            if (order) {
                const itemsHtml = (order.items || []).map(item => `
                    <tr>
                        <td>${item.menu_item_id}</td>
                        <td>${item.quantity}</td>
                        <td>MWK ${Number(item.price).toLocaleString()}</td>
                        <td>MWK ${Number(item.subtotal).toLocaleString()}</td>
                    </tr>
                `).join('');

                document.getElementById('modal-body').innerHTML = `
                    <p><strong>Order ID:</strong> ${order.order_id}</p>
                    <p><strong>Status:</strong> <span class="order-status status-${order.status.toLowerCase().replace(/\s+/g, '-')}">${order.status}</span></p>
                    <p><strong>Delivery Address:</strong> ${escapeHtml(order.delivery_address)}</p>
                    <p><strong>Contact:</strong> ${escapeHtml(order.phone_num)}</p>
                    <p><strong>Date:</strong> ${new Date(order.order_date).toLocaleDateString()}</p>
                    <hr>
                    <h5>Items:</h5>
                    <table class="table table-sm">
                        <thead>
                            <tr><th>Item ID</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr>
                        </thead>
                        <tbody>
                            ${itemsHtml}
                        </tbody>
                    </table>
                    <p><strong>Total:</strong> MWK ${Number(order.total_amount).toLocaleString()}</p>
                `;

                document.getElementById('order-modal').classList.add('show');
            }
        })
        .catch(error => {
            console.error('Error fetching order details:', error);
            alert('Failed to load order details');
        });
}

function closeModal() {
    document.getElementById('order-modal').classList.remove('show');
}

function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

document.addEventListener('DOMContentLoaded', loadOrders);
document.addEventListener('click', (e) => {
    if (e.target.id === 'order-modal') closeModal();
});
