<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import GuestLayout from '../layouts/GuestLayout.vue';
import BaseInput from '../components/BaseInput.vue';
import BaseButton from '../components/BaseButton.vue';

const router = useRouter();
const authStore = useAuthStore();

const form = ref({
  email: '',
  password: '',
  remember: false,
});

const loading = ref(false);
const error = ref('');

const handleSubmit = async () => {
  loading.value = true;
  error.value = '';

  try {
    await authStore.login({
      email: form.value.email,
      password: form.value.password,
      remember: form.value.remember,
    });
    
    router.push({ name: 'dashboard' });
  } catch (err) {
    error.value = err.response?.data?.message || 'Invalid credentials. Please try again.';
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <GuestLayout>
    <div class="text-center mb-6">
      <h2 class="text-2xl font-bold text-slate-900">Welcome back</h2>
      <p class="text-slate-600 mt-1">Sign in to your admin account</p>
    </div>

    <!-- Error Alert -->
    <div
      v-if="error"
      class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm"
    >
      {{ error }}
    </div>

    <form @submit.prevent="handleSubmit" class="space-y-6">
      <!-- Email -->
      <BaseInput
        v-model="form.email"
        type="email"
        label="Email Address"
        placeholder="admin@aadi.test"
        required
        :disabled="loading"
      />

      <!-- Password -->
      <BaseInput
        v-model="form.password"
        type="password"
        label="Password"
        placeholder="••••••••"
        required
        :disabled="loading"
      />

      <!-- Remember Me -->
      <div class="flex items-center">
        <input
          id="remember"
          v-model="form.remember"
          type="checkbox"
          class="h-4 w-4 rounded border-slate-300 text-aadi-teal focus:ring-aadi-teal"
          :disabled="loading"
        />
        <label for="remember" class="ml-2 text-sm text-slate-600">
          Remember me for 30 days
        </label>
      </div>

      <!-- Submit Button -->
      <BaseButton
        type="submit"
        variant="primary"
        size="lg"
        class="w-full"
        :loading="loading"
        :disabled="loading"
      >
        Sign In
      </BaseButton>
    </form>

    <!-- Test Credentials -->
    <div class="mt-6 p-4 rounded-lg bg-slate-50 border border-slate-200">
      <p class="text-xs font-semibold text-slate-700 mb-2">Test Credentials:</p>
      <p class="text-xs text-slate-600">Email: <span class="font-mono">admin@aadi.test</span></p>
      <p class="text-xs text-slate-600">Password: <span class="font-mono">password</span></p>
    </div>
  </GuestLayout>
</template>
