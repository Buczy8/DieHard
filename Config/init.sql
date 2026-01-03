-- Plik: init.sql
CREATE TABLE IF NOT EXISTS users
(
    id         SERIAL PRIMARY KEY,
    email      VARCHAR(255) NOT NULL UNIQUE,
    username   VARCHAR(100) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    role       VARCHAR(20) DEFAULT 'user',
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
INSERT INTO users (email, username, password, role)
VALUES ('admin@admin.com', 'admin', '$2a$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'admin')
ON CONFLICT (email) DO NOTHING;
