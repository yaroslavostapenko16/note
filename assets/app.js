/**
 * Note Application - Frontend JavaScript
 * Complete rewrite with correct API endpoints for Hostinger
 */

// API Configuration
const API_BASE = '/api';

const APP_CONFIG = {
    maxNoteLength: 50000,
    maxTitleLength: 500,
    debounceTime: 300
};

// Global State
let appState = {
    currentUser: null,
    currentNote: null,
    notes: [],
    labels: [],
    currentView: 'all',
    isDarkTheme: false,
    currentNoteColor: '#FFFFFF'
};

// Initialize Application
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

/**
 * Initialize Application
 */
function initializeApp() {
    checkAuthentication();
    loadUserPreferences();
    setupEventListeners();
}

/**
 * Setup Event Listeners
 */
function setupEventListeners() {
    // Close modals on background click
    document.getElementById('note-modal').addEventListener('click', function(e) {
        if (e.target === this) closeNoteModal();
    });
    
    document.getElementById('label-modal').addEventListener('click', function(e) {
        if (e.target === this) closeLabelModal();
    });
    
    document.getElementById('profile-modal').addEventListener('click', function(e) {
        if (e.target === this) closeProfileModal();
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        const menu = document.getElementById('user-menu');
        const btn = document.querySelector('.btn-icon');
        if (btn && menu && !btn.contains(e.target) && !menu.contains(e.target)) {
            menu.style.display = 'none';
        }
    });
    
    // Search with debounce
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('keyup', debounce(searchNotes, APP_CONFIG.debounceTime));
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') searchNotes();
        });
    }
    
    // New note title input
    const newNoteTitle = document.getElementById('new-note-title');
    if (newNoteTitle) {
        newNoteTitle.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') createNewNote();
        });
    }
}

/**
 * Check Authentication Status
 */
async function checkAuthentication() {
    try {
        const response = await fetch(`${API_BASE}/user`, {
            method: 'GET',
            credentials: 'include'
        });
        
        if (response.ok) {
            const data = await response.json();
            if (data.status === 'success') {
                appState.currentUser = data.data;
                showMainApp();
                await loadNotes('all');
                await loadLabels();
                return;
            }
        }
        
        showAuthScreen();
    } catch (error) {
        console.error('Auth check error:', error);
        showAuthScreen();
    }
}

/**
 * Show Auth Screen
 */
function showAuthScreen() {
    document.getElementById('auth-screen').style.display = 'flex';
    document.getElementById('main-app').style.display = 'none';
}

/**
 * Show Main App
 */
function showMainApp() {
    document.getElementById('auth-screen').style.display = 'none';
    document.getElementById('main-app').style.display = 'flex';
}

/**
 * Toggle Auth Form
 */
function toggleAuthForm() {
    document.getElementById('login-form').classList.toggle('active');
    document.getElementById('register-form').classList.toggle('active');
}

/**
 * Handle User Login
 */
async function handleLogin(event) {
    event.preventDefault();
    
    const email = document.getElementById('login-email').value.trim();
    const password = document.getElementById('login-password').value;
    
    if (!email || !password) {
        showToast('Please fill in all fields', 'error');
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}/auth/login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({ email, password })
        });
        
        const data = await response.json();
        
        if (response.ok && data.status === 'success') {
            appState.currentUser = data.data;
            document.getElementById('login-form').reset();
            showToast('Logged in successfully! Redirecting...', 'success');
            
            // Wait a moment for the toast to show, then redirect
            setTimeout(async () => {
                showMainApp();
                try {
                    await loadNotes('all');
                    await loadLabels();
                } catch (error) {
                    console.error('Error loading data:', error);
                }
            }, 500);
        } else {
            showToast(data.error || 'Login failed', 'error');
        }
    } catch (error) {
        console.error('Login error:', error);
        showToast('Login error. Please try again.', 'error');
    }
}

/**
 * Handle User Registration
 */
