--
-- PostgreSQL database dump
--

-- Dumped from database version 16.9
-- Dumped by pg_dump version 16.9

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
-- Name: auth; Type: SCHEMA; Schema: -; Owner: postgres
--

CREATE SCHEMA auth;


ALTER SCHEMA auth OWNER TO postgres;

--
-- Name: negozi; Type: SCHEMA; Schema: -; Owner: postgres
--

CREATE SCHEMA negozi;


ALTER SCHEMA negozi OWNER TO postgres;

--
-- Name: email_lowercase(); Type: FUNCTION; Schema: auth; Owner: postgres
--

CREATE FUNCTION auth.email_lowercase() RETURNS trigger
    LANGUAGE plpgsql COST 1
    AS $$
BEGIN
  IF NEW.email IS NOT NULL THEN
    NEW.email := lower(NEW.email);
  END IF;
  RETURN NEW;
END;
$$;


ALTER FUNCTION auth.email_lowercase() OWNER TO postgres;

--
-- Name: aggiorna_magazzino_fornitore(character, integer, integer); Type: FUNCTION; Schema: negozi; Owner: postgres
--

CREATE FUNCTION negozi.aggiorna_magazzino_fornitore(_fornitore character, _prodotto integer, _quantita integer) RETURNS void
    LANGUAGE plpgsql COST 1
    AS $$
BEGIN
  UPDATE negozi.magazzino_fornitore mf
     SET quantita = mf.quantita - _quantita
   WHERE mf.piva_fornitore = _fornitore
     AND mf.prodotto  = _prodotto
     AND mf.quantita >= _quantita;
  IF NOT FOUND THEN
    RAISE EXCEPTION 'Stock insufficiente: fornitore %, prodotto %, richiesti %',
      _fornitore, _prodotto, _quantita;
  END IF;
  RETURN;
END;
$$;


ALTER FUNCTION negozi.aggiorna_magazzino_fornitore(_fornitore character, _prodotto integer, _quantita integer) OWNER TO postgres;

--
-- Name: aggiorna_ordine(integer, text, text); Type: FUNCTION; Schema: negozi; Owner: postgres
--

CREATE FUNCTION negozi.aggiorna_ordine(_id_ordine integer, _new_stato text, _old_stato text) RETURNS void
    LANGUAGE plpgsql COST 1
    AS $$
DECLARE
	v_quantita INT := 0;
	v_prodotto INT;
	v_negozio INT;
	v_fornitore CHAR(11);
BEGIN
SELECT quantita, prodotto, negozio, fornitore INTO v_quantita, v_prodotto, v_negozio, v_fornitore
	from negozi.ordini_fornitori o
	WHERE o.id_ordine = _id_ordine;
IF NOT FOUND THEN
    RETURN;
END IF;
IF _old_stato = 'emesso' AND
	_new_stato = 'consegnato' THEN
	UPDATE negozi.listino_negozio SET magazzino = magazzino + v_quantita
		WHERE negozio = v_negozio AND prodotto = v_prodotto;
	
	RETURN;	
END IF;
IF _old_stato = 'emesso' AND
	_new_stato = 'annullato' THEN
	UPDATE negozi.magazzino_fornitore SET quantita = quantita + v_quantita
		WHERE piva_fornitore = v_fornitore AND prodotto = v_prodotto;
	RETURN;
END IF;
RETURN;
END;
$$;


ALTER FUNCTION negozi.aggiorna_ordine(_id_ordine integer, _new_stato text, _old_stato text) OWNER TO postgres;

--
-- Name: aggiorna_punti_tessera(integer, smallint); Type: PROCEDURE; Schema: negozi; Owner: postgres
--

CREATE PROCEDURE negozi.aggiorna_punti_tessera(IN _tessera integer, IN _sconto_percentuale smallint)
    LANGUAGE plpgsql
    AS $$
DECLARE
  v_punti_sconto INT := 0;
BEGIN
  IF COALESCE(_sconto_percentuale,0) > 0 THEN
	SELECT punti_richiesti INTO v_punti_sconto
		FROM negozi.livelli_sconto 
		WHERE sconto_percentuale = _sconto_percentuale;
END IF;
v_punti_sconto := COALESCE(v_punti_sconto,0);
  -- Aggiorna saldo punti
  UPDATE negozi.tessere
  SET saldo_punti = saldo_punti - v_punti_sconto
  WHERE id_tessera = _tessera;
  RETURN;
END;
$$;


ALTER PROCEDURE negozi.aggiorna_punti_tessera(IN _tessera integer, IN _sconto_percentuale smallint) OWNER TO postgres;

--
-- Name: aggiorna_totale_fattura(integer); Type: PROCEDURE; Schema: negozi; Owner: postgres
--

CREATE PROCEDURE negozi.aggiorna_totale_fattura(IN _fattura integer)
    LANGUAGE plpgsql
    AS $$
DECLARE
v_totale_lordo NUMERIC(6,2) := 0;
v_totale_netto NUMERIC(6,2) := 0;
v_sconto 	  NUMERIC(6,2) := 0;
BEGIN
SELECT SUM(prezzo_unita * quantita) 
	INTO v_totale_lordo FROM negozi.dettagli_fattura
	WHERE fattura = _fattura;
SELECT sconto_percentuale 
	INTO v_sconto FROM negozi.fatture
	WHERE id_fattura = _fattura;
v_totale_netto := v_totale_lordo - (v_totale_lordo * (v_sconto / 100));
UPDATE negozi.fatture SET totale_pagato = v_totale_netto 
	WHERE id_fattura = _fattura;
END;
$$;


ALTER PROCEDURE negozi.aggiorna_totale_fattura(IN _fattura integer) OWNER TO postgres;

--
-- Name: archivia_tessere_negozio(integer); Type: FUNCTION; Schema: negozi; Owner: postgres
--

CREATE FUNCTION negozi.archivia_tessere_negozio(_idnegozio integer) RETURNS void
    LANGUAGE plpgsql COST 1
    AS $$
BEGIN
INSERT INTO negozi.storico_tessere
	    (codice_tessera, cliente, saldo_punti, negozio_emittente, data_emissione)
	  	SELECT t.id_tessera, c.id_cliente, t.saldo_punti, t.negozio_emittente, t.data_richiesta
	  	FROM negozi.tessere t
	  	left JOIN negozi.clienti c ON c.tessera = t.id_tessera
		WHERE t.negozio_emittente = _idnegozio;
  
	  DELETE FROM negozi.tessere
	 	 WHERE negozio_emittente = _idnegozio;
RETURN;
END;
$$;


ALTER FUNCTION negozi.archivia_tessere_negozio(_idnegozio integer) OWNER TO postgres;

--
-- Name: crea_tessera_cliente(integer, integer); Type: FUNCTION; Schema: negozi; Owner: postgres
--

CREATE FUNCTION negozi.crea_tessera_cliente(_id_cliente integer, _id_negozio integer) RETURNS integer
    LANGUAGE plpgsql COST 1
    AS $$
DECLARE
  v_id_tessera INT;
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM negozi.clienti
    WHERE id_cliente = _id_cliente
  ) THEN
    RETURN NULL;
  END IF;
  IF EXISTS (
    SELECT 1
    FROM negozi.clienti
    WHERE id_cliente = _id_cliente
      AND tessera IS NOT NULL
  ) THEN
    RETURN NULL;
  END IF;
  INSERT INTO negozi.tessere (negozio_emittente, data_richiesta, saldo_punti)
  VALUES (_id_negozio, CURRENT_DATE, 0)
  RETURNING id_tessera INTO v_id_tessera;
  IF v_id_tessera IS NOT NULL THEN
    UPDATE negozi.clienti
    SET tessera = v_id_tessera
    WHERE id_cliente = _id_cliente;
    RETURN v_id_tessera;
  ELSE
    RETURN NULL;
  END IF;
END;
$$;


ALTER FUNCTION negozi.crea_tessera_cliente(_id_cliente integer, _id_negozio integer) OWNER TO postgres;

--
-- Name: lista_ordini_fornitore(character); Type: FUNCTION; Schema: negozi; Owner: postgres
--

CREATE FUNCTION negozi.lista_ordini_fornitore(_piva_fornitore character) RETURNS TABLE(nome_fornitore text, nome_prodotto text, nome_negozio text, quantita integer, data_ordine timestamp without time zone, stato_ordine text)
    LANGUAGE plpgsql COST 1
    AS $$
BEGIN
  RETURN QUERY
  SELECT
		f.nome_fornitore, 
		p.nome_prodotto,
		n.nome_negozio,
		of.quantita,
		of.data_ordine,
		of.stato_ordine
	  FROM negozi.ordini_fornitori of
	  LEFT JOIN negozi.fornitori f ON f.piva = of.fornitore
	  LEFT JOIN negozi.prodotti  p ON p.id_prodotto = of.prodotto
	  LEFT JOIN negozi.negozi n ON n.id_negozio = of.negozio
		WHERE of.fornitore = _piva_fornitore
  ORDER BY of.data_ordine DESC, of.id_ordine DESC;
