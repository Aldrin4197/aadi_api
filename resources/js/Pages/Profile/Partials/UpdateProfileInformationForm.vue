<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    profile?: Record<string, any>;
    mustVerifyEmail?: Boolean;
    status?: String;
}>();

const user = { ...usePage().props.auth.user, ...props.profile };
const isVerified = computed(() => usePage().props.auth.user?.emailVerified === true);
const verifiedOn = computed(() => {
    const value = props.profile?.email_verified_at;
    return value ? new Date(value).toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' }) : null;
});

const form = useForm({
    name: user.name,
    email: user.email,
    phone: props.profile?.phone || '', bio: props.profile?.bio || '', city: props.profile?.city || '', address: props.profile?.address || '',
});
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                Profile Information
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Update your account's profile information and email address.
            </p>
        </header>

        <form
            @submit.prevent="form.patch(route('profile.update'))"
            class="mt-6 space-y-6"
        >
            <div>
                <InputLabel for="name" value="Name" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <div class="email-label-row">
                    <InputLabel for="email" value="Email" />
                    <span v-if="isVerified" class="email-badge verified" :title="verifiedOn ? `Verified on ${verifiedOn}` : undefined"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12.5l4.2 4.2L19 7" /></svg>Verified</span>
                    <span v-else class="email-badge unverified"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 7v6M12 17h.01" /></svg>Not verified</span>
                </div>

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
                <p v-if="isVerified && verifiedOn" class="mt-2 text-xs text-gray-500">Verified on {{ verifiedOn }}. Changing your email address will require you to verify it again.</p>
            </div>

            <div><InputLabel for="phone" value="Phone number"/><TextInput id="phone" v-model="form.phone" type="tel" autocomplete="tel" maxlength="40" class="mt-1 block w-full"/><InputError :message="form.errors.phone"/></div>
            <div><InputLabel for="city" value="City / municipality"/><TextInput id="city" v-model="form.city" autocomplete="address-level2" maxlength="255" class="mt-1 block w-full"/><InputError :message="form.errors.city"/></div>
            <div><InputLabel for="address" value="Address (private)"/><TextInput id="address" v-model="form.address" autocomplete="street-address" maxlength="255" class="mt-1 block w-full"/><InputError :message="form.errors.address"/></div>
            <div><InputLabel for="bio" value="About you"/><textarea id="bio" v-model="form.bio" rows="4" maxlength="1000"/><InputError :message="form.errors.bio"/></div>

            <div v-if="mustVerifyEmail && !isVerified">
                <p class="mt-2 text-sm text-gray-800">
                    Your email address is unverified.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton type="submit" :disabled="form.processing">Save</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-gray-600"
                    >
                        Saved.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
<style scoped>
.email-label-row { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.email-badge { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px 3px 8px; border-radius: 999px; font-size: 12px; font-weight: 700; line-height: 1.4; }
.email-badge svg { width: 14px; height: 14px; fill: none; stroke: currentColor; stroke-width: 2.4; stroke-linecap: round; stroke-linejoin: round; }
.email-badge.verified { background: #e3f6ea; color: #14683a; border: 1px solid #b9e3c8; }
.email-badge.unverified { background: #fff5df; color: #6b4a0a; border: 1px solid #f0d9a4; }
</style>
