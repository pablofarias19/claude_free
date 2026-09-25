/* ================================================================
   MailGenius Pro — Email Composer JS
   ================================================================ */

document.addEventListener('DOMContentLoaded', () => {

  // ── Quill init ──────────────────────────────────────────────
  const quill = new Quill('#quillEditor', {
    theme: 'snow',
    modules: {
      toolbar: [
        [{ 'header': [1, 2, 3, false] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ 'color': [] }, { 'background': [] }],
        [{ 'list': 'ordered' }, { 'list': 'bullet' }],
        [{ 'align': [] }],
        ['link', 'image', 'video'],
        ['blockquote', 'code-block'],
        ['clean'],
      ]
    },
    placeholder: 'Escribe tu mensaje aquí...',
  });

  // Sync Quill content to hidden input
  quill.on('text-change', () => {
    document.getElementById('bodyHtml').value = quill.root.innerHTML;
    updatePreview();
  });

  // ── Tags inputs (To, CC, BCC) ───────────────────────────────
  const toTags  = new TagsInput(document.getElementById('toField'),  document.getElementById('toInput'),  document.getElementById('toHidden'));
  const ccTags  = new TagsInput(document.getElementById('ccField'),  document.getElementById('ccInput'),  document.getElementById('ccHidden'));
  const bccTags = new TagsInput(document.getElementById('bccField'), document.getElementById('bccInput'), document.getElementById('bccHidden'));

  // ── CC / BCC toggle ─────────────────────────────────────────
  document.getElementById('toggleCc')?.addEventListener('click', () => {
    const row = document.getElementById('ccRow');
    row.style.display = row.style.display === 'none' ? 'flex' : 'none';
    if (row.style.display === 'flex') document.getElementById('ccInput').focus();
  });
  document.getElementById('toggleBcc')?.addEventListener('click', () => {
    const row = document.getElementById('bccRow');
    row.style.display = row.style.display === 'none' ? 'flex' : 'none';
    if (row.style.display === 'flex') document.getElementById('bccInput').focus();
  });

  // ── Attachments ─────────────────────────────────────────────
  const attachedFiles = [];

  ['attachInput', 'imageInput', 'videoInput'].forEach(id => {
    document.getElementById(id)?.addEventListener('change', function() {
      Array.from(this.files).forEach(file => {
        if (file.size > 26214400) { toast('El archivo ' + file.name + ' supera 25MB', 'error'); return; }
        attachedFiles.push(file);
        renderAttachList();
      });
    });
  });

  function renderAttachList() {
    const list = document.getElementById('attachList');
    list.innerHTML = '';
    attachedFiles.forEach((f, i) => {
      const chip = document.createElement('div');
      chip.className = 'attach-chip';
      const icon = f.type.startsWith('image/') ? 'bi-image' :
                   f.type.startsWith('video/') ? 'bi-camera-video' :
                   f.type.startsWith('audio/') ? 'bi-music-note' : 'bi-paperclip';
      chip.innerHTML = `<i class="bi ${icon}"></i><span>${escHtml(f.name)}</span><small class="text-muted">${formatSize(f.size)}</small><span class="remove-attach" data-idx="${i}">×</span>`;
      chip.querySelector('.remove-attach').addEventListener('click', () => {
        attachedFiles.splice(i, 1);
        renderAttachList();
      });
      list.appendChild(chip);
    });
  }

  // ── Preview ──────────────────────────────────────────────────
  function updatePreview() {
    const container = document.getElementById('previewContainer');
    if (!container) return;
    const html = quill.root.innerHTML;
    if (html === '<p><br></p>' || !html.trim()) {
      container.innerHTML = '<em style="color:#9ca3af">Escribe el email para previsualizar...</em>';
      return;
    }
    container.innerHTML = html;
  }

  document.getElementById('previewDesktop')?.addEventListener('click', () => {
    document.getElementById('previewContainer').style.maxWidth = '600px';
  });
  document.getElementById('previewMobile')?.addEventListener('click', () => {
    document.getElementById('previewContainer').style.maxWidth = '375px';
  });

  // ── Template search & apply ──────────────────────────────────
  let selectedTpl = null;

  document.getElementById('tplSearch')?.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.tpl-item').forEach(item => {
      item.style.display = item.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });

  document.querySelectorAll('.tpl-item').forEach(item => {
    item.addEventListener('click', async () => {
      selectedTpl = item.dataset.id;
      // Load template details
      const res  = await fetch(`${APP_URL}/api/templates.php?action=get&id=${selectedTpl}`);
      const data = await res.json();
      if (!data.id) return;

      const vars = data.variables ? JSON.parse(data.variables) : [];
      if (vars.length > 0) {
        // Show vars modal
        const body = vars.map(v => `
          <div class="mb-3">
            <label class="form-label"><code>{{${escHtml(v)}}}</code></label>
            <input type="text" class="form-control" id="tplVar_${v}" placeholder="Valor para {{${escHtml(v)}}}">
          </div>
        `).join('');
        document.getElementById('tplVarsBody').innerHTML = body;
        new bootstrap.Modal(document.getElementById('tplVarsModal')).show();
      } else {
        applyTemplate(data, {});
      }
    });
  });

  document.getElementById('applyTplBtn')?.addEventListener('click', async () => {
    if (!selectedTpl) return;
    const res  = await fetch(`${APP_URL}/api/templates.php?action=get&id=${selectedTpl}`);
    const data = await res.json();
    const vars = data.variables ? JSON.parse(data.variables) : [];
    const vals = {};
    vars.forEach(v => {
      vals[v] = document.getElementById('tplVar_' + v)?.value ?? '';
    });
    applyTemplate(data, vals);
    bootstrap.Modal.getInstance(document.getElementById('tplVarsModal')).hide();
  });

  function applyTemplate(tpl, vars) {
    let subject = tpl.subject;
    let body    = tpl.body_html;
    for (const [k, v] of Object.entries(vars)) {
      subject = subject.replaceAll(`{{${k}}}`, v);
      body    = body.replaceAll(`{{${k}}}`, escHtml(v));
    }
    document.getElementById('subjectInput').value = subject;
    quill.root.innerHTML = body;
    document.getElementById('bodyHtml').value = body;
    updatePreview();
    toast('Plantilla aplicada: ' + tpl.name);
  }

  // ── Flatpickr for schedule ───────────────────────────────────
  if (document.getElementById('scheduleDateInput')) {
    flatpickr('#scheduleDateInput', {
      enableTime: true,
      dateFormat: 'Y-m-d H:i',
      minDate: 'today',
      time_24hr: true,
      locale: 'es',
    });
  }

  // ── Send now ─────────────────────────────────────────────────
  document.getElementById('sendNowBtn')?.addEventListener('click', async () => {
    const data = collectFormData();
    if (!validateForm(data)) return;

    const btn = document.getElementById('sendNowBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando...';

    try {
      const fd = buildFormData(data, 'send');
      const res  = await fetch(`${APP_URL}/api/send.php`, { method: 'POST', body: fd });
      const json = await res.json();
      if (json.success) {
        toast('Email enviado correctamente', 'success');
        setTimeout(() => window.location.href = `${APP_URL}/inbox.php`, 1500);
      } else {
        toast(json.error || 'Error al enviar', 'error');
      }
    } finally {
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-send me-2"></i>Enviar ahora';
    }
  });

  // ── Save draft ───────────────────────────────────────────────
  document.getElementById('saveDraftBtn')?.addEventListener('click', async () => {
    const data = collectFormData();
    const fd   = buildFormData(data, 'draft');
    const res  = await fetch(`${APP_URL}/api/send.php`, { method: 'POST', body: fd });
    const json = await res.json();
    if (json.success || json.email_id) {
      document.getElementById('emailId').value = json.email_id ?? '';
      toast('Borrador guardado', 'success');
    }
  });

  // ── Schedule ─────────────────────────────────────────────────
  document.getElementById('confirmScheduleBtn')?.addEventListener('click', async () => {
    const scheduledAt = document.getElementById('scheduleDateInput').value;
    if (!scheduledAt) { toast('Selecciona una fecha y hora', 'warning'); return; }

    const data = collectFormData();
    if (!validateForm(data)) return;

    const fd = buildFormData(data, 'schedule');
    fd.append('scheduled_at', scheduledAt);

    const res  = await fetch(`${APP_URL}/api/send.php`, { method: 'POST', body: fd });
    const json = await res.json();
    if (json.success) {
      bootstrap.Modal.getInstance(document.getElementById('scheduleModal')).hide();
      toast('Email programado para ' + scheduledAt, 'success');
      setTimeout(() => window.location.href = `${APP_URL}/scheduler.php`, 1500);
    } else {
      toast(json.error || 'Error al programar', 'error');
    }
  });

  // ── Helpers ──────────────────────────────────────────────────
  function collectFormData() {
    return {
      to:         document.getElementById('toHidden').value,
      cc:         document.getElementById('ccHidden').value,
      bcc:        document.getElementById('bccHidden').value,
      subject:    document.getElementById('subjectInput').value,
      from_email: document.querySelector('input[name="from_email"]').value,
      body_html:  quill.root.innerHTML,
      priority:   document.querySelector('select[name="priority"]').value,
      email_id:   document.getElementById('emailId').value,
    };
  }

  function validateForm(data) {
    if (!data.to.trim()) {
      toast('Agrega al menos un destinatario', 'warning');
      document.getElementById('toInput').focus();
      return false;
    }
    if (!data.subject.trim()) {
      toast('El asunto no puede estar vacío', 'warning');
      document.getElementById('subjectInput').focus();
      return false;
    }
    if (quill.getText().trim().length < 2) {
      toast('Escribe el contenido del email', 'warning');
      return false;
    }
    return true;
  }

  function buildFormData(data, action) {
    const fd = new FormData();
    fd.append('action',     action);
    fd.append('csrf_token', CSRF);
    fd.append('to',         data.to);
    fd.append('cc',         data.cc);
    fd.append('bcc',        data.bcc);
    fd.append('subject',    data.subject);
    fd.append('from_email', data.from_email);
    fd.append('body_html',  data.body_html);
    fd.append('priority',   data.priority);
    if (data.email_id) fd.append('email_id', data.email_id);
    attachedFiles.forEach(f => fd.append('attachments[]', f));
    return fd;
  }

  // Auto-save draft every 60s
  setInterval(() => {
    const data = collectFormData();
    if (data.subject || quill.getText().trim().length > 5) {
      const fd = buildFormData(data, 'draft');
      fetch(`${APP_URL}/api/send.php`, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(json => {
          if (json.email_id) document.getElementById('emailId').value = json.email_id;
        });
    }
  }, 60000);

});