END;
$$;


ALTER FUNCTION negozi.lista_ordini_fornitore(_piva_fornitore character) OWNER TO postgres;

--
-- Name: miglior_fornitore(integer, integer); Type: FUNCTION; Schema: negozi; Owner: postgres
--

CREATE FUNCTION negozi.miglior_fornitore(_prodotto integer, _quantita integer) RETURNS character
    LANGUAGE plpgsql COST 1
    AS $$
DECLARE 
	v_fornitore CHAR(11);
BEGIN
	SELECT piva_fornitore INTO v_fornitore
		FROM negozi.magazzino_fornitore mf
		JOIN negozi.fornitori f ON mf.piva_fornitore = f.piva
		WHERE mf.prodotto = _prodotto AND
			  mf.quantita >= _quantita AND
			 f.attivo = true
		ORDER BY mf.prezzo ASC
		LIMIT 1;
RETURN v_fornitore;
END;
$$;


ALTER FUNCTION negozi.miglior_fornitore(_prodotto integer, _quantita integer) OWNER TO postgres;

--
-- Name: tessere_negozio(integer); Type: FUNCTION; Schema: negozi; Owner: postgres
--

CREATE FUNCTION negozi.tessere_negozio(_id_negozio integer) RETURNS TABLE(id_cliente integer, nome text, cognome text, numero_tessera integer, data_richiesta date, saldo_punti integer)
    LANGUAGE plpgsql COST 1
    AS $$
BEGIN
  RETURN QUERY
  SELECT
    c.id_cliente              AS id_cliente,
    c.nome,
    c.cognome,
    c.tessera              AS numero_tessera,
	t.data_richiesta,
    COALESCE(t.saldo_punti,0) AS saldo_punti
  FROM negozi.clienti c
  JOIN negozi.tessere t
    ON t.id_tessera = c.tessera
  WHERE t.negozio_emittente = _id_negozio;
END;
$$;


ALTER FUNCTION negozi.tessere_negozio(_id_negozio integer) OWNER TO postgres;

--
-- Name: trg_aggiorna_magazzino_fornitore(); Type: FUNCTION; Schema: negozi; Owner: postgres
--

CREATE FUNCTION negozi.trg_aggiorna_magazzino_fornitore() RETURNS trigger
    LANGUAGE plpgsql COST 1
    AS $$
BEGIN
	PERFORM negozi.aggiorna_magazzino_fornitore (
    		NEW.fornitore,
		NEW.prodotto,
		NEW.quantita
	);
	RETURN NEW;
END;
$$;


ALTER FUNCTION negozi.trg_aggiorna_magazzino_fornitore() OWNER TO postgres;

--
-- Name: trg_aggiorna_ordine(); Type: FUNCTION; Schema: negozi; Owner: postgres
--

CREATE FUNCTION negozi.trg_aggiorna_ordine() RETURNS trigger
    LANGUAGE plpgsql COST 1
    AS $$
BEGIN
 	-- se lo stato non cambia, non fare nulla
  	IF NEW.stato_ordine IS NOT DISTINCT FROM OLD.stato_ordine THEN
    		RETURN NEW;
  	END IF;
	PERFORM negozi.aggiorna_ordine (
    		NEW.id_ordine,
		NEW.stato_ordine,
		OLD.stato_ordine
	);
	RETURN NEW;
END;
$$;


ALTER FUNCTION negozi.trg_aggiorna_ordine() OWNER TO postgres;

--
-- Name: trg_aggiorna_punti_tessera(); Type: FUNCTION; Schema: negozi; Owner: postgres
--

CREATE FUNCTION negozi.trg_aggiorna_punti_tessera() RETURNS trigger
    LANGUAGE plpgsql COST 1
    AS $$
DECLARE
    v_punti INT;
    v_tessera INT;
BEGIN
    -- Prendi la tessera del cliente
    SELECT tessera INTO v_tessera 
    FROM negozi.clienti 
    WHERE id_cliente = NEW.cliente;
    IF v_tessera IS NULL THEN
        RETURN NEW;
    END IF;
    v_punti := floor(NEW.totale_pagato)::INT;
    UPDATE negozi.tessere 
    SET saldo_punti = saldo_punti + v_punti 
    WHERE id_tessera = v_tessera;
    RETURN NEW;
END;
$$;


ALTER FUNCTION negozi.trg_aggiorna_punti_tessera() OWNER TO postgres;

--
-- Name: trg_archivia_tessere_negozio(); Type: FUNCTION; Schema: negozi; Owner: postgres
--

CREATE FUNCTION negozi.trg_archivia_tessere_negozio() RETURNS trigger
    LANGUAGE plpgsql COST 1
    AS $$
BEGIN
IF OLD.attivo = TRUE and NEW.attivo = FALSE THEN
	PERFORM negozi.archivia_tessere_negozio (
    		OLD.id_negozio
	);
	RETURN NEW;
END IF;
END;
$$;


ALTER FUNCTION negozi.trg_archivia_tessere_negozio() OWNER TO postgres;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: ruolo; Type: TABLE; Schema: auth; Owner: postgres
--

CREATE TABLE auth.ruolo (
    id_ruolo integer NOT NULL,
    nome text NOT NULL,
    descrizione text
);


ALTER TABLE auth.ruolo OWNER TO postgres;

--
-- Name: ruolo_id_ruolo_seq; Type: SEQUENCE; Schema: auth; Owner: postgres
--

CREATE SEQUENCE auth.ruolo_id_ruolo_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE auth.ruolo_id_ruolo_seq OWNER TO postgres;

--
-- Name: ruolo_id_ruolo_seq; Type: SEQUENCE OWNED BY; Schema: auth; Owner: postgres
--

ALTER SEQUENCE auth.ruolo_id_ruolo_seq OWNED BY auth.ruolo.id_ruolo;


--
-- Name: utente_ruolo; Type: TABLE; Schema: auth; Owner: postgres
--

CREATE TABLE auth.utente_ruolo (
    id_utente integer NOT NULL,
    id_ruolo integer NOT NULL
);


ALTER TABLE auth.utente_ruolo OWNER TO postgres;

--
-- Name: utenti; Type: TABLE; Schema: auth; Owner: postgres
--

CREATE TABLE auth.utenti (
    id_utente integer NOT NULL,
    email text NOT NULL,
    password text NOT NULL,
    attivo boolean DEFAULT false NOT NULL,
    creato timestamp without time zone DEFAULT now() NOT NULL,
    ultimo_accesso timestamp without time zone
);


ALTER TABLE auth.utenti OWNER TO postgres;

--
-- Name: utenti_id_utente_seq; Type: SEQUENCE; Schema: auth; Owner: postgres
--

CREATE SEQUENCE auth.utenti_id_utente_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE auth.utenti_id_utente_seq OWNER TO postgres;

--
-- Name: utenti_id_utente_seq; Type: SEQUENCE OWNED BY; Schema: auth; Owner: postgres
--

ALTER SEQUENCE auth.utenti_id_utente_seq OWNED BY auth.utenti.id_utente;


--
-- Name: clienti; Type: TABLE; Schema: negozi; Owner: postgres
--

CREATE TABLE negozi.clienti (
    id_cliente integer NOT NULL,
    cf character(16) NOT NULL,
    nome text NOT NULL,
    cognome text,
    utente integer NOT NULL,
    telefono text,
    tessera integer
);


ALTER TABLE negozi.clienti OWNER TO postgres;

--
-- Name: clienti_id_cliente_seq; Type: SEQUENCE; Schema: negozi; Owner: postgres
--

CREATE SEQUENCE negozi.clienti_id_cliente_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE negozi.clienti_id_cliente_seq OWNER TO postgres;

--
-- Name: clienti_id_cliente_seq; Type: SEQUENCE OWNED BY; Schema: negozi; Owner: postgres
--

ALTER SEQUENCE negozi.clienti_id_cliente_seq OWNED BY negozi.clienti.id_cliente;


--
-- Name: dettagli_fattura; Type: TABLE; Schema: negozi; Owner: postgres
--

CREATE TABLE negozi.dettagli_fattura (
    fattura integer NOT NULL,
    prodotto integer NOT NULL,
    quantita integer NOT NULL,
    prezzo_unita numeric(10,2) NOT NULL
);


ALTER TABLE negozi.dettagli_fattura OWNER TO postgres;

--
-- Name: fatture; Type: TABLE; Schema: negozi; Owner: postgres
--

CREATE TABLE negozi.fatture (
    id_fattura integer NOT NULL,
    cliente integer NOT NULL,
    data_fattura date DEFAULT now() NOT NULL,
    sconto_percentuale numeric(3,0) DEFAULT 0 NOT NULL,
    totale_pagato numeric(10,2) DEFAULT 0 NOT NULL,
    CONSTRAINT totale_check CHECK ((totale_pagato >= (0)::numeric))
);


