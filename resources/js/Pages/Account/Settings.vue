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
                <h2 class="text-lg font-semibold">Social sign-in</h2>
                <p class="mt-2 text-sm text-gray-600">Connect Google or Facebook to this Aadi account. We’ll ask you to confirm your password first. If you signed up socially, use Forgot password to set one.</p>
                <p v-if="status" role="status" class="mt-3 text-sm">{{ status }}</p>
                <div class="mt-4 flex flex-wrap gap-3"><template v-for="provider in socialProviders" :key="provider"><span v-if="connectedProviders?.includes(provider)" class="rounded-xl bg-[#fff3bf] px-4 py-3 text-sm font-semibold">{{ provider === 'google' ? 'Google' : 'Facebook' }} connected</span><a v-else :href="`/settings/social/${provider}/connect`" class="inline-flex min-h-11 items-center rounded-xl border border-[#137671] px-4 py-3 text-sm font-semibold text-[#137671]">Connect {{ provider === 'google' ? 'Google' : 'Facebook' }}</a></template></div>
            </section>
            <p v-else-if="status" role="status" class="mb-4 text-sm">{{ status }}</p>
            <section class="profile-section" aria-label="Password">
                <UpdatePasswordForm class="max-w-xl" />
            </section>
            <section class="profile-section" aria-label="Delete account">
                <DeleteUserForm class="max-w-xl" />
            </section>
        </main>
    </AccountLayout>
</template>

<style scoped>
@media (pointer: coarse) {
    .account-settings :deep(input) { font-size: 16px; }
}
</style>