async function handleRegister(event) {
    event.preventDefault();
    
    const username = document.getElementById('register-username').value.trim();
    const email = document.getElementById('register-email').value.trim();
    const password = document.getElementById('register-password').value;
    const fullName = document.getElementById('register-fullname').value.trim();
    
    if (!username || !email || !password) {
        showToast('Please fill in required fields', 'error');
        return;
    }
    
    if (password.length < 6) {
        showToast('Password must be at least 6 characters', 'error');
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}/auth/register`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({ username, email, password, full_name: fullName })
        });
        
        const data = await response.json();
        
        if (response.ok && data.status === 'success') {
            appState.currentUser = data.data;
            document.getElementById('register-form').reset();
            showToast('Registration successful! Loading your notebook...', 'success');
            
            // Wait a moment then show the app
            setTimeout(async () => {
                showMainApp();
                try {
                    await loadNotes('all');
                    await loadLabels();
                } catch (error) {
                    console.error('Error loading data:', error);
                }
            }, 500);
        } else {
            showToast(data.error || 'Registration failed', 'error');
        }
    } catch (error) {
        console.error('Registration error:', error);
        showToast('Registration error. Please try again.', 'error');
    }
}

/**
 * Logout User
 */
async function logoutUser() {
    try {
        await fetch(`${API_BASE}/auth/logout`, {
            method: 'POST',
            credentials: 'include'
        });
        
        appState.currentUser = null;
        appState.notes = [];
        appState.labels = [];
        showToast('Logged out successfully', 'success');
        checkAuthentication();
    } catch (error) {
        console.error('Logout error:', error);
        showToast('Error logging out', 'error');
    }
}

/**
 * Open User Menu
 */
function openUserMenu() {
    const menu = document.getElementById('user-menu');
    menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
}

/**
 * Load Notes
 */
async function loadNotes(view = 'all') {
    try {
        appState.currentView = view;
        
        // Update sidebar active state
        document.querySelectorAll('.sidebar-item').forEach(item => {
            item.classList.remove('active');
        });
        const viewElement = document.querySelector(`[data-view="${view}"]`);
        if (viewElement) viewElement.classList.add('active');
        
        const params = new URLSearchParams();
        
        switch(view) {
            case 'pinned':
                params.append('sort', 'is_pinned');
                break;
            case 'archived':
                params.append('archived', 'true');
                break;
            default:
                params.append('sort', 'created_at');
        }
        
        const response = await fetch(`${API_BASE}/notes?${params.toString()}`, {
            method: 'GET',
            credentials: 'include'
        });
        
        const data = await response.json();
        
        if (response.ok && data.status === 'success') {
            appState.notes = data.data || [];
            renderNotes(appState.notes);
        } else {
            showToast('Failed to load notes', 'error');
        }
    } catch (error) {
        console.error('Load notes error:', error);
        showToast('Error loading notes', 'error');
    }
}

/**
 * Render Notes Grid
 */
function renderNotes(notes) {
    const grid = document.getElementById('notes-grid');
    
    if (!notes || notes.length === 0) {
        grid.innerHTML = '<div class="empty-state"><p>No notes yet. Create your first note!</p></div>';
        return;
    }
    
    grid.innerHTML = notes.map(note => `
        <div class="note-card" style="background-color: ${note.color}" onclick="editNote('${note.note_id}')">
            <div class="note-card-title">${escapeHtml(note.title || 'Untitled')}</div>
            <div class="note-card-content">${escapeHtml(note.content || '').substring(0, 200)}</div>
            <div class="note-card-meta">
                <span>${formatDate(note.created_at)}</span>
                <div class="note-card-actions" onclick="event.stopPropagation()">
                    <button class="note-card-action" onclick="togglePinNote('${note.note_id}', event)">
                        ${note.is_pinned ? '📌' : '📍'}
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

/**
 * Create New Note
 */
async function createNewNote() {
    const title = document.getElementById('new-note-title').value.trim();
    
    if (!title) {
        showToast('Please enter a note title', 'info');
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}/notes`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ 
                title: title,
                content: '' 
            })
        });
        
        const data = await response.json();
        
        if (response.ok && data.status === 'success') {
            document.getElementById('new-note-title').value = '';
            showToast('Note created successfully', 'success');
            await loadNotes(appState.currentView);
        } else {
            showToast(data.error || 'Failed to create note', 'error');
        }
    } catch (error) {
        console.error('Create note error:', error);
        showToast('Error creating note', 'error');
    }
}

/**
 * Edit Note
 */
function editNote(noteId) {
    const note = appState.notes.find(n => n.note_id === noteId);
    if (!note) return;
    
    appState.currentNote = note;
    appState.currentNoteColor = note.color;
    
    document.getElementById('modal-note-title').value = note.title || '';
    document.getElementById('modal-note-content').value = note.content || '';
    
    // Update color picker
    document.querySelectorAll('.color-option').forEach(opt => {
        opt.classList.remove('selected');
        if (opt.style.backgroundColor === note.color) {
            opt.classList.add('selected');
        }
    });
    
    openNoteModal();
}

/**
 * Open Note Modal
 */
function openNoteModal() {
    const modal = document.getElementById('note-modal');
    modal.style.display = 'flex';
    modal.classList.add('active');
    const content = document.getElementById('modal-note-content');
    if (content) content.focus();
}

/**
 * Close Note Modal
 */
function closeNoteModal() {
    const modal = document.getElementById('note-modal');
    modal.style.display = 'none';
    modal.classList.remove('active');
    appState.currentNote = null;
}

/**
 * Save Note
 */
async function saveCurrentNote() {
    if (!appState.currentNote) return;
    
    const title = document.getElementById('modal-note-title').value.trim();
    const content = document.getElementById('modal-note-content').value;
    
    if (!title && !content) {
        showToast('Note cannot be empty', 'error');
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}/notes`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({
                note_id: appState.currentNote.note_id,
                title: title || 'Untitled Note',
                content: content,
                color: appState.currentNoteColor
            })
        });
        
        const data = await response.json();
        
        if (response.ok && data.status === 'success') {
            showToast('Note saved successfully', 'success');
            closeNoteModal();
            await loadNotes(appState.currentView);
        } else {
            showToast(data.error || 'Failed to save note', 'error');
        }
    } catch (error) {
        console.error('Save note error:', error);
        showToast('Error saving note', 'error');
    }
}