ALTER TABLE negozi.fatture OWNER TO postgres;

--
-- Name: fatture_id_fattura_seq; Type: SEQUENCE; Schema: negozi; Owner: postgres
--

CREATE SEQUENCE negozi.fatture_id_fattura_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE negozi.fatture_id_fattura_seq OWNER TO postgres;

--
-- Name: fatture_id_fattura_seq; Type: SEQUENCE OWNED BY; Schema: negozi; Owner: postgres
--

ALTER SEQUENCE negozi.fatture_id_fattura_seq OWNED BY negozi.fatture.id_fattura;


--
-- Name: fornitori; Type: TABLE; Schema: negozi; Owner: postgres
--

CREATE TABLE negozi.fornitori (
    piva character(11) NOT NULL,
    nome_fornitore text NOT NULL,
    indirizzo text,
    email text,
    telefono text,
    attivo boolean DEFAULT true NOT NULL
);


ALTER TABLE negozi.fornitori OWNER TO postgres;

--
-- Name: listino_negozio; Type: TABLE; Schema: negozi; Owner: postgres
--

CREATE TABLE negozi.listino_negozio (
    negozio integer NOT NULL,
    prodotto integer NOT NULL,
    prezzo_listino numeric(10,2) NOT NULL,
    magazzino integer DEFAULT 0 NOT NULL
);


ALTER TABLE negozi.listino_negozio OWNER TO postgres;

--
-- Name: livelli_sconto; Type: TABLE; Schema: negozi; Owner: postgres
--

CREATE TABLE negozi.livelli_sconto (
    sconto_percentuale smallint NOT NULL,
    punti_richiesti integer NOT NULL
);


ALTER TABLE negozi.livelli_sconto OWNER TO postgres;

--
-- Name: magazzino_fornitore; Type: TABLE; Schema: negozi; Owner: postgres
--

CREATE TABLE negozi.magazzino_fornitore (
    piva_fornitore character(11),
    prodotto integer NOT NULL,
    quantita integer,
    prezzo numeric(10,2),
    CONSTRAINT prezzo CHECK ((prezzo >= (0)::numeric)),
    CONSTRAINT quantita CHECK ((quantita >= 0))
);


ALTER TABLE negozi.magazzino_fornitore OWNER TO postgres;

--
-- Name: negozi; Type: TABLE; Schema: negozi; Owner: postgres
--

CREATE TABLE negozi.negozi (
    id_negozio integer NOT NULL,
    nome_negozio text NOT NULL,
    responsabile text NOT NULL,
    indirizzo text NOT NULL,
    attivo boolean DEFAULT true NOT NULL
);


ALTER TABLE negozi.negozi OWNER TO postgres;

--
-- Name: negozi_id_negozio_seq; Type: SEQUENCE; Schema: negozi; Owner: postgres
--

CREATE SEQUENCE negozi.negozi_id_negozio_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE negozi.negozi_id_negozio_seq OWNER TO postgres;

--
-- Name: negozi_id_negozio_seq; Type: SEQUENCE OWNED BY; Schema: negozi; Owner: postgres
--

ALTER SEQUENCE negozi.negozi_id_negozio_seq OWNED BY negozi.negozi.id_negozio;


--
-- Name: orari; Type: TABLE; Schema: negozi; Owner: postgres
--

CREATE TABLE negozi.orari (
    negozio integer NOT NULL,
    dow smallint NOT NULL,
    iod smallint NOT NULL,
    apertura time without time zone NOT NULL,
    chiusura time without time zone NOT NULL,
    CONSTRAINT dow_1_7 CHECK (((dow >= 1) AND (dow <= 7))),
    CONSTRAINT iod_1_2 CHECK ((iod = ANY (ARRAY[1, 2])))
);


ALTER TABLE negozi.orari OWNER TO postgres;

--
-- Name: ordini_fornitori; Type: TABLE; Schema: negozi; Owner: postgres
--

CREATE TABLE negozi.ordini_fornitori (
    id_ordine integer NOT NULL,
    fornitore character(11) NOT NULL,
    negozio integer,
    prodotto integer,
    quantita integer NOT NULL,
    data_ordine timestamp without time zone DEFAULT now() NOT NULL,
    data_consegna date NOT NULL,
    stato_ordine text NOT NULL,
    CONSTRAINT check_stato CHECK ((stato_ordine = ANY (ARRAY['emesso'::text, 'consegnato'::text, 'annullato'::text])))
);


ALTER TABLE negozi.ordini_fornitori OWNER TO postgres;

--
-- Name: ordini_fornitori_id_ordine_seq; Type: SEQUENCE; Schema: negozi; Owner: postgres
--

CREATE SEQUENCE negozi.ordini_fornitori_id_ordine_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE negozi.ordini_fornitori_id_ordine_seq OWNER TO postgres;

--
-- Name: ordini_fornitori_id_ordine_seq; Type: SEQUENCE OWNED BY; Schema: negozi; Owner: postgres
--

ALTER SEQUENCE negozi.ordini_fornitori_id_ordine_seq OWNED BY negozi.ordini_fornitori.id_ordine;


--
-- Name: prodotti; Type: TABLE; Schema: negozi; Owner: postgres
--

CREATE TABLE negozi.prodotti (
    id_prodotto integer NOT NULL,
    nome_prodotto text NOT NULL,
    descrizione text,
    immagine_url text
);


ALTER TABLE negozi.prodotti OWNER TO postgres;

--
-- Name: prodotti_id_prodotto_seq; Type: SEQUENCE; Schema: negozi; Owner: postgres
--

CREATE SEQUENCE negozi.prodotti_id_prodotto_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE negozi.prodotti_id_prodotto_seq OWNER TO postgres;

--
-- Name: prodotti_id_prodotto_seq; Type: SEQUENCE OWNED BY; Schema: negozi; Owner: postgres
--

ALTER SEQUENCE negozi.prodotti_id_prodotto_seq OWNED BY negozi.prodotti.id_prodotto;


--
-- Name: storico_tessere; Type: TABLE; Schema: negozi; Owner: postgres
--

CREATE TABLE negozi.storico_tessere (
    id_storico integer NOT NULL,
    codice_tessera integer NOT NULL,
    cliente integer,
    saldo_punti integer,
    negozio_emittente integer NOT NULL,
    data_emissione date NOT NULL
);


ALTER TABLE negozi.storico_tessere OWNER TO postgres;

--
-- Name: storico_tessere_id_storico_seq; Type: SEQUENCE; Schema: negozi; Owner: postgres
--

CREATE SEQUENCE negozi.storico_tessere_id_storico_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE negozi.storico_tessere_id_storico_seq OWNER TO postgres;

--
-- Name: storico_tessere_id_storico_seq; Type: SEQUENCE OWNED BY; Schema: negozi; Owner: postgres
--

ALTER SEQUENCE negozi.storico_tessere_id_storico_seq OWNED BY negozi.storico_tessere.id_storico;


--
-- Name: tessere; Type: TABLE; Schema: negozi; Owner: postgres
--

CREATE TABLE negozi.tessere (
    id_tessera integer NOT NULL,
    negozio_emittente integer NOT NULL,
    data_richiesta date DEFAULT now() NOT NULL,
    saldo_punti integer DEFAULT 0 NOT NULL,
    CONSTRAINT saldo_punti_neg CHECK ((saldo_punti >= 0))
);


ALTER TABLE negozi.tessere OWNER TO postgres;

--
-- Name: tessere_id_tessera_seq; Type: SEQUENCE; Schema: negozi; Owner: postgres
--

CREATE SEQUENCE negozi.tessere_id_tessera_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE negozi.tessere_id_tessera_seq OWNER TO postgres;

--
-- Name: tessere_id_tessera_seq; Type: SEQUENCE OWNED BY; Schema: negozi; Owner: postgres
--

ALTER SEQUENCE negozi.tessere_id_tessera_seq OWNED BY negozi.tessere.id_tessera;


--
-- Name: v_archivio_tessere; Type: VIEW; Schema: negozi; Owner: postgres
--

CREATE VIEW negozi.v_archivio_tessere AS
 SELECT c.nome,
    c.cognome,
    st.codice_tessera,
    n.nome_negozio,
    st.saldo_punti
   FROM ((negozi.storico_tessere st
     JOIN negozi.clienti c ON ((st.cliente = c.id_cliente)))
     JOIN negozi.negozi n ON ((st.negozio_emittente = n.id_negozio)));


ALTER VIEW negozi.v_archivio_tessere OWNER TO postgres;

--
-- Name: v_saldi_punti_300; Type: VIEW; Schema: negozi; Owner: postgres
--

CREATE VIEW negozi.v_saldi_punti_300 AS
 SELECT c.nome,
    c.cognome,
    n.nome_negozio,
    t.saldo_punti,
    t.data_richiesta
   FROM ((negozi.clienti c
     LEFT JOIN negozi.tessere t ON ((c.tessera = t.id_tessera)))
     LEFT JOIN negozi.negozi n ON ((n.id_negozio = t.negozio_emittente)))
  WHERE (t.saldo_punti > 300);


