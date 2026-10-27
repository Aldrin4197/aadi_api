import {test} from 'node:test';
import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import vm from 'node:vm';
const source=await readFile(new URL('../../public/sw.js',import.meta.url),'utf8');
function worker(fetch){
 const handlers={},writes=[],removed=[];
 const fallback={offline:true};
 const context={URL,fetch,caches:{open:async()=>({addAll:async assets=>writes.push(...assets)}),match:async()=>fallback,keys:async()=>['aadi-offline-v0','other-app'],delete:async key=>removed.push(key)},self:{location:{origin:'https://aadi.test'},clients:{claim:async()=>{}},skipWaiting(){context.skipped=true},addEventListener:(name,handler)=>handlers[name]=handler}};
 vm.runInNewContext(source,context);
 return {handlers,writes,removed,fallback,context};
}
test('PWA caches only the explicit offline assets and cleans only its own old cache',async()=>{
 const w=worker(async()=>({online:true}));let task;
 w.handlers.install({waitUntil:p=>task=p});await task;
 assert(w.writes.includes('/offline.html'));
 assert(w.writes.every(path=>path==='/offline.html'||path.startsWith('/pwa/')));
 assert(!w.context.skipped);
 w.handlers.activate({waitUntil:p=>task=p});await task;
 assert.deepEqual(w.removed,['aadi-offline-v0']);
});
test('private, API, Inertia, map and mutation requests never enter a cache handler',()=>{
 const w=worker(async()=>{});
 for(const [url,method,mode] of [['/api/me','GET','cors'],['/admin/dashboard','GET','cors'],['/?service=spaces','GET','cors'],['/login','POST','navigate'],['https://tiles.example/1.png','GET','cors']]){
  w.handlers.fetch({request:{url:new URL(url,'https://aadi.test').href,method,mode},respondWith:()=>assert.fail('Request must pass through')});
 }
});
test('offline navigation returns a generic page, while online responses pass through',async()=>{
 for(const offline of [true,false]){
  const response={status:200};const w=worker(async()=>{if(offline)throw Error('offline');return response});let task;
  w.handlers.fetch({request:{url:'https://aadi.test/admin/dashboard',method:'GET',mode:'navigate'},respondWith:p=>task=p});
  assert.equal(await task,offline?w.fallback:response);
  assert.deepEqual(w.writes,[]);
 }
});
test('manifest has standalone identity and required PNG sizes',async()=>{
 const manifest=JSON.parse(await readFile(new URL('../../public/manifest.webmanifest',import.meta.url),'utf8'));
 assert.equal(manifest.display,'standalone');assert.equal(manifest.scope,'/');
 for(const size of [192,512])assert(manifest.icons.some(icon=>icon.sizes===`${size}x${size}`&&icon.purpose==='any'));
 for(const icon of [...manifest.icons,{src:'/pwa/apple-touch-icon.png',sizes:'180x180'}]){
  const png=await readFile(new URL(`../../public${icon.src}`,import.meta.url));
  assert.equal(`${png.readUInt32BE(16)}x${png.readUInt32BE(20)}`,icon.sizes);
 }
});
