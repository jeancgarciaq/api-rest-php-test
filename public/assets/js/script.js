
// public/assets/js/script.js

/**
 * @file Contiene la lógica del frontend para interactuar con la API de cotizaciones.
 * @author Jean Carlo Garcia
 * @version 1.0.0
*/

/**
 * La URL base de la API REST de cotizaciones.
 * @type {string}
*/
const apiBaseUrl = '../api.php'; 
/**
 * Elemento DOM para mostrar mensajes al usuario.
 * @type {HTMLElement}
 */
const messageDiv = document.getElementById('message');

/**
 * Elemento DOM para mostrar los resultados de las consultas.
 * @type {HTMLElement}
 */
const resultDiv = document.getElementById('result');
const resultFecha = document.getElementById('resultFecha');
const resultApertura = document.getElementById('resultApertura');
const resultCierre = document.getElementById('resultCierre');
const resultBcv = document.getElementById('resultBcv');
const resultPromedioApertura = document.getElementById('resultPromedioApertura');
const resultPromedioCierre = document.getElementById('resultPromedioCierre');

/**
 * Muestra un mensaje al usuario en el div de mensajes.
 * El mensaje desaparecerá después de 5 segundos.
 *
 * @param {string} msg - El texto del mensaje a mostrar.
 * @param {'success'|'error'} type - El tipo de mensaje (para aplicar estilos CSS).
 * @returns {void}
*/
function showMessage(msg, type) {
    messageDiv.textContent = msg;
    messageDiv.className = `success ${type}`;
    setTimeout(() => {
        messageDiv.textContent = '';
        messageDiv.className = '';
    }, 5000);
}

/**
 * Muestra los datos de una cotización en la sección de resultados.
 * Realiza cálculos de promedios si los valores de apertura/cierre son válidos.
 *
 * @param {object|null} data - El objeto de datos de la cotización, o null para limpiar.
 * @param {string} data.fecha - La fecha de la cotización.
 * @param {number} data.apertura - El valor de apertura.
 * @param {number} data.cierre - El valor de cierre.
 * @param {number} data.bcv - El valor BCV.
 * @returns {void}
*/
function displayResult(data) {
    if (data) {
        resultFecha.textContent = data.fecha;
        resultApertura.textContent = parseFloat(data.apertura).toFixed(2);
        resultCierre.textContent = parseFloat(data.cierre).toFixed(2);
        resultBcv.textContent = parseFloat(data.bcv).toFixed(2);

        // Cálculo de promedios
        const aperturaVal = parseFloat(data.apertura);
        const cierreVal = parseFloat(data.cierre);
        const bcvVal = parseFloat(data.bcv);

        let promedioApertura = 'N/A';
        if (aperturaVal > 0) { // Solo calcular si apertura tiene un valor real
            promedioApertura = ((bcvVal + aperturaVal) / 2).toFixed(2);
        }
        resultPromedioApertura.textContent = promedioApertura;

        let promedioCierre = 'N/A';
        if (cierreVal > 0) { // Solo calcular si cierre tiene un valor real
            promedioCierre = ((bcvVal + cierreVal) / 2).toFixed(2);
        }
        resultPromedioCierre.textContent = promedioCierre;

    } else {
        resultFecha.textContent = '';
        resultApertura.textContent = '';
        resultCierre.textContent = '';
        resultBcv.textContent = '';
        resultPromedioApertura.textContent = '';
        resultPromedioCierre.textContent = '';
    }
}

/**
 * Maneja el envío del formulario de consulta (GET).
 * Realiza una solicitud GET a la API para obtener cotizaciones por fecha o todas.
 *
 * @param {Event} e - El evento de envío del formulario.
 * @returns {Promise<void>}
*/
document.getElementById('consultForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fecha = document.getElementById('consultFecha').value;
    if (!fecha) {
        showMessage('Por favor, ingresa una fecha para consultar.', 'error');
        return;
    }

    try {
        const response = await fetch(`${apiBaseUrl}?fecha=${fecha}`);
        const data = await response.json();

        if (response.ok) {
            displayResult(data);
            showMessage('Cotización consultada exitosamente.', 'success');
        } else {
            displayResult(null); // Limpiar resultados anteriores
            showMessage(`Error al consultar: ${data.message || 'Error desconocido'}`, 'error');
        }
    } catch (error) {
        console.error('Error en la solicitud de consulta:', error);
        showMessage('Error de conexión al consultar cotización.', 'error');
    }
});

/**
 * Maneja el envío del formulario para añadir una nueva cotización (POST).
 * Envía los datos del formulario a la API para crear un nuevo registro.
 *
 * @param {Event} e - El evento de envío del formulario.
 * @returns {Promise<void>}
*/
document.getElementById('addForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fecha = document.getElementById('addFecha').value;
    const apertura = parseFloat(document.getElementById('addApertura').value);
    const cierre = parseFloat(document.getElementById('addCierre').value);
    const bcv = parseFloat(document.getElementById('addBcv').value);

    if (!fecha || isNaN(bcv)) {
        showMessage('Fecha y BCV son campos requeridos para añadir.', 'error');
        return;
    }

    const cotizacion = { fecha, apertura, cierre, bcv };

    try {
        const response = await fetch(apiBaseUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(cotizacion)
        });
        const data = await response.json();

        if (response.ok) {
            showMessage(data.message, 'success');
            document.getElementById('addForm').reset();
            document.getElementById('addApertura').value = '0.00';
            document.getElementById('addCierre').value = '0.00';
        } else {
            showMessage(`Error al añadir: ${data.message || 'Error desconocido'}`, 'error');
        }
    } catch (error) {
        console.error('Error en la solicitud de añadir:', error);
        showMessage('Error de conexión al añadir cotización.', 'error');
    }
});

/**
 * Maneja el envío del formulario para actualizar una cotización (PUT).
 * Envía los datos del formulario a la API para modificar un registro existente.
 *
 * @param {Event} e - El evento de envío del formulario.
 * @returns {Promise<void>}
*/
document.getElementById('updateForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fecha = document.getElementById('updateFecha').value;
    const apertura = document.getElementById('updateApertura').value;
    const cierre = document.getElementById('updateCierre').value;
    const bcv = document.getElementById('updateBcv').value;

    if (!fecha) {
        showMessage('La fecha es requerida para actualizar.', 'error');
        return;
    }

    const updateData = { fecha };
    if (apertura !== '') updateData.apertura = parseFloat(apertura);
    if (cierre !== '') updateData.cierre = parseFloat(cierre);
    if (bcv !== '') updateData.bcv = parseFloat(bcv);

    if (Object.keys(updateData).length === 1) {
        showMessage('Ingresa al menos un campo (Apertura, Cierre o BCV) para actualizar.', 'error');
        return;
    }

    try {
        const response = await fetch(apiBaseUrl, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(updateData)
        });
        const data = await response.json();

        if (response.ok) {
            showMessage(data.message, 'success');
            document.getElementById('updateForm').reset();
        } else {
            showMessage(`Error al actualizar: ${data.message || 'Error desconocido'}`, 'error');
        }
    } catch (error) {
        console.error('Error en la solicitud de actualizar:', error);
        showMessage('Error de conexión al actualizar cotización.', 'error');
    }
});

/**
 * Inicializa el formulario de añadir cotización al cargar el DOM,
 * estableciendo valores predeterminados para apertura y cierre.
 *
 * @listens DOMContentLoaded
 * @returns {void}
*/
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('addApertura').value = '0.00';
    document.getElementById('addCierre').value = '0.00';
});