ALTER VIEW negozi.v_saldi_punti_300 OWNER TO postgres;

--
-- Name: ruolo id_ruolo; Type: DEFAULT; Schema: auth; Owner: postgres
--

ALTER TABLE ONLY auth.ruolo ALTER COLUMN id_ruolo SET DEFAULT nextval('auth.ruolo_id_ruolo_seq'::regclass);


--
-- Name: utenti id_utente; Type: DEFAULT; Schema: auth; Owner: postgres
--

ALTER TABLE ONLY auth.utenti ALTER COLUMN id_utente SET DEFAULT nextval('auth.utenti_id_utente_seq'::regclass);


--
-- Name: clienti id_cliente; Type: DEFAULT; Schema: negozi; Owner: postgres
--

ALTER TABLE ONLY negozi.clienti ALTER COLUMN id_cliente SET DEFAULT nextval('negozi.clienti_id_cliente_seq'::regclass);


--
-- Name: fatture id_fattura; Type: DEFAULT; Schema: negozi; Owner: postgres
--

ALTER TABLE ONLY negozi.fatture ALTER COLUMN id_fattura SET DEFAULT nextval('negozi.fatture_id_fattura_seq'::regclass);


--
-- Name: negozi id_negozio; Type: DEFAULT; Schema: negozi; Owner: postgres
--

ALTER TABLE ONLY negozi.negozi ALTER COLUMN id_negozio SET DEFAULT nextval('negozi.negozi_id_negozio_seq'::regclass);


--
-- Name: ordini_fornitori id_ordine; Type: DEFAULT; Schema: negozi; Owner: postgres
--

ALTER TABLE ONLY negozi.ordini_fornitori ALTER COLUMN id_ordine SET DEFAULT nextval('negozi.ordini_fornitori_id_ordine_seq'::regclass);


--
-- Name: prodotti id_prodotto; Type: DEFAULT; Schema: negozi; Owner: postgres
--

ALTER TABLE ONLY negozi.prodotti ALTER COLUMN id_prodotto SET DEFAULT nextval('negozi.prodotti_id_prodotto_seq'::regclass);


--
-- Name: storico_tessere id_storico; Type: DEFAULT; Schema: negozi; Owner: postgres
--

ALTER TABLE ONLY negozi.storico_tessere ALTER COLUMN id_storico SET DEFAULT nextval('negozi.storico_tessere_id_storico_seq'::regclass);


--
-- Name: tessere id_tessera; Type: DEFAULT; Schema: negozi; Owner: postgres
--

ALTER TABLE ONLY negozi.tessere ALTER COLUMN id_tessera SET DEFAULT nextval('negozi.tessere_id_tessera_seq'::regclass);


--
-- Data for Name: ruolo; Type: TABLE DATA; Schema: auth; Owner: postgres
--

COPY auth.ruolo (id_ruolo, nome, descrizione) FROM stdin;
1	manager	Gestore catena negozi
2	cliente	Cliente finale
\.


--
-- Data for Name: utente_ruolo; Type: TABLE DATA; Schema: auth; Owner: postgres
--

COPY auth.utente_ruolo (id_utente, id_ruolo) FROM stdin;
1	1
2	2
3	2
4	2
5	2
6	2
7	2
8	2
9	2
10	2
11	2
12	2
13	2
14	2
15	2
16	2
\.


--
-- Data for Name: utenti; Type: TABLE DATA; Schema: auth; Owner: postgres
--

COPY auth.utenti (id_utente, email, password, attivo, creato, ultimo_accesso) FROM stdin;
3	laura.bianchi@email.it	$2y$12$3s0hbFChwthdBVaLLCnnveeJAkj96ifquHpuW/hQJZvKtU43EloXi	t	2026-01-21 21:48:11.462523	\N
4	paolo.verdi@email.it	$2y$12$3s0hbFChwthdBVaLLCnnveeJAkj96ifquHpuW/hQJZvKtU43EloXi	t	2026-01-21 21:48:11.462523	\N
5	anna.neri@email.it	$2y$12$3s0hbFChwthdBVaLLCnnveeJAkj96ifquHpuW/hQJZvKtU43EloXi	t	2026-01-21 21:48:11.462523	\N
6	luca.ferrari@email.it	$2y$12$3s0hbFChwthdBVaLLCnnveeJAkj96ifquHpuW/hQJZvKtU43EloXi	t	2026-01-21 21:48:11.462523	\N
7	giulia.russo@email.it	$2y$12$3s0hbFChwthdBVaLLCnnveeJAkj96ifquHpuW/hQJZvKtU43EloXi	t	2026-01-21 21:48:11.462523	\N
8	marco.colombo@email.it	$2y$12$3s0hbFChwthdBVaLLCnnveeJAkj96ifquHpuW/hQJZvKtU43EloXi	t	2026-01-21 21:48:11.462523	\N
9	sara.ricci@email.it	$2y$12$3s0hbFChwthdBVaLLCnnveeJAkj96ifquHpuW/hQJZvKtU43EloXi	t	2026-01-21 21:48:11.462523	\N
10	andrea.bruno@email.it	$2y$12$3s0hbFChwthdBVaLLCnnveeJAkj96ifquHpuW/hQJZvKtU43EloXi	t	2026-01-21 21:48:11.462523	\N
11	elena.gallo@email.it	$2y$12$3s0hbFChwthdBVaLLCnnveeJAkj96ifquHpuW/hQJZvKtU43EloXi	t	2026-01-21 21:48:11.462523	\N
12	fabio.conti@email.it	$2y$12$3s0hbFChwthdBVaLLCnnveeJAkj96ifquHpuW/hQJZvKtU43EloXi	t	2026-01-21 21:48:11.462523	\N
13	chiara.romano@email.it	$2y$12$3s0hbFChwthdBVaLLCnnveeJAkj96ifquHpuW/hQJZvKtU43EloXi	t	2026-01-21 21:48:11.462523	\N
14	roberto.moretti@email.it	$2y$12$3s0hbFChwthdBVaLLCnnveeJAkj96ifquHpuW/hQJZvKtU43EloXi	t	2026-01-21 21:48:11.462523	\N
15	valentina.fontana@email.it	$2y$12$3s0hbFChwthdBVaLLCnnveeJAkj96ifquHpuW/hQJZvKtU43EloXi	t	2026-01-21 21:48:11.462523	\N
16	davide.greco@email.it	$2y$12$3s0hbFChwthdBVaLLCnnveeJAkj96ifquHpuW/hQJZvKtU43EloXi	t	2026-01-21 21:48:11.462523	\N
2	mario.rossi@email.it	$2y$12$3s0hbFChwthdBVaLLCnnveeJAkj96ifquHpuW/hQJZvKtU43EloXi	t	2026-01-21 21:48:11.462523	2026-01-22 07:36:13.490022
1	manager@retrogaming.it	$2y$12$tVK3xXy63eHfjqSvsig0vOfbqw8LI0luAMSLs.9nN3Yp6pg4FNJA.	t	2026-01-21 21:48:11.462523	2026-01-22 07:37:28.993001
\.


--
-- Data for Name: clienti; Type: TABLE DATA; Schema: negozi; Owner: postgres
--

COPY negozi.clienti (id_cliente, cf, nome, cognome, utente, telefono, tessera) FROM stdin;
1	RSSMRA80A01H501Z	Mario	Rossi	2	339-1234567	1
2	BNCLRA85M42F205W	Laura	Bianchi	3	347-2345678	2
3	VRDPLA78C15L219X	Paolo	Verdi	4	338-3456789	3
4	NRENNN82D50A794Y	Anna	Neri	5	340-4567890	4
5	FRRLCU83E10D612V	Luca	Ferrari	6	349-5678901	5
6	RSSGLI86H47H501U	Giulia	Russo	7	346-6789012	6
7	CLMMRC79L20F205T	Marco	Colombo	8	333-7890123	7
8	RCCSRA81M52L219S	Sara	Ricci	9	348-8901234	8
9	BRNNDR84P11A794R	Andrea	Bruno	10	339-9012345	9
10	GLLLNE87R48D612Q	Elena	Gallo	11	347-0123456	10
11	CNTFBA80S19H501P	Fabio	Conti	12	338-1234560	11
12	RMNCHR88T50F205O	Chiara	Romano	13	340-2345601	12
13	MRTRRT76A12L219N	Roberto	Moretti	14	349-3456012	13
14	FNTVNT89B51A794M	Valentina	Fontana	15	346-4560123	14
15	GRCDVD81C13D612L	Davide	Greco	16	333-5601234	15
\.


--
-- Data for Name: dettagli_fattura; Type: TABLE DATA; Schema: negozi; Owner: postgres
--

COPY negozi.dettagli_fattura (fattura, prodotto, quantita, prezzo_unita) FROM stdin;
1	8	1	124.99
\.


