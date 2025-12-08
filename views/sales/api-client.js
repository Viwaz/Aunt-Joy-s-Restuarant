/**
 * API Client Service - Sales/Orders View
 * Centralized API endpoint management for sales operations
 */

class ApiClient {
    static BASE_URL = '../../api';

    /**
     * Generic fetch handler with error management
     */
    static async request(endpoint, options = {}) {
        try {
            const response = await fetch(`${this.BASE_URL}/${endpoint}`, {
                method: options.method || 'GET',
                headers: options.headers || { 'Content-Type': 'application/json' },
                body: options.body
            });

            const data = await response.json();
            
            if (!response.ok && !data.success) {
                throw new Error(data.message || `HTTP ${response.status}`);
            }
            
            return data;
        } catch (error) {
            console.error(`API Error (${endpoint}):`, error);
            throw error;
        }
    }

    // ===== ORDER ENDPOINTS =====
    
    static async listOrders() {
        return this.request('orders_api.php?action=list');
    }

    static async getOrderDetails(orderId) {
        return this.request(`orders_api.php?action=get&id=${orderId}`);
    }

    static async updateOrderStatus(orderId, status) {
        return this.request('orders_api.php?action=update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: orderId, status })
        });
    }

    static async getOrdersByStatus(status) {
        return this.request(`orders_api.php?action=list&status=${status}`);
    }

    static async filterOrders(filters) {
        return this.request('orders_api.php?action=filter', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(filters)
        });
    }
}
