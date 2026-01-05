document.addEventListener('DOMContentLoaded', () => {
    fetchDashboardData();
});

function fetchDashboardData() {
    fetch('/api/dashboard')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            updateDashboard(data);
        })
        .catch(error => {
            console.error('Error fetching dashboard data:', error);
            document.getElementById('recent-games-body').innerHTML = 
                '<tr><td colspan="4" style="text-align:center; padding: 20px; color: var(--color-error-text);">Error loading data</td></tr>';
        });
}

function updateDashboard(data) {
    // 1. Update Username
    if (data.username) {
        document.getElementById('welcome-username').textContent = data.username;
    }

    // 2. Update Stats
    if (data.stats) {
        document.getElementById('stat-highscore').textContent = data.stats.highScore;
        document.getElementById('stat-games-played').textContent = data.stats.gamesPlayed;
        document.getElementById('stat-win-rate').textContent = data.stats.winRate + '%';
        document.getElementById('stat-games-won').textContent = data.stats.gamesWon;
    }

    // 3. Update Recent Games Table
    const tbody = document.getElementById('recent-games-body');
    tbody.innerHTML = '';

    if (!data.recentGames || data.recentGames.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding: 20px;">No recent games</td></tr>';
        return;
    }

    data.recentGames.forEach(game => {
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

function escapeHtml(text) {
    if (!text) return '';
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}