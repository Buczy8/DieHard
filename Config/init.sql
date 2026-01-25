--init.sql

-- --- TABELE ---
CREATE TABLE IF NOT EXISTS users
(
    id         SERIAL PRIMARY KEY,
    email      VARCHAR(255) NOT NULL UNIQUE,
    username   VARCHAR(100) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    role       VARCHAR(20) DEFAULT 'user',
    avatar     VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP   DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS user_statistics
(
    id           SERIAL PRIMARY KEY,
    user_id      INT NOT NULL UNIQUE,
    games_played INT       DEFAULT 0,
    games_won    INT       DEFAULT 0,
    high_score   INT       DEFAULT 0,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user
        FOREIGN KEY (user_id)
            REFERENCES users (id)
            ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS games
(
    id            SERIAL PRIMARY KEY,
    user_id       INT NOT NULL,
    score         INT NOT NULL,
    opponent_name VARCHAR(50) DEFAULT 'Bot',
    result        VARCHAR(10) CHECK (result IN ('win', 'loss', 'draw')),
    played_at     TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_game_user
        FOREIGN KEY (user_id)
            REFERENCES users (id)
            ON DELETE CASCADE
);

-- --- INDEKSY ---
-- Przyspieszają wyszukiwanie po często używanych kolumnach
CREATE INDEX IF NOT EXISTS idx_games_user_id ON games (user_id);
CREATE INDEX IF NOT EXISTS idx_games_played_at ON games (played_at);
-- Indeksy na email i username są już tworzone przez UNIQUE
CREATE INDEX IF NOT EXISTS idx_users_email ON users (email);
CREATE INDEX IF NOT EXISTS idx_users_username ON users (username);


-- --- WIDOKI ---
CREATE OR REPLACE VIEW v_user_leaderboard AS
SELECT
    u.id,
    u.username,
    u.avatar,
    us.high_score,
    us.games_played,
    us.games_won,
    CASE
        WHEN us.games_played > 0 THEN ROUND((us.games_won::DECIMAL / us.games_played) * 100, 2)
        ELSE 0
    END as win_rate
FROM users u
JOIN user_statistics us ON u.id = us.user_id
ORDER BY us.high_score DESC, win_rate DESC;


-- --- FUNKCJE I WYZWALACZE (TRIGGERS) ---

-- Automatyczna aktualizacja 'updated_at' w user_statistics
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
   NEW.updated_at = NOW();
   RETURN NEW;
END;
$$ language 'plpgsql';

CREATE OR REPLACE TRIGGER trg_update_user_stats_timestamp
BEFORE UPDATE ON user_statistics
FOR EACH ROW
EXECUTE FUNCTION update_updated_at_column();


-- Automatyczne tworzenie statystyk dla nowego użytkownika
CREATE OR REPLACE FUNCTION initialize_user_statistics()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO user_statistics (user_id) VALUES (NEW.id);
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE OR REPLACE TRIGGER trg_create_user_stats
AFTER INSERT ON users
FOR EACH ROW
EXECUTE FUNCTION initialize_user_statistics();


-- Automatyczna aktualizacja statystyk po dodaniu nowej gry
CREATE OR REPLACE FUNCTION update_user_stats_on_game()
RETURNS TRIGGER AS $$
BEGIN
    UPDATE user_statistics
    SET
        games_played = games_played + 1,
        games_won = games_won + CASE WHEN NEW.result = 'win' THEN 1 ELSE 0 END,
        high_score = GREATEST(high_score, NEW.score)
    WHERE user_id = NEW.user_id;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE OR REPLACE TRIGGER trg_update_stats_after_game
AFTER INSERT ON games
FOR EACH ROW
EXECUTE FUNCTION update_user_stats_on_game();


-- --- WSTĘPNE DANE ---
INSERT INTO users (email, username, password, role)
VALUES ('admin@admin.com', 'admin', '$2a$12$OcqJ1HoHzCYJqyWnSWIXQuZ3zlGGGIvjKqd/qptMR9Bu0EyW6dkGm', 'admin') ---hasło admina: admin
ON CONFLICT (email) DO NOTHING;
