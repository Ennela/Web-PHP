const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const payload = `'"><img src=x onerror=globalThis.__xss=1><svg onload=globalThis.__xss=2>`;
const allNodes = [];
class Node {
  constructor(tag, text = '') { this.tagName=tag; this.children=[]; this.dataset={}; this.events={}; this.className=''; this.textContent=text; this.style={}; this.classList={add(){},remove(){},toggle(){}};allNodes.push(this); }
  append(...nodes) { this.children.push(...nodes); }
  appendChild(node) { this.children.push(node); return node; }
  replaceChildren(...nodes) { this.children=nodes; }
  addEventListener(name, callback) { (this.events[name] ??= []).push(callback); }
  querySelectorAll() { return []; }
  set innerHTML(value) { throw new Error('HTML parsing sink reached: '+value); }
  get innerHTML() { throw new Error('HTML serialization sink reached'); }
}
const addressGrid=new Node('div'),sizeGrid=new Node('div'),savedSizesList=new Node('div'),body=new Node('body');
const address={id:12,hoten:payload,sdt:'0900000000',diachi_cuthe:payload,tinh:'Hà Nội',phuong_xa:payload,quan_huyen:payload,macdinh:'0'};
const size={id:15,he_size:payload,size_value:payload,ghichu:payload};
const requests=[];
const context = {console,URL,FormData,Number,String,Array,JSON,Math,Promise,setTimeout(){},clearTimeout(){},location:{origin:'http://127.0.0.1:8000',href:'http://127.0.0.1:8000/auth/thongtintaikhoan.php'},window:{location:{hash:''}},history:{replaceState(){}},
  document:{body,createElement:tag=>new Node(tag),createTextNode:text=>new Node('#text',text),querySelectorAll:()=>[],querySelector:selector=>selector.includes('base-url')?{content:'http://127.0.0.1:8000/'}:selector.includes('csrf-token')?{content:'a'.repeat(64)}:null,getElementById:id=>({addressGrid,sizeGrid,savedSizesList})[id]??null,addEventListener(){}},
  fetch:async (url,opts={})=>{requests.push({url,opts}); return {json:async()=>opts.method==='POST'?{success:true,message:payload}:{success:true,data:url.includes('api_diachi')?[address]:[size]}}}
};
context.globalThis=context;
let source=fs.readFileSync(require('node:path').join(__dirname, '../assets/js/account.js'),'utf8');
source=source.replace(/\}\)\(\);\s*$/, 'globalThis.__test = { avatarUrl };\n})();');
vm.runInNewContext(source,context);
const flush=()=>new Promise(resolve=>setImmediate(resolve));
(async()=>{
 await flush();
 const texts=allNodes.map(node=>node.textContent);
 assert(texts.includes(payload),'Untrusted address/size strings must stay literal text');
 assert(allNodes.every(node=>!['img','svg','script'].includes(node.tagName)),'Payload must never create elements');
 const edit=allNodes.find(node=>node.className.includes('addr-edit-btn'));
 assert.deepEqual(JSON.parse(edit.dataset.addr),address,'Address values with quotes must round-trip in safe DOM dataset');
 const del=allNodes.find(node=>node.className==='saved-size-delete');
 let stopped=false;
 del.events.click[0]({stopPropagation(){stopped=true;}});
 await flush();
 assert(stopped);
 assert(requests.some(r=>r.opts.method==='POST'&&r.opts.headers['X-CSRF-Token']==='a'.repeat(64)),'Delete listener must preserve CSRF header');
 assert(allNodes.some(node=>node.tagName==='span'&&node.textContent===payload),'Toast must render server messages as literal text');
 assert.equal(context.__xss,undefined);
 const avatarUrl=context.__test.avatarUrl;
 for(const value of ['javascript:alert(1)','data:image/svg+xml,<svg/>','https://evil.example/avatar.png','http://127.0.0.1:8000/auth/uploads/avatars/../evil.png','http://127.0.0.1:8000/auth/uploads/avatars/evil.svg'])assert.equal(avatarUrl(value),null);
 assert.equal(avatarUrl('http://127.0.0.1:8000/auth/uploads/avatars/abc.png'),'http://127.0.0.1:8000/auth/uploads/avatars/abc.png');
 console.log('PASS: literal address/size/toast rendering, safe dataset quotes, working delete listener+CSRF, avatar URL allowlist.');
})().catch(error=>{console.error(error);process.exitCode=1;});
