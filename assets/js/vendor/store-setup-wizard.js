// ES6 module for Store Setup Wizard (vanilla JS)
const REST_BASE = window.VMP_StoreSetup.restBase;
const NONCE = window.VMP_StoreSetup.nonce;
const PLUGIN_URL = window.VMP_StoreSetup.pluginUrl;

const steps = [
  { id: 1, title: 'معلومات المتجر', key: 'store' },
  { id: 2, title: 'العلامة التجارية', key: 'branding' },
  { id: 3, title: 'الاتصال', key: 'contact' },
  { id: 4, title: 'السياسات', key: 'policies' },
  { id: 5, title: 'وسائل التواصل', key: 'social' },
];

let session = null;
let sessionUuid = null;
let currentStep = 1;
let debounceTimer = null;

function qs(sel, ctx=document) { return ctx.querySelector(sel); }
function ce(tag, attrs={}, txt='') { const el = document.createElement(tag); for(const k in attrs) el.setAttribute(k, attrs[k]); if (txt) el.textContent = txt; return el; }

function showToast(msg) {
  let t = document.getElementById('vmp-toast');
  if (!t) { t = document.createElement('div'); t.id='vmp-toast'; t.className='toast'; document.body.appendChild(t); }
  t.textContent = msg; t.classList.add('show'); setTimeout(()=>t.classList.remove('show'), 2500);
}

function setProgress() {
  const prog = Math.round((currentStep-1)/(steps.length-1)*100);
  const cont = qs('#vmp-wizard-progress');
  cont.innerHTML = `<div class="bar" style="width:${prog}%"></div>`;
  const label = qs('#vmp-wizard-progress-label');
  if (label) label.textContent = `Step ${currentStep} of ${steps.length}`;
}

async function api(path, method='GET', body=null, headers={}){
  const url = REST_BASE + path;
  const opts = { method, credentials: 'same-origin', headers: Object.assign({'X-WP-Nonce': NONCE}, headers) };
  if (body) { opts.body = typeof body === 'string' ? body : JSON.stringify(body); opts.headers['Content-Type'] = 'application/json'; }
  const res = await fetch(url, opts);
  return res.json();
}

async function ensureSession(){
  // check localStorage
  sessionUuid = localStorage.getItem('vmp_store_setup_uuid');
  if (sessionUuid) {
    const s = await api('/store-setup/state?session_uuid=' + encodeURIComponent(sessionUuid));
    if (s && s.success) { session = s.session; currentStep = session.current_step || 1; renderWizard(); setProgress(); return; }
    // else remove invalid
    localStorage.removeItem('vmp_store_setup_uuid');
    sessionUuid = null;
  }
  // create new
  const res = await api('/store-setup/start', 'POST', {});
  if (res && res.success) { session = res.session; sessionUuid = session.session_uuid; localStorage.setItem('vmp_store_setup_uuid', sessionUuid); currentStep = session.current_step || 1; renderWizard(); setProgress(); showToast('تم إنشاء جلسة الإعداد'); }
}

