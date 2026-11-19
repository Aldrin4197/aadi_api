<script setup lang="ts">
import AccountPageHeader from '@/Components/Account/AccountPageHeader.vue';
import AvatarEditor from '@/Components/Account/AvatarEditor.vue';
import AccountLayout from '@/Layouts/AccountLayout.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    profile?: Record<string, any>;
    mustVerifyEmail?: boolean;
    status?: string;
    profileIncomplete?: boolean;
}>();
</script>

<template>
    <Head title="Profile" />

    <AccountLayout>
        <main class="account-page account-profile">
            <AccountPageHeader title="Profile" description="Manage your personal details and profile photo."/>
            <p v-if="profileIncomplete" role="status" class="notice">Complete your birthdate, sex assigned at birth, city and full address to use account features. Your city may be public; the other details are private.</p>
            <p v-if="status === 'delivery-failed'" role="alert" class="mb-4 text-sm text-red-700">The verification email could not be sent. Please try again shortly.</p>
            <section class="profile-section" aria-label="Profile photo"><AvatarEditor/></section>
            <section class="profile-section" aria-label="Personal information">
                <UpdateProfileInformationForm
                    :profile="profile"
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                    class="max-w-xl"
                />
            </section>
            <section class="profile-section account-settings-link"><div><h2>Account settings</h2><p>Manage sign-in, your password and account deletion.</p></div><Link href="/settings" class="button">Open settings</Link></section>
        </main>
    </AccountLayout>
</template>
