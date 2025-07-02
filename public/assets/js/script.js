/**
 * @file Frontend para la API de cotizaciones con autenticación JWT.
 * @author Jean 
 * @version 1.1.1
 *
 * @description Lógica principal del frontend para autenticación JWT,
 *              consumo de la API de cotizaciones y gestión de UI.
 */

document.addEventListener('DOMContentLoaded', () => {
  // Elementos del DOM
  const loginSection     = document.getElementById('loginSection');
  const loginForm        = document.getElementById('loginForm');
  const logoutBtn        = document.getElementById('logoutBtn');
  const currentApiToken  = document.getElementById('currentApiToken');
  const consultSection   = document.getElementById('consultSection');
  const consultForm      = document.getElementById('consultForm');
  const resultFecha      = document.getElementById('resultFecha');
  const resultApertura   = document.getElementById('resultApertura');
  const resultCierre     = document.getElementById('resultCierre');
  const resultBcv        = document.getElementById('resultBcv');
  const resultPromApert  = document.getElementById('resultPromedioApertura');
  const resultPromCierre = document.getElementById('resultPromedioCierre');
  const addSection       = document.getElementById('addSection');
  const addForm          = document.getElementById('addForm');
  const updateSection    = document.getElementById('updateSection');
  const updateForm       = document.getElementById('updateForm');
  const messageDiv       = document.getElementById('message');

  // Inputs de updateForm para auto‐llenado
  const updFecha    = updateForm.querySelector('input[name="fecha"]');
  const updApertura = updateForm.querySelector('input[name="apertura"]');
  const updCierre   = updateForm.querySelector('input[name="cierre"]');
  const updBcv      = updateForm.querySelector('input[name="bcv"]');

  // Comprueba token al cargar
  const token = localStorage.getItem('jwtToken');
  if (token && !isTokenExpired(token)) {
    const payload = decodeJWT(token) || {};
    currentApiToken.textContent = token;
    setupAuthenticatedUI(payload);
  } else {
    showLoginUI();
  }

  // Eventos
  loginForm.addEventListener('submit', handleLogin);
  logoutBtn.addEventListener('click', handleLogout);
  consultForm.addEventListener('submit', handleConsult);
  addForm.addEventListener('submit', handleAdd);
  updateForm.addEventListener('submit', handleUpdate);

  // Cuando cambia la fecha en el formulario de actualización,
  // traemos los datos existentes y rellenamos los inputs
  updFecha.addEventListener('change', async (e) => {
    const fecha = e.target.value;
    if (!fecha) return;
    clearMessage();
    try {
      const data = await apiRequest(`?fecha=${fecha}`, 'GET');
      const apertura = parseFloat(data.apertura) || 0;
      const cierre   = parseFloat(data.cierre)   || 0;
      const bcv      = parseFloat(data.bcv)      || 0;
      updApertura.value = apertura.toFixed(2);
      updCierre.value   = cierre.toFixed(2);
      updBcv.value      = bcv.toFixed(2);
    } catch (err) {
      showMessage('No se pudo cargar la cotización para esa fecha', 'error');
      updApertura.value = '';
      updCierre.value   = '';
      updBcv.value      = '';
    }
  });

  /** Configura la UI tras autenticación exitosa. */
  function setupAuthenticatedUI(payload) {
    clearMessage();
    loginSection.style.display   = 'none';
    logoutBtn.style.display      = 'inline-block';
    consultSection.style.display = 'block';
    currentApiToken.textContent  = localStorage.getItem('jwtToken') || 'Ninguno';

    // Roles
    const roles  = payload.roles || [];
    const canPost = roles.includes('admin') || roles.includes('editor');
    const canPut  = roles.includes('admin') || roles.includes('editor');

    addSection.style.display    = canPost ? 'block' : 'none';
    updateSection.style.display = canPut  ? 'block' : 'none';
  }

  /** Muestra el formulario de login y oculta secciones autenticadas. */
  function showLoginUI() {
    loginSection.style.display   = 'block';
    logoutBtn.style.display      = 'none';
    consultSection.style.display = 'none';
    addSection.style.display     = 'none';
    updateSection.style.display  = 'none';
    currentApiToken.textContent  = 'Ninguno';
    clearMessage();
  }

  /** Maneja login */
  async function handleLogin(ev) {
    ev.preventDefault();
    clearMessage();
    const uname = ev.target.username.value.trim();
    const pwd   = ev.target.password.value.trim();
    if (!uname || !pwd) {
      showMessage('Usuario y contraseña requeridos', 'error');
      return;
    }
    try {
      const data = await apiRequest('/login', 'POST', { username: uname, password: pwd });
      localStorage.setItem('jwtToken', data.token);
      setupAuthenticatedUI(decodeJWT(data.token) || {});
    } catch (err) {
      showMessage(err.message || 'Error de autenticación', 'error');
    }
  }

  /** Logout */
  function handleLogout() {
    localStorage.removeItem('jwtToken');
    window.location.reload();
  }

  /** Consulta cotización */
  async function handleConsult(ev) {
    ev.preventDefault();
    clearMessage();

    const fecha = consultForm.fecha.value;
    try {
      const data = await apiRequest(`?fecha=${fecha}`, 'GET');

      const bcv      = parseFloat(data.bcv)      || 0;
      const apertura = parseFloat(data.apertura) || 0;
      const cierre   = parseFloat(data.cierre)   || 0;

      resultFecha.textContent      = data.fecha || '';
      resultApertura.textContent   = apertura.toFixed(2);
      resultCierre.textContent     = cierre.toFixed(2);
      resultBcv.textContent        = bcv.toFixed(2);

      resultPromApert.textContent  = ((bcv + apertura) / 2).toFixed(2);
      resultPromCierre.textContent = ((bcv + cierre)   / 2).toFixed(2);

    } catch (err) {
      showMessage(err.message, 'error');
    }
  }

  /** Añade nueva cotización */
  async function handleAdd(ev) {
    ev.preventDefault();
    clearMessage();
    const body = {
      fecha:    addForm.fecha.value,
      bcv:      parseFloat(addForm.bcv.value)      || 0,
      apertura: parseFloat(addForm.apertura.value) || 0,
      cierre:   parseFloat(addForm.cierre.value)   || 0
    };
    try {
      const res = await apiRequest('', 'POST', body);
      showMessage(res.message || 'Añadido con éxito', 'success');
    } catch (err) {
      showMessage(err.message, 'error');
    }
  }

  /** Actualiza cotización */
  async function handleUpdate(ev) {
    ev.preventDefault();
    clearMessage();
    const body = { fecha: updFecha.value };
    if (updApertura.value) body.apertura = parseFloat(updApertura.value);
    if (updCierre.value)   body.cierre   = parseFloat(updCierre.value);
    if (updBcv.value)      body.bcv      = parseFloat(updBcv.value);

    try {
      const res = await apiRequest('', 'PUT', body);
      showMessage(res.message || 'Actualizado con éxito', 'success');
    } catch (err) {
      showMessage(err.message, 'error');
    }
  }

  /** Fetch genérico con JWT en Authorization */
  async function apiRequest(path, method = 'GET', body = null) {
    const token = localStorage.getItem('jwtToken');
    const headers = { 'Content-Type': 'application/json' };
    if (token) headers['Authorization'] = 'Bearer ' + token;
    const res = await fetch('/api.php' + path, {
      method,
      headers,
      body: body ? JSON.stringify(body) : undefined
    });
    if (!res.ok) {
      const errorBody = await res.json().catch(() => ({}));
      throw new Error(errorBody.error || errorBody.message || `${res.status} ${res.statusText}`);
    }
    return res.json();
  }

  /** Decodifica JWT sin verificar */
  function decodeJWT(token) {
    try {
      const pl = token.split('.')[1];
      const json = atob(pl.replace(/-/g, '+').replace(/_/g, '/'));
      return JSON.parse(decodeURIComponent(escape(json)));
    } catch {
      return null;
    }
  }

  /** Comprueba expiración */
  function isTokenExpired(token) {
    const pl = decodeJWT(token);
    return !pl || !pl.exp || Date.now() >= pl.exp * 1000;
  }

  /** Mensajes */
  function showMessage(text, type = 'success') {
    messageDiv.textContent = text;
    messageDiv.className   = type;
  }
  function clearMessage() {
    messageDiv.textContent = '';
    messageDiv.className   = '';
  }

}); // Cierre de DOMContentLoaded