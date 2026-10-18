<script setup>
import { ref } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import BaseInput from '@/Components/Aadi/Forms/Input.vue';
import BaseButton from '@/Components/Aadi/UI/Button.vue';
import Logo from '@/Components/Aadi/UI/Logo.vue';

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const submit = () => {
  form.post(route('admin.login'), {
    onFinish: () => form.reset('password'),
  });
};
</script>

<template>
  <Head title="Admin Login" />
  
  <div class="min-h-screen bg-gradient-to-br from-aadi-teal-50 via-white to-aadi-orange-50 flex items-center justify-center p-6">
    <div class="w-full max-w-md">
      <!-- Logo -->
      <div class="text-center mb-8">
        <div class="flex justify-center mb-6">
          <Logo variant="full" size="xl" />
        </div>
        <h2 class="text-2xl font-bold text-slate-900">Admin Panel</h2>
        <p class="text-slate-600 mt-1">Sign in to manage Aadi Rental</p>
      </div>

      <!-- Login Card -->
      <div class="bg-white rounded-xl shadow-lg border border-slate-200 p-8">
        <!-- Error Alert -->
        <div v-if="form.errors.email || form.errors.password" class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200">
          <p class="text-sm text-red-800">
            {{ form.errors.email || form.errors.password || 'Invalid credentials. Please try again.' }}
          </p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
          <!-- Email -->
          <BaseInput
            v-model="form.email"
            type="email"
            label="Email Address"
            placeholder="admin@aadi.test"
            required
            autofocus
            autocomplete="username"
          />

          <!-- Password -->
          <BaseInput
            v-model="form.password"
            type="password"
            label="Password"
            placeholder="••••••••"
            required
            autocomplete="current-password"
          />

          <!-- Remember Me -->
          <div class="flex items-center">
            <input
              id="remember"
              v-model="form.remember"
              type="checkbox"
              class="h-4 w-4 rounded border-slate-300 text-aadi-teal focus:ring-aadi-teal"
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
            :loading="form.processing"
            :disabled="form.processing"
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
      </div>

      <!-- Back to Site -->
      <div class="text-center mt-6">
        <Link href="/" class="text-sm text-aadi-teal hover:text-aadi-teal-800">
          ← Back to Aadi Rental
        </Link>
      </div>
    </div>
  </div>
</template>
