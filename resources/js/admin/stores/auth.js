import { defineStore } from 'pinia';
import axios from 'axios';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    loading: false,
    error: null,
  }),

  getters: {
    isAuthenticated: (state) => !!state.user,
    userName: (state) => state.user?.name || 'User',
  },

  actions: {
    async login(credentials) {
      this.loading = true;
      this.error = null;

      try {
        // Get CSRF cookie first
        await axios.get('/sanctum/csrf-cookie');

        // Login
        const response = await axios.post('/api/admin/login', credentials);
        this.user = response.data.user;
        
        return true;
      } catch (error) {
        this.error = error.response?.data?.message || 'Login failed';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async logout() {
      try {
        await axios.post('/api/admin/logout');
        this.user = null;
      } catch (error) {
        console.error('Logout failed:', error);
      }
    },

    async fetchUser() {
      try {
        const response = await axios.get('/api/admin/me');
        this.user = response.data;
      } catch (error) {
        this.user = null;
      }
    },

    clearError() {
      this.error = null;
    },
  },
});