--
-- Data for Name: fatture; Type: TABLE DATA; Schema: negozi; Owner: postgres
--

COPY negozi.fatture (id_fattura, cliente, data_fattura, sconto_percentuale, totale_pagato) FROM stdin;
1	1	2026-01-22	0	124.99
\.


--
-- Data for Name: fornitori; Type: TABLE DATA; Schema: negozi; Owner: postgres
--

COPY negozi.fornitori (piva, nome_fornitore, indirizzo, email, telefono, attivo) FROM stdin;
01234567890	Vintage Computer Wholesale SRL	Via Industriale 45, Segrate (MI)	info@vintagewholesale.it	02-87654321	t
09876543210	Retro Gaming Italia SPA	Viale Europa 123, Roma	vendite@retrogamingitalia.it	06-12345678	t
11223344556	Classic Electronics Distribution	Corso Francia 88, Torino	ordini@classicelectronics.it	011-9876543	t
66554433221	Old School Tech Supply	Via Bologna 67, Firenze	supply@oldschooltech.it	055-7654321	t
01482870936	Il Fornitore	Via Brombol 23, Poggibonsi	\N	\N	t
\.


--
-- Data for Name: listino_negozio; Type: TABLE DATA; Schema: negozi; Owner: postgres
--

COPY negozi.listino_negozio (negozio, prodotto, prezzo_listino, magazzino) FROM stdin;
1	1	149.99	8
1	2	349.99	5
1	3	109.99	12
1	5	139.99	7
1	6	119.99	6
1	7	179.99	4
1	8	129.99	15
1	9	99.99	10
1	10	89.99	8
1	11	34.99	25
1	12	109.99	6
1	13	129.99	4
1	14	24.99	40
1	15	5.99	100
1	16	4.49	80
1	17	59.99	12
1	18	29.99	18
1	19	11.99	50
1	20	16.99	35
1	21	44.99	8
1	22	42.99	10
1	23	37.99	12
1	24	24.99	15
1	25	29.99	10
1	26	49.99	6
1	27	39.99	14
1	28	54.99	5
1	29	59.99	4
1	30	32.99	18
1	31	79.99	5
2	1	159.99	6
2	2	359.99	4
2	3	99.99	10
2	4	429.99	2
2	5	129.99	5
2	6	109.99	5
2	7	189.99	3
2	8	119.99	12
2	9	94.99	8
2	10	84.99	6
2	11	32.99	20
2	12	114.99	5
2	13	119.99	3
2	14	22.99	35
2	15	5.49	90
2	16	3.99	70
2	17	54.99	10
2	18	27.99	15
2	19	10.99	45
2	20	15.99	30
2	21	47.99	7
2	22	44.99	8
2	23	34.99	10
2	24	22.99	12
2	25	27.99	8
2	26	52.99	5
2	27	42.99	12
2	28	57.99	4
2	29	62.99	3
2	30	34.99	15
2	31	84.99	4
3	1	154.99	5
3	2	339.99	3
3	3	104.99	8
3	4	419.99	2
3	5	134.99	4
3	6	114.99	4
3	7	169.99	3
3	9	89.99	7
3	10	79.99	5
3	11	36.99	18
3	12	104.99	4
3	13	124.99	2
3	14	26.99	30
3	15	6.49	80
3	16	4.29	60
3	17	52.99	8
3	18	31.99	12
3	19	12.99	40
3	20	17.99	25
3	21	49.99	6
3	22	46.99	7
3	23	32.99	8
3	24	21.99	10
3	25	26.99	7
3	26	47.99	4
3	27	37.99	10
3	28	52.99	3
3	29	57.99	2
3	30	36.99	12
3	31	82.99	3
4	1	144.99	4
4	2	329.99	2
4	3	114.99	6
4	4	439.99	1
4	5	144.99	3
4	6	124.99	3
4	7	174.99	2
4	8	134.99	8
4	9	104.99	5
4	10	94.99	4
4	11	29.99	15
4	12	119.99	3
4	13	114.99	2
4	14	23.99	25
4	15	5.29	70
4	16	3.79	50
4	17	57.99	6
4	18	26.99	10
4	19	9.99	35
4	20	14.99	20
4	21	42.99	5
4	22	39.99	5
4	23	29.99	6
4	24	19.99	8
4	25	24.99	5
4	26	54.99	3
4	27	44.99	8
4	28	59.99	2
4	29	54.99	2
4	30	29.99	10
4	31	77.99	4
5	1	139.99	5
5	2	319.99	3
5	3	107.99	9
5	4	459.99	2
5	5	124.99	4
5	6	104.99	4
5	7	184.99	3
5	8	117.99	11
5	9	92.99	7
5	10	82.99	5
5	11	33.99	18
5	12	107.99	4
5	13	127.99	3
5	14	21.99	32
5	15	5.79	85
5	16	4.19	65
5	17	64.99	9
5	18	24.99	14
5	19	8.99	42
5	20	18.99	28
5	21	45.99	6
5	22	41.99	7
5	23	35.99	9
5	24	27.99	11
5	25	31.99	7
5	26	48.99	4
5	27	38.99	11
5	28	55.99	3
5	29	56.99	3
5	30	31.99	14
5	31	75.99	6
1	4	449.99	5
3	8	124.99	9
\.


--
-- Data for Name: livelli_sconto; Type: TABLE DATA; Schema: negozi; Owner: postgres
--

COPY negozi.livelli_sconto (sconto_percentuale, punti_richiesti) FROM stdin;
5	100
15	200
30	300
\.


--
-- Data for Name: magazzino_fornitore; Type: TABLE DATA; Schema: negozi; Owner: postgres
--

COPY negozi.magazzino_fornitore (piva_fornitore, prodotto, quantita, prezzo) FROM stdin;
01234567890	1	50	115.00
11223344556	1	35	125.00
66554433221	1	40	120.00
01234567890	2	30	285.00
09876543210	2	25	270.00
11223344556	2	20	290.00
01234567890	3	45	88.00
09876543210	3	55	85.00
11223344556	3	30	80.00
11223344556	4	20	355.00
01234567890	4	15	360.00
66554433221	5	35	115.00
01234567890	5	28	105.00
11223344556	5	22	112.00
66554433221	6	40	98.00
11223344556	6	32	95.00
09876543210	6	25	90.00
11223344556	7	35	135.00
01234567890	7	28	145.00
66554433221	7	30	142.00
09876543210	8	80	98.00
01234567890	8	45	100.00
66554433221	8	50	92.00
09876543210	9	60	78.00
66554433221	9	42	76.00
01234567890	9	55	70.00
09876543210	10	40	60.00
66554433221	10	50	68.00
01234567890	10	35	65.00
11223344556	10	28	70.00
01234567890	11	100	26.00
09876543210	11	75	28.00
11223344556	11	90	22.00
01234567890	12	40	88.00
11223344556	12	35	85.00
66554433221	12	28	80.00
01234567890	13	25	90.00
11223344556	13	30	98.00
09876543210	13	22	95.00
09876543210	14	150	16.50
11223344556	14	200	19.00
01234567890	14	120	18.00
66554433221	14	100	18.50
11223344556	15	500	4.00
66554433221	15	400	4.80
09876543210	15	450	4.50
01234567890	15	380	4.60
11223344556	16	300	3.20
66554433221	16	250	2.80
09876543210	16	350	3.00
01234567890	16	280	3.40
01234567890	17	60	40.00
66554433221	17	45	48.00
11223344556	17	55	45.00
11223344556	18	80	24.00
01234567890	18	60	25.00
09876543210	18	70	18.00
09876543210	19	180	32.00
01234567890	19	150	35.00
11223344556	19	200	28.00
09876543210	20	120	14.00
66554433221	20	90	10.00
01234567890	20	100	12.00
09876543210	21	25	38.00
11223344556	21	20	40.00
01234567890	21	28	32.00
09876543210	22	30	28.00
01234567890	22	22	35.00
11223344556	22	35	33.00
11223344556	23	22	24.00
09876543210	23	18	30.00
66554433221	23	25	28.00
11223344556	24	40	20.00
66554433221	24	32	15.00
09876543210	24	45	18.00
11223344556	25	28	24.00
09876543210	25	22	26.00
01234567890	25	30	19.00
09876543210	26	20	35.00
01234567890	26	15	42.00
66554433221	26	25	40.00
09876543210	27	35	32.00
11223344556	27	28	26.00
01234567890	27	40	30.00
09876543210	28	18	45.00
66554433221	28	15	38.00
11223344556	28	22	42.00
66554433221	29	15	48.00
09876543210	29	12	50.00
01234567890	29	18	42.00
66554433221	30	30	28.00
09876543210	30	22	22.00
01234567890	30	35	25.00
01234567890	31	20	55.00
09876543210	31	15	62.00
66554433221	31	18	60.00
66554433221	4	16	345.00
01482870936	4	1	100.00
01482870936	32	2	100.00
\.


