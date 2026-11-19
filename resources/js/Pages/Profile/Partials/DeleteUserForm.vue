<script setup lang="ts">
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const props = defineProps<{ email?: string; usesSocialLogin?: boolean }>();

const confirmingUserDeletion = ref(false);
const passwordInput = ref<HTMLInputElement | null>(null);

const form = useForm({
    password: '',
    email: '',
    reason: '',
    note: '',
});

const reasons = [
    ['not_using', 'I no longer use Aadi'],
    ['found_what_needed', 'I found what I needed'],
    ['privacy', 'Privacy concerns'],
    ['too_many_notifications', 'Too many emails or notifications'],
    ['bad_experience', 'I had a bad experience'],
    ['technical_problems', 'Technical problems'],
    ['other', 'Something else'],
];

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    nextTick(() => passwordInput.value?.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: (e) => { if (!e.reason) passwordInput.value?.focus(); },
        onFinish: () => {
            form.reset();
        },
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                Delete Account
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Your account is deactivated right away and permanently deleted after 30 days. Before deleting your account, please
                download any data or information that you wish to retain.
            </p>
        </header>

        <DangerButton @click="confirmUserDeletion">Delete Account</DangerButton>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-6">
                <h2
                    class="text-lg font-medium text-gray-900"
                >
                    Are you sure you want to delete your account?
                </h2>

                <p class="mt-1 text-sm text-gray-600">
                    Your account will be deactivated now and, after 30 days, permanently deleted with your listings, messages and uploaded files. To keep it, contact info@aadirentals.com within 30 days.
                    Provider rental, job and payment history, including the customer name, is retained for six months after deletion and removed by scheduled cleanup. An active provider with the relevant approved role can view it until the deadline.
                    Enter your password to confirm.
                    <template v-if="usesSocialLogin">If you signed up with Google or Facebook, you can type your account email address instead.</template>
                    See the <a href="/data-deletion" class="underline">data deletion details</a>.
                </p>

                <div class="mt-4">
                    <InputLabel for="delete-reason" value="Why are you leaving?" />
                    <select id="delete-reason" v-model="form.reason" class="mt-1 block w-full rounded-md border-gray-300 text-base shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="" disabled>Select a reason</option>
                        <option v-for="[value, label] in reasons" :key="value" :value="value">{{ label }}</option>
                    </select>
                    <InputError :message="form.errors.reason" class="mt-2" />
                    <InputLabel for="delete-note" value="Anything else we should know? (optional)" class="mt-3" />
                    <textarea id="delete-note" v-model="form.note" maxlength="500" rows="2" class="mt-1 block w-full rounded-md border-gray-300 text-base shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Please don't include personal details." />
                    <InputError :message="form.errors.note" class="mt-2" />
                </div>

                <div class="mt-6">
                    <InputLabel
                        for="delete-password"
                        value="Password"
                        class="sr-only"
                    />

                    <TextInput
                        id="delete-password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-3/4"
                        placeholder="Password"
                        autocomplete="current-password"
                        @keyup.enter="deleteUser"
                    />

                    <InputError :message="form.errors.password" class="mt-2" />

                    <template v-if="usesSocialLogin">
                        <InputLabel for="delete-email" value="Or confirm your email" class="sr-only" />
                        <TextInput
                            id="delete-email"
                            v-model="form.email"
                            type="email"
                            class="mt-3 block w-3/4"
                            :placeholder="props.email || 'Account email address'"
                            autocomplete="off"
                            @keyup.enter="deleteUser"
                        />
                        <InputError :message="form.errors.email" class="mt-2" />
                    </template>
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal">
                        Cancel
                    </SecondaryButton>

                    <DangerButton
                        class="ms-3"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="deleteUser"
                    >
                        Delete Account
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </section>
</template>
