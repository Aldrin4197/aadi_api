<script setup>
import { ref, watch } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import UserAvatar from './UserAvatar.vue';

const props = defineProps({ userId: { type: String, required: true }, likes: { type: Object, required: true } });
const page = usePage();
const form = useForm({});
const open = ref(false);
const loading = ref(false);
const error = ref('');
const people = ref(null);
let sequence = 0;
async function load(number = 1) {
    const requestId = ++sequence;
    loading.value = true;
    error.value = '';
    try {
        const { data } = await axios.get(`/users/${props.userId}/likes`, { params: { page: number } });
        if (requestId === sequence) people.value = data;
    } catch {
        if (requestId === sequence) error.value = 'Could not load likes. Please try again.';
    } finally {
        if (requestId === sequence) loading.value = false;
    }
}
function toggleList() {
    open.value = !open.value;
    if (open.value) load();
}
function toggleLike() {
    const options = { preserveScroll: true, onSuccess: () => { if (open.value) load(); } };
    if (props.likes.liked) form.delete(`/users/${props.userId}/likes`, options);
    else form.put(`/users/${props.userId}/likes`, options);
}
watch(() => props.userId, () => { sequence++; open.value = false; people.value = null; });
</script>

<template>
    <section class="profile-likes" aria-label="Profile actions">
        <div class="like-actions">
            <button v-if="page.props.auth?.user && page.props.auth.user.id !== userId && (!page.props.auth.user.is_admin || likes.liked)" type="button" class="like-button" :aria-label="likes.liked ? 'Unlike profile' : 'Like profile'" :aria-pressed="likes.liked" :disabled="form.processing" @click="toggleLike">
                <svg viewBox="0 0 24 24" aria-hidden="true" :fill="likes.liked ? 'currentColor' : 'none'">
                    <path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21l7.8-7.5 1.1-1.1a5.5 5.5 0 0 0-.1-7.8Z" />
                </svg>
            </button>
            <Link v-else-if="!page.props.auth?.user" class="like-button" href="/login" aria-label="Log in to like this profile">
                <svg viewBox="0 0 24 24" aria-hidden="true" fill="none">
                    <path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21l7.8-7.5 1.1-1.1a5.5 5.5 0 0 0-.1-7.8Z" />
                </svg>
            </Link>
            <slot />
            <button type="button" class="like-count" :aria-expanded="open" aria-controls="profile-likers" @click="toggleList">{{ likes.count }} {{ likes.count === 1 ? 'like' : 'likes' }}</button>
        </div>
        <p v-if="Object.keys(form.errors).length" class="error" role="alert">Could not update your like. Please try again.</p>
        <div v-if="open" id="profile-likers" class="likers">
            <h2>People who liked this profile</h2>
            <p v-if="loading" role="status">Loading likes…</p>
            <p v-else-if="error" class="error" role="alert">{{ error }} <button type="button" @click="load()">Try again</button></p>
            <template v-else-if="people">
                <p v-if="!people.data.length">No likes yet.</p>
                <ul v-else><li v-for="person in people.data" :key="person.id"><UserAvatar :user="person" /><Link :href="`/providers/${person.id}`">{{ person.name }}</Link></li></ul>
                <nav v-if="people.last_page > 1" aria-label="People who liked this profile">
                    <button type="button" :disabled="people.current_page <= 1" @click="load(people.current_page - 1)">Previous</button>
                    <span>Page {{ people.current_page }} of {{ people.last_page }}</span>
                    <button type="button" :disabled="people.current_page >= people.last_page" @click="load(people.current_page + 1)">Next</button>
                </nav>
            </template>
        </div>
    </section>
</template>

<style scoped>
.profile-likes { margin:20px 0 24px; }
.like-actions { display:flex; flex-wrap:wrap; align-items:center; gap:8px; }
.like-button { display:inline-grid; place-items:center; width:44px; height:44px; padding:0; border:1px solid #c5ded9; border-radius:50%; color:#12675f; background:#fff; text-decoration:none; cursor:pointer; }
.like-button:hover { background:#e2f2ef; }
.like-button[aria-pressed=true] { border-color:#12675f; color:#fff; background:#12675f; }
.like-button[aria-pressed=true]:hover { background:#0b504a; }
.like-button svg { width:20px; height:20px; stroke:currentColor; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; }
.like-button:disabled { opacity:.6; }
.like-count, nav button, .error button { min-height:44px; padding:0 10px; color:#42665e; font-size:13px; font-weight:700; cursor:pointer; }
.like-count:hover, nav button:hover, .error button:hover { text-decoration:underline; }
.likers { max-width:440px; margin-top:12px; padding:16px; border:1px solid #dcece9; border-radius:12px; background:white; }
.likers h2 { margin:0 0 12px; font-size:15px; font-weight:700; }
.likers p { font-size:13px; }
.likers ul { margin:0; padding:0; list-style:none; }
.likers li { display:flex; align-items:center; gap:10px; padding:6px 0; font-size:13px; font-weight:700; }
.likers :deep(.user-avatar) { width:36px; height:36px; font-size:13px; }
nav { display:flex; align-items:center; gap:12px; font-size:12px; }
nav button:disabled { opacity:.4; cursor:default; }
.error { color:#a12626; font-size:13px; }
.profile-likes :is(button,a):focus-visible { outline:2px solid #137671; outline-offset:3px; }
@media (max-width:480px) { .like-actions { gap:8px; } }
</style>