--
-- Data for Name: negozi; Type: TABLE DATA; Schema: negozi; Owner: postgres
--

COPY negozi.negozi (id_negozio, nome_negozio, responsabile, indirizzo, attivo) FROM stdin;
1	Retro Gaming Milano	Mario Plummer	Via Dante 42, 20121 Milano	t
2	Retro Gaming Roma	Luigi Plummer	Corso Vittorio Emanuele 156, 00186 Roma	t
3	Retro Gaming Torino	Bruno Frank	Via Garibaldi 88, 10122 Torino	t
4	Retro Gaming Bologna	Guybrush Threepwood	Piazza Maggiore 15, 40124 Bologna	t
5	Retro Gaming Firenze	Conan Barbarian	Via Mazzini 33, 50123 Firenze	t
\.


--
-- Data for Name: orari; Type: TABLE DATA; Schema: negozi; Owner: postgres
--

COPY negozi.orari (negozio, dow, iod, apertura, chiusura) FROM stdin;
1	1	1	09:30:00	13:00:00
2	1	1	09:30:00	13:00:00
3	1	1	09:30:00	13:00:00
4	1	1	09:30:00	13:00:00
5	1	1	09:30:00	13:00:00
1	2	1	09:30:00	13:00:00
2	2	1	09:30:00	13:00:00
3	2	1	09:30:00	13:00:00
4	2	1	09:30:00	13:00:00
5	2	1	09:30:00	13:00:00
1	3	1	09:30:00	13:00:00
2	3	1	09:30:00	13:00:00
3	3	1	09:30:00	13:00:00
4	3	1	09:30:00	13:00:00
5	3	1	09:30:00	13:00:00
1	4	1	09:30:00	13:00:00
2	4	1	09:30:00	13:00:00
3	4	1	09:30:00	13:00:00
4	4	1	09:30:00	13:00:00
5	4	1	09:30:00	13:00:00
1	5	1	09:30:00	13:00:00
2	5	1	09:30:00	13:00:00
3	5	1	09:30:00	13:00:00
4	5	1	09:30:00	13:00:00
5	5	1	09:30:00	13:00:00
1	6	1	09:30:00	13:00:00
2	6	1	09:30:00	13:00:00
3	6	1	09:30:00	13:00:00
4	6	1	09:30:00	13:00:00
5	6	1	09:30:00	13:00:00
1	1	2	15:30:00	19:30:00
2	1	2	15:30:00	19:30:00
3	1	2	15:30:00	19:30:00
4	1	2	15:30:00	19:30:00
5	1	2	15:30:00	19:30:00
1	2	2	15:30:00	19:30:00
2	2	2	15:30:00	19:30:00
3	2	2	15:30:00	19:30:00
4	2	2	15:30:00	19:30:00
5	2	2	15:30:00	19:30:00
1	3	2	15:30:00	19:30:00
2	3	2	15:30:00	19:30:00
3	3	2	15:30:00	19:30:00
4	3	2	15:30:00	19:30:00
5	3	2	15:30:00	19:30:00
1	4	2	15:30:00	19:30:00
2	4	2	15:30:00	19:30:00
3	4	2	15:30:00	19:30:00
4	4	2	15:30:00	19:30:00
5	4	2	15:30:00	19:30:00
1	5	2	15:30:00	19:30:00
2	5	2	15:30:00	19:30:00
3	5	2	15:30:00	19:30:00
4	5	2	15:30:00	19:30:00
5	5	2	15:30:00	19:30:00
1	6	2	15:30:00	19:30:00
2	6	2	15:30:00	19:30:00
3	6	2	15:30:00	19:30:00
4	6	2	15:30:00	19:30:00
5	6	2	15:30:00	19:30:00
1	7	1	10:00:00	13:00:00
2	7	1	10:00:00	13:00:00
3	7	1	10:00:00	13:00:00
4	7	1	10:00:00	13:00:00
5	7	1	10:00:00	13:00:00
1	7	2	16:00:00	19:00:00
2	7	2	16:00:00	19:00:00
3	7	2	16:00:00	19:00:00
4	7	2	16:00:00	19:00:00
5	7	2	16:00:00	19:00:00
\.


--
-- Data for Name: ordini_fornitori; Type: TABLE DATA; Schema: negozi; Owner: postgres
--

COPY negozi.ordini_fornitori (id_ordine, fornitore, negozio, prodotto, quantita, data_ordine, data_consegna, stato_ordine) FROM stdin;
1	66554433221	1	4	2	2026-01-21 00:00:00	2026-01-28	consegnato
2	01482870936	2	4	1	2026-01-21 00:00:00	2026-01-28	annullato
\.


--
-- Data for Name: prodotti; Type: TABLE DATA; Schema: negozi; Owner: postgres
--

COPY negozi.prodotti (id_prodotto, nome_prodotto, descrizione, immagine_url) FROM stdin;
1	Commodore 64	Il computer più venduto della storia! CPU MOS 6510 a 1MHz, 64KB RAM, chip sonoro SID leggendario. Include alimentatore e cavi. Perfettamente funzionante, testato.	/images/products/c64.jpg
2	Commodore Amiga 500	La rivoluzione multimediale! CPU Motorola 68000 a 7.14MHz, 512KB Chip RAM espandibile, grafica OCS con 4096 colori, audio stereo Paula. Sistema Workbench 1.3 su floppy. Condizioni eccellenti.	/images/products/amiga500.jpg
4	Apple II Europlus	Versione europea dell Apple II. CPU MOS 6502 a 1MHz, 48KB RAM, tastiera meccanica, drive 5.25" Disk II incluso. Perfetto per collezionisti.	/images/products/appleii.jpg
5	Atari 800XL	Home computer di lusso. CPU 6502C a 1.79MHz, 64KB RAM, chip grafici GTIA e ANTIC, chip audio POKEY. BASIC integrato. Ottime condizioni.	/images/products/atari800xl.jpg
6	MSX Sony HitBit HB-75P	Standard MSX giapponese. CPU Z80A a 3.58MHz, 32KB RAM, 32KB VROM, MSX-BASIC integrato. Compatibile con migliaia di giochi.	/images/products/msx.jpg
7	Amstrad CPC 464	All-in-one britannico. CPU Z80A, 64KB RAM, monitor a colori CTM644 integrato, lettore cassette incorporato. Sistema completo e funzionante.	/images/products/cpc464.jpg
8	Commodore Calcolatore 776M	Calcolatrice programmabile vintage Commodore. Display a LED rosso, memoria a 4 registri, funzioni scientifiche avanzate. Alimentatore originale incluso. Perfetto per collezionisti.	/images/products/commodore776M.jpg
9	Nintendo NES	La console che ha salvato il videogioco! 2 controller originali, Zapper light gun, Super Mario Bros/Duck Hunt. CPU Ricoh 2A03, grafica PPU. PAL italiano.	/images/products/nes.jpg
10	Sega Master System	Rivale di Nintendo in Europa. CPU Z80A, grafica superiore al NES, Alex Kidd in Miracle World integrato. 2 controller e Phaser inclusi.	/images/products/mastersystem.jpg
11	Atari 2600 Jr	La pioniera! Console che ha creato l industria. Include joystick e 5 cartucce classiche: Pac-Man, Space Invaders, Pitfall, River Raid, Barnstorming.	/images/products/atari2600.jpg
13	Floppy Drive 1541-II	Drive 5.25" ufficiale Commodore, versione migliorata e più veloce. Perfettamente funzionante, testato con dischetti di verifica.	/images/products/drive1541.jpg
14	Monitor 1084S	Monitor RGB 14" stereo Philips/Commodore. Ideale per Amiga e C64. Ingresso RGB analogico, composite e S-Video. Immagine nitida, colori vividi.	/images/products/monitor1084.jpg
15	Joystick Competition Pro	Il migliore joystick vintage! Microswitches Zippy professionali, impugnatura ergonomica rossa. Compatibile C64/Amiga/Atari. Indistruttibile.	/images/products/competitionpro.jpg
17	Cassette C30 x5	Cassette magnetiche nuove per datasette. Qualità premium, basso rumore. Perfette per salvataggi e giochi.	/images/products/cassette.jpg
19	Compute! Gazette 1985	Annata completa rilegata. 12 numeri con centinaia di listati BASIC per C64. Giochi, utility, tutorial. Condizioni ottime.	/images/products/gazette.jpg
21	Elite C64/Spectrum	Simulatore spaziale 3D rivoluzionario. Trading, combattimenti, esplorazione galattica. Include mappa stellare e tastierino comandi. Versioni C64 e Spectrum.	/images/products/elite.jpg
25	Turrican C64	Capolavoro di Manfred Trenz. Run n gun epico, grafica spettacolare, musica Chris Huelsbeck indimenticabile. Versione cassetta, mint condition.	/images/products/turrican.jpg
26	International Karate C64	Picchiaduro perfetto di Archer Maclean. Grafica fluida, mosse spettacolari, IA avanzata. Include poster movimenti. Floppy originale System 3.	/images/products/karate.jpg
27	Monkey Island Amiga	The Secret of Monkey Island! Avventura grafica LucasArts. Pirati, umorismo, enigmi brillanti. 11 floppy, manuale originale, Dial-a-Pirate.	/images/products/monkey.jpg
28	Lemmings Amiga	Puzzle game geniale di DMA Design. Salva i lemmings! 120 livelli, grafica adorabile, musica orecchiabile. Box originale Psygnosis.	/images/products/lemmings.jpg
3	ZX Spectrum 48K	Icona britannica degli anni 80. CPU Zilog Z80A a 3.5MHz, 48KB RAM, tastiera a membrana originale. Uscita RF per TV. Funzionante, con manuale originale.	/images/products/spectrum.jpg
18	Epyx Fast Load C64	Cartuccia acceleratore caricamenti. Riduce i tempi di 5x! Include desktop utility e sprite editor. Essenziale per ogni C64.	/images/products/epyx.jpg
12	Datasette C1530	Lettore cassette ufficiale Commodore per C64/VIC-20. Testine pulite, funzionamento garantito. Include cavi di collegamento.	/images/products/datasette_c1530.jpg
16	Floppy Disk 5.25" DD x10	Dischetti vergini doppia densità, confezione sigillata. Perfetti per C64/Amiga. Etichette incluse.	/images/products/floppy.jpg
20	The Last Ninja C64	Capolavoro assoluto di System 3. Grafica isometrica mozzafiato, colonna sonora epica di Ben Daglish. Cassetta originale con manuale. Raro!	/images/products/ninja.jpg
22	Zak McKracken Amiga	Avventura grafica LucasArts. Giornalista contro alieni! Sistema SCUMM, umorismo brillante, enigmi geniali. Confezione big box originale con tutti i materiali.	/images/products/zak.png
23	Maniac Mansion C64	Prima avventura SCUMM di LucasFilm Games. Horror comedy con 7 personaggi giocabili, finali multipli. Box originale con poster e manuale.	/images/products/maniac.jpg
24	Impossible Mission C64	Capolavoro platform/puzzle di Epstein. Sintetizzatore vocale 'Another visitor!', 8 ore per salvare il mondo. Cassetta originale.	/images/products/impossible.png
29	Speedball 2 Amiga	Sport futuristico brutale dei Bitmap Brothers. Grafica metallica, gameplay perfetto. 'Ice cream! Ice cream!' Versione big box.	/images/products/speedball.jpg
30	Shadow of the Beast Amiga	Showcase tecnico Amiga. 12 layer parallax, 132 colori su schermo, colonna sonora David Whittaker. Box lungo Psygnosis con poster.	/images/products/shadow.jpg
31	Sensible Soccer Amiga	Il miglior calcio in 2D di sempre! Controllo perfetto, 1500+ squadre, edit mode. Versione Sensible Software con aggiornamenti.	/images/products/sensible.png
32	Game Boy	Nintendo Game Boy. Primo video game portatile multi cartridge della Nintendo.	/images/products/gameboy.png
\.


