/**
 * API Client Service - Customer/User View
 * Centralized API endpoint management for customer operations
 */

class ApiClient {
    static BASE_URL = '../../../api';

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

    // ===== MENU ENDPOINTS =====
    
    static async getCategories() {
        return this.request('menu_api.php?action=categories');
    }

    static async getMeals(categoryId = null) {
        const endpoint = categoryId 
            ? `menu_api.php?action=meals&category_id=${categoryId}`
            : 'menu_api.php?action=meals';
        return this.request(endpoint);
    }

    static async searchMeals(keyword) {
        return this.request(`menu_api.php?action=search&keyword=${encodeURIComponent(keyword)}`);
    }

    // ===== CART ENDPOINTS =====
    
    static async addToCart(menuItemId, quantity) {
        return this.request('add_to_cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ menu_item_id: menuItemId, quantity })
        });
    }

    static async getCartCount() {
        return this.request('cart_count.php');
    }

    // ===== ORDER ENDPOINTS =====
    
    static async placeOrder(orderData) {
        return this.request('orders_api.php?action=create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(orderData)
        });
    }

    static async getUserOrders() {
        return this.request('orders_api.php?action=list_user');
    }

    static async getOrderDetails(orderId) {
        return this.request(`orders_api.php?action=get&id=${orderId}`);
    }
}
