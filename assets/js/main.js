// assets/js/main.js

const API_BASE = 'api';

// Landing Page Auth Tabs
function switchTab(tab) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.auth-form').forEach(form => form.style.display = 'none');
    
    const tabBtn = document.getElementById(`tab-${tab}`);
    if(tabBtn) tabBtn.classList.add('active');
    
    const targetForm = document.getElementById(`${tab}-form`);
    if(targetForm) targetForm.style.display = 'block';
}

// Toast Notification System
function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerText = message;

    container.appendChild(toast);
    
    // Trigger animation
    setTimeout(() => toast.classList.add('show'), 10);

    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

// Modal handling
function openModal(id) {
    const el = document.getElementById(id);
    if(el) el.classList.add('active');
}

function closeModal(id) {
    const el = document.getElementById(id);
    if(el) el.classList.remove('active');
}

// Attach close events to all close buttons
document.querySelectorAll('.close-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
        e.target.closest('.modal-overlay').classList.remove('active');
    });
});
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
        if(e.target === overlay) {
            overlay.classList.remove('active');
        }
    });
});

// Auth Handlers
const registerForm = document.getElementById('register-form');
if (registerForm) {
    registerForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(registerForm);
        formData.append('action', 'register');

        try {
            const res = await fetch(`${API_BASE}/auth.php`, { method: 'POST', body: formData });
            const data = await res.json();
            
            if (data.status === 'success') {
                showToast(data.message, 'success');
                setTimeout(() => window.location.href = 'dashboard.php', 1000);
            } else {
                showToast(data.message, 'error');
            }
        } catch (error) {
            showToast('Network error occurred.', 'error');
        }
    });
}

const loginForm = document.getElementById('login-form');
if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(loginForm);
        formData.append('action', 'login');

        try {
            const res = await fetch(`${API_BASE}/auth.php`, { method: 'POST', body: formData });
            const data = await res.json();
            
            if (data.status === 'success') {
                showToast(data.message, 'success');
                setTimeout(() => window.location.href = 'dashboard.php', 1000);
            } else {
                showToast(data.message, 'error');
            }
        } catch (error) {
            showToast('Network error occurred.', 'error');
        }
    });
}

// Logout handler
const logoutBtn = document.getElementById('logout-btn');
if (logoutBtn) {
    logoutBtn.addEventListener('click', async (e) => {
        e.preventDefault();
        const formData = new FormData();
        formData.append('action', 'logout');
        
        try {
            const res = await fetch(`${API_BASE}/auth.php`, { method: 'POST', body: formData });
            const data = await res.json();
            if (data.status === 'success') {
                window.location.href = 'index.php';
            }
        } catch (err) {
            showToast('Failed to logout', 'error');
        }
    });
}

// Upload Handler
const uploadForm = document.getElementById('upload-form');
if (uploadForm) {
    uploadForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(uploadForm);
        
        const submitBtn = uploadForm.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerText;
        submitBtn.innerText = 'Uploading...';
        submitBtn.disabled = true;

        try {
            const res = await fetch(`${API_BASE}/upload.php`, { method: 'POST', body: formData });
            const data = await res.json();
            
            if (data.status === 'success') {
                showToast(data.message, 'success');
                closeModal('upload-modal');
                uploadForm.reset();
            } else {
                showToast(data.message, 'error');
            }
        } catch (error) {
            showToast('Network error occurred during upload.', 'error');
        } finally {
            submitBtn.innerText = originalText;
            submitBtn.disabled = false;
        }
    });
}

// Fetch Documents
async function loadDocuments() {
    const grid = document.getElementById('documents-grid');
    if (!grid) return;

    const search = document.getElementById('search-input')?.value || '';
    const subject = document.getElementById('subject-filter')?.value || '';
    const tag = document.getElementById('tag-filter')?.value || '';

    try {
        const res = await fetch(`${API_BASE}/documents.php?action=list&search=${encodeURIComponent(search)}&subject=${encodeURIComponent(subject)}&tag=${encodeURIComponent(tag)}`);
        const data = await res.json();

        if (data.status === 'success') {
            grid.innerHTML = '';
            if (data.data.length === 0) {
                grid.innerHTML = '<p>No documents found.</p>';
                return;
            }

            data.data.forEach(doc => {
                const card = document.createElement('div');
                card.className = 'card glass';
                card.onclick = () => openDocumentView(doc.id);
                card.innerHTML = `
                    <div class="card-title" style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <span>${doc.title}</span>
                        <button class="btn btn-secondary" style="padding:0.2rem 0.5rem; background:transparent; border:none; box-shadow:none; font-size:1.2rem;" onclick="event.stopPropagation(); openReportModal(${doc.id})" title="Report this document">🚩</button>
                    </div>
                    <div class="card-meta">
                        <span>By ${doc.username}</span>
                        <span>★ ${parseFloat(doc.avg_rating).toFixed(1)}</span>
                    </div>
                    <div class="card-meta" style="margin-top:0.5rem;">
                        <span class="badge">${doc.subject}</span>
                        <span class="badge">${doc.tag}</span>
                    </div>
                `;
                grid.appendChild(card);
            });
        }
    } catch (err) {
        showToast('Failed to load documents.', 'error');
    }
}

