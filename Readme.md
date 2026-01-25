# DieHard

DieHard to aplikacja internetowa umożliwiająca grę w kości przeciwko komputerowi. Projekt został zrealizowany w ramach przedmiotu WdPAI (Wprowadzenie do Projektowania Aplikacji Internetowych).

## Spis treści
1. [Opis projektu](#opis-projektu)
2. [Technologie](#technologie)
3. [Wymagania](#wymagania)
4. [Instalacja i uruchomienie](#instalacja-i-uruchomienie)
5. [Struktura projektu](#struktura-projektu)
6. [Wzorce projektowe](#wzorce-projektowe)
7. [Baza danych](#baza-danych)
8. [API](#api)
9. [Funkcjonalności](#funkcjonalności)

## Opis projektu

Aplikacja pozwala użytkownikom na rejestrację, logowanie oraz rozgrywanie partii gry w kości. Wyniki gier są zapisywane w bazie danych, co pozwala na śledzenie historii rozgrywek oraz statystyk użytkownika. Dostępny jest również panel administratora do zarządzania użytkownikami.

## Technologie

Projekt został wykonany przy użyciu następujących technologii:

*   **Backend:** PHP 8.3 (bez frameworka, architektura MVC)
*   **Frontend:** HTML, CSS, JavaScript
*   **Baza danych:** PostgreSQL
*   **Konteneryzacja:** Docker, Docker Compose
*   **Serwer WWW:** Nginx
*   **Zarządzanie zależnościami:** Composer

## Wymagania

Aby uruchomić projekt, potrzebujesz zainstalowanych następujących narzędzi:

*   Docker
*   Docker Compose

Opcjonalnie (do lokalnego developmentu bez Dockera):
*   PHP 8.3+
*   Composer
*   PostgreSQL

## Instalacja i uruchomienie

### Używając Dockera (Zalecane)

1.  **Sklonuj repozytorium:**
    ```bash
    git clone <adres_repozytorium>
    cd Projekt
    ```

2.  **Skonfiguruj zmienne środowiskowe:**
    Stwórz plik `.env` na podstawie `.env.example`.
    ```bash
    cp .env.example .env
    ```

    Poniższa tabela opisuje dostępne zmienne konfiguracyjne:

    | Zmienna | Opis | Domyślna wartość |
    | :--- | :--- | :--- |
    | `DB_HOST` | Nazwa hosta bazy danych (nazwa serwisu w docker-compose) | `db` |
    | `DB_PORT` | Port bazy danych | `5432` |
    | `DB_NAME` | Nazwa bazy danych | `db` |
    | `DB_USER` | Użytkownik bazy danych | `docker` |
    | `DB_PASSWORD` | Hasło do bazy danych | `docker` |
    | `APP_ENV` | Środowisko aplikacji (`development` / `production`) | `development` |

3.  **Uruchom kontenery:**
    ```bash
    docker-compose up -d --build
    ```

4.  **Zainstaluj zależności:**
    Po uruchomieniu kontenerów należy zainstalować zależności PHP:
    ```bash
    docker-compose run --rm -w /app php composer install
    ```

5.  **Dostęp do aplikacji:**
    Aplikacja będzie dostępna pod adresem: `http://localhost:8080`


## Struktura projektu

```
Projekt/
├── Config/             # Pliki konfiguracyjne (np. init.sql)
├── docker/             # Pliki Dockerfile dla poszczególnych usług
├── Public/             # Pliki publiczne (CSS, JS, obrazy, widoki)
├── src/                # Kod źródłowy aplikacji (PHP)
│   ├── Controllers/    # Kontrolery
│   ├── Models/         # Modele
│   ├── Repository/     # Repozytoria (dostęp do danych)
│   ├── DTO/            # Data Transfer Objects
│   ├── Middleware/     # Middleware (np. autoryzacja)
│   ├── Routing.php     # Konfiguracja routingu
│   └── Database.php    # Połączenie z bazą danych
├── .env.example        # Przykładowy plik konfiguracyjny
├── composer.json       # Zależności PHP i konfiguracja autoloadera
├── docker-compose.yaml # Konfiguracja Docker Compose
├── index.php           # Punkt wejścia aplikacji
└── Readme.md           # Dokumentacja
```

## Wzorce projektowe

W projekcie zastosowano szereg wzorców projektowych, aby zapewnić czytelność, skalowalność i łatwość utrzymania kodu:

*   **MVC (Model-View-Controller):** Główny wzorzec architektoniczny oddzielający logikę biznesową (Modele), warstwę prezentacji (Widoki) i logikę sterującą (Kontrolery).
*   **Singleton:** Użyty w klasie `Database` oraz klasach repozytoriów (np. `GamesRepository`, `UserRepository`), aby zapewnić istnienie tylko jednej instancji połączenia z bazą danych lub repozytorium w ramach żądania.
*   **Repository:** Warstwa abstrakcji pomiędzy logiką biznesową a bazą danych. Repozytoria (np. `GamesRepository`) odpowiadają za operacje CRUD i zapytania SQL, ukrywając szczegóły implementacji bazy danych przed kontrolerami.
*   **Front Controller:** Wszystkie żądania trafiają do jednego punktu wejścia (`index.php`), który inicjalizuje środowisko i przekazuje sterowanie do odpowiedniego kontrolera za pośrednictwem routera.
*   **Middleware:** Mechanizm pośredniczący w obsłudze żądań, wykorzystywany do sprawdzania uprawnień, autoryzacji czy poprawności metod HTTP (np. `CheckAuthRequirements`, `CheckRequestAllowed`).

## Baza danych

Projekt wykorzystuje bazę danych PostgreSQL. Schemat bazy danych znajduje się w pliku `Config/init.sql`.

Główne tabele:
*   `users`: Przechowuje dane użytkowników (login, hasło, email, rola).
*   `user_statistics`: Przechowuje statystyki graczy (liczba gier, wygrane, najlepszy wynik).
*   `games`: Historia rozegranych gier.

Baza zawiera również widoki (`v_user_leaderboard`) oraz triggery automatyzujące aktualizację statystyk.

Domyślne konto administratora:
*   **Email:** admin@admin.com
*   **Hasło:** admin

## API

Aplikacja udostępnia wewnętrzne API wykorzystywane przez frontend (AJAX/Fetch) do dynamicznej obsługi gry i pobierania danych.

### Przykładowe endpointy:

*   **POST `/api/dice`**: Główny endpoint obsługujący logikę gry.
    *   Wymaga JSON w ciele żądania z polem `action` (np. `roll`, `select_score`, `computer_turn`, `restart`).
    *   Zwraca stan gry (kości, wynik, tura) w formacie JSON.
*   **GET `/api/dashboard`**: Pobiera dane do dashboardu użytkownika.
*   **GET `/api/history`**: Pobiera historię gier użytkownika.
*   **GET `/api/profile`**: Pobiera dane profilowe.
*   **GET `/admin/users`**: (Admin) Pobiera listę wszystkich użytkowników.
*   **POST `/admin/change-role`**: (Admin) Zmienia rolę użytkownika.

Większość endpointów API wymaga uwierzytelnienia (sesja) i zwraca odpowiedzi w formacie JSON. Błędy są zgłaszane odpowiednimi kodami HTTP (4xx, 5xx) oraz komunikatem w JSON.

## Funkcjonalności

*   **Uwierzytelnianie:** Rejestracja i logowanie użytkowników.
*   **Gra:** Interaktywna gra w kości z komputerem.
*   **Historia:** Przeglądanie historii własnych gier.
*   **Statystyki:** Wyświetlanie statystyk gracza (wygrane, przegrane, high score).
*   **Profil:** Edycja ustawień profilu.
*   **Panel Administratora:**
    *   Przeglądanie listy użytkowników.
    *   Zmiana ról użytkowników.
    *   Usuwanie użytkowników.
    *   Podgląd statystyk serwisu.