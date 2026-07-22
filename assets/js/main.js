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
    const category = document.getElementById('category-filter')?.value || '';
    const tag = document.getElementById('tag-filter')?.value || '';

    try {
        const res = await fetch(`${API_BASE}/documents.php?action=list&search=${encodeURIComponent(search)}&category=${encodeURIComponent(category)}&tag=${encodeURIComponent(tag)}`);
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
                    <div class="card-title">${doc.title}</div>
                    <div class="card-meta">
                        <span>By ${doc.username}</span>
                        <span>★ ${parseFloat(doc.avg_rating).toFixed(1)}</span>
                    </div>
                    <div class="card-meta" style="margin-top:0.5rem;">
                        <span class="badge">${doc.category}</span>
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
const categoryFilter = document.getElementById('category-filter');
const tagFilter = document.getElementById('tag-filter');

if (searchInput) {
    let debounceTimer;
    searchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(loadDocuments, 500);
    });
}
if (categoryFilter) categoryFilter.addEventListener('change', loadDocuments);
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
                    <td>${doc.category} / ${doc.tag}</td>
                    <td>${(doc.size / 1024 / 1024).toFixed(2)} MB</td>
                    <td><a href="${doc.file_path}" target="_blank">View File</a></td>
                    <td class="admin-actions">
                        <button class="btn btn-primary" onclick="adminAction(${doc.id}, 'approved')">Approve</button>
                        <button class="btn btn-secondary" onclick="adminAction(${doc.id}, 'rejected')">Reject</button>
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