/**
 * Delete Note
 */
async function deleteCurrentNote() {
    if (!appState.currentNote) return;
    
    if (!confirm('Are you sure you want to delete this note?')) return;
    
    try {
        const response = await fetch(`${API_BASE}/notes`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({
                note_id: appState.currentNote.note_id
            })
        });
        
        const data = await response.json();
        
        if (response.ok && data.status === 'success') {
            showToast('Note deleted successfully', 'success');
            closeNoteModal();
            await loadNotes(appState.currentView);
        } else {
            showToast(data.error || 'Failed to delete note', 'error');
        }
    } catch (error) {
        console.error('Delete note error:', error);
        showToast('Error deleting note', 'error');
    }
}

/**
 * Toggle Pin Note
 */
async function togglePinNote(noteId, event) {
    event.stopPropagation();
    
    const note = appState.notes.find(n => n.note_id === noteId);
    if (!note) return;
    
    try {
        const response = await fetch(`${API_BASE}/notes`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({
                note_id: noteId,
                is_pinned: note.is_pinned ? 0 : 1
            })
        });
        
        const data = await response.json();
        
        if (response.ok && data.status === 'success') {
            await loadNotes(appState.currentView);
        }
    } catch (error) {
        console.error('Pin note error:', error);
    }
}

/**
 * Change Note Color
 */
function changeNoteColor(color) {
    appState.currentNoteColor = color;
    
    // Update color picker UI
    document.querySelectorAll('.color-option').forEach(opt => {
        opt.classList.remove('selected');
        if (opt.style.backgroundColor === color) {
            opt.classList.add('selected');
        }
    });
}

/**
 * Share Note
 */
async function shareCurrentNote() {
    if (!appState.currentNote) return;
    
    try {
        const response = await fetch(`${API_BASE}/share`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({
                note_id: appState.currentNote.note_id,
                share_type: 'link'
            })
        });
        
        const data = await response.json();
        
        if (response.ok && data.status === 'success') {
            const shareUrl = data.data.share_url;
            if (navigator.share) {
                navigator.share({
                    title: appState.currentNote.title,
                    text: 'Check out my note',
                    url: shareUrl
                });
            } else {
                // Fallback: copy to clipboard
                navigator.clipboard.writeText(shareUrl);
                showToast('Share link copied to clipboard', 'success');
            }
        } else {
            showToast(data.error || 'Failed to share note', 'error');
        }
    } catch (error) {
        console.error('Share note error:', error);
        showToast('Error sharing note', 'error');
    }
}

/**
 * Search Notes
 */
async function searchNotes() {
    const query = document.getElementById('search-input').value.trim();
    
    if (!query || query.length < 2) {
        await loadNotes(appState.currentView);
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}/search?q=${encodeURIComponent(query)}`, {
            method: 'GET',
            credentials: 'include'
        });
        
        const data = await response.json();
        
        if (response.ok && data.status === 'success') {
            renderNotes(data.data || []);
        }
    } catch (error) {
        console.error('Search error:', error);
        showToast('Error searching notes', 'error');
    }
}

/**
 * Load Labels
 */
async function loadLabels() {
    try {
        const response = await fetch(`${API_BASE}/labels`, {
            method: 'GET',
            credentials: 'include'
        });
        
        const data = await response.json();
        
        if (response.ok && data.status === 'success') {
            appState.labels = data.data || [];
            renderLabels(appState.labels);
        }
    } catch (error) {
        console.error('Load labels error:', error);
    }
}

/**
 * Render Labels
 */
function renderLabels(labels) {
    const container = document.getElementById('labels-list');
    
    if (!labels || labels.length === 0) {
        container.innerHTML = '';
        return;
    }
    
    container.innerHTML = labels.map(label => `
        <div class="label-item" style="--color: ${label.color}" onclick="filterByLabel('${label.label_id}')">
            ${escapeHtml(label.name)}
        </div>
    `).join('');
}

/**
 * Open Label Dialog
 */
function openLabelDialog() {
    document.getElementById('label-modal').style.display = 'flex';
    document.getElementById('label-modal').classList.add('active');
    const input = document.getElementById('label-name');
    if (input) input.focus();
}

/**
 * Close Label Modal
 */
function closeLabelModal() {
    const modal = document.getElementById('label-modal');
    modal.style.display = 'none';
    modal.classList.remove('active');
    document.getElementById('label-name').value = '';
    document.getElementById('label-color').value = '#808080';
}

/**
 * Select Label Color
 */
function selectLabelColor(color) {
    document.getElementById('label-color').value = color;
    document.querySelectorAll('.modal-small .color-option').forEach(opt => {
        opt.classList.remove('selected');
    });
    if (event && event.target) {
        event.target.classList.add('selected');
    }
}

/**
 * Create Label
 */
async function createLabel() {
    const name = document.getElementById('label-name').value.trim();
    const color = document.getElementById('label-color').value;
    
    if (!name) {
        showToast('Label name required', 'error');
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}/labels`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({ name, color })
        });
        
        const data = await response.json();
        
        if (response.ok && data.status === 'success') {
            showToast('Label created successfully', 'success');
            closeLabelModal();
            await loadLabels();
        } else {
            showToast(data.error || 'Failed to create label', 'error');
        }
    } catch (error) {
        console.error('Create label error:', error);
        showToast('Error creating label', 'error');
    }
}

/**
 * Filter By Label
 */
async function filterByLabel(labelId) {
    // Implementation for filtering notes by label
    showToast('Label filtering coming soon', 'info');
}

/**
 * Open User Profile
 */
async function openProfile() {
    try {
        const response = await fetch(`${API_BASE}/user`, {
            method: 'GET',
            credentials: 'include'
        });
        
        const data = await response.json();
        
        if (response.ok && data.status === 'success') {
            const user = data.data;
            document.getElementById('profile-fullname').value = user.full_name || '';
            document.getElementById('profile-bio').value = user.bio || '';
            document.getElementById('profile-theme').value = user.theme || 'light';
            
            document.getElementById('profile-modal').style.display = 'flex';
            document.getElementById('profile-modal').classList.add('active');
        }
    } catch (error) {
        console.error('Open profile error:', error);
        showToast('Error loading profile', 'error');
    }
}

/**
 * Close Profile Modal
 */
function closeProfileModal() {
    const modal = document.getElementById('profile-modal');
    modal.style.display = 'none';
    modal.classList.remove('active');
}

/**
 * Save Profile
 */
async function saveProfile() {
    const fullName = document.getElementById('profile-fullname').value.trim();
    const bio = document.getElementById('profile-bio').value.trim();
    const theme = document.getElementById('profile-theme').value;
    
    try {
        const response = await fetch(`${API_BASE}/user`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({ full_name: fullName, bio, theme })
        });
        
        const data = await response.json();
        
        if (response.ok && data.status === 'success') {
            appState.isDarkTheme = theme === 'dark';
            document.body.classList.toggle('dark-theme', appState.isDarkTheme);
            localStorage.setItem('theme', theme);
            
            showToast('Profile updated successfully', 'success');
            closeProfileModal();
        } else {
            showToast(data.error || 'Failed to update profile', 'error');
        }
    } catch (error) {
        console.error('Save profile error:', error);
        showToast('Error updating profile', 'error');
    }
}

/**
 * Load User Preferences
 */
function loadUserPreferences() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    appState.isDarkTheme = savedTheme === 'dark';
    document.body.classList.toggle('dark-theme', appState.isDarkTheme);
}

/**
 * Show Toast Notification
 */
function showToast(message, type = 'info') {
    const toast = document.getElementById('toast');
    if (!toast) return;
    
    toast.textContent = message;
    toast.className = `toast show ${type}`;
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

/**
 * Format Date
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    const today = new Date();
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);
    
    if (date.toDateString() === today.toDateString()) {
        return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
    } else if (date.toDateString() === yesterday.toDateString()) {
        return 'Yesterday';
    } else if (date.getFullYear() === today.getFullYear()) {
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    } else {
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: '2-digit' });
    }
}

/**
 * Escape HTML
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Debounce Function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