// Search and Filter Listeners
const searchInput = document.getElementById('search-input');
const subjectFilter = document.getElementById('subject-filter');
const tagFilter = document.getElementById('tag-filter');

if (searchInput) {
    let debounceTimer;
    searchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(loadDocuments, 500);
    });
}
if (subjectFilter) subjectFilter.addEventListener('change', loadDocuments);
if (tagFilter) tagFilter.addEventListener('change', loadDocuments);


// Load documents on init if grid exists
if (document.getElementById('documents-grid')) {
    loadDocuments();
}

// Viewer Logic
let currentDocId = null;

async function openDocumentView(id) {
    try {
        const res = await fetch(`${API_BASE}/documents.php?action=details&id=${id}`);
        const data = await res.json();
        
        if (data.status === 'success') {
            const doc = data.data;
            currentDocId = doc.id;
            
            document.getElementById('view-title').innerText = doc.title;
            document.getElementById('view-desc').innerText = doc.description || 'No description provided.';
            document.getElementById('view-meta').innerText = `Uploaded by ${doc.username} | ★ ${parseFloat(doc.avg_rating).toFixed(1)}`;
            
            const viewerContainer = document.getElementById('viewer-container');
            const fileUrl = window.location.origin + window.location.pathname.replace(/[^\\/]*$/, '') + doc.file_path;
            
            if (doc.file_type.includes('image')) {
                viewerContainer.innerHTML = `<img src="${doc.file_path}" alt="Document" style="max-width:100%; border-radius:8px;">`;
            } else if (doc.file_type === 'application/pdf') {
                viewerContainer.innerHTML = `<iframe id="viewer-iframe" src="${doc.file_path}"></iframe>`;
            } else {
                // Using Google Docs viewer for PPTs as fallback
                const encodedUrl = encodeURIComponent(fileUrl);
                viewerContainer.innerHTML = `<iframe id="viewer-iframe" src="https://docs.google.com/viewer?url=${encodedUrl}&embedded=true"></iframe>`;
            }
            
            // Render comments
            const commentsContainer = document.getElementById('comments-list');
            commentsContainer.innerHTML = '';
            doc.comments.forEach(c => {
                commentsContainer.innerHTML += `
                    <div class="comment">
                        <div class="comment-author">${c.username}</div>
                        <div class="comment-text">${c.comment}</div>
                    </div>
                `;
            });
            
            openModal('view-modal');
        } else {
            showToast(data.message, 'error');
        }
    } catch (err) {
        showToast('Failed to load document details.', 'error');
    }
}

// Engagement Form Handlers
const rateForm = document.getElementById('rate-form');
if (rateForm) {
    rateForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(rateForm);
        formData.append('action', 'rate');
        formData.append('document_id', currentDocId);
        
        try {
            const res = await fetch(`${API_BASE}/engage.php`, { method: 'POST', body: formData });
            const data = await res.json();
            if (data.status === 'success') {
                showToast(data.message, 'success');
                openDocumentView(currentDocId); // Reload view
            } else {
                showToast(data.message, 'error');
            }
        } catch (err) {
            showToast('Error submitting rating.', 'error');
        }
    });
}

const commentForm = document.getElementById('comment-form');
if (commentForm) {
    commentForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(commentForm);
        formData.append('action', 'comment');
        formData.append('document_id', currentDocId);
        
        try {
            const res = await fetch(`${API_BASE}/engage.php`, { method: 'POST', body: formData });
            const data = await res.json();
            if (data.status === 'success') {
                showToast(data.message, 'success');
                commentForm.reset();
                openDocumentView(currentDocId); // Reload view
            } else {
                showToast(data.message, 'error');
            }
        } catch (err) {
            showToast('Error submitting comment.', 'error');
        }
    });
}

