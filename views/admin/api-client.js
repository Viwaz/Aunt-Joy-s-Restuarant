/**
 * API Client Service
 * Centralized API endpoint management for admin operations
 * Eliminates duplication and standardizes request/response handling
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

    // ===== MEALS ENDPOINTS =====
    
    static async getMeals() {
        return this.request('meals_api.php?action=list');
    }

    static async createMeal(mealData) {
        let body;
        let headers;

        if (mealData instanceof FormData) {
            body = mealData;
            headers = {}; // Don't set Content-Type for FormData
        } else {
            body = JSON.stringify(mealData);
            headers = { 'Content-Type': 'application/json' };
        }

        return this.request('meals_api.php?action=create', {
            method: 'POST',
            headers,
            body
        });
    }

    static async updateMeal(mealData) {
        let body;
        let headers;

        if (mealData instanceof FormData) {
            body = mealData;
            headers = {}; // Don't set Content-Type for FormData
        } else {
            body = JSON.stringify(mealData);
            headers = { 'Content-Type': 'application/json' };
        }

        return this.request('meals_api.php?action=update', {
            method: 'POST',
            headers,
            body
        });
    }

    static async deleteMeal(mealId) {
        return this.request('meals_api.php?action=delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: mealId })
        });
    }

    static async toggleMealAvailability(mealId, currentStatus) {
        return this.request('meals_api.php?action=toggle', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: mealId, status: currentStatus })
        });
    }

    // ===== USERS ENDPOINTS =====

    static async getUsers() {
        return this.request('users_api.php?action=list');
    }

    static async createUser(userData) {
        return this.request('users_api.php?action=create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(userData)
        });
    }

    static async deleteUser(userId) {
        return this.request('users_api.php?action=delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: userId })
        });
    }
}
