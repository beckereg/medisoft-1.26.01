// API Configuration
const API_BASE_URL = '../Backend/api';

// Global state
let currentPatient = null;
let currentPatientId = localStorage.getItem('currentPatientId') || null;
let treatments = JSON.parse(localStorage.getItem('treatments') || '[]');
let consumables = JSON.parse(localStorage.getItem('consumables') || '[]');
let symptomsData = JSON.parse(localStorage.getItem('symptomsData') || '[]');
let assessmentsData = JSON.parse(localStorage.getItem('assessmentsData') || '[]');

// DOM Elements
let activeTab = 'patient';

// Symptoms and Assessments Lists
const symptomsList = [
    'Redness', 'Pain', 'Blurred Vision', 'Itching', 'Discharge',
    'Foreign Body Sensation', 'Watering', 'Photophobia', 'Swelling', 'Headache'
];

const assessmentItems = [
    { name: 'Visual Acuity', values: ['6/6', '6/9', '6/12', '6/18', '6/24', '6/36', '6/60', 'CF', 'HM', 'PL', 'NPL'] },
    { name: 'Intraocular Pressure', values: ['Normal', 'Elevated', 'Low'] },
    { name: 'Cornea', values: ['Clear', 'Opacity', 'Edema', 'Ulcer', 'Scar'] },
    { name: 'Lens', values: ['Clear', 'Nuclear Sclerosis', 'Cortical Cataract', 'Mature Cataract'] },
    { name: 'Conjunctiva', values: ['Normal', 'Injected', 'Pale', 'Chemosis', 'Follicles'] }
];

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    initTabs();
    initPatientSearch();
    initSymptoms();
    initAssessments();
    loadSavedData();
    loadPatientIfExists();
    
    // Event listeners
    document.getElementById('saveBtn')?.addEventListener('click', saveConsultation);
    document.getElementById('cancelBtn')?.addEventListener('click', clearAllData);
    document.getElementById('addTreatmentBtn')?.addEventListener('click', addTreatment);
    document.getElementById('addConsumableBtn')?.addEventListener('click', addConsumable);
    
    // Set default discharge date
    const dischargeDate = document.getElementById('dischargeDate');
    if (dischargeDate && !dischargeDate.value) {
        dischargeDate.value = new Date().toISOString().split('T')[0];
    }
});

// Initialize tabs
function initTabs() {
    const tabs = document.querySelectorAll('.tab-button');
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const tabId = tab.dataset.tab;
            activeTab = tabId;
            
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('active');
            });
            document.getElementById(`${tabId}Tab`).classList.add('active');
        });
    });
}

// Load patient if exists
async function loadPatientIfExists() {
    if (currentPatientId) {
        await loadPatientDetails();
        displayPatientDetails();
    } else {
        showPatientSearch();
    }
}

// Show patient search UI
function showPatientSearch() {
    const container = document.getElementById('patientDetailsSection');
    if (!container) return;
    
    container.innerHTML = `
        <div class="patient-search-box">
            <h3>Find Patient</h3>
            <div class="search-input-group">
                <input type="text" id="patientSearchInput" class="search-input" placeholder="Enter Patient ID" autofocus>
                <button id="searchPatientBtn" class="search-button">🔍 Search</button>
            </div>
            <div id="patientSearchError" class="error-text" style="display:none;"></div>
            <div class="search-examples">
                <p>Enter Patient ID from your database (e.g., 2)</p>
            </div>
        </div>
    `;
    
    document.getElementById('searchPatientBtn')?.addEventListener('click', searchPatient);
    document.getElementById('patientSearchInput')?.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') searchPatient();
    });
}

// Search patient
async function searchPatient() {
    const input = document.getElementById('patientSearchInput');
    const patientId = input?.value.trim();
    
    if (!patientId) {
        showError('patientSearchError', 'Please enter a Patient ID');
        return;
    }
    
    showLoading('patientDetailsSection');
    
    try {
        const response = await fetch(`${API_BASE_URL}/patients/get.php?id=${patientId}`);
        const result = await response.json();
        
        if (result.success) {
            currentPatient = result.data;
            currentPatientId = patientId;
            saveToLocalStorage();
            displayPatientDetails();
            hideError('patientSearchError');
            displayPatientInfo();
        } else {
            showError('patientSearchError', result.message || 'Patient not found');
            showPatientSearch();
        }
    } catch (error) {
        console.error('Error:', error);
        showError('patientSearchError', 'Network error. Please check if backend is running.');
        showPatientSearch();
    }
}

