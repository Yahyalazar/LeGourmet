/**
 * LE GOURMET — main.js
 * JavaScript principal : Bootstrap + interactions + validation + UX
 * Organisé en modules autonomes, initialisés au DOMContentLoaded
 */

'use strict';

/* ================================================================
   MODULE 1 — TOAST (notifications visuelles)
   ================================================================ */
const LGToast = (() => {
  let _container = null;

  const ICONS = { success:'✅', danger:'❌', warning:'⚠️', info:'ℹ️' };
  const COLORS = {
    success: { title:'#86EFAC', bar:'#4ADE80' },
    danger:  { title:'#FCA5A5', bar:'#F87171' },
    warning: { title:'#FCD34D', bar:'#FBBF24' },
    info:    { title:'#E8C96B', bar:'#C9A84C' },
  };

  function _getContainer() {
    if (!_container) {
      _container = document.getElementById('toast-container');
      if (!_container) {
        _container = Object.assign(document.createElement('div'), { id:'toast-container' });
        document.body.appendChild(_container);
      }
    }
    return _container;
  }

  function show(type, title, message, duration = 5500) {
    const c   = _getContainer();
    const col = COLORS[type] || COLORS.info;
    const el  = document.createElement('div');
    el.className = `lg-toast ${type}`;
    el.setAttribute('role', 'alert');
    el.innerHTML = `
      <span class="t-icon">${ICONS[type] || 'ℹ'}</span>
      <div class="t-body">
        <div class="t-title" style="color:${col.title}">${title}</div>
        <div class="t-msg">${message}</div>
      </div>
      <button class="t-close" aria-label="Fermer">&times;</button>
      <div class="t-progress" style="background:${col.bar};animation-duration:${duration}ms"></div>`;

    let timer;
    const dismiss = () => {
      el.style.animation = 'tOut .3s ease forwards';
      setTimeout(() => el.remove(), 320);
    };

    el.querySelector('.t-close').addEventListener('click', () => { clearTimeout(timer); dismiss(); });

    // Pause on hover
    el.addEventListener('mouseenter', () => {
      clearTimeout(timer);
      el.querySelector('.t-progress').style.animationPlayState = 'paused';
    });
    el.addEventListener('mouseleave', () => {
      timer = setTimeout(dismiss, 1500);
      el.querySelector('.t-progress').style.animationPlayState = 'running';
    });

    c.appendChild(el);
    timer = setTimeout(dismiss, duration);
    return el;
  }

  // Lire les paramètres URL générés par PHP et afficher les toasts correspondants
  function fromURL() {
    const p = new URLSearchParams(location.search);

    if (p.get('success') === '1' && p.get('code'))
      show('success', 'Réservation confirmée !',
        `Code : <code style="color:var(--gold-light);background:rgba(201,168,76,.12);padding:.1em .4em;border-radius:2px">${p.get('code')}</code>`, 9000);

    if (p.get('error') === 'full')
      show('danger', 'Aucune table disponible', 'Essayez une autre date ou un autre créneau.', 7000);

    if (p.get('erreur') === 'date_invalide')
      show('warning', 'Date invalide', 'Impossible de réserver pour une date passée.', 6000);

    if (p.get('erreur') === 'connexion_requise_reservation')
      show('info', 'Connexion requise', 'Veuillez vous connecter pour réserver.', 6000);

    if (p.get('erreur') === 'creneau_invalide')
      show('warning', 'Créneau invalide', 'Le créneau sélectionné n\'existe pas.', 6000);

    if (p.get('msg') === 'updated')
      show('success', 'Réservation mise à jour', 'Les modifications ont bien été enregistrées.', 6000);

    if (p.get('msg') === 'deleted')
      show('danger', 'Réservation supprimée', 'La réservation a été définitivement supprimée de la base de données.', 7000);

    if (p.get('msg') === 'error')
      show('warning', 'Erreur de suppression', 'Une erreur est survenue lors de la suppression. Veuillez réessayer.', 7000);

    if (p.get('succes') === 'inscription')
      show('success', 'Compte créé !', 'Bienvenue ! Vous pouvez maintenant vous connecter.', 6500);

    // Nettoyer l'URL sans recharger la page
    if (p.toString()) history.replaceState({}, '', location.pathname);
  }

  return {
    show,
    success: (t, m, d) => show('success', t, m, d),
    danger:  (t, m, d) => show('danger',  t, m, d),
    warning: (t, m, d) => show('warning', t, m, d),
    info:    (t, m, d) => show('info',    t, m, d),
    fromURL,
  };
})();


