let currentPage = 1;
const itemsPerPage = 8;
let totalItems = 0;

let currentSearch = '';
let currentRole = 'all';
let currentSort = 'DESC';
let modalCallback = null;

document.addEventListener('DOMContentLoaded', () => {
    fetchUsers(currentPage);
    fetchStats();
    setupFilters();
    setupModal();

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.custom-dropdown')) {
            document.querySelectorAll('.custom-dropdown').forEach(el => el.classList.remove('active'));
        }
    });
});

function setupModal() {
    const modal = document.getElementById('confirmationModal');
    const btnCancel = document.getElementById('btnCancelModal');
    const btnConfirm = document.getElementById('btnConfirmModal');

    btnCancel.addEventListener('click', closeModal);
    
    btnConfirm.addEventListener('click', () => {
        if (modalCallback) modalCallback();
        closeModal();
    });

    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });
}

function showModal(title, message, type, callback) {
    const modal = document.getElementById('confirmationModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalMessage = document.getElementById('modalMessage');
    const modalIconWrapper = document.getElementById('modalIconWrapper');
    const modalIcon = document.getElementById('modalIcon');
    const btnConfirm = document.getElementById('btnConfirmModal');

    modalTitle.innerText = title;
    modalMessage.innerText = message;
    modalCallback = callback;

    modalIconWrapper.className = 'modal-icon-wrapper';
    btnConfirm.className = 'btn-primary modal-btn';
    
    if (type === 'delete') {
        modalIconWrapper.classList.add('danger'); // Red
        modalIconWrapper.style.backgroundColor = '#fee2e2';
        modalIconWrapper.style.color = '#dc2626';
        modalIcon.className = 'fa-solid fa-trash-can';
        btnConfirm.classList.add('btn-danger');
        btnConfirm.innerText = 'Delete';
    } else {
        modalIconWrapper.classList.add('warning'); // Yellow/Orange
        modalIconWrapper.style.backgroundColor = '#fef3c7';
        modalIconWrapper.style.color = '#d97706';
        modalIcon.className = 'fa-solid fa-triangle-exclamation';
        btnConfirm.innerText = 'Confirm';
    }

    modal.classList.add('active');
}

function closeModal() {
    const modal = document.getElementById('confirmationModal');
    modal.classList.remove('active');
    modalCallback = null;
}

function setupFilters() {

    const searchInput = document.getElementById('searchInput');
    let debounceTimer;
    searchInput.addEventListener('input', (e) => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            currentSearch = e.target.value;
            currentPage = 1;
            fetchUsers(currentPage);
        }, 300);
    });

    const roleFilter = document.getElementById('roleFilter');
    roleFilter.addEventListener('change', (e) => {
        currentRole = e.target.value;
        currentPage = 1;
        fetchUsers(currentPage);
    });

    const sortBtn = document.querySelector('.btn-sort');
    sortBtn.addEventListener('click', () => {
        if (currentSort === 'DESC') {
            currentSort = 'ASC';
            sortBtn.innerHTML = '<i class="fa-solid fa-arrow-up-short-wide"></i> Sort: Oldest';
        } else {
            currentSort = 'DESC';
            sortBtn.innerHTML = '<i class="fa-solid fa-arrow-down-short-wide"></i> Sort: Newest';
        }
        fetchUsers(currentPage);
    });
}

function fetchStats() {
    fetch('/admin/stats')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error loading stats:', data.error);
                return;
            }
            document.getElementById('stat-total-users').innerText = data.totalUsers;
            document.getElementById('stat-active-players').innerText = data.activePlayers;
            document.getElementById('stat-admins').innerText = data.admins;
        })
        .catch(err => console.error('Network error loading stats:', err));
}

function fetchUsers(page = 1) {
    const tbody = document.getElementById('usersTableBody');
    tbody.innerHTML = '<tr><td colspan="4" class="loading-cell">Loading users...</td></tr>';

    const params = new URLSearchParams({
        page: page,
        limit: itemsPerPage,
        search: currentSearch,
        role: currentRole,
        sort: currentSort
    });

    fetch(`/admin/users?${params.toString()}`)
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    throw new Error(`Server error: ${response.status} ${text}`);
                });
            }
            return response.json();
        })
        .then(data => {
            const users = data.users;
            const pagination = data.pagination;

            console.log("Loaded users:", users);

            currentPage = pagination.currentPage;
            totalItems = pagination.totalItems;

            renderTable(users);
            updatePaginationInfo((currentPage - 1) * itemsPerPage + 1, Math.min(currentPage * itemsPerPage, totalItems), totalItems);
            renderPaginationControls(pagination.totalPages);
        })
        .catch(error => {
            console.error('Error fetching users:', error);
            tbody.innerHTML = `<tr><td colspan="4" class="error-cell">Error loading users: ${error.message}</td></tr>`;
        });
}

