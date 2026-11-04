<script setup>
import { computed, nextTick, ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const categories = {
    account: 'Account & login',
    listing: 'A listing or booking',
    provider: 'Provider application',
    report: 'Report a listing or user',
    bug: 'Something is not working',
    feedback: 'Feedback or an idea',
    other: 'Something else',
};
const form = useForm({ name: user.value?.name || '', email: user.value?.email || '', category: '', subject: '', message: '', website: '' });
const reference = ref('');
const done = ref(false);
const status = ref(null);
const left = computed(() => 4000 - form.message.length);

function submit() {
    form.post('/support/tickets', {
        preserveScroll: true,
        onSuccess: async () => {
            reference.value = page.props.flash?.ticket || '';
            done.value = true;
            form.reset('category', 'subject', 'message', 'website');
            await nextTick();
            status.value?.focus();
        },
    });
}
function another() { done.value = false; reference.value = ''; }
</script>

<template>
    <section id="support" class="landing-section support" aria-labelledby="support-title">
        <div class="support-copy">
            <p class="landing-kicker">NEED A HAND?</p>
            <h2 id="support-title">Talk to the Aadi team.</h2>
            <p>Trouble with your account, a listing that looks wrong, or an idea to make Aadi better? Send us a message and we reply by email, usually within a day.</p>
            <ul>
                <li>No account needed.</li>
                <li>Reporting a listing or user? Choose that topic and we prioritise it.</li>
                <li>Please never send passwords or card numbers.</li>
            </ul>
        </div>

        <div class="support-card">
            <div v-if="done" ref="status" class="support-done" role="status" tabindex="-1">
                <span class="done-mark" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" /></svg></span>
                <h3>Message sent</h3>
                <p>Thanks, we have it. We will reply to the email you gave us.</p>
                <p v-if="reference" class="reference">Your reference: <strong>{{ reference }}</strong></p>
                <button type="button" class="support-link" @click="another">Send another message</button>
            </div>

            <form v-else novalidate @submit.prevent="submit">
                <div class="row">
                    <label>Your name<input v-model="form.name" type="text" autocomplete="name" maxlength="120" required :aria-invalid="!!form.errors.name" aria-describedby="support-name-error" /><span id="support-name-error" class="err" role="alert">{{ form.errors.name }}</span></label>
                    <label>Email<input v-model="form.email" type="email" autocomplete="email" maxlength="190" required :aria-invalid="!!form.errors.email" aria-describedby="support-email-error" /><span id="support-email-error" class="err" role="alert">{{ form.errors.email }}</span></label>
                </div>
                <label>What is this about?
                    <select v-model="form.category" required :aria-invalid="!!form.errors.category" aria-describedby="support-category-error">
                        <option value="" disabled>Choose a topic</option>
                        <option v-for="(label, key) in categories" :key="key" :value="key">{{ label }}</option>
                    </select>
                    <span id="support-category-error" class="err" role="alert">{{ form.errors.category }}</span>
                </label>
                <label>Subject<input v-model="form.subject" type="text" maxlength="160" placeholder="A short summary" required :aria-invalid="!!form.errors.subject" aria-describedby="support-subject-error" /><span id="support-subject-error" class="err" role="alert">{{ form.errors.subject }}</span></label>
                <label>Message
                    <textarea v-model="form.message" rows="5" maxlength="4000" placeholder="Tell us what happened and what you expected. Include the listing name if it is about one." required :aria-invalid="!!form.errors.message" aria-describedby="support-message-error support-count" />
                    <span class="foot"><span id="support-message-error" class="err" role="alert">{{ form.errors.message }}</span><small id="support-count">{{ left.toLocaleString() }} characters left</small></span>
                </label>
                <div class="trap" aria-hidden="true"><label>Leave this empty<input v-model="form.website" type="text" tabindex="-1" autocomplete="off" /></label></div>
                <p v-if="form.errors.website || (form.hasErrors && !Object.keys(form.errors).some(k => ['name','email','category','subject','message'].includes(k)))" class="err" role="alert">We could not send your message. Please try again in a minute.</p>
                <button type="submit" class="button primary" :disabled="form.processing">{{ form.processing ? 'Sending…' : 'Send message' }} <span aria-hidden="true">↗</span></button>
            </form>
        </div>
    </section>
</template>

<style scoped>
.support { display:grid; grid-template-columns:1fr 1.15fr; gap:72px; align-items:start; border-top:1px solid var(--line); }
.support-copy h2 { margin-bottom:18px; }
.support-copy > p:not(.landing-kicker) { max-width:46ch; margin:0 0 20px; }
.support-copy ul { list-style:none; margin:0; padding:0; display:grid; gap:10px; font-size:14px; color:#586e6c; }
.support-copy li { display:flex; gap:10px; }
.support-copy li::before { content:''; width:6px; height:6px; border-radius:50%; background:#1fa6a0; margin-top:9px; flex:none; }
.support-card { background:#fff; border:1px solid var(--line); border-radius:16px; padding:28px; box-shadow:0 8px 28px #0b3a380d; }
form { display:grid; gap:16px; }
.row { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
label { display:grid; gap:6px; font-size:13px; font-weight:700; color:var(--aadi-dark); }
input, select, textarea { width:100%; min-height:46px; padding:11px 13px; border:1px solid #cbded8; border-radius:10px; background:#fff; color:#183e3b; font:inherit; font-size:14px; font-weight:500; }
textarea { resize:vertical; min-height:130px; line-height:1.6; }
input:focus-visible, select:focus-visible, textarea:focus-visible { outline:2px solid #137671; outline-offset:1px; border-color:#137671; }
[aria-invalid=true] { border-color:#b42318; }
.err { min-height:0; font-size:12px; font-weight:600; color:#b42318; }
.err:empty { display:none; }
.foot { display:flex; justify-content:space-between; gap:12px; align-items:flex-start; }
.foot small { margin-left:auto; font-size:11px; font-weight:500; color:#7b918d; white-space:nowrap; }
.trap { position:absolute; left:-9999px; width:1px; height:1px; overflow:hidden; }
.button { justify-self:start; min-height:48px; padding:12px 22px; gap:20px; }
.button:disabled { opacity:.6; cursor:wait; }
.support-done { text-align:center; padding:28px 12px; }
.support-done:focus { outline:none; }
.done-mark { display:inline-grid; place-items:center; width:52px; height:52px; border-radius:50%; background:#e1f5ec; color:#12744a; margin-bottom:14px; }
.done-mark svg { width:26px; height:26px; fill:none; stroke:currentColor; stroke-width:2.4; stroke-linecap:round; stroke-linejoin:round; }
.support-done h3 { margin:0 0 8px; }
.support-done p { margin:0 auto 10px; max-width:40ch; }
.reference { padding:10px 14px; background:#f4f8f6; border-radius:10px; display:inline-block; font-size:14px !important; }
.reference strong { letter-spacing:.06em; }
.support-link { display:block; margin:8px auto 0; min-height:44px; font-size:13px; font-weight:700; color:#137671; text-decoration:underline; cursor:pointer; }
@media (max-width:900px) { .support { grid-template-columns:1fr; gap:32px; } }
@media (max-width:600px) { .row { grid-template-columns:1fr; } .support-card { padding:20px; } }
</style>