// Display patient details
function displayPatientDetails() {
    const container = document.getElementById('patientDetailsSection');
    if (!container || !currentPatient) return;
    
    container.innerHTML = `
        <div class="patient-header">
            <div class="patient-id-badge">
                <span class="badge">Patient ID: ${currentPatient.id}</span>
                <button class="change-patient-btn" id="changePatientBtn" title="Change Patient">🔄</button>
            </div>
        </div>
        <div class="form-grid">
            <div class="form-column">
                <div class="form-field">
                    <label>Patient <span class="required">*</span></label>
                    <input type="text" value="${escapeHtml(currentPatient.name || '')}" readonly class="readonly-field">
                </div>
                <div class="form-field">
                    <label>Insurance</label>
                    <input type="text" value="${escapeHtml(currentPatient.insurance || 'N/A')}" readonly class="readonly-field">
                </div>
                <div class="form-field">
                    <label>Catchment Area</label>
                    <input type="text" id="catchmentArea" placeholder="Enter catchment area" class="form-input" 
                           value="${escapeHtml(localStorage.getItem('catchmentArea') || '')}">
                </div>
                <div class="form-field">
                    <label>District</label>
                    <input type="text" value="${escapeHtml(currentPatient.district || 'N/A')}" readonly class="readonly-field">
                </div>
                <div class="form-field">
                    <label>Village</label>
                    <input type="text" value="${escapeHtml(currentPatient.village || 'N/A')}" readonly class="readonly-field">
                </div>
            </div>
            <div class="form-column">
                <div class="form-field">
                    <label>Consultation Date</label>
                    <input type="text" value="${new Date().toLocaleDateString()}" readonly class="readonly-field">
                </div>
                <div class="form-field">
                    <label>Phone</label>
                    <input type="text" value="${escapeHtml(currentPatient.phone || 'N/A')}" readonly class="readonly-field">
                </div>
                <div class="form-field">
                    <label>Age</label>
                    <input type="text" value="${escapeHtml(currentPatient.age || 'N/A')}" readonly class="readonly-field">
                </div>
                <div class="form-field">
                    <label>Gender</label>
                    <input type="text" value="${escapeHtml(currentPatient.gender || 'N/A')}" readonly class="readonly-field">
                </div>
                <div class="form-field">
                    <label>Province</label>
                    <input type="text" value="${escapeHtml(currentPatient.province || 'N/A')}" readonly class="readonly-field">
                </div>
                <div class="form-field">
                    <label>Sector</label>
                    <input type="text" value="${escapeHtml(currentPatient.sector || 'N/A')}" readonly class="readonly-field">
                </div>
                <div class="form-field">
                    <label>Cell</label>
                    <input type="text" value="${escapeHtml(currentPatient.cell || 'N/A')}" readonly class="readonly-field">
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('changePatientBtn')?.addEventListener('click', () => {
        currentPatient = null;
        currentPatientId = null;
        saveToLocalStorage();
        showPatientSearch();
    });
    
    document.getElementById('catchmentArea')?.addEventListener('change', (e) => {
        localStorage.setItem('catchmentArea', e.target.value);
    });
}

// Initialize symptoms table
function initSymptoms() {
    const container = document.getElementById('symptomsTable');
    if (!container) return;
    
    let html = `<div class="table-header"><div class="col-symptom">Symptom</div><div class="col-present">Present (Yes/No)</div><div class="col-eye">Affected eye</div></div>`;
    
    symptomsList.forEach(symptom => {
        const saved = symptomsData.find(s => s.name === symptom);
        const isChecked = saved?.present || false;
        const laterality = saved?.laterality || '';
        
        html += `
            <div class="table-row" data-symptom="${symptom}">
                <div class="col-symptom">${symptom}</div>
                <div class="col-present">
                    <label class="checkbox-label">
                        <input type="checkbox" class="symptom-checkbox" data-symptom="${symptom}" ${isChecked ? 'checked' : ''}>
                        <span>Yes</span>
                    </label>
                </div>
                <div class="col-eye">
                    <div class="laterality-group">
                        <label class="radio-label ${!isChecked ? 'disabled' : ''}">
                            <input type="radio" name="laterality_${symptom}" value="RE" ${laterality === 'RE' ? 'checked' : ''} ${!isChecked ? 'disabled' : ''}> Right Eye
                        </label>
                        <label class="radio-label ${!isChecked ? 'disabled' : ''}">
                            <input type="radio" name="laterality_${symptom}" value="LE" ${laterality === 'LE' ? 'checked' : ''} ${!isChecked ? 'disabled' : ''}> Left Eye
                        </label>
                        <label class="radio-label ${!isChecked ? 'disabled' : ''}">
                            <input type="radio" name="laterality_${symptom}" value="BE" ${laterality === 'BE' ? 'checked' : ''} ${!isChecked ? 'disabled' : ''}> Both Eyes
                        </label>
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
    
    // Add event listeners
    document.querySelectorAll('.symptom-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', (e) => {
            const row = e.target.closest('.table-row');
            const radios = row.querySelectorAll('.radio-label');
            const symptomName = row.dataset.symptom;
            
            if (e.target.checked) {
                radios.forEach(r => {
                    r.classList.remove('disabled');
                    r.querySelector('input').disabled = false;
                });
            } else {
                radios.forEach(r => {
                    r.classList.add('disabled');
                    r.querySelector('input').disabled = true;
                    r.querySelector('input').checked = false;
                });
            }
            updateSymptomsData();
        });
        
        document.querySelectorAll(`input[type="radio"]`).forEach(radio => {
            radio.addEventListener('change', () => updateSymptomsData());
        });
    });
}

