<link rel="stylesheet" href="Public/styles/navbar.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<nav class="main-navbar">
    <div class="nav-left">
        <a href="/" class="nav-logo-link">
            <img src="Public/assets/dice-solid-full.svg" alt="Logo" class="logo-icon">
            <span class="logo-text">DieHard</span>
        </a>

        <div class="nav-links">
            <a href="/" class="nav-link <?= $_SERVER['REQUEST_URI'] === '/' ? 'active' : '' ?>">Home</a>
            <a href="/dicegame" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/dicegame') !== false ? 'active' : '' ?>">Play</a>
            <a href="/history" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/history') !== false ? 'active' : '' ?>">History</a>
        </div>
    </div>

    <div class="nav-right">
        <div class="user-menu-container">
            <button class="user-avatar-btn" id="avatarBtn">
                <i class="fa-solid fa-user"></i>
            </button>

            <div class="user-dropdown" id="userDropdown">
                <div class="dropdown-header">
                    <span class="dropdown-username"><?= isset($username) ? htmlspecialchars($username) : 'User:' ?></span>
                    <span class="dropdown-email">Player</span>
                </div>

                <ul class="dropdown-list">
                    <li>
                        <a href="/profile" class="dropdown-item">
                            <i class="fa-solid fa-user"></i> My profile
                        </a>
                    </li>
                    <li>
                        <a href="/rules" class="dropdown-item">
                            <i class="fa-solid fa-book-open"></i> Rules
                        </a>
                    </li>
                    <li class="divider"></li>
                    <li>
                        <a href="/logout" class="dropdown-item text-danger">
                            <img src="Public/assets/logout.svg" alt="" style="width: 16px; height: 16px;"> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<script src="Public/scripts/navbar.js"></script>