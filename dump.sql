--
-- PostgreSQL database dump
--

\restrict HKp1hw0ohgWSQ7nGrllWyyN5VJY9yaBLEtFHrXGVXjTNQ8sIde06GTY0OLJrl1I

-- Dumped from database version 16.11 (Debian 16.11-1.pgdg13+1)
-- Dumped by pg_dump version 16.11 (Debian 16.11-1.pgdg13+1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: initialize_user_statistics(); Type: FUNCTION; Schema: public; Owner: docker
--

CREATE FUNCTION public.initialize_user_statistics() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    INSERT INTO user_statistics (user_id) VALUES (NEW.id);
    RETURN NEW;
END;
$$;


ALTER FUNCTION public.initialize_user_statistics() OWNER TO docker;

--
-- Name: update_updated_at_column(); Type: FUNCTION; Schema: public; Owner: docker
--

CREATE FUNCTION public.update_updated_at_column() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
   NEW.updated_at = NOW();
   RETURN NEW;
END;
$$;


ALTER FUNCTION public.update_updated_at_column() OWNER TO docker;

--
-- Name: update_user_stats_on_game(); Type: FUNCTION; Schema: public; Owner: docker
--

CREATE FUNCTION public.update_user_stats_on_game() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    UPDATE user_statistics
    SET
        games_played = games_played + 1,
        games_won = games_won + CASE WHEN NEW.result = 'win' THEN 1 ELSE 0 END,
        high_score = GREATEST(high_score, NEW.score)
    WHERE user_id = NEW.user_id;
    RETURN NEW;
END;
$$;


ALTER FUNCTION public.update_user_stats_on_game() OWNER TO docker;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: games; Type: TABLE; Schema: public; Owner: docker
--

CREATE TABLE public.games (
    id integer NOT NULL,
    user_id integer NOT NULL,
    score integer NOT NULL,
    opponent_name character varying(50) DEFAULT 'Bot'::character varying,
    result character varying(10),
    played_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT games_result_check CHECK (((result)::text = ANY ((ARRAY['win'::character varying, 'loss'::character varying, 'draw'::character varying])::text[])))
);


ALTER TABLE public.games OWNER TO docker;

--
-- Name: games_id_seq; Type: SEQUENCE; Schema: public; Owner: docker
--

CREATE SEQUENCE public.games_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.games_id_seq OWNER TO docker;

--
-- Name: games_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: docker
--

ALTER SEQUENCE public.games_id_seq OWNED BY public.games.id;


--
-- Name: user_statistics; Type: TABLE; Schema: public; Owner: docker
--

CREATE TABLE public.user_statistics (
    id integer NOT NULL,
    user_id integer NOT NULL,
    games_played integer DEFAULT 0,
    games_won integer DEFAULT 0,
    high_score integer DEFAULT 0,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.user_statistics OWNER TO docker;

--
-- Name: user_statistics_id_seq; Type: SEQUENCE; Schema: public; Owner: docker
--

CREATE SEQUENCE public.user_statistics_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.user_statistics_id_seq OWNER TO docker;

--
-- Name: user_statistics_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: docker
--

ALTER SEQUENCE public.user_statistics_id_seq OWNED BY public.user_statistics.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: docker
--

CREATE TABLE public.users (
    id integer NOT NULL,
    email character varying(255) NOT NULL,
    username character varying(100) NOT NULL,
    password character varying(255) NOT NULL,
    role character varying(20) DEFAULT 'user'::character varying,
    avatar character varying(255) DEFAULT NULL::character varying,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.users OWNER TO docker;

--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: docker
--

CREATE SEQUENCE public.users_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.users_id_seq OWNER TO docker;

--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: docker
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: v_user_leaderboard; Type: VIEW; Schema: public; Owner: docker
--

CREATE VIEW public.v_user_leaderboard AS
 SELECT u.id,
    u.username,
    u.avatar,
    us.high_score,
    us.games_played,
    us.games_won,
        CASE
            WHEN (us.games_played > 0) THEN round((((us.games_won)::numeric / (us.games_played)::numeric) * (100)::numeric), 2)
            ELSE (0)::numeric
        END AS win_rate
   FROM (public.users u
     JOIN public.user_statistics us ON ((u.id = us.user_id)))
  ORDER BY us.high_score DESC,
        CASE
            WHEN (us.games_played > 0) THEN round((((us.games_won)::numeric / (us.games_played)::numeric) * (100)::numeric), 2)
            ELSE (0)::numeric
        END DESC;


ALTER VIEW public.v_user_leaderboard OWNER TO docker;

--
-- Name: games id; Type: DEFAULT; Schema: public; Owner: docker
--

ALTER TABLE ONLY public.games ALTER COLUMN id SET DEFAULT nextval('public.games_id_seq'::regclass);


--
-- Name: user_statistics id; Type: DEFAULT; Schema: public; Owner: docker
--

ALTER TABLE ONLY public.user_statistics ALTER COLUMN id SET DEFAULT nextval('public.user_statistics_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: docker
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Data for Name: games; Type: TABLE DATA; Schema: public; Owner: docker
--

COPY public.games (id, user_id, score, opponent_name, result, played_at) FROM stdin;
\.


--
-- Data for Name: user_statistics; Type: TABLE DATA; Schema: public; Owner: docker
--

COPY public.user_statistics (id, user_id, games_played, games_won, high_score, created_at, updated_at) FROM stdin;
1	1	0	0	0	2026-01-25 19:12:55.868183	2026-01-25 19:12:55.868183
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: docker
--

COPY public.users (id, email, username, password, role, avatar, created_at) FROM stdin;
1	admin@admin.com	admin	$2a$12$OcqJ1HoHzCYJqyWnSWIXQuZ3zlGGGIvjKqd/qptMR9Bu0EyW6dkGm	admin	\N	2026-01-25 19:12:55.868183
\.


--
-- Name: games_id_seq; Type: SEQUENCE SET; Schema: public; Owner: docker
--

SELECT pg_catalog.setval('public.games_id_seq', 1, false);


--
-- Name: user_statistics_id_seq; Type: SEQUENCE SET; Schema: public; Owner: docker
--

SELECT pg_catalog.setval('public.user_statistics_id_seq', 1, true);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: docker
--

SELECT pg_catalog.setval('public.users_id_seq', 1, true);


--
-- Name: games games_pkey; Type: CONSTRAINT; Schema: public; Owner: docker
--

ALTER TABLE ONLY public.games
    ADD CONSTRAINT games_pkey PRIMARY KEY (id);


--
-- Name: user_statistics user_statistics_pkey; Type: CONSTRAINT; Schema: public; Owner: docker
--

ALTER TABLE ONLY public.user_statistics
    ADD CONSTRAINT user_statistics_pkey PRIMARY KEY (id);


--
-- Name: user_statistics user_statistics_user_id_key; Type: CONSTRAINT; Schema: public; Owner: docker
--

ALTER TABLE ONLY public.user_statistics
    ADD CONSTRAINT user_statistics_user_id_key UNIQUE (user_id);


--
-- Name: users users_email_key; Type: CONSTRAINT; Schema: public; Owner: docker
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_key UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: docker
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: users users_username_key; Type: CONSTRAINT; Schema: public; Owner: docker
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_username_key UNIQUE (username);


--
-- Name: idx_games_played_at; Type: INDEX; Schema: public; Owner: docker
--

CREATE INDEX idx_games_played_at ON public.games USING btree (played_at);


--
-- Name: idx_games_user_id; Type: INDEX; Schema: public; Owner: docker
--

CREATE INDEX idx_games_user_id ON public.games USING btree (user_id);


--
-- Name: idx_users_email; Type: INDEX; Schema: public; Owner: docker
--

CREATE INDEX idx_users_email ON public.users USING btree (email);


--
-- Name: idx_users_username; Type: INDEX; Schema: public; Owner: docker
--

CREATE INDEX idx_users_username ON public.users USING btree (username);


--
-- Name: users trg_create_user_stats; Type: TRIGGER; Schema: public; Owner: docker
--

CREATE TRIGGER trg_create_user_stats AFTER INSERT ON public.users FOR EACH ROW EXECUTE FUNCTION public.initialize_user_statistics();


--
-- Name: games trg_update_stats_after_game; Type: TRIGGER; Schema: public; Owner: docker
--

CREATE TRIGGER trg_update_stats_after_game AFTER INSERT ON public.games FOR EACH ROW EXECUTE FUNCTION public.update_user_stats_on_game();


--
-- Name: user_statistics trg_update_user_stats_timestamp; Type: TRIGGER; Schema: public; Owner: docker
--

CREATE TRIGGER trg_update_user_stats_timestamp BEFORE UPDATE ON public.user_statistics FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: games fk_game_user; Type: FK CONSTRAINT; Schema: public; Owner: docker
--

ALTER TABLE ONLY public.games
    ADD CONSTRAINT fk_game_user FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: user_statistics fk_user; Type: FK CONSTRAINT; Schema: public; Owner: docker
--

ALTER TABLE ONLY public.user_statistics
    ADD CONSTRAINT fk_user FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

\unrestrict HKp1hw0ohgWSQ7nGrllWyyN5VJY9yaBLEtFHrXGVXjTNQ8sIde06GTY0OLJrl1I