function renderTable(users) {
    const tbody = document.getElementById('usersTableBody');
    tbody.innerHTML = '';

    if (!Array.isArray(users) || users.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="no-users-cell">No users found</td></tr>';
        return;
    }

    users.forEach(user => {
        const tr = document.createElement('tr');

        const avatarSrc = user.avatar ? user.avatar : `https://ui-avatars.com/api/?name=${user.username}&background=random`;
        const gamesPlayed = user.games_played !== undefined ? user.games_played : 0;

        const roleClass = getRoleClass(user.role);

        tr.innerHTML = `
            <td>
                <div class="user-cell">
                    <img src="${avatarSrc}" alt="${user.username}" class="user-avatar-img">
                    <div class="user-info">
                        <span class="user-nick">${user.username}</span>
                        <span class="user-email">${user.email}</span>
                    </div>
                </div>
            </td>
            <td>
                <span class="role-badge ${roleClass}">${user.role}</span>
            </td>
            <td style="font-weight: 500; color: var(--text-main);">
                ${gamesPlayed}
            </td>
            <td class="actions-cell">
                ${user.role !== 'admin' ?
            `<div class="action-group">
                        <div class="custom-dropdown" id="dropdown-${user.id}">
                            <div class="dropdown-selected" onclick="toggleDropdown(${user.id})">
                                <span>${user.role === 'admin' ? 'Admin' : 'Player'}</span>
                                <i class="fa-solid fa-chevron-down"></i>
                            </div>
                            <div class="dropdown-options">
                                <div class="dropdown-option ${user.role === 'user' ? 'selected' : ''}" onclick="selectRole(${user.id}, 'user')">
                                    <i class="fa-solid fa-user"></i> Player
                                </div>
                                <div class="dropdown-option ${user.role === 'admin' ? 'selected' : ''}" onclick="selectRole(${user.id}, 'admin')">
                                    <i class="fa-solid fa-shield-halved"></i> Admin
                                </div>
                            </div>
                        </div>
                        <button class="btn-secondary btn-delete-user" onclick="deleteUser(${user.id})" title="Delete User">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>`
            :
            `<span class="protected-badge">
                        <i class="fa-solid fa-shield-halved"></i> Protected
                    </span>`
        }
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function toggleDropdown(userId) {
    document.querySelectorAll('.custom-dropdown').forEach(el => {
        if (el.id !== `dropdown-${userId}`) {
            el.classList.remove('active');
        }
    });
    
    const dropdown = document.getElementById(`dropdown-${userId}`);
    dropdown.classList.toggle('active');
}

function selectRole(userId, newRole) {
    const dropdown = document.getElementById(`dropdown-${userId}`);
    dropdown.classList.remove('active');
    
    updateUserRole(userId, newRole);
}

function updatePaginationInfo(start, end, total) {
    const info = document.querySelector('.pagination-info');
    if(info) {
        if (total === 0) info.innerHTML = 'No users found';
        else info.innerHTML = `Showing <strong>${start}</strong> to <strong>${end}</strong> of <strong>${total}</strong> users`;
    }
}

function renderPaginationControls(totalPages) {
    const container = document.querySelector('.pagination-buttons');
    if (!container) return;
    container.innerHTML = '';

    if (totalPages <= 1) return;

    const prevBtn = document.createElement('button');
    prevBtn.className = 'btn-secondary page-btn';
    prevBtn.innerHTML = '<i class="fa-solid fa-chevron-left"></i>';
    prevBtn.disabled = currentPage === 1;
    prevBtn.onclick = () => {
        if (currentPage > 1) {
            fetchUsers(currentPage - 1);
        }
    };
    container.appendChild(prevBtn);

    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
            const btn = document.createElement('button');
            btn.className = `btn-secondary page-btn ${i === currentPage ? 'active' : ''}`;
            btn.innerText = i;
            btn.onclick = () => {
                fetchUsers(i);
            };
            container.appendChild(btn);
        } else if (
            (i === currentPage - 2 && i > 1) ||
            (i === currentPage + 2 && i < totalPages)
        ) {
            if (container.lastChild && container.lastChild.className === 'dots') continue;

            const span = document.createElement('span');
            span.className = 'dots';
            span.innerText = '...';
            container.appendChild(span);
        }
    }

    const nextBtn = document.createElement('button');
    nextBtn.className = 'btn-secondary page-btn';
    nextBtn.innerHTML = '<i class="fa-solid fa-chevron-right"></i>';
    nextBtn.disabled = currentPage === totalPages;
    nextBtn.onclick = () => {
        if (currentPage < totalPages) {
            fetchUsers(currentPage + 1);
        }
    };
    container.appendChild(nextBtn);
}

function deleteUser(id) {
    showModal(
        'Delete User',
        'Are you sure you want to delete this user? This action cannot be undone.',
        'delete',
        () => {
            fetch('/admin/delete-user', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: id })
            }).then(response => {
                if(response.ok) {
                    fetchUsers(currentPage);
                } else {
                    alert('Error deleting user');
                }
            }).catch(err => {
                console.error(err);
                alert('Network error when deleting user');
            });
        }
    );
}

function updateUserRole(id, newRole) {
    showModal(
        'Change Role',
        `Are you sure you want to change this user's role to ${newRole}?`,
        'warning',
        () => {
            fetch('/admin/change-role', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: id, role: newRole })
            }).then(response => {
                if(response.ok) {
                    fetchUsers(currentPage);
                } else {
                    return response.json().then(data => {
                        alert(data.message || 'Error changing role');
                        fetchUsers(currentPage);
                    });
                }
            }).catch(err => {
                console.error(err);
                alert('Network error when changing role');
                fetchUsers(currentPage);
            });
        }
    );
}

function getRoleClass(role) {
    if (!role) return 'role-player';
    return role.toLowerCase() === 'admin' ? 'role-admin' : 'role-player';
}