/* ================================================================
   MODULE 2 — NAVBAR
   Bootstrap collapse + shrink au scroll + lien actif auto
   ================================================================ */
function initNavbar() {
  const nav  = document.querySelector('.navbar');
  const page = location.pathname.split('/').pop() || 'index.php';

  // Marquer le lien actif automatiquement
  document.querySelectorAll('.navbar .nav-link, .nav-link-logout').forEach(a => {
    if (a.getAttribute('href') === page) a.classList.add('active');
  });

  // Réduire la navbar au scroll
  if (nav) {
    window.addEventListener('scroll', () => {
      if (window.scrollY > 30) {
        nav.style.boxShadow = '0 4px 32px rgba(0,0,0,.9)';
        nav.style.borderBottomColor = 'rgba(201,168,76,.25)';
      } else {
        nav.style.boxShadow = '';
        nav.style.borderBottomColor = '';
      }
    }, { passive: true });
  }

  // Fermer le menu mobile après clic sur un lien
  const collapse = document.getElementById('navMain');
  if (collapse) {
    collapse.querySelectorAll('.nav-link').forEach(link => {
      link.addEventListener('click', () => {
        const bsc = bootstrap.Collapse.getInstance(collapse);
        if (bsc) bsc.hide();
      });
    });
  }

  // Initialiser les tooltips Bootstrap
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
    new bootstrap.Tooltip(el, { trigger: 'hover focus', placement: 'bottom' });
  });
}


/* ================================================================
   MODULE 3 — SCROLL REVEAL
   IntersectionObserver — éléments avec class .reveal
   ================================================================ */
function initScrollReveal() {
  const els = document.querySelectorAll('.reveal');
  if (!els.length) return;

  if (!('IntersectionObserver' in window)) {
    els.forEach(el => el.classList.add('visible'));
    return;
  }

  const io = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        e.target.classList.add('visible');
        io.unobserve(e.target);
      }
    });
  }, { threshold: 0.05, rootMargin: '0px 0px -20px 0px' });

  els.forEach(el => io.observe(el));
}


/* ================================================================
   MODULE 4 — FORMULAIRE DE RÉSERVATION
   Validation live, hints dynamiques, email pré-rempli, submit guard
   ================================================================ */