// Admin Logic
async function loadAdminPending() {
    const tbody = document.querySelector('#admin-pending-table tbody');
    if (!tbody) return;

    try {
        const res = await fetch(`${API_BASE}/admin.php?action=list_pending`);
        const data = await res.json();

        if (data.status === 'success') {
            tbody.innerHTML = '';
            if (data.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6">No pending documents.</td></tr>';
                return;
            }

            data.data.forEach(doc => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${doc.title}</td>
                    <td>${doc.username}</td>
                    <td>${doc.subject} / ${doc.tag}</td>
                    <td>${(doc.size / 1024 / 1024).toFixed(2)} MB</td>
                    <td><a href="${doc.file_path}" target="_blank">View File</a></td>
                    <td class="admin-actions">
                        <button class="btn btn-primary" onclick="adminAction(${doc.id}, 'approved')">Approve</button>
                        <button class="btn btn-secondary" onclick="openRejectionModal(${doc.id})">Reject</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }
    } catch (err) {
        showToast('Failed to load pending documents.', 'error');
    }
}

async function adminAction(id, status) {
    if (!confirm(`Are you sure you want to mark this document as ${status}?`)) return;

    const formData = new FormData();
    formData.append('action', 'update_status');
    formData.append('id', id);
    formData.append('status', status);

    try {
        const res = await fetch(`${API_BASE}/admin.php`, { method: 'POST', body: formData });
        const data = await res.json();
        
        if (data.status === 'success') {
            showToast(data.message, 'success');
            loadAdminPending();
        } else {
            showToast(data.message, 'error');
        }
    } catch (err) {
        showToast('Admin action failed.', 'error');
    }
}

if (document.getElementById('admin-pending-table')) {
    loadAdminPending();
}

// Profile Update Form
const profileForm = document.getElementById('profile-form');
if (profileForm) {
    profileForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(profileForm);
        formData.append('action', 'update');
        
        const submitBtn = profileForm.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerText;
        submitBtn.innerText = 'Saving...';
        submitBtn.disabled = true;

        try {
            const res = await fetch(`${API_BASE}/profile.php`, { method: 'POST', body: formData });
            const data = await res.json();
            
            if (data.status === 'success') {
                showToast(data.message, 'success');
            } else {
                showToast(data.message, 'error');
            }
        } catch (err) {
            showToast('Failed to update profile.', 'error');
        } finally {
            submitBtn.innerText = originalText;
            submitBtn.disabled = false;
        }
    });
}