--
-- Data for Name: storico_tessere; Type: TABLE DATA; Schema: negozi; Owner: postgres
--

COPY negozi.storico_tessere (id_storico, codice_tessera, cliente, saldo_punti, negozio_emittente, data_emissione) FROM stdin;
\.


--
-- Data for Name: tessere; Type: TABLE DATA; Schema: negozi; Owner: postgres
--

COPY negozi.tessere (id_tessera, negozio_emittente, data_richiesta, saldo_punti) FROM stdin;
2	4	2025-04-07	79
3	4	2024-04-10	128
4	2	2025-05-22	394
5	3	2024-12-06	426
6	2	2025-08-05	170
7	2	2024-07-05	294
8	5	2024-05-22	139
9	2	2025-12-06	463
10	4	2025-10-07	57
11	2	2024-08-05	272
12	1	2025-10-25	452
13	2	2025-10-31	3
14	3	2025-03-18	320
15	4	2024-07-10	239
1	5	2025-02-08	411
\.


--
-- Name: ruolo_id_ruolo_seq; Type: SEQUENCE SET; Schema: auth; Owner: postgres
--

SELECT pg_catalog.setval('auth.ruolo_id_ruolo_seq', 2, true);


--
-- Name: utenti_id_utente_seq; Type: SEQUENCE SET; Schema: auth; Owner: postgres
--

SELECT pg_catalog.setval('auth.utenti_id_utente_seq', 16, true);


--
-- Name: clienti_id_cliente_seq; Type: SEQUENCE SET; Schema: negozi; Owner: postgres
--

SELECT pg_catalog.setval('negozi.clienti_id_cliente_seq', 15, true);


--
-- Name: fatture_id_fattura_seq; Type: SEQUENCE SET; Schema: negozi; Owner: postgres
--

SELECT pg_catalog.setval('negozi.fatture_id_fattura_seq', 1, true);


--
-- Name: negozi_id_negozio_seq; Type: SEQUENCE SET; Schema: negozi; Owner: postgres
--

SELECT pg_catalog.setval('negozi.negozi_id_negozio_seq', 5, true);


--
-- Name: ordini_fornitori_id_ordine_seq; Type: SEQUENCE SET; Schema: negozi; Owner: postgres
--

SELECT pg_catalog.setval('negozi.ordini_fornitori_id_ordine_seq', 2, true);


--
-- Name: prodotti_id_prodotto_seq; Type: SEQUENCE SET; Schema: negozi; Owner: postgres
--

SELECT pg_catalog.setval('negozi.prodotti_id_prodotto_seq', 32, true);


--
-- Name: storico_tessere_id_storico_seq; Type: SEQUENCE SET; Schema: negozi; Owner: postgres
--

SELECT pg_catalog.setval('negozi.storico_tessere_id_storico_seq', 1, false);


--
-- Name: tessere_id_tessera_seq; Type: SEQUENCE SET; Schema: negozi; Owner: postgres
--

SELECT pg_catalog.setval('negozi.tessere_id_tessera_seq', 15, true);


--
-- Name: utenti id_utente_pk; Type: CONSTRAINT; Schema: auth; Owner: postgres
--

ALTER TABLE ONLY auth.utenti
    ADD CONSTRAINT id_utente_pk PRIMARY KEY (id_utente);


--
-- Name: ruolo nome_univoco; Type: CONSTRAINT; Schema: auth; Owner: postgres
--

ALTER TABLE ONLY auth.ruolo
    ADD CONSTRAINT nome_univoco UNIQUE (nome);


--
-- Name: ruolo ruolo_pk; Type: CONSTRAINT; Schema: auth; Owner: postgres
--

ALTER TABLE ONLY auth.ruolo
    ADD CONSTRAINT ruolo_pk PRIMARY KEY (id_ruolo);


--
-- Name: utenti unique_email; Type: CONSTRAINT; Schema: auth; Owner: postgres
--

ALTER TABLE ONLY auth.utenti
    ADD CONSTRAINT unique_email UNIQUE (email);


--
-- Name: utente_ruolo utente_ruolo_pk; Type: CONSTRAINT; Schema: auth; Owner: postgres
--

ALTER TABLE ONLY auth.utente_ruolo
    ADD CONSTRAINT utente_ruolo_pk PRIMARY KEY (id_utente, id_ruolo);


--
-- Name: clienti cf_unique; Type: CONSTRAINT; Schema: negozi; Owner: postgres
--

ALTER TABLE ONLY negozi.clienti
    ADD CONSTRAINT cf_unique UNIQUE (cf);


--
-- Name: clienti clienti_pk; Type: CONSTRAINT; Schema: negozi; Owner: postgres
--

ALTER TABLE ONLY negozi.clienti
    ADD CONSTRAINT clienti_pk PRIMARY KEY (id_cliente);


--
-- Name: dettagli_fattura dettagli_fattura_pk; Type: CONSTRAINT; Schema: negozi; Owner: postgres
--

ALTER TABLE ONLY negozi.dettagli_fattura
    ADD CONSTRAINT dettagli_fattura_pk PRIMARY KEY (fattura, prodotto);


--
-- Name: fatture fatture_pk; Type: CONSTRAINT; Schema: negozi; Owner: postgres
--

ALTER TABLE ONLY negozi.fatture
    ADD CONSTRAINT fatture_pk PRIMARY KEY (id_fattura);


--
-- Name: magazzino_fornitore fornitore_prodotto; Type: CONSTRAINT; Schema: negozi; Owner: postgres
--

ALTER TABLE ONLY negozi.magazzino_fornitore
    ADD CONSTRAINT fornitore_prodotto UNIQUE (piva_fornitore, prodotto);


--
-- Name: fornitori fornitori_pk; Type: CONSTRAINT; Schema: negozi; Owner: postgres
--

ALTER TABLE ONLY negozi.fornitori
    ADD CONSTRAINT fornitori_pk PRIMARY KEY (piva);


--
-- Name: listino_negozio listino_negozio_pk; Type: CONSTRAINT; Schema: negozi; Owner: postgres
--

ALTER TABLE ONLY negozi.listino_negozio
    ADD CONSTRAINT listino_negozio_pk PRIMARY KEY (negozio, prodotto);


--
-- Name: negozi negozio_pk; Type: CONSTRAINT; Schema: negozi; Owner: postgres
--

ALTER TABLE ONLY negozi.negozi
    ADD CONSTRAINT negozio_pk PRIMARY KEY (id_negozio);


--
-- Name: orari orari_pk; Type: CONSTRAINT; Schema: negozi; Owner: postgres
--

ALTER TABLE ONLY negozi.orari
    ADD CONSTRAINT orari_pk PRIMARY KEY (negozio, dow, iod);


--
-- Name: ordini_fornitori ordini_fornitori_pk; Type: CONSTRAINT; Schema: negozi; Owner: postgres
--

ALTER TABLE ONLY negozi.ordini_fornitori
    ADD CONSTRAINT ordini_fornitori_pk PRIMARY KEY (id_ordine);


--
-- Name: prodotti prodotti_pk; Type: CONSTRAINT; Schema: negozi; Owner: postgres
--

ALTER TABLE ONLY negozi.prodotti
    ADD CONSTRAINT prodotti_pk PRIMARY KEY (id_prodotto);


--
-- Name: storico_tessere stroico_tessere_pk; Type: CONSTRAINT; Schema: negozi; Owner: postgres
--

ALTER TABLE ONLY negozi.storico_tessere
    ADD CONSTRAINT stroico_tessere_pk PRIMARY KEY (id_storico);


--
-- Name: clienti tessera_unica; Type: CONSTRAINT; Schema: negozi; Owner: postgres
--

ALTER TABLE ONLY negozi.clienti
    ADD CONSTRAINT tessera_unica UNIQUE (tessera);


--
-- Name: tessere tessere_pk; Type: CONSTRAINT; Schema: negozi; Owner: postgres
--

ALTER TABLE ONLY negozi.tessere
    ADD CONSTRAINT tessere_pk PRIMARY KEY (id_tessera);


--
-- Name: utenti email_to_lower; Type: TRIGGER; Schema: auth; Owner: postgres
--

CREATE TRIGGER email_to_lower BEFORE INSERT OR UPDATE ON auth.utenti FOR EACH ROW EXECUTE FUNCTION auth.email_lowercase();


--
-- Name: ordini_fornitori trg_aggiorna_magazzino; Type: TRIGGER; Schema: negozi; Owner: postgres
--

CREATE TRIGGER trg_aggiorna_magazzino AFTER INSERT ON negozi.ordini_fornitori FOR EACH ROW EXECUTE FUNCTION negozi.trg_aggiorna_magazzino_fornitore();


--
-- Name: ordini_fornitori trg_aggiorna_ordine; Type: TRIGGER; Schema: negozi; Owner: postgres
--

CREATE TRIGGER trg_aggiorna_ordine AFTER UPDATE OF stato_ordine ON negozi.ordini_fornitori FOR EACH ROW EXECUTE FUNCTION negozi.trg_aggiorna_ordine();


--
-- Name: fatture trg_aggiorna_punti; Type: TRIGGER; Schema: negozi; Owner: postgres
--

CREATE TRIGGER trg_aggiorna_punti AFTER UPDATE OF totale_pagato ON negozi.fatture FOR EACH ROW EXECUTE FUNCTION negozi.trg_aggiorna_punti_tessera();


--
-- Name: negozi trg_archivia_tessere_negozio; Type: TRIGGER; Schema: negozi; Owner: postgres
--

CREATE TRIGGER trg_archivia_tessere_negozio BEFORE UPDATE OF attivo ON negozi.negozi FOR EACH ROW EXECUTE FUNCTION negozi.trg_archivia_tessere_negozio();


--
-- Name: utente_ruolo fk_ruolo; Type: FK CONSTRAINT; Schema: auth; Owner: postgres
--

ALTER TABLE ONLY auth.utente_ruolo
    ADD CONSTRAINT fk_ruolo FOREIGN KEY (id_ruolo) REFERENCES auth.ruolo(id_ruolo) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: utente_ruolo fk_utente; Type: FK CONSTRAINT; Schema: auth; Owner: postgres
--

ALTER TABLE ONLY auth.utente_ruolo
    ADD CONSTRAINT fk_utente FOREIGN KEY (id_utente) REFERENCES auth.utenti(id_utente) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: fatture cliente_fk; Type: FK CONSTRAINT; Schema: negozi; Owner: postgres
--

ALTER TABLE ONLY negozi.fatture
    ADD CONSTRAINT cliente_fk FOREIGN KEY (cliente) REFERENCES negozi.clienti(id_cliente) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: dettagli_fattura fattura_fk; Type: FK CONSTRAINT; Schema: negozi; Owner: postgres
--

ALTER TABLE ONLY negozi.dettagli_fattura
    ADD CONSTRAINT fattura_fk FOREIGN KEY (fattura) REFERENCES negozi.fatture(id_fattura) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: ordini_fornitori fornitore_fk; Type: FK CONSTRAINT; Schema: negozi; Owner: postgres
--

ALTER TABLE ONLY negozi.ordini_fornitori
    ADD CONSTRAINT fornitore_fk FOREIGN KEY (fornitore) REFERENCES negozi.fornitori(piva);


--
-- Name: tessere negozio_fk; Type: FK CONSTRAINT; Schema: negozi; Owner: postgres
--

ALTER TABLE ONLY negozi.tessere
    ADD CONSTRAINT negozio_fk FOREIGN KEY (negozio_emittente) REFERENCES negozi.negozi(id_negozio);


--
-- Name: orari negozio_fk; Type: FK CONSTRAINT; Schema: negozi; Owner: postgres
--

ALTER TABLE ONLY negozi.orari
    ADD CONSTRAINT negozio_fk FOREIGN KEY (negozio) REFERENCES negozi.negozi(id_negozio) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: listino_negozio negozio_fk; Type: FK CONSTRAINT; Schema: negozi; Owner: postgres
--

ALTER TABLE ONLY negozi.listino_negozio
    ADD CONSTRAINT negozio_fk FOREIGN KEY (negozio) REFERENCES negozi.negozi(id_negozio) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: ordini_fornitori negozio_fk; Type: FK CONSTRAINT; Schema: negozi; Owner: postgres
--

ALTER TABLE ONLY negozi.ordini_fornitori
    ADD CONSTRAINT negozio_fk FOREIGN KEY (negozio) REFERENCES negozi.negozi(id_negozio);


--
-- Name: magazzino_fornitore piva_fornitore_fk; Type: FK CONSTRAINT; Schema: negozi; Owner: postgres
--

ALTER TABLE ONLY negozi.magazzino_fornitore
    ADD CONSTRAINT piva_fornitore_fk FOREIGN KEY (piva_fornitore) REFERENCES negozi.fornitori(piva) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: listino_negozio prodotto_fk; Type: FK CONSTRAINT; Schema: negozi; Owner: postgres
--

ALTER TABLE ONLY negozi.listino_negozio
    ADD CONSTRAINT prodotto_fk FOREIGN KEY (prodotto) REFERENCES negozi.prodotti(id_prodotto) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: magazzino_fornitore prodotto_fk; Type: FK CONSTRAINT; Schema: negozi; Owner: postgres
--

ALTER TABLE ONLY negozi.magazzino_fornitore
    ADD CONSTRAINT prodotto_fk FOREIGN KEY (prodotto) REFERENCES negozi.prodotti(id_prodotto) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: ordini_fornitori prodotto_fk; Type: FK CONSTRAINT; Schema: negozi; Owner: postgres
--

ALTER TABLE ONLY negozi.ordini_fornitori
    ADD CONSTRAINT prodotto_fk FOREIGN KEY (prodotto) REFERENCES negozi.prodotti(id_prodotto);


--
-- Name: dettagli_fattura prodotto_fk; Type: FK CONSTRAINT; Schema: negozi; Owner: postgres
--

ALTER TABLE ONLY negozi.dettagli_fattura
    ADD CONSTRAINT prodotto_fk FOREIGN KEY (prodotto) REFERENCES negozi.prodotti(id_prodotto) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: clienti tessera_fk; Type: FK CONSTRAINT; Schema: negozi; Owner: postgres
--

ALTER TABLE ONLY negozi.clienti
    ADD CONSTRAINT tessera_fk FOREIGN KEY (tessera) REFERENCES negozi.tessere(id_tessera) ON DELETE SET NULL;


--
-- Name: clienti utente_fk; Type: FK CONSTRAINT; Schema: negozi; Owner: postgres
--

ALTER TABLE ONLY negozi.clienti
    ADD CONSTRAINT utente_fk FOREIGN KEY (utente) REFERENCES auth.utenti(id_utente) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