function initReservationForm() {
  const form      = document.getElementById('resForm');
  const submitBtn = document.getElementById('submitBtn');
  if (!form) return;

  // ── Date : forcer ≥ aujourd'hui + afficher le jour de la semaine ──
  const dateInput = form.querySelector('#date_reservation');
  if (dateInput) {
    const today = new Date().toISOString().split('T')[0];
    dateInput.min = today;

    const DAYS = ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
    const hint = _createHint('dateHint', dateInput);

    dateInput.addEventListener('change', function () {
      if (!this.value) { hint.textContent = ''; return; }
      const d = new Date(this.value + 'T12:00:00');
      hint.style.color = 'var(--gold)';
      hint.innerHTML = `📅 ${DAYS[d.getDay()]} ${d.toLocaleDateString('fr-FR', {day:'numeric',month:'long',year:'numeric'})}`;
    });
  }

  // ── Nombre de convives : feedback contextuel ──
  const guestsInput = form.querySelector('#nombre_personnes');
  if (guestsInput) {
    const hint = _createHint('guestHint', guestsInput);
    guestsInput.addEventListener('input', function () {
      const v = parseInt(this.value) || 0;
      if      (v >= 1 && v <= 2)  { hint.textContent='🪑 Table intime'; hint.style.color='var(--gold)'; }
      else if (v <= 4)            { hint.textContent='👥 Table conviviale'; hint.style.color='var(--gold)'; }
      else if (v <= 8)            { hint.textContent='🎉 Grande tablée'; hint.style.color='var(--gold)'; }
      else if (v <= 20)           { hint.textContent='🏛 Événement — notre équipe vous contactera'; hint.style.color='var(--warn,#FBBF24)'; }
      else                        { hint.textContent='⚠ Maximum 20 personnes'; hint.style.color='var(--err,#F87171)'; }
    });
  }

  // ── Créneau : badge midi/soir ──
  const creneauSel = form.querySelector('#creneau_id');
  if (creneauSel) {
    const badge = _createHint('creneauBadge', creneauSel);
    creneauSel.addEventListener('change', function () {
      const opt = this.options[this.selectedIndex];
      const grp = opt.parentElement?.label || '';
      if (grp.toLowerCase().includes('midi')) {
        badge.innerHTML = '🌞 Service du midi';
        badge.style.cssText = 'display:block;margin-top:.28rem;font-family:var(--ff-u);font-size:.6rem;font-weight:700;letter-spacing:.1em;color:var(--gold-light);';
      } else if (grp.toLowerCase().includes('soir')) {
        badge.innerHTML = '🌙 Service du soir';
        badge.style.cssText = 'display:block;margin-top:.28rem;font-family:var(--ff-u);font-size:.6rem;font-weight:700;letter-spacing:.1em;color:#93C5FD;';
      } else {
        badge.textContent = '';
      }
    });
  }

  // ── Compteur commentaires ──
  const commArea = form.querySelector('#commentaires');
  if (commArea) {
    const counter = _createHint('commCounter', commArea);
    counter.style.textAlign = 'right';
    counter.style.color = 'var(--t3-c, #90887A)';
    counter.textContent = '0 / 300';
    commArea.addEventListener('input', function () {
      const len = Math.min(this.value.length, 300);
      this.value = this.value.substring(0, 300);
      counter.textContent = `${len} / 300`;
      counter.style.color = len > 250 ? 'var(--warn,#FBBF24)' : 'var(--t3-c,#90887A)';
    });
  }

  // ── Validation live sur blur ──
  form.querySelectorAll('[required]').forEach(el => {
    el.addEventListener('blur',  () => _validateField(el));
    el.addEventListener('input', () => {
      el.classList.remove('is-invalid');
      if (el.value.trim()) el.classList.add('is-valid');
    });
  });

  // ── Submit ──
  form.addEventListener('submit', function (e) {
    let ok = true;
    form.querySelectorAll('[required]').forEach(el => { if (!_validateField(el)) ok = false; });

    if (!ok) {
      e.preventDefault();
      const first = form.querySelector('.is-invalid');
      if (first) first.scrollIntoView({ behavior: 'smooth', block: 'center' });
      LGToast.warning('Formulaire incomplet', 'Corrigez les champs en rouge avant de continuer.');
      return;
    }
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status"></span>Traitement…`;
    }
  });
}

// Créer un élément hint (small) inséré après l'input
function _createHint(id, input) {
  let hint = document.getElementById(id);
  if (!hint) {
    hint = document.createElement('small');
    hint.id = id;
    hint.style.cssText = 'display:block;margin-top:.25rem;font-family:var(--ff-u);font-size:.62rem;font-weight:600;letter-spacing:.06em;transition:color .3s';
    input.insertAdjacentElement('afterend', hint);
  }
  return hint;
}

// Valider un champ individuel
function _validateField(el) {
  const val = el.value.trim();
  let ok = true;

  if (el.required && !val) ok = false;
  if (el.type === 'email' && val && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) ok = false;
  if (el.type === 'number' && val) {
    const v = parseFloat(val);
    if (el.min !== '' && v < parseFloat(el.min)) ok = false;
    if (el.max !== '' && v > parseFloat(el.max)) ok = false;
  }
  if (el.id === 'date_reservation' && val) {
    if (val < new Date().toISOString().split('T')[0]) ok = false;
  }

  el.classList.toggle('is-invalid', !ok);
  el.classList.toggle('is-valid', ok && !!val);
  return ok;
}


/* ================================================================
   MODULE 5 — EMAIL AUTO-FILL
   L'email de session PHP est injecté via data-session-email sur <body>
   ================================================================ */
function initEmailAutofill() {
  const email = document.body.dataset.sessionEmail;
  if (!email) return;

  // Chercher tous les champs email non encore remplis
  document.querySelectorAll('input[type="email"]#email').forEach(input => {
    if (!input.value) {
      input.value = email;
      input.classList.add('is-valid');

      // Petit message visuel
      const hint = document.createElement('small');
      hint.style.cssText = 'display:block;margin-top:.22rem;font-family:var(--ff-u);font-size:.6rem;font-weight:600;letter-spacing:.08em;color:var(--gold);';
      hint.innerHTML = `<i class="bi bi-check-circle me-1"></i>Pré-rempli depuis votre compte`;
      input.insertAdjacentElement('afterend', hint);
      // Disparaît après 3 secondes
      setTimeout(() => { hint.style.transition = 'opacity .5s'; hint.style.opacity = '0'; }, 3000);
    }
  });
}


