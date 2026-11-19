// Real Chromium checks, controlled through CDP in an isolated browser profile.
// Invoked by test_xss.py; cookie fixtures arrive through stdin and are never logged.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const fixture = JSON.parse(fs.readFileSync(0, 'utf8'));

class CDP {
  constructor(url) {
    this.socket = new WebSocket(url);
    this.next = 1;
    this.pending = new Map();
    this.listeners = [];
    this.ready = new Promise((resolve, reject) => {
      this.socket.addEventListener('open', resolve, { once: true });
      this.socket.addEventListener('error', reject, { once: true });
    });
    this.socket.addEventListener('message', event => {
      const message = JSON.parse(event.data);
      if (message.id) {
        const pending = this.pending.get(message.id);
        if (!pending) return;
        this.pending.delete(message.id);
        clearTimeout(pending.timer);
        message.error ? pending.reject(new Error(message.error.message)) : pending.resolve(message.result);
      } else {
        this.listeners.forEach(listener => listener(message));
      }
    });
  }
  async send(method, params = {}, sessionId) {
    await this.ready;
    return new Promise((resolve, reject) => {
      const id = this.next++;
      const timer = setTimeout(() => { this.pending.delete(id); reject(new Error('CDP timeout: ' + method)); }, 20000);
      this.pending.set(id, { resolve, reject, timer });
      this.socket.send(JSON.stringify({ id, method, params, ...(sessionId ? { sessionId } : {}) }));
    });
  }
}