function renderWizard(){
  const root = qs('#vmp-wizard-main'); root.innerHTML = '';
  // header progress small label
  const progLabel = ce('div',{id:'vmp-wizard-progress-label','class':'vmp-progress-label'}, `Step ${currentStep} of ${steps.length}`);
  root.appendChild(progLabel);

  steps.forEach(s => {
    const stepEl = ce('section',{class:'step', 'data-step':s.id});
    if (s.id === currentStep) stepEl.classList.add('active');
    const h = ce('h2',{}, s.title);
    stepEl.appendChild(h);
    // form content per step
    const container = ce('div',{class:'step-content'});
    if (s.id === 1) {
      container.appendChild(ce('label',{class:'label'}, 'اسم المتجر'));
      const name = ce('input',{class:'input', id:'store_name', name:'store_name', placeholder:'مثال: متجر أحمد'});
      container.appendChild(name);
      container.appendChild(ce('label',{class:'label'}, 'وصف المتجر'));
      const desc = ce('textarea',{class:'input', id:'store_description', name:'store_description', rows:4});
      container.appendChild(desc);
      container.appendChild(ce('label',{class:'label'}, 'معاينة الـ slug'));
      const slugPreview = ce('div',{id:'slug_preview'}, ''); container.appendChild(slugPreview);
    }
    if (s.id === 2) {
      container.appendChild(ce('label',{class:'label'}, 'الشعار (Placeholder)'));
      container.appendChild(ce('div',{class:'input'}, 'مكان رفع الشعار سيُضاف لاحقًا'));
      container.appendChild(ce('label',{class:'label'}, 'بانر (Placeholder)'));
      container.appendChild(ce('div',{class:'input'}, 'مكان رفع البانر سيُضاف لاحقًا'));
      container.appendChild(ce('label',{class:'label'}, 'لون العلامة'));
      container.appendChild(ce('input',{class:'input', id:'brand_color', name:'brand_color', placeholder:'#RRGGBB'}));
    }
    if (s.id === 3) {
      container.appendChild(ce('label',{class:'label'}, 'الهاتف'));
      container.appendChild(ce('input',{class:'input', id:'contact_phone', name:'contact_phone'}));
      container.appendChild(ce('label',{class:'label'}, 'البريد'));
      container.appendChild(ce('input',{class:'input', id:'contact_email', name:'contact_email'}));
      container.appendChild(ce('label',{class:'label'}, 'العنوان'));
      container.appendChild(ce('input',{class:'input', id:'contact_address', name:'contact_address'}));
    }
    if (s.id === 4) {
      container.appendChild(ce('label',{class:'label'}, 'سياسة الشحن'));
      container.appendChild(ce('textarea',{class:'input', id:'policy_shipping', rows:4}));
      container.appendChild(ce('label',{class:'label'}, 'سياسة الإرجاع'));
      container.appendChild(ce('textarea',{class:'input', id:'policy_returns', rows:4}));
      container.appendChild(ce('label',{class:'label'}, 'سياسة الخصوصية'));
      container.appendChild(ce('textarea',{class:'input', id:'policy_privacy', rows:4}));
    }
    if (s.id === 5) {
      container.appendChild(ce('label',{class:'label'}, 'Facebook'));
      container.appendChild(ce('input',{class:'input', id:'social_facebook'}));
      container.appendChild(ce('label',{class:'label'}, 'Instagram'));
      container.appendChild(ce('input',{class:'input', id:'social_instagram'}));
      container.appendChild(ce('label',{class:'label'}, 'X'));
      container.appendChild(ce('input',{class:'input', id:'social_x'}));
      container.appendChild(ce('label',{class:'label'}, 'Website'));
      container.appendChild(ce('input',{class:'input', id:'social_website'}));
    }

    stepEl.appendChild(container);
    // actions
    const actions = ce('div',{class:'row-actions'});
    if (s.id > 1) actions.appendChild(ce('button',{class:'button secondary', id:`prev_${s.id}`}, 'السابق'));
    if (s.id < steps.length) actions.appendChild(ce('button',{class:'button', id:`next_${s.id}`}, 'التالي'));
    if (s.id === steps.length) actions.appendChild(ce('button',{class:'button', id:'finish_btn'}, 'إنهاء'));
    stepEl.appendChild(actions);

    root.appendChild(stepEl);
  });

  bindEvents();
}

function getStepData(step) {
  if (step === 1) return { store: { store_name: qs('#store_name')?.value || '', description: qs('#store_description')?.value || '', store_slug: qs('#slug_preview')?.textContent || '' } };
  if (step === 2) return { branding: { brand_color: qs('#brand_color')?.value || '' } };
  if (step === 3) return { contact: { phone: qs('#contact_phone')?.value || '', email: qs('#contact_email')?.value || '', address: qs('#contact_address')?.value || '' } };
  if (step === 4) return { policies: { shipping: qs('#policy_shipping')?.value || '', returns: qs('#policy_returns')?.value || '', privacy: qs('#policy_privacy')?.value || '' } };
  if (step === 5) return { social: { facebook: qs('#social_facebook')?.value || '', instagram: qs('#social_instagram')?.value || '', x: qs('#social_x')?.value || '', website: qs('#social_website')?.value || '' } };
  return {};
}