// Update symptoms data
function updateSymptomsData() {
    symptomsData = [];
    document.querySelectorAll('.table-row').forEach(row => {
        const symptomName = row.dataset.symptom;
        const checkbox = row.querySelector('.symptom-checkbox');
        if (checkbox && checkbox.checked) {
            const selectedRadio = row.querySelector('input[type="radio"]:checked');
            const laterality = selectedRadio ? selectedRadio.value : '';
            symptomsData.push({ name: symptomName, present: true, laterality });
        }
    });
    saveToLocalStorage();
}

// Initialize assessments table
function initAssessments() {
    const container = document.getElementById('assessmentTable');
    if (!container) return;
    
    let html = `<div class="table-header"><div class="col-assessment">Symptom / Assessment item</div><div class="col-eye-details">Right eye details</div><div class="col-eye-details">Left eye details</div></div>`;
    
    assessmentItems.forEach(item => {
        const saved = assessmentsData.find(a => a.name === item.name);
        const rightVal = saved?.rightEye || '';
        const leftVal = saved?.leftEye || '';
        
        html += `
            <div class="table-row">
                <div class="col-assessment"><strong>${item.name}</strong></div>
                <div class="col-eye-details">
                    <select class="assessment-re" data-assessment="${item.name}" data-eye="right">
                        <option value="">Select value</option>
                        ${item.values.map(v => `<option value="${v}" ${rightVal === v ? 'selected' : ''}>${v}</option>`).join('')}
                    </select>
                </div>
                <div class="col-eye-details">
                    <select class="assessment-le" data-assessment="${item.name}" data-eye="left">
                        <option value="">Select value</option>
                        ${item.values.map(v => `<option value="${v}" ${leftVal === v ? 'selected' : ''}>${v}</option>`).join('')}
                    </select>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
    
    document.querySelectorAll('.assessment-re, .assessment-le').forEach(select => {
        select.addEventListener('change', () => updateAssessmentsData());
    });
}

// Update assessments data
function updateAssessmentsData() {
    assessmentsData = [];
    document.querySelectorAll('.table-row').forEach(row => {
        const name = row.querySelector('.col-assessment strong')?.innerText;
        const rightSelect = row.querySelector('.assessment-re');
        const leftSelect = row.querySelector('.assessment-le');
        if (name) {
            assessmentsData.push({
                name: name,
                rightEye: rightSelect?.value || '',
                leftEye: leftSelect?.value || ''
            });
        }
    });
    saveToLocalStorage();
}

// Load saved data from localStorage
function loadSavedData() {
    const savedType = localStorage.getItem('consultationType');
    const savedNotes = localStorage.getItem('consultationNotes');
    const savedDiagnosisName = localStorage.getItem('diagnosisName');
    const savedDiagnosisCode = localStorage.getItem('diagnosisCode');
    const savedDiagnosisNotes = localStorage.getItem('diagnosisNotes');
    const savedPatientStatus = localStorage.getItem('patientStatus');
    const savedDischargeDate = localStorage.getItem('dischargeDate');
    const savedService = localStorage.getItem('dischargingService');
    const savedSummary = localStorage.getItem('clinicalSummary');
    
    if (savedType) document.getElementById('consultationType').value = savedType;
    if (savedNotes) document.getElementById('consultationNotes').value = savedNotes;
    if (savedDiagnosisName) document.getElementById('diagnosisName').value = savedDiagnosisName;
    if (savedDiagnosisCode) document.getElementById('diagnosisCode').value = savedDiagnosisCode;
    if (savedDiagnosisNotes) document.getElementById('diagnosisNotes').value = savedDiagnosisNotes;
    if (savedPatientStatus) document.getElementById('patientStatus').value = savedPatientStatus;
    if (savedDischargeDate) document.getElementById('dischargeDate').value = savedDischargeDate;
    if (savedService) document.getElementById('dischargingService').value = savedService;
    if (savedSummary) document.getElementById('clinicalSummary').value = savedSummary;
    
    // Save on change
    document.getElementById('consultationType')?.addEventListener('change', (e) => {
        localStorage.setItem('consultationType', e.target.value);
    });
    document.getElementById('consultationNotes')?.addEventListener('input', (e) => {
        localStorage.setItem('consultationNotes', e.target.value);
    });
    document.getElementById('diagnosisName')?.addEventListener('input', (e) => {
        localStorage.setItem('diagnosisName', e.target.value);
    });
    document.getElementById('diagnosisCode')?.addEventListener('input', (e) => {
        localStorage.setItem('diagnosisCode', e.target.value);
    });
    document.getElementById('diagnosisNotes')?.addEventListener('input', (e) => {
        localStorage.setItem('diagnosisNotes', e.target.value);
    });
    document.getElementById('patientStatus')?.addEventListener('change', (e) => {
        localStorage.setItem('patientStatus', e.target.value);
    });
    document.getElementById('dischargeDate')?.addEventListener('change', (e) => {
        localStorage.setItem('dischargeDate', e.target.value);
    });
    document.getElementById('dischargingService')?.addEventListener('change', (e) => {
        localStorage.setItem('dischargingService', e.target.value);
    });
    document.getElementById('clinicalSummary')?.addEventListener('input', (e) => {
        localStorage.setItem('clinicalSummary', e.target.value);
    });
    
    updateTreatmentList();
    updateConsumableList();
}

// Add treatment
function addTreatment() {
    const medication = document.getElementById('medicationName')?.value.trim();
    const dosage = document.getElementById('dosage')?.value.trim();
    const frequency = document.getElementById('frequency')?.value.trim();
    const duration = document.getElementById('duration')?.value.trim();
    
    if (!medication) {
        alert('Please enter medication name');
        return;
    }
    
    treatments.push({
        id: Date.now(),
        medication: medication,
        dosage: dosage || '',
        frequency: frequency || '',
        duration: duration || ''
    });
    
    saveToLocalStorage();
    updateTreatmentList();
    
    document.getElementById('medicationName').value = '';
    document.getElementById('dosage').value = '';
    document.getElementById('frequency').value = '';
    document.getElementById('duration').value = '';
}

// Update treatment list display
function updateTreatmentList() {
    const container = document.getElementById('treatmentList');
    if (!container) return;
    
    if (treatments.length === 0) {
        container.innerHTML = '<p style="text-align:center; color:#7f8c8d; padding:20px;">No treatments added yet.</p>';
        return;
    }
    
    container.innerHTML = treatments.map(t => `
        <div class="treatment-card">
            <div class="treatment-header">
                <h4>${escapeHtml(t.medication)}</h4>
                <button class="remove-button" onclick="removeTreatment(${t.id})">✕ Remove</button>
            </div>
            <div class="treatment-details">
                <div class="detail-item"><label>Dosage</label><span>${escapeHtml(t.dosage) || '—'}</span></div>
                <div class="detail-item"><label>Frequency</label><span>${escapeHtml(t.frequency) || '—'}</span></div>
                <div class="detail-item"><label>Duration</label><span>${escapeHtml(t.duration) || '—'}</span></div>
            </div>
        </div>
    `).join('');
}

// Remove treatment
function removeTreatment(id) {
    treatments = treatments.filter(t => t.id !== id);
    saveToLocalStorage();
    updateTreatmentList();
}

// Add consumable
function addConsumable() {
    const name = document.getElementById('consumableName')?.value.trim();
    const quantity = document.getElementById('consumableQuantity')?.value;
    const unit = document.getElementById('consumableUnit')?.value;
    
    if (!name || !quantity) {
        alert('Please enter consumable name and quantity');
        return;
    }
    
    consumables.push({
        id: Date.now(),
        name: name,
        quantity: quantity,
        unit: unit,
        dateIssued: new Date().toLocaleDateString()
    });
    
    saveToLocalStorage();
    updateConsumableList();
    
    document.getElementById('consumableName').value = '';
    document.getElementById('consumableQuantity').value = '';
}

// Update consumable list display
function updateConsumableList() {
    const tbody = document.getElementById('consumablesList');
    if (!tbody) return;
    
    if (consumables.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;">No consumables added yet.</td></tr>';
        return;
    }
    
    tbody.innerHTML = consumables.map(c => `
        <tr>
            <td>${escapeHtml(c.name)}</td>
            <td>${c.quantity} ${c.unit}</td>
            <td>${c.dateIssued}</td>
            <td><button class="remove-button" onclick="removeConsumable(${c.id})">✕</button></td>
        </tr>
    `).join('');
}

// Remove consumable
function removeConsumable(id) {
    consumables = consumables.filter(c => c.id !== id);
    saveToLocalStorage();
    updateConsumableList();
}

// Save consultation to backend
async function saveConsultation() {
    if (!currentPatientId) {
        alert('Please search and select a patient first');
        document.querySelector('.tab-button[data-tab="patient"]').click();
        return;
    }
    
    const diagnosisName = localStorage.getItem('diagnosisName');
    if (!diagnosisName) {
        alert('Please enter a diagnosis in the Final Diagnosis tab');
        document.querySelector('.tab-button[data-tab="diagnosis"]').click();
        return;
    }
    
    showSaveStatus('saving', 'Saving...');
    
    const consultationData = {
        patientId: currentPatientId,
        consultation: {
            type: localStorage.getItem('consultationType') || '',
            notes: localStorage.getItem('consultationNotes') || '',
            date: new Date().toISOString().split('T')[0]
        },
        symptoms: symptomsData,
        assessments: assessmentsData,
        diagnosis: {
            primary: diagnosisName,
            code: localStorage.getItem('diagnosisCode') || '',
            notes: localStorage.getItem('diagnosisNotes') || ''
        },
        treatment: treatments,
        consumables: consumables,
        discharge: {
            patientStatus: localStorage.getItem('patientStatus') || '',
            dischargeDate: localStorage.getItem('dischargeDate') || '',
            dischargingService: localStorage.getItem('dischargingService') || '',
            clinicalSummary: localStorage.getItem('clinicalSummary') || ''
        }
    };
    
    try {
        const response = await fetch(`${API_BASE_URL}/consultations/post.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(consultationData)
        });
        const result = await response.json();
        
        if (result.success) {
            // Save complete consultation data for overview page
            localStorage.setItem('completeConsultation', JSON.stringify({
                ...consultationData,
                patient: currentPatient,
                clinician: 'Dr. Emmanuel Ndayisaba', // You can make this dynamic
                savedAt: new Date().toISOString()
            }));
            
            // Also save to patient-specific consultations list for history
            const allConsultationsKey = `consultations_${currentPatientId}`;
            let existingConsultations = [];
            try {
                const saved = localStorage.getItem(allConsultationsKey);
                if (saved) {
                    existingConsultations = JSON.parse(saved);
                }
            } catch(e) {}
            
            // Add the new consultation to the list
            existingConsultations.unshift({
                ...consultationData,
                patient: currentPatient,
                clinician: 'Dr. Emmanuel Ndayisaba',
                savedAt: new Date().toISOString(),
                id: result.data?.consultationId || Date.now() // Use API ID if available
            });
            
            // Keep only the last 10 consultations to avoid localStorage bloat
            if (existingConsultations.length > 10) {
                existingConsultations = existingConsultations.slice(0, 10);
            }
            
            localStorage.setItem(allConsultationsKey, JSON.stringify(existingConsultations));
            
            showSaveStatus('saved', 'Saved ✓');
            alert('Consultation saved successfully!');
            setTimeout(() => showSaveStatus('not-saved', 'Not Saved'), 2000);
        } else {
            throw new Error(result.message || 'Save failed');
        }
    } catch (error) {
        console.error('Save error:', error);
        showSaveStatus('error', 'Error');
        alert('Error saving consultation: ' + error.message);
    }
}