(async () => {
  const version = await (await fetch(fixture.chrome + '/json/version')).json();
  const cdp = new CDP(version.webSocketDebuggerUrl);
  const contexts = [];
  try {
    async function actor(cookie) {
      const { browserContextId } = await cdp.send('Target.createBrowserContext');
      contexts.push(browserContextId);
      const { targetId } = await cdp.send('Target.createTarget', { url: 'about:blank', browserContextId });
      const { sessionId } = await cdp.send('Target.attachToTarget', { targetId, flatten: true });
      await cdp.send('Page.enable', {}, sessionId);
      await cdp.send('Runtime.enable', {}, sessionId);
      await cdp.send('Network.enable', {}, sessionId);
      await cdp.send('Network.setCookie', { name: 'PHPSESSID', value: cookie, url: fixture.base, httpOnly: true, sameSite: 'Lax' }, sessionId);
      await cdp.send('Page.addScriptToEvaluateOnNewDocument', {
        source: `window.__XSS_EXECUTED=0;window.__xssViolations=[];document.addEventListener('securitypolicyviolation',e=>window.__xssViolations.push({directive:e.violatedDirective,blocked:e.blockedURI}));`
      }, sessionId);
      const evaluate = async expression => {
        const result = await cdp.send('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true }, sessionId);
        if (result.exceptionDetails) throw new Error(result.exceptionDetails.exception?.description || result.exceptionDetails.text);
        return result.result.value;
      };
      const navigate = async path => {
        await cdp.send('Page.navigate', { url: fixture.base + path }, sessionId);
        let loaded = false;
        for (let attempt = 0; attempt < 100; attempt++) {
          if (await evaluate(`document.readyState==='complete'`)) { loaded = true; break; }
          await new Promise(resolve => setTimeout(resolve, 100));
        }
        assert.ok(loaded, 'Browser page load timed out: ' + path);
        const state = await evaluate(`({xss:window.__XSS_EXECUTED, html:document.documentElement.outerHTML, text:document.body.textContent, handlers:[...document.querySelectorAll('*')].flatMap(el=>[...el.attributes].filter(a=>a.name.startsWith('on')).map(a=>a.name))})`);
        assert.equal(state.xss, 0, 'Payload executed: ' + path);
        assert.deepEqual(state.handlers, [], 'Active HTML event attribute remained: ' + path);
        assert.ok(!state.text.includes('Hệ thống tạm thời không khả dụng.'), 'Application error: ' + path);
        return state;
      };
      return { evaluate, navigate };
    }
    const customer = await actor(fixture.customerCookie);
    for (const path of [
      'shop/shop.php?search=' + encodeURIComponent(fixture.payload),
      'auth/thongtintaikhoan.php',
      'shop/chitietsanpham.php?masp=' + fixture.product,
      'blog/chitietbaiviet.php?id=' + fixture.blog,
      'shop/chitietdonhang.php?id=' + fixture.order
    ]) {
      const page = await customer.navigate(path);
      if (path.includes('chitietsanpham') || path.includes('chitietbaiviet')) {
        assert.ok(page.html.includes('<strong>SAFE_FORMAT_MARKER</strong>'), 'Safe rich formatting was lost');
      }
    }
    await customer.navigate('shop/chitietsanpham.php?masp=' + fixture.product);
    const productInteraction = await customer.evaluate(`(() => {
      document.querySelector('.size-btn[data-size="40"]').click();
      document.querySelector('[data-product-action="quantity"][data-delta="1"]').click();
      return {size:document.getElementById('form-size').value,quantity:document.getElementById('qty-field').value};
    })()`);
    assert.equal(productInteraction.size, '40');
    assert.equal(productInteraction.quantity, '2');
    await customer.navigate('auth/thongtintaikhoan.php');
    const account = await customer.evaluate(`new Promise(resolve=>setTimeout(()=>resolve({text:document.body.textContent,xss:window.__XSS_EXECUTED}),500))`);
    assert.equal(account.xss, 0);
    assert.ok(account.text.includes('XSS_MARKER'), 'Stored address/size text did not render');
    const toast = await customer.evaluate(`(async () => {
      const originalFetch=window.fetch;
      window.fetch=(url,options)=>String(url).includes('api_capnhat_thongtin.php')?Promise.resolve({json:async()=>({success:false,message:${JSON.stringify(fixture.payload)}})}):originalFetch(url,options);
      document.getElementById('btnEditProfile').click();
      document.getElementById('btnSaveProfile').click();
      await new Promise(resolve=>setTimeout(resolve,100));
      return {text:document.querySelector('.toast-container')?.textContent,xss:window.__XSS_EXECUTED};
    })()`);
    assert.equal(toast.xss, 0);
    assert.ok(toast.text?.includes('XSS_MARKER'), 'Toast message was not rendered as text');
    await customer.navigate('');
    const noncePolicy = await customer.evaluate(`new Promise(resolve=>{
      const script=document.createElement('script');script.textContent='window.__XSS_EXECUTED=99';document.body.append(script);
      const image=document.createElement('img');image.setAttribute('onerror','window.__XSS_EXECUTED=98');image.src='/xss-missing-image';document.body.append(image);
      setTimeout(()=>resolve({xss:window.__XSS_EXECUTED,blocked:window.__xssViolations.length}),200);
    })`);
    assert.equal(noncePolicy.xss, 0);
    assert.ok(noncePolicy.blocked >= 2, 'CSP did not block injected script and event handler');
    const admin = await actor(fixture.adminCookie);
    for (const path of ['admin/trangchu.php','admin/quanlisanpham.php','admin/quanlikhachhang.php','admin/quanlibaidang.php','admin/edit_qlsanpham.php?masp='+fixture.product]) {
      await admin.navigate(path);
    }
    await admin.navigate('admin/trangchu.php');
    assert.equal(await admin.evaluate(`(() => {document.querySelector('[data-dropdown-toggle]').click();return document.getElementById('myDropdown').classList.contains('invisible');})()`), false);
    console.log('PASS browser: reflected/stored/rich/DOM payloads inert; nonce CSP blocks injected script/events; product controls and admin dropdown work.');
  } finally {
    for (const browserContextId of contexts) await cdp.send('Target.disposeBrowserContext', { browserContextId });
    cdp.socket.close();
  }
})().catch(error => { console.error(error.stack); process.exitCode=1; });
