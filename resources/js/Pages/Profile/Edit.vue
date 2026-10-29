<script setup lang="ts">
import AccountPageHeader from '@/Components/Account/AccountPageHeader.vue';
import AvatarEditor from '@/Components/Account/AvatarEditor.vue';
import AccountLayout from '@/Layouts/AccountLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps<{
    socialProviders?: string[];
    connectedProviders?: string[];
    profile?: Record<string, any>;
    mustVerifyEmail?: boolean;
    status?: string;
}>();
</script>

<template>
    <Head title="Profile" />

    <AccountLayout>
        <main class="account-page account-profile">
            <AccountPageHeader title="Profile & settings" description="Manage your personal details, password, and account."/>
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
            <section v-if="socialProviders?.length" class="profile-section" aria-label="Connected sign-in accounts">
                <h2 class="text-lg font-semibold">Social sign-in</h2>
                <p class="mt-2 text-sm text-gray-600">Connect Google or Facebook to this Aadi account. We’ll ask you to confirm your password first. If you signed up socially, use Forgot password to set one.</p>
                <p v-if="status && !['verification-link-sent','delivery-failed'].includes(status)" role="status" class="mt-3 text-sm">{{ status }}</p>
                <div class="mt-4 flex flex-wrap gap-3"><template v-for="provider in socialProviders" :key="provider"><span v-if="connectedProviders?.includes(provider)" class="rounded-xl bg-[#fff3bf] px-4 py-3 text-sm font-semibold">{{ provider === 'google' ? 'Google' : 'Facebook' }} connected</span><a v-else :href="`/settings/social/${provider}/connect`" class="inline-flex min-h-11 items-center rounded-xl border border-[#137671] px-4 py-3 text-sm font-semibold text-[#137671]">Connect {{ provider === 'google' ? 'Google' : 'Facebook' }}</a></template></div>
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
