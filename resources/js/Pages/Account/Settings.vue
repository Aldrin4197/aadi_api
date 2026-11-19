<script setup lang="ts">
import AccountPageHeader from '@/Components/Account/AccountPageHeader.vue';
import AccountLayout from '@/Layouts/AccountLayout.vue';
import DeleteUserForm from '@/Pages/Profile/Partials/DeleteUserForm.vue';
import UpdatePasswordForm from '@/Pages/Profile/Partials/UpdatePasswordForm.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    socialProviders?: string[];
    connectedProviders?: string[];
    status?: string;
    email?: string;
    usesSocialLogin?: boolean;
}>();
</script>

<template>
    <Head title="Settings" />
    <AccountLayout>
        <main class="account-page account-profile account-settings">
            <AccountPageHeader title="Settings" description="Manage your profile, sign-in and account." />
            <section class="profile-section account-settings-link">
                <div><h2>Personal profile</h2><p>Update your photo, name and contact details.</p></div>
                <Link href="/profile" class="button">Edit profile</Link>
            </section>
            <section v-if="socialProviders?.length" class="profile-section" aria-label="Connected sign-in accounts">
                <h2>Social sign-in</h2>
                <p class="settings-description">Connect Google or Facebook to this Aadi account. We’ll ask you to confirm your password first. If you signed up socially, use Forgot password to set one.</p>
                <p v-if="status" role="status" class="account-feedback">{{ status }}</p>
                <div class="settings-social-actions"><template v-for="provider in socialProviders" :key="provider"><span v-if="connectedProviders?.includes(provider)" class="account-status is-connected">{{ provider === 'google' ? 'Google' : 'Facebook' }} connected</span><a v-else :href="`/settings/social/${provider}/connect`" class="button">Connect {{ provider === 'google' ? 'Google' : 'Facebook' }}</a></template></div>
            </section>
            <p v-else-if="status" role="status" class="mb-4 text-sm">{{ status }}</p>
            <section class="profile-section" aria-label="Password">
                <UpdatePasswordForm class="max-w-xl" />
            </section>
            <section class="profile-section" aria-label="Delete account">
                <DeleteUserForm class="max-w-xl" :email="email" :uses-social-login="usesSocialLogin" />
            </section>
        </main>
    </AccountLayout>
</template>

<style scoped>
@media (pointer: coarse) {
    .account-settings :deep(input) { font-size: 16px; }
}
    .settings-description { color:var(--muted); font-size:var(--aadi-text-body); line-height:1.6; margin:8px 0 0; max-width:70ch; }
    .settings-social-actions { display:flex; align-items:center; flex-wrap:wrap; gap:10px; margin-top:16px; }
</style>