/* ================================================================
   MODULE 6 — LOGIN FORM
   Toggle mot de passe, spinner submit
   ================================================================ */
function initLoginForm() {
  // Toggle password — fonctionne pour tout bouton avec data-pwd-toggle="id_du_champ"
  document.querySelectorAll('[data-pwd-toggle]').forEach(btn => {
    btn.addEventListener('click', function () {
      const inp  = document.getElementById(this.dataset.pwdToggle);
      const icon = this.querySelector('i');
      if (!inp) return;
      const isHidden = inp.type === 'password';
      inp.type = isHidden ? 'text' : 'password';
      if (icon) icon.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
      this.style.color = isHidden ? 'var(--gold)' : 'var(--t3-c, #90887A)';
      inp.focus();
    });
  });

  const form = document.getElementById('loginForm');
  if (!form) return;

  form.addEventListener('submit', function () {
    const btn = document.getElementById('loginBtn');
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status"></span>Connexion…`;
    }
  });
}


/* ================================================================
   MODULE 7 — INSCRIPTION FORM
   Force du mot de passe (5 niveaux) + vérification concordance
   ================================================================ */
function initInscriptionForm() {
  const form     = document.getElementById('inscriptForm');
  const pwdInput = document.getElementById('mot_de_passe');
  const confInput= document.getElementById('mot_de_passe_confirm');
  if (!form || !pwdInput) return;

  const LEVELS = [
    { w:'14%',  c:'#EF4444', t:'Trop court'  },
    { w:'34%',  c:'#F97316', t:'Faible'      },
    { w:'56%',  c:'#EAB308', t:'Moyen'       },
    { w:'80%',  c:'#22C55E', t:'Fort'        },
    { w:'100%', c:'#10B981', t:'Excellent ✓' },
  ];

  const bar      = document.getElementById('pwdBar');
  const hintEl   = document.getElementById('pwdHint');
  const matchEl  = document.getElementById('matchHint');

  pwdInput.addEventListener('input', function () {
    const v = this.value;
    let score = 0;
    if (v.length >= 6)           score++;
    if (v.length >= 10)          score++;
    if (/[A-Z]/.test(v))         score++;
    if (/[0-9]/.test(v))         score++;
    if (/[^A-Za-z0-9]/.test(v)) score++;

    const lvl = LEVELS[Math.max(0, score - 1)];
    if (bar)    { bar.style.width = v ? lvl.w : '0'; bar.style.background = lvl.c; }
    if (hintEl) { hintEl.textContent = v ? lvl.t : ''; hintEl.style.color = lvl.c; }

    // Vérifier correspondance en temps réel si l'autre champ est rempli
    if (confInput && confInput.value) _checkMatch();
  });

  if (confInput) {
    confInput.addEventListener('input', _checkMatch);
  }

  function _checkMatch() {
    if (!confInput.value) { confInput.classList.remove('is-valid','is-invalid'); if (matchEl) matchEl.textContent=''; return; }
    const match = confInput.value === pwdInput.value;
    confInput.classList.toggle('is-valid',   match);
    confInput.classList.toggle('is-invalid', !match);
    if (matchEl) {
      matchEl.textContent = match ? '✓ Mots de passe identiques' : '✗ Ne correspond pas';
      matchEl.style.color = match ? '#86EFAC' : '#FCA5A5';
    }
  }

  // Guard submit
  form.addEventListener('submit', function (e) {
    if (pwdInput.value.length < 6) {
      e.preventDefault(); pwdInput.classList.add('is-invalid');
      LGToast.warning('Mot de passe trop court', 'Minimum 6 caractères.'); return;
    }
    if (confInput && confInput.value !== pwdInput.value) {
      e.preventDefault(); confInput.classList.add('is-invalid');
      LGToast.danger('Erreur', 'Les mots de passe ne correspondent pas.'); return;
    }
    const btn = document.getElementById('subBtn');
    if (btn) { btn.disabled = true; btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Création…`; }
  });
}


/* ================================================================
   MODULE 8 — ADMIN
   Bootstrap pills filtres + recherche live + confirmer suppression
   ================================================================ */
function initAdmin() {
  const grid = document.getElementById('reservationsGrid');
  if (!grid) return;

  const items      = Array.from(grid.querySelectorAll('.res-grid-item'));
  const noResults  = document.getElementById('noResults');
  const searchInput= document.getElementById('searchInput');
  const pills      = document.querySelectorAll('.fpill');
  const countBadge = document.getElementById('cnt-visible');

  let activeFilter = 'all';
  let activeSearch = '';

  function _getToday() { return new Date().toISOString().split('T')[0]; }

  function applyFilters() {
    let visible = 0;
    items.forEach(item => {
      const statut = item.dataset.statut  || '';
      const client = item.dataset.client  || '';
      const date   = item.dataset.date    || '';

      let show = true;
      if      (activeFilter === 'today')   show = (date === _getToday());
      else if (activeFilter !== 'all')     show = (statut === activeFilter);
      if (show && activeSearch)            show = client.includes(activeSearch.toLowerCase());

      // Animation Bootstrap-friendly
      item.style.transition = 'opacity .25s, transform .25s';
      if (show) {
        item.style.display   = '';
        requestAnimationFrame(() => { item.style.opacity='1'; item.style.transform='translateY(0)'; });
        visible++;
      } else {
        item.style.opacity   = '0';
        item.style.transform = 'translateY(6px)';
        setTimeout(() => { if (!show) item.style.display='none'; }, 250);
      }
    });

    if (noResults) noResults.style.display = visible === 0 ? '' : 'none';
    if (countBadge) countBadge.textContent = visible;
  }

  // Pills Bootstrap — utiliser les classes Bootstrap active
  pills.forEach(pill => {
    pill.addEventListener('click', function () {
      pills.forEach(p => p.classList.remove('active'));
      this.classList.add('active');
      activeFilter = this.dataset.f;
      applyFilters();
    });
  });

  // Recherche live
  if (searchInput) {
    searchInput.addEventListener('input', function () {
      activeSearch = this.value.trim();
      applyFilters();
    });
    searchInput.addEventListener('keydown', e => {
      if (e.key === 'Escape') { searchInput.value=''; activeSearch=''; applyFilters(); }
    });
  }
}

/* ── Modal de suppression personnalisé (admin) ── */
(function buildDeleteModal() {
  const style = document.createElement('style');
  style.textContent = `
    #lgDelOverlay {
      position:fixed;inset:0;background:rgba(0,0,0,.92);z-index:9998;
      display:flex;align-items:center;justify-content:center;
      opacity:0;transition:opacity .22s ease;pointer-events:none;
      backdrop-filter:blur(3px);
    }
    #lgDelOverlay.open { opacity:1;pointer-events:all; }
    #lgDelBox {
      background:#0d0d0d;border:1px solid rgba(231,76,60,.22);
      border-radius:14px;width:100%;max-width:420px;margin:20px;
      box-shadow:0 40px 100px rgba(0,0,0,.9),0 0 0 1px rgba(231,76,60,.08);
      transform:translateY(18px) scale(.97);transition:transform .25s cubic-bezier(.22,1,.36,1);
      overflow:hidden;
    }
    #lgDelOverlay.open #lgDelBox { transform:none; }
    #lgDelHead {
      background:#0f0505;padding:20px 24px 16px;
      border-bottom:1px solid rgba(231,76,60,.15);
      display:flex;align-items:center;gap:12px;
    }
    #lgDelHead .del-icon-wrap {
      width:36px;height:36px;border-radius:50%;flex-shrink:0;
      background:rgba(231,76,60,.12);border:1px solid rgba(231,76,60,.28);
      display:flex;align-items:center;justify-content:center;font-size:1rem;
    }
    #lgDelHead .del-title {
      font-family:'Montserrat',sans-serif;font-size:.66rem;letter-spacing:.22em;
      text-transform:uppercase;color:#e74c3c;font-weight:600;
    }
    #lgDelBody { padding:22px 24px 18px;color:#aaa;font-size:.93rem;line-height:1.65; }
    #lgDelName {
      display:inline-block;background:rgba(231,76,60,.08);
      border:1px solid rgba(231,76,60,.2);border-radius:6px;
      color:#e8796b;padding:3px 12px;font-weight:600;margin:2px 0;
    }
    #lgDelWarn {
      display:flex;align-items:center;gap:8px;margin-top:14px;
      background:rgba(231,76,60,.05);border:1px solid rgba(231,76,60,.12);
      border-radius:8px;padding:10px 14px;font-size:.8rem;color:#876060;
    }
    #lgDelFoot {
      background:#080808;border-top:1px solid #141414;
      padding:14px 24px;display:flex;gap:10px;justify-content:flex-end;
    }
    .lgdel-btn {
      font-family:'Montserrat',sans-serif;font-size:.62rem;letter-spacing:.18em;
      text-transform:uppercase;padding:10px 20px;border-radius:7px;
      cursor:pointer;transition:all .18s;border:1px solid transparent;
    }
    .lgdel-cancel {
      background:#1a1a1a;border-color:#2a2a2a;color:#777;
    }
    .lgdel-cancel:hover { background:#222;color:#aaa;border-color:#333; }
    .lgdel-confirm {
      background:rgba(231,76,60,.1);border-color:rgba(231,76,60,.4);color:#e74c3c;
      display:flex;align-items:center;gap:7px;
    }
    .lgdel-confirm:hover { background:rgba(231,76,60,.18);border-color:rgba(231,76,60,.6);color:#ff6b5b; }
  `;
  document.head.appendChild(style);

  const overlay = document.createElement('div');
  overlay.id = 'lgDelOverlay';
  overlay.innerHTML = `
    <div id="lgDelBox">
      <div id="lgDelHead">
        <div class="del-icon-wrap">🗑</div>
        <div class="del-title">Confirmer la suppression</div>
      </div>
      <div id="lgDelBody">
        Vous êtes sur le point de supprimer définitivement la réservation de
        <span id="lgDelName"></span>.
        <div id="lgDelWarn">
          <span style="font-size:1rem;flex-shrink:0">⚠️</span>
          <span>Cette action est <strong style="color:#c0706a">irréversible</strong> — aucune récupération possible.</span>
        </div>
      </div>
      <div id="lgDelFoot">
        <button class="lgdel-btn lgdel-cancel" id="lgDelCancel">Annuler</button>
        <button class="lgdel-btn lgdel-confirm" id="lgDelConfirm">
          <span>🗑</span> Supprimer définitivement
        </button>
      </div>
    </div>`;
  document.body.appendChild(overlay);

  let _resolve = null;
  let _href    = null;

  overlay.addEventListener('click', e => { if (e.target === overlay) close(false); });
  document.getElementById('lgDelCancel').addEventListener('click',  () => close(false));
  document.getElementById('lgDelConfirm').addEventListener('click', () => close(true));
  document.addEventListener('keydown', e => { if (e.key === 'Escape' && _resolve) close(false); });

  function close(confirmed) {
    overlay.classList.remove('open');
    if (_resolve) { _resolve(confirmed); _resolve = null; }
    if (confirmed && _href) { window.location.href = _href; _href = null; }
  }

  window.confirmDel = function(anchorEl, name) {
    document.getElementById('lgDelName').textContent = name;
    _href = anchorEl.href;
    overlay.classList.add('open');
    return false; // empêche la navigation immédiate
  };
})();


/* ================================================================
   MODULE 9 — EDIT FORM (admin)
   Aperçu couleur du statut + spinner submit
   ================================================================ */
function initEditForm() {
  const sel     = document.getElementById('statut');
  const preview = document.getElementById('statusPreview');
  const form    = document.getElementById('editForm');
  const saveBtn = document.getElementById('saveBtn');
  if (!sel) return;

  const STATUS_INFO = {
    'en_attente': { color:'#FBBF24', text:'⏳ En attente — le client voit sa réservation comme non confirmée' },
    'confirmee' : { color:'#4ADE80', text:'✅ Confirmée — visible comme confirmée pour le client' },
    'annulee'   : { color:'#F87171', text:'❌ Annulée — la réservation sera archivée' },
  };

  function updatePreview() {
    const info = STATUS_INFO[sel.value];
    if (preview && info) {
      preview.textContent = info.text;
      preview.style.color = info.color;
    }
  }

  sel.addEventListener('change', updatePreview);
  updatePreview();

  form?.addEventListener('submit', () => {
    if (saveBtn) { saveBtn.disabled=true; saveBtn.innerHTML=`<span class="spinner-border spinner-border-sm me-2"></span>Sauvegarde…`; }
  });
}


/* ================================================================
   MODULE 10 — BACK TO TOP
   Bouton flottant, apparaît après 300px de scroll
   ================================================================ */
function initBackToTop() {
  const btn = document.createElement('button');
  btn.id = 'backToTop';
  btn.setAttribute('aria-label', 'Retour en haut de page');
  btn.innerHTML = '<i class="bi bi-arrow-up-short"></i>';
  Object.assign(btn.style, {
    position:'fixed', bottom:'1.5rem', right:'1.3rem', zIndex:'900',
    width:'40px', height:'40px', borderRadius:'2px',
    background:'var(--bg-card2,#1A1A20)',
    color:'var(--gold,#C9A84C)',
    border:'1px solid rgba(201,168,76,.28)',
    cursor:'pointer', opacity:'0', pointerEvents:'none',
    display:'flex', alignItems:'center', justifyContent:'center',
    fontSize:'1.3rem', lineHeight:'1',
    transition:'opacity .3s, transform .3s, background .2s, color .2s, box-shadow .2s',
  });
  document.body.appendChild(btn);

  window.addEventListener('scroll', () => {
    const show = window.scrollY > 300;
    btn.style.opacity       = show ? '1' : '0';
    btn.style.pointerEvents = show ? 'auto' : 'none';
    btn.style.transform     = show ? 'translateY(0)' : 'translateY(10px)';
  }, { passive:true });

  btn.addEventListener('click',      () => window.scrollTo({ top:0, behavior:'smooth' }));
  btn.addEventListener('mouseenter', () => { btn.style.background='var(--gold)'; btn.style.color='#0D0D0F'; btn.style.boxShadow='0 4px 18px rgba(201,168,76,.4)'; });
  btn.addEventListener('mouseleave', () => { btn.style.background='var(--bg-card2,#1A1A20)'; btn.style.color='var(--gold)'; btn.style.boxShadow='none'; });
}


/* ================================================================
   MODULE 11 — AUTO-DISMISS ALERTS
   Les alertes Bootstrap disparaissent après 6 secondes
   ================================================================ */
function initAlertAutoDismiss() {
  document.querySelectorAll('.alert:not(.alert-permanent)').forEach(el => {
    setTimeout(() => {
      el.style.transition = 'opacity .5s, max-height .5s, margin .5s, padding .5s';
      el.style.opacity    = '0';
      setTimeout(() => {
        el.style.maxHeight = '0'; el.style.overflow = 'hidden';
        el.style.margin = '0'; el.style.padding = '0';
      }, 520);
    }, 6000);
  });
}


/* ================================================================
   MODULE 12 — MOBILE UX
   Fix iOS zoom + classe tactile + fermer navbar au clic
   ================================================================ */
function initMobileUX() {
  // Éviter le zoom iOS sur focus input (iOS zoome si font-size < 16px)
  if (/iPhone|iPad|iPod/i.test(navigator.userAgent)) {
    document.querySelectorAll('input, select, textarea').forEach(el => {
      if (parseFloat(getComputedStyle(el).fontSize) < 16) {
        el.style.fontSize = '16px';
      }
    });
  }

  // Classe css pour appareils tactiles
  if ('ontouchstart' in window || navigator.maxTouchPoints > 0) {
    document.body.classList.add('is-touch');
  }
}


/* ================================================================
   INIT — point d'entrée, tout est appelé ici
   ================================================================ */
document.addEventListener('DOMContentLoaded', () => {
  LGToast.fromURL();       // 1. Toasts depuis paramètres URL PHP
  initNavbar();            // 2. Navbar + tooltips Bootstrap
  initScrollReveal();      // 3. Animations au scroll
  initReservationForm();   // 4. Formulaire réservation
  initEmailAutofill();     // 5. Pré-remplissage email
  initLoginForm();         // 6. Login (toggle pwd, spinner)
  initInscriptionForm();   // 7. Inscription (force pwd, match)
  initAdmin();             // 8. Admin (filtres, recherche)
  initEditForm();          // 9. Édition réservation
  initBackToTop();         // 10. Bouton retour en haut
  initAlertAutoDismiss();  // 11. Auto-dismiss alerts
  initMobileUX();          // 12. Corrections mobile/iOS
});