// Faculty Management Logic (Principle Only)
async function loadFacultyList() {
    const tbody = document.querySelector('#faculty-table tbody');
    if (!tbody) return;

    try {
        const res = await fetch(`${API_BASE}/admin.php?action=list_faculty`);
        const data = await res.json();
        if (data.status === 'success') {
            tbody.innerHTML = '';
            if (data.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4">No faculty members found.</td></tr>';
                return;
            }
            const subjects = ['FCSN', 'DSDA', 'FCPP', 'Physics', 'EM-2'];
            data.data.forEach(fac => {
                const tr = document.createElement('tr');
                const additional = fac.additional_subjects ? fac.additional_subjects.split(',') : [];
                let options = subjects
                    .filter(s => s !== fac.primary_subject)
                    .map(s => `<option value="${s}" ${additional.includes(s) ? 'selected' : ''}>${s}</option>`)
                    .join('');
                
                tr.innerHTML = `
                    <td><a href="profile.php?user_id=${fac.id}" style="color: var(--accent-color); text-decoration: none; font-weight: 500;">${fac.username}</a></td>
                    <td>${fac.email}</td>
                    <td>
                        <span style="font-weight: 600;">Permanent Subject:</span> ${fac.primary_subject || 'None'}<br>
                        <span style="font-size: 0.85rem; color: var(--text-secondary);">Additional: ${fac.additional_subjects || 'None'}</span>
                    </td>
                    <td style="display:flex; gap:0.5rem; align-items:center;">
                        <select id="faculty-subject-${fac.id}" multiple style="padding:0.4rem; border-radius:4px; border:1px solid var(--border-color); height: 60px;">${options}</select>
                        <button class="btn btn-primary" onclick="updateFaculty(${fac.id})">Update</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }
    } catch (e) {
        showToast('Failed to load faculty list.', 'error');
    }
}

async function updateFaculty(id) {
    const select = document.getElementById(`faculty-subject-${id}`);
    const subjects = Array.from(select.selectedOptions).map(opt => opt.value);
    
    const formData = new FormData();
    formData.append('action', 'update_faculty');
    formData.append('id', id);
    formData.append('subjects', JSON.stringify(subjects));
    
    try {
        const res = await fetch(`${API_BASE}/admin.php`, { method: 'POST', body: formData });
        const data = await res.json();
        if (data.status === 'success') {
            showToast(data.message, 'success');
            loadFacultyList();
        } else {
            showToast(data.message, 'error');
        }
    } catch (e) {
        showToast('Update failed.', 'error');
    }
}

if (document.getElementById('faculty-table')) {
    loadFacultyList();
}

// Notifications Logic
document.addEventListener('DOMContentLoaded', () => {
    const navLinks = document.querySelector('.nav-links');
    if (navLinks && document.getElementById('logout-btn')) {
        fetchNotifications();
    }
});

function fetchNotifications() {
    fetch('api/fetch_notifications.php')
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                renderNotifications(data.data, data.unread_count);
            }
        })
        .catch(console.error);
}

function renderNotifications(notifications, unreadCount) {
    let notifWrapper = document.getElementById('notif-wrapper');
    if (!notifWrapper) {
        notifWrapper = document.createElement('div');
        notifWrapper.id = 'notif-wrapper';
        notifWrapper.className = 'notification-wrapper';
        
        notifWrapper.innerHTML = `
            <button class="notification-bell" id="notif-bell" onclick="toggleNotifications()">
                🔔
                <span class="unread-badge" id="notif-badge" style="display: none;">0</span>
            </button>
            <div class="notification-dropdown glass" id="notif-dropdown">
                <div class="notification-header">Notifications</div>
                <div id="notif-list" style="max-height: 300px; overflow-y: auto;"></div>
            </div>
        `;
        
        const navLinks = document.querySelector('.nav-links');
        const profileBtn = navLinks.querySelector('a[href="profile.php"]');
        if (profileBtn) {
            navLinks.insertBefore(notifWrapper, profileBtn);
        } else {
            navLinks.appendChild(notifWrapper);
        }
        
        document.addEventListener('click', (e) => {
            if (!notifWrapper.contains(e.target)) {
                const dropdown = document.getElementById('notif-dropdown');
                if (dropdown) dropdown.classList.remove('active');
            }
        });
    }

    const badge = document.getElementById('notif-badge');
    if (unreadCount > 0) {
        badge.textContent = unreadCount;
        badge.style.display = 'flex';
    } else {
        badge.style.display = 'none';
    }

    const list = document.getElementById('notif-list');
    if (notifications.length === 0) {
        list.innerHTML = '<div class="notification-item" style="text-align:center; color:var(--text-secondary);">No notifications</div>';
    } else {
        list.innerHTML = notifications.map(n => `
            <div class="notification-item ${n.is_read == 0 ? 'unread' : ''}">
                ${n.message}
                <span class="notification-date">${new Date(n.created_at).toLocaleString()}</span>
            </div>
        `).join('');
    }
}

window.toggleNotifications = function() {
    const dropdown = document.getElementById('notif-dropdown');
    dropdown.classList.toggle('active');
    
    const badge = document.getElementById('notif-badge');
    if (dropdown.classList.contains('active') && badge.style.display !== 'none') {
        fetch('api/mark_notifications_read.php')
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    badge.style.display = 'none';
                    document.querySelectorAll('.notification-item.unread').forEach(el => el.classList.remove('unread'));
                }
            });
    }
};

// Rejection Feedback Logic
window.openRejectionModal = function(id) {
    const docIdInput = document.getElementById('reject-doc-id');
    if (docIdInput) {
        docIdInput.value = id;
        document.getElementById('rejection-reason').value = '';
        openModal('rejection-modal');
    }
};

const rejectionForm = document.getElementById('rejection-form');
if (rejectionForm) {
    rejectionForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = document.getElementById('reject-doc-id').value;
        const reason = document.getElementById('rejection-reason').value;
        
        const formData = new FormData();
        formData.append('action', 'update_status');
        formData.append('id', id);
        formData.append('status', 'rejected');
        formData.append('rejection_reason', reason);

        try {
            const res = await fetch(`${API_BASE}/admin.php`, { method: 'POST', body: formData });
            const data = await res.json();
            
            if (data.status === 'success') {
                showToast(data.message, 'success');
                closeModal('rejection-modal');
                if (typeof loadAdminPending === 'function') loadAdminPending();
            } else {
                showToast(data.message, 'error');
            }
        } catch (err) {
            showToast('Rejection action failed.', 'error');
        }
    });
}

// My Uploads Logic
async function loadMyUploads() {
    const list = document.getElementById('my-uploads-list');
    if (!list) return;
    
    try {
        const res = await fetch(`${API_BASE}/documents.php?action=my_uploads`);
        const data = await res.json();
        
        if (data.status === 'success') {
            list.innerHTML = '';
            if (data.data.length === 0) {
                list.innerHTML = '<p style="color:var(--text-secondary);">You have not uploaded any documents yet.</p>';
                return;
            }
            
            data.data.forEach(doc => {
                const item = document.createElement('div');
                item.style = 'padding: 1rem; border-bottom: 1px solid var(--border-color);';
                
                let rejectionHtml = '';
                if (doc.status === 'rejected' && doc.rejection_reason) {
                    rejectionHtml = `
                        <div style="background: rgba(231, 76, 60, 0.1); border-left: 4px solid #e74c3c; padding: 1rem; margin-top: 0.5rem; border-radius: 0 4px 4px 0;">
                            <strong style="color: #c0392b;">Rejection Feedback:</strong> 
                            <span style="color: var(--text-primary);">${doc.rejection_reason}</span>
                        </div>
                    `;
                }
                
                let statusColor = doc.status === 'approved' ? 'var(--accent-color)' : (doc.status === 'rejected' ? '#e74c3c' : 'var(--text-secondary)');
                
                item.innerHTML = `
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <div style="font-weight:600; font-size:1.1rem; color:var(--text-primary);">${doc.title}</div>
                        <span class="badge" style="text-transform:capitalize; background-color:${statusColor}20; color:${statusColor}; border-color:${statusColor}40;">${doc.status}</span>
                    </div>
                    <div style="font-size:0.85rem; color:var(--text-secondary); margin-top:0.3rem;">
                        ${doc.subject} • ${doc.tag} • Uploaded on ${new Date(doc.created_at).toLocaleDateString()}
                    </div>
                    ${rejectionHtml}
                `;
                list.appendChild(item);
            });
        }
    } catch (e) {
        list.innerHTML = '<p style="color:#e74c3c;">Failed to load uploads.</p>';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('my-uploads-list')) {
        loadMyUploads();
    }
});

// Reporting Logic
window.openReportModal = function(id) {
    const docIdInput = document.getElementById('report-doc-id');
    if (docIdInput) {
        docIdInput.value = id;
        document.getElementById('report-form').reset();
        openModal('report-modal');
    }
};

const reportForm = document.getElementById('report-form');
if (reportForm) {
    reportForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(reportForm);
        
        try {
            const res = await fetch(`${API_BASE}/report_document.php`, { method: 'POST', body: formData });
            const data = await res.json();
            
            if (data.status === 'success') {
                showToast(data.message, 'success');
                closeModal('report-modal');
            } else {
                showToast(data.message, 'error');
            }
        } catch (err) {
            showToast('Failed to submit report.', 'error');
        }

// Reported Documents Logic
async function loadReportedDocuments() {
    const tbody = document.querySelector('#admin-reports-table tbody');
    if (!tbody) return;

    try {
        const res = await fetch(`${API_BASE}/admin.php?action=list_reports`);
        const data = await res.json();
        
        if (data.status === 'success') {
            tbody.innerHTML = '';
            if (data.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">No pending reports</td></tr>';
                return;
            }

            data.data.forEach(report => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>
                        <div><strong>${report.title}</strong></div>
                        <div style="font-size:0.85rem; color:var(--text-secondary);"><a href="${report.file_path}" target="_blank">View File</a></div>
                    </td>
                    <td>${report.reporter_username}</td>
                    <td>${report.reason}</td>
                    <td>${new Date(report.created_at).toLocaleDateString()}</td>
                    <td class="admin-actions">
                        <button class="btn btn-secondary" onclick="resolveReport(${report.id}, 'dismiss')">Dismiss</button>
                        <button class="btn btn-primary" style="background:#e74c3c;" onclick="resolveReport(${report.id}, 'delete')">Delete Document</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }
    } catch (err) {
        showToast('Failed to load reported documents.', 'error');
    }
}

async function resolveReport(reportId, action) {
    if (!confirm(`Are you sure you want to ${action} this report?${action === 'delete' ? ' This will permanently delete the document.' : ''}`)) return;

    const formData = new FormData();
    formData.append('action', 'resolve_report');
    formData.append('report_id', reportId);
    formData.append('resolution', action);

    try {
        const res = await fetch(`${API_BASE}/admin.php`, { method: 'POST', body: formData });
        const data = await res.json();
        
        if (data.status === 'success') {
            showToast(data.message, 'success');
            loadReportedDocuments();
        } else {
            showToast(data.message, 'error');
        }
    } catch (err) {
        showToast('Failed to resolve report.', 'error');
    }
}

if (document.getElementById('admin-reports-table')) {
    loadReportedDocuments();
}
    });
}
