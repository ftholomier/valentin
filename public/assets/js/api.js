/* Client API LastFit — wrapper fetch autour des endpoints PHP. */
const API_BASE = '/api';

async function apiRequest(method, path, body) {
  const opts = {
    method,
    headers: { 'Accept': 'application/json' },
    credentials: 'same-origin',
  };
  if (body !== undefined) {
    opts.headers['Content-Type'] = 'application/json';
    opts.body = JSON.stringify(body);
  }
  const res = await fetch(API_BASE + path, opts);
  let json = {};
  try { json = await res.json(); } catch (_) {}
  if (!res.ok || json.success === false) {
    const err = new Error(json.message || `Erreur ${res.status}`);
    err.status = res.status;
    err.errors = json.errors || null;
    throw err;
  }
  return json.data;
}

const api = {
  get:  (p)    => apiRequest('GET', p),
  post: (p, b) => apiRequest('POST', p, b),

  config:        ()      => api.get('/config'),
  slots:         (qs='') => api.get('/slots' + (qs ? '?' + qs : '')),
  slot:          (id)    => api.get('/slots/' + id),
  me:            ()      => api.get('/auth/me'),
  register:      (d)     => api.post('/auth/register', d),
  login:         (d)     => api.post('/auth/login', d),
  logout:        ()      => api.post('/auth/logout', {}),
  book:          (slotId)=> api.post('/bookings', { slot_id: slotId }),
  bookings:      ()      => api.get('/bookings'),
  checkout:      (d)     => api.post('/payments/checkout', d),
  proDashboard:  ()      => api.get('/pro/dashboard'),
  validateQr:    (token) => api.post('/bookings/validate', { qr_token: token }),
  adminOverview: ()      => api.get('/admin/overview'),
  adminConfig:   (d)     => api.post('/admin/config', d),
};

/* ---------- Formatage ---------- */
const fmt = {
  euro(v) {
    const n = Number(v);
    return (Number.isInteger(n) ? n : n.toFixed(2)) + ' €';
  },
  heure(dateStr) {
    const d = new Date(dateStr.replace(' ', 'T'));
    return d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
  },
  jourHeure(dateStr) {
    const d = new Date(dateStr.replace(' ', 'T'));
    return d.toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric', month: 'short' })
         + ' · ' + d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
  },
  distance(km) {
    if (km === null || km === undefined) return '';
    return km < 1 ? Math.round(km * 1000) + ' m' : km.toFixed(1).replace('.', ',') + ' km';
  },
};

function qsParam(name) {
  return new URLSearchParams(location.search).get(name);
}
