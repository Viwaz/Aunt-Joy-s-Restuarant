/**
 * API Client Service - Manager View
 * Centralized API endpoint management for manager operations
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

    // ===== REPORT ENDPOINTS =====
    
    static async getReportData(month, year) {
        return this.request(`reports_api.php?action=get&month=${month}&year=${year}`);
    }

    static async getOrderStats(month, year) {
        return this.request(`reports_api.php?action=orders_stats&month=${month}&year=${year}`);
    }

    static async getMealStats(month, year) {
        return this.request(`reports_api.php?action=meal_stats&month=${month}&year=${year}`);
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
}
