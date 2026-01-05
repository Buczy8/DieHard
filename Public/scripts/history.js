document.addEventListener('DOMContentLoaded', () => {
    // Pobierz parametr page z URL, jeśli istnieje (dla zachowania stanu przy odświeżaniu)
    const urlParams = new URLSearchParams(window.location.search);
    const initialPage = parseInt(urlParams.get('page')) || 1;
    
    fetchHistory(initialPage);
});

function fetchHistory(page) {
    const tbody = document.getElementById('history-table-body');
    tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding: 20px; color: var(--text-secondary);">Loading history...</td></tr>';

    fetch(`/api/history?page=${page}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            renderHistoryTable(data.games);
            renderPagination(data.pagination);
            
            // Aktualizuj URL bez przeładowania strony
            const newUrl = `${window.location.pathname}?page=${page}`;
            window.history.pushState({path: newUrl}, '', newUrl);
        })
        .catch(error => {
            console.error('Error fetching history:', error);
            tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding: 20px; color: var(--color-error-text);">Error loading history</td></tr>';
        });
}

function renderHistoryTable(games) {
    const tbody = document.getElementById('history-table-body');
    tbody.innerHTML = '';

    if (!games || games.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="4" class="empty-state">
                    No games played yet.
                    <a href="/dicegame" class="text-link">Start playing!</a>
                </td>
            </tr>
        `;
        return;
    }

    games.forEach(game => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="col-date">${game.playedAtFormatted}</td>
            <td class="col-opponent">
                <div class="opponent-info">
                    <span class="avatar avatar-blue">${game.opponentInitials}</span>
                    <span>${escapeHtml(game.opponentName)}</span>
                </div>
            </td>
            <td><span class="badge ${game.resultBadgeClass}">${game.resultLabel}</span></td>
            <td class="col-score">${game.score}</td>
        `;
        tbody.appendChild(tr);
    });
}

function renderPagination(pagination) {
    const container = document.getElementById('pagination-container');
    container.innerHTML = '';

    if (pagination.totalPages <= 1) return;

    const currentPage = pagination.currentPage;
    const totalPages = pagination.totalPages;

    // Prev Button
    const prevLink = document.createElement(currentPage > 1 ? 'a' : 'span');
    prevLink.className = `page-link ${currentPage <= 1 ? 'disabled' : ''}`;
    prevLink.innerHTML = '←';
    if (currentPage > 1) {
        prevLink.href = '#';
        prevLink.onclick = (e) => {
            e.preventDefault();
            fetchHistory(currentPage - 1);
        };
    }
    container.appendChild(prevLink);

    // Page Numbers
    for (let i = 1; i <= totalPages; i++) {
        const pageLink = document.createElement('a');
        pageLink.className = `page-link ${i === currentPage ? 'active' : ''}`;
        pageLink.innerHTML = i;
        pageLink.href = '#';
        pageLink.onclick = (e) => {
            e.preventDefault();
            fetchHistory(i);
        };
        container.appendChild(pageLink);
    }

    // Next Button
    const nextLink = document.createElement(currentPage < totalPages ? 'a' : 'span');
    nextLink.className = `page-link ${currentPage >= totalPages ? 'disabled' : ''}`;
    nextLink.innerHTML = '→';
    if (currentPage < totalPages) {
        nextLink.href = '#';
        nextLink.onclick = (e) => {
            e.preventDefault();
            fetchHistory(currentPage + 1);
        };
    }
    container.appendChild(nextLink);
}

function escapeHtml(text) {
    if (!text) return '';
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}