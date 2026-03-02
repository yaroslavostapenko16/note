<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Note - Online notebook application similar to Google Keep. Create, organize, and share notes easily.">
    <meta name="keywords" content="notes, notebook, google keep alternative, online notes, task management">
    <meta name="author" content="Note Application Team">
    <meta property="og:title" content="Note - Online Notebook">
    <meta property="og:description" content="Create and organize your notes in the cloud">
    <meta property="og:image" content="https://note.websweos.com/assets/og-image.png">
    <meta property="og:url" content="https://note.websweos.com">
    <meta name="robots" content="index, follow">
    <meta name="canonical" content="https://note.websweos.com">
    <title>Note - Online Notebook | Create, Organize & Share Notes</title>
    <link rel="stylesheet" href="/assets/style.css">
    <link rel="icon" type="image/x-icon" href="/assets/favicon.ico">
</head>
<body>
    <div id="app">
        <!-- Authentication Screen -->
        <div id="auth-screen" class="auth-container">
            <div class="auth-box">
                <div class="auth-logo">
                    <h1>📝 Note</h1>
                    <p>Online Notebook</p>
                </div>
                
                <div id="login-form" class="auth-form active">
                    <h2>Login</h2>
                    <form onsubmit="handleLogin(event)">
                        <div class="form-group">
                            <label for="login-email">Email</label>
                            <input type="email" id="login-email" required>
                        </div>
                        <div class="form-group">
                            <label for="login-password">Password</label>
                            <input type="password" id="login-password" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Login</button>
                    </form>
                    <p class="auth-toggle">Don't have account? <a href="#" onclick="toggleAuthForm()">Register</a></p>
                </div>
                
                <div id="register-form" class="auth-form">
                    <h2>Register</h2>
                    <form onsubmit="handleRegister(event)">
                        <div class="form-group">
                            <label for="register-username">Username</label>
                            <input type="text" id="register-username" required>
                        </div>
                        <div class="form-group">
                            <label for="register-email">Email</label>
                            <input type="email" id="register-email" required>
                        </div>
                        <div class="form-group">
                            <label for="register-password">Password</label>
                            <input type="password" id="register-password" minlength="6" required>
                        </div>
                        <div class="form-group">
                            <label for="register-fullname">Full Name (optional)</label>
                            <input type="text" id="register-fullname">
                        </div>
                        <button type="submit" class="btn btn-primary">Register</button>
                    </form>
                    <p class="auth-toggle">Already have account? <a href="#" onclick="toggleAuthForm()">Login</a></p>
                </div>
            </div>
        </div>
        
        <!-- Main App Screen -->
        <div id="main-app" class="main-container" style="display: none;">
            <!-- Header -->
            <header class="app-header">
                <div class="header-left">
                    <h1 class="app-title">📝 Note</h1>
                </div>
                
                <div class="header-center">
                    <div class="search-box">
                        <input type="text" id="search-input" placeholder="Search notes...">
                        <button onclick="searchNotes()">🔍</button>
                    </div>
                </div>
                
                <div class="header-right">
                    <button class="btn-icon" onclick="openUserMenu()">👤</button>
                    <div id="user-menu" class="dropdown-menu" style="display: none;">
                        <a href="#" onclick="openProfile()">Profile</a>
                        <a href="#" onclick="logoutUser()">Logout</a>
                    </div>
                </div>
            </header>
            
            <!-- Main Content -->
            <div class="app-content">
                <!-- Sidebar -->
                <aside class="sidebar">
                    <div class="sidebar-section">
                        <h3>Views</h3>
                        <a href="#" onclick="loadNotes('all')" class="sidebar-item active" data-view="all">
                            📄 All Notes
                        </a>
                        <a href="#" onclick="loadNotes('pinned')" class="sidebar-item" data-view="pinned">
                            📌 Pinned
                        </a>
                        <a href="#" onclick="loadNotes('archived')" class="sidebar-item" data-view="archived">
                            📚 Archived
                        </a>
                    </div>
                    
                    <div class="sidebar-section">
                        <h3>Labels</h3>
                        <div id="labels-list" class="labels-container"></div>
                        <button class="btn-small" onclick="openLabelDialog()">+ New Label</button>
                    </div>
                </aside>
                
                <!-- Notes Area -->
                <main class="notes-area">
                    <!-- New Note Section -->
                    <div class="new-note-section">
                        <div class="note-input-area">
                            <input type="text" id="new-note-title" placeholder="Take a note..." class="note-input">
                            <button class="btn btn-create" onclick="createNewNote()">Create Note</button>
                        </div>
                    </div>
                    
                    <!-- Notes Grid -->
                    <div id="notes-grid" class="notes-grid">
                        <div class="empty-state">
                            <p>No notes yet. Create your first note!</p>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>
    
    <!-- Modal: Note Editor -->
    <div id="note-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <input type="text" id="modal-note-title" placeholder="Note Title" class="modal-title">
                <button class="btn-close" onclick="closeNoteModal()">&times;</button>
            </div>
            <div class="modal-body">
                <textarea id="modal-note-content" placeholder="Note content..."></textarea>
                <div class="note-meta">
                    <div class="color-picker">
                        <label>Color:</label>
                        <div id="color-options" class="color-options">
                            <div class="color-option" style="background-color: #FFFFFF;" onclick="changeNoteColor('#FFFFFF')"></div>
                            <div class="color-option" style="background-color: #FFF8DC;" onclick="changeNoteColor('#FFF8DC')"></div>
                            <div class="color-option" style="background-color: #FFE4E1;" onclick="changeNoteColor('#FFE4E1')"></div>
                            <div class="color-option" style="background-color: #E0FFFF;" onclick="changeNoteColor('#E0FFFF')"></div>
                            <div class="color-option" style="background-color: #E0FFE0;" onclick="changeNoteColor('#E0FFE0')"></div>
                            <div class="color-option" style="background-color: #FFFACD;" onclick="changeNoteColor('#FFFACD')"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="deleteCurrentNote()">Delete</button>
                <button class="btn btn-secondary" onclick="shareCurrentNote()">Share</button>
                <button class="btn btn-primary" onclick="saveCurrentNote()">Save</button>
            </div>
        </div>
    </div>
    
    <!-- Modal: Label Dialog -->
    <div id="label-modal" class="modal" style="display: none;">
        <div class="modal-content modal-small">
            <div class="modal-header">
                <h2>Create New Label</h2>
                <button class="btn-close" onclick="closeLabelModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Label Name</label>
                    <input type="text" id="label-name" placeholder="Enter label name">
                </div>
                <div class="form-group">
                    <label>Color</label>
                    <div class="color-options">
                        <div class="color-option" style="background-color: #808080;" onclick="selectLabelColor('#808080')"></div>
                        <div class="color-option" style="background-color: #FF6B6B;" onclick="selectLabelColor('#FF6B6B')"></div>
                        <div class="color-option" style="background-color: #4ECDC4;" onclick="selectLabelColor('#4ECDC4')"></div>
                        <div class="color-option" style="background-color: #45B7D1;" onclick="selectLabelColor('#45B7D1')"></div>
                        <div class="color-option" style="background-color: #FFA07A;" onclick="selectLabelColor('#FFA07A')"></div>
                    </div>
                    <input type="hidden" id="label-color" value="#808080">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeLabelModal()">Cancel</button>
                <button class="btn btn-primary" onclick="createLabel()">Create</button>
            </div>
        </div>
    </div>
    
    <!-- Modal: Profile -->
    <div id="profile-modal" class="modal" style="display: none;">
        <div class="modal-content modal-small">
            <div class="modal-header">
                <h2>User Profile</h2>
                <button class="btn-close" onclick="closeProfileModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" id="profile-fullname" placeholder="Enter your full name">
                </div>
                <div class="form-group">
                    <label>Bio</label>
                    <textarea id="profile-bio" placeholder="Tell us about yourself"></textarea>
                </div>
                <div class="form-group">
                    <label>Theme</label>
                    <select id="profile-theme">
                        <option value="light">Light</option>
                        <option value="dark">Dark</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeProfileModal()">Cancel</button>
                <button class="btn btn-primary" onclick="saveProfile()">Save</button>
            </div>
        </div>
    </div>
    
    <!-- Toast Notification -->
    <div id="toast" class="toast"></div>
    
    <script src="/assets/app.js"></script>
</body>
</html>