function debounceSave(step) {
  if (debounceTimer) clearTimeout(debounceTimer);
  debounceTimer = setTimeout(()=> saveStep(step), 400);
}

async function saveStep(step) {
  const data = getStepData(step);
  showSpinner(true);
  try {
    const res = await api('/store-setup/step/' + step, 'POST', data, { 'X-Session-UUID': sessionUuid });
    showSpinner(false);
    if (res && res.success) {
      session = res.session;
      currentStep = session.current_step || step;
      setProgress();
      showToast('تم الحفظ');
    } else {
      console.error('save error', res);
      showToast('فشل الحفظ');
    }
  } catch (e) {
    showSpinner(false);
    console.error(e); showToast('خطأ في الشبكة');
  }
}

function showSpinner(on){
  let s = qs('#vmp-spinner');
  if (!s) { s = ce('span',{id:'vmp-spinner', class:'spinner'}); qs('#vmp-wizard-progress').appendChild(s); }
  s.style.display = on ? 'inline-block' : 'none';
}

function bindEvents(){
  steps.forEach(s=>{
    const prev = qs(`#prev_${s.id}`);
    const next = qs(`#next_${s.id}`);
    if (prev) prev.addEventListener('click', ()=> switchStep(s.id-1));
    if (next) next.addEventListener('click', ()=> { if (validateStep(s.id)) { debounceSave(s.id); switchStep(s.id+1); } });
  });
  const finish = qs('#finish_btn'); if (finish) finish.addEventListener('click', onFinish);

  // Bind input listeners for autosave
  ['#store_name','#store_description','#brand_color','#contact_phone','#contact_email','#contact_address','#policy_shipping','#policy_returns','#policy_privacy','#social_facebook','#social_instagram','#social_x','#social_website'].forEach(sel => {
    const el = qs(sel); if (el) el.addEventListener('input', ()=> debounceSave(currentStep));
  });

  // slug preview from name
  const nameInput = qs('#store_name'); if (nameInput) nameInput.addEventListener('input', ()=>{
    const slug = nameInput.value.toLowerCase().trim().replace(/[^a-z0-9\s\-]/gi,'').replace(/\s+/g,'-').replace(/\-+/g,'-');
    qs('#slug_preview').textContent = slug || '';
  });
}

function validateStep(step){
  // basic client side validation
  if (step === 1) {
    const name = qs('#store_name')?.value || '';
    if (!name.trim()) { alert('اسم المتجر مطلوب'); return false; }
  }
  if (step === 3) {
    const phone = qs('#contact_phone')?.value || '';
    if (!phone.trim()) { alert('الهاتف مطلوب'); return false; }
  }
  return true;
}

function switchStep(next){
  if (next < 1 || next > steps.length) return;
  qs(`.step.active`).classList.remove('active');
  const newEl = qs(`.step[data-step="${next}"]`);
  if (newEl) newEl.classList.add('active');
  currentStep = next; setProgress();
}

async function onFinish(){
  if (!confirm('هل أنت متأكد من إكمال إعداد المتجر؟')) return;
  showSpinner(true);
  try {
    const res = await api('/store-setup/finish', 'POST', {}, {'X-Session-UUID': sessionUuid});
    showSpinner(false);
    if (res && res.success) {
      showToast('اكتمل إعداد المتجر، قيد مراجعة المشرف');
      // redirect to dashboard or show message
      setTimeout(()=>{ window.location.href = '/'; }, 1800);
    } else {
      console.error(res); alert('فشل الإنهاء: ' + (res.error || 'خطأ'));
    }
  } catch(e){ showSpinner(false); console.error(e); alert('خطأ في الشبكة'); }
}

// init on load
window.addEventListener('DOMContentLoaded', async ()=>{
  // render empty wizard then ensure session
  renderWizard();
  setProgress();
  await ensureSession();
});

export {};
