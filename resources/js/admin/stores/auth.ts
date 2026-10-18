import { defineStore } from 'pinia';
import axios from 'axios';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null as any,
        loading: false,
    }),

    getters: {
        isAuthenticated: (state) => !!state.user,
    },

    actions: {
        async login(credentials: { email: string; password: string; remember?: boolean }) {
            this.loading = true;
            try {
                const response = await axios.post('/admin/login', credentials);
                this.user = response.data.user;
                return response.data;
            } catch (error) {
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async logout() {
            try {
                await axios.post('/admin/logout');
                this.user = null;
            } catch (error) {
                console.error('Logout error:', error);
            }
        },

        async fetchUser() {
            try {
                const response = await axios.get('/admin/me');
                this.user = response.data;
            } catch (error) {
                this.user = null;
            }
        },
    },
});
