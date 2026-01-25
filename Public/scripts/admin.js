let currentPage = 1;
const itemsPerPage = 8;
let totalItems = 0;

let currentSearch = '';
let currentRole = 'all';
let currentSort = 'DESC';

document.addEventListener('DOMContentLoaded', () => {
    fetchUsers(currentPage);
    fetchStats();
    setupFilters();
});

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
            <td style="text-align: right;">
                ${user.role !== 'admin' ?
            `<button class="btn-secondary btn-delete-user" onclick="deleteUser(${user.id})" title="Delete User">
                        <i class="fa-solid fa-trash"></i>
                    </button>`
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
    if(!confirm('Are you sure you want to ban/delete this user?')) return;

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

function getRoleClass(role) {
    if (!role) return 'role-player';
    return role.toLowerCase() === 'admin' ? 'role-admin' : 'role-player';
}