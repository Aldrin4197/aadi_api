<script setup>
import { onBeforeUnmount, ref, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import UserAvatar from './UserAvatar.vue';
const page = usePage();
const fileInput = ref(null);
const image = ref(null), source = ref(''), zoom = ref(1), x = ref(50), y = ref(50), error = ref(''), preparing = ref(false);
const form = useForm({avatar:null});
let selection = 0;
const previewStyle = computed(() => ({ width:'100%', height:'100%', objectFit:'cover', objectPosition:`${x.value}% ${y.value}%`, transform:`scale(${zoom.value})`, transformOrigin:`${x.value}% ${y.value}%` }));
function discard() { selection++; if(source.value)URL.revokeObjectURL(source.value);source.value='';image.value=null;zoom.value=1;x.value=50;y.value=50; }
async function choose(event) {
    const file=event.target.files?.[0];event.target.value='';error.value='';form.clearErrors();
    if(!file)return;
    if(!['image/jpeg','image/png','image/webp'].includes(file.type)||file.size>5*1024*1024){error.value='Choose a JPG, PNG, or WebP image up to 5 MB.';return;}
    discard();const currentSelection=selection;const url=URL.createObjectURL(file);const img=new Image();
    try { img.src=url;await img.decode();if(currentSelection!==selection){URL.revokeObjectURL(url);return;}if(img.width>6000||img.height>6000)throw Error('Image dimensions must be at most 6000 × 6000.');source.value=url;image.value=img; }
    catch(e){URL.revokeObjectURL(url);error.value=e.message||'Unable to read this photo.';}
}
async function save() {
    if(!image.value||form.processing||preparing.value)return;preparing.value=true;error.value='';
    try { const img=image.value;const size=Math.min(img.width,img.height)/zoom.value;const canvas=document.createElement('canvas');canvas.width=canvas.height=512;const context=canvas.getContext('2d');context.fillStyle='#fff';context.fillRect(0,0,512,512);context.drawImage(img,(img.width-size)*x.value/100,(img.height-size)*y.value/100,size,size,0,0,512,512);const blob=await new Promise(resolve=>canvas.toBlob(resolve,'image/jpeg',.9));if(!blob)throw Error('Could not prepare the photo.');form.avatar=new File([blob],'avatar.jpg',{type:'image/jpeg'});form.post('/profile/avatar',{forceFormData:true,preserveScroll:true,onSuccess:()=>{discard();form.reset();},onError:()=>{error.value=form.errors.avatar || 'Your photo could not be uploaded. Please try again.';}}); }
    catch(e){error.value=e.message||'Could not prepare the photo.';}finally{preparing.value=false;}
}
function remove(){form.delete('/profile/avatar',{preserveScroll:true,onSuccess:discard});}
onBeforeUnmount(discard);
</script>
<template><section class="avatar-editor"><h2>Profile photo</h2><p>Your avatar is public. Keep IDs and verification documents in your private provider application.</p><div class="avatar-editor-row"><UserAvatar :user="page.props.auth.user"/><button type="button" class="button" :disabled="form.processing||preparing" @click="fileInput?.click()">Choose photo</button><input ref="fileInput" class="avatar-file-input" type="file" accept="image/jpeg,image/png,image/webp" aria-label="Choose profile photo" :disabled="form.processing||preparing" @change="choose"/><button v-if="page.props.auth.user.avatar_url" type="button" class="text-button" :disabled="form.processing" @click="remove">Remove photo</button></div>
<div v-if="source" class="avatar-crop"><div class="avatar-crop-preview"><img :src="source" alt="Cropped avatar preview" :style="previewStyle"/></div><div><label>Zoom<input v-model.number="zoom" type="range" min="1" max="3" step=".05"/></label><label>Horizontal position<input v-model.number="x" type="range" min="0" max="100"/></label><label>Vertical position<input v-model.number="y" type="range" min="0" max="100"/></label><div class="avatar-editor-row"><button type="button" class="button primary" :disabled="form.processing||preparing" @click="save">{{ form.processing||preparing?'Saving…':'Save photo' }}</button><button type="button" class="button" :disabled="form.processing" @click="discard">Cancel</button></div></div></div><p v-if="error||form.errors.avatar" role="alert">{{ error||form.errors.avatar }}</p></section></template>
<style scoped>
.avatar-file-input { display:none; }
.avatar-editor p { color:var(--muted);font-size:14px;max-width:65ch; }.avatar-editor-row { display:flex;align-items:center;flex-wrap:wrap;gap:16px;margin:20px 0; }.avatar-editor-row :deep(.user-avatar) { width:72px;height:72px; }.avatar-editor input[type=file] { width:200px;max-width:100%; }.avatar-crop { display:grid;grid-template-columns:180px minmax(0,320px);gap:24px;margin-top:20px; }.avatar-crop-preview { width:180px;height:180px;overflow:hidden;border-radius:50%;background:#fff; }.avatar-crop label { margin-bottom:12px; }.avatar-crop input[type=range] { padding:0;accent-color:#137671; }@media(max-width:600px){.avatar-crop{grid-template-columns:1fr;}.avatar-editor input[type=file]{width:160px;}}
</style>