// Clear all data
function clearAllData() {
    if (confirm('Are you sure you want to clear all data? This will reset the entire consultation.')) {
        localStorage.clear();
        currentPatient = null;
        currentPatientId = null;
        treatments = [];
        consumables = [];
        symptomsData = [];
        assessmentsData = [];
        saveToLocalStorage();
        location.reload();
    }
}

// Helper functions
function saveToLocalStorage() {
    localStorage.setItem('currentPatientId', currentPatientId || '');
    localStorage.setItem('treatments', JSON.stringify(treatments));
    localStorage.setItem('consumables', JSON.stringify(consumables));
    localStorage.setItem('symptomsData', JSON.stringify(symptomsData));
    localStorage.setItem('assessmentsData', JSON.stringify(assessmentsData));
}

async function loadPatientDetails() {
    if (!currentPatientId) return;
    
    try {
        const response = await fetch(`${API_BASE_URL}/patients/get.php?id=${currentPatientId}`);
        const result = await response.json();
        if (result.success) {
            currentPatient = result.data;
            displayPatientInfo();
        }
    } catch (error) {
        console.error('Error loading patient:', error);
    }
}

function displayPatientInfo() {
    const patientInfoDiv = document.getElementById('patientInfo');
    if (patientInfoDiv && currentPatient) {
        patientInfoDiv.innerHTML = `<span class="badge" style="background:#27ae60;">👤 ${escapeHtml(currentPatient.name)} (ID: ${currentPatientId})</span>`;
    }
}

function showSaveStatus(status, text) {
    const statusDiv = document.getElementById('saveStatus');
    if (statusDiv) {
        statusDiv.className = `save-status status-${status}`;
        statusDiv.textContent = text;
    }
}

function showError(elementId, message) {
    const element = document.getElementById(elementId);
    if (element) {
        element.textContent = message;
        element.style.display = 'block';
    }
}

function hideError(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.style.display = 'none';
    }
}

function showLoading(containerId) {
    const container = document.getElementById(containerId);
    if (container) {
        container.innerHTML = '<div class="loading-container"><div class="spinner"></div><p>Loading...</p></div>';
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
// Load patient from localStorage on page load
function loadPatientFromStorage() {
    const savedPatientId = localStorage.getItem('currentPatientId');
    const savedPatient = localStorage.getItem('currentPatient');
    
    if (savedPatientId && savedPatient) {
        currentPatientId = savedPatientId;
        currentPatient = JSON.parse(savedPatient);
        displayPatientDetails();
        displayPatientInfo();
        return true;
    }
    return false;
}

// Call this at the beginning of DOMContentLoaded
document.addEventListener('DOMContentLoaded', () => {
    const patientLoaded = loadPatientFromStorage();
    if (!patientLoaded) {
        // Redirect to search page if no patient selected
        window.location.href = 'index.html';
        return;
    }
    
    initTabs();
    initSymptoms();
    initAssessments();
    loadSavedData();
    
    // Event listeners
    document.getElementById('saveBtn')?.addEventListener('click', saveConsultation);
    document.getElementById('cancelBtn')?.addEventListener('click', clearAllData);
    document.getElementById('addTreatmentBtn')?.addEventListener('click', addTreatment);
    document.getElementById('addConsumableBtn')?.addEventListener('click', addConsumable);
    
    // Set default discharge date
    const dischargeDate = document.getElementById('dischargeDate');
    if (dischargeDate && !dischargeDate.value) {
        dischargeDate.value = new Date().toISOString().split('T')[0];
    }
});

// Make functions global for onclick handlers
window.removeTreatment = removeTreatment;
window.removeConsumable = removeConsumable;