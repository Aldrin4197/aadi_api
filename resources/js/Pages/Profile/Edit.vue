<script setup lang="ts">
import AccountLayout from '@/Layouts/AccountLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps<{
    mustVerifyEmail?: boolean;
    status?: string;
}>();
</script>

<template>
    <Head title="Profile" />

    <AccountLayout>
        <main class="account-profile">
            <h1>Profile & settings</h1>
            <p>Manage your personal details, password, and account.</p>
            <section class="profile-section" aria-label="Personal information">
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                    class="max-w-xl"
                />
            </section>
            <section class="profile-section" aria-label="Password">
                <UpdatePasswordForm class="max-w-xl" />
            </section>
            <section class="profile-section" aria-label="Delete account">
                <DeleteUserForm class="max-w-xl" />
            </section>
        </main>
    </AccountLayout>
</template>
