-- Database generated with pgModeler (PostgreSQL Database Modeler).
-- pgModeler version: 1.1.3
-- PostgreSQL version: 16.0
-- Project Site: pgmodeler.io
-- Model Author: ---

-- Database creation must be performed outside a multi lined SQL file. 
-- These commands were put in this file only as a convenience.
-- 
-- object: negozi_db | type: DATABASE --
-- DROP DATABASE IF EXISTS negozi_db;
CREATE DATABASE negozi_db;
-- ddl-end --


SET check_function_bodies = false;
-- ddl-end --

-- object: negozi | type: SCHEMA --
-- DROP SCHEMA IF EXISTS negozi CASCADE;
CREATE SCHEMA negozi;
-- ddl-end --
ALTER SCHEMA negozi OWNER TO postgres;
-- ddl-end --

-- object: auth | type: SCHEMA --
-- DROP SCHEMA IF EXISTS auth CASCADE;
CREATE SCHEMA auth;
-- ddl-end --
ALTER SCHEMA auth OWNER TO postgres;
-- ddl-end --

SET search_path TO pg_catalog,public,negozi,auth;
-- ddl-end --

-- object: auth.utenti | type: TABLE --
-- DROP TABLE IF EXISTS auth.utenti CASCADE;
CREATE TABLE auth.utenti (
	id_utente serial NOT NULL,
	email text NOT NULL,
	password text NOT NULL,
	attivo boolean NOT NULL DEFAULT FALSE,
	creato timestamp NOT NULL DEFAULT now(),
	ultimo_accesso timestamp,
	CONSTRAINT id_utente_pk PRIMARY KEY (id_utente),
	CONSTRAINT unique_email UNIQUE (email)
);
-- ddl-end --
ALTER TABLE auth.utenti OWNER TO postgres;
-- ddl-end --

-- object: auth.ruolo | type: TABLE --
-- DROP TABLE IF EXISTS auth.ruolo CASCADE;
CREATE TABLE auth.ruolo (
	id_ruolo serial NOT NULL,
	nome text NOT NULL,
	descrizione text,
	CONSTRAINT nome_univoco UNIQUE (nome),
	CONSTRAINT ruolo_pk PRIMARY KEY (id_ruolo)
);
-- ddl-end --
ALTER TABLE auth.ruolo OWNER TO postgres;
-- ddl-end --

-- object: auth.utente_ruolo | type: TABLE --
-- DROP TABLE IF EXISTS auth.utente_ruolo CASCADE;
CREATE TABLE auth.utente_ruolo (
	id_utente integer NOT NULL,
	id_ruolo integer NOT NULL,
	CONSTRAINT utente_ruolo_pk PRIMARY KEY (id_utente,id_ruolo)
);
-- ddl-end --
ALTER TABLE auth.utente_ruolo OWNER TO postgres;
-- ddl-end --

-- object: auth.sessions | type: TABLE --
-- DROP TABLE IF EXISTS auth.sessions CASCADE;
CREATE TABLE auth.sessions (
	id_sessione uuid NOT NULL,
	utente integer,
	creato timestamp NOT NULL DEFAULT now(),
	scade timestamp NOT NULL DEFAULT now() + interval '7 days',
	CONSTRAINT sessions_pk PRIMARY KEY (id_sessione)
);
-- ddl-end --
ALTER TABLE auth.sessions OWNER TO postgres;
-- ddl-end --

-- object: auth.reset_token | type: TABLE --
-- DROP TABLE IF EXISTS auth.reset_token CASCADE;
CREATE TABLE auth.reset_token (
	id_token uuid NOT NULL,
	utente integer,
	token_hash text NOT NULL,
	creato timestamp NOT NULL DEFAULT now(),
	scade timestamp NOT NULL,
	usato timestamp,
	CONSTRAINT reset_token_pk PRIMARY KEY (id_token)
);
-- ddl-end --
ALTER TABLE auth.reset_token OWNER TO postgres;
-- ddl-end --

-- object: auth.email_lowercase | type: FUNCTION --
-- DROP FUNCTION IF EXISTS auth.email_lowercase() CASCADE;
CREATE FUNCTION auth.email_lowercase ()
	RETURNS trigger
	LANGUAGE plpgsql
	VOLATILE 
	CALLED ON NULL INPUT
	SECURITY INVOKER
	PARALLEL UNSAFE
	COST 1
	AS $$
BEGIN
  IF NEW.email IS NOT NULL THEN
    NEW.email := lower(NEW.email);
  END IF;
  RETURN NEW;
END;
$$;
-- ddl-end --
ALTER FUNCTION auth.email_lowercase() OWNER TO postgres;
-- ddl-end --

-- object: email_to_lower | type: TRIGGER --
-- DROP TRIGGER IF EXISTS email_to_lower ON auth.utenti CASCADE;
CREATE TRIGGER email_to_lower
	BEFORE INSERT OR UPDATE
	ON auth.utenti
	FOR EACH ROW
	EXECUTE PROCEDURE auth.email_lowercase();
-- ddl-end --

-- object: negozi.clienti | type: TABLE --
-- DROP TABLE IF EXISTS negozi.clienti CASCADE;
CREATE TABLE negozi.clienti (
	id_cliente serial NOT NULL,
	cf char(16) NOT NULL,
	nome text NOT NULL,
	cognome text,
	utente integer NOT NULL,
	telefono text,
	tessera integer,
	CONSTRAINT clienti_pk PRIMARY KEY (id_cliente),
	CONSTRAINT cf_unique UNIQUE (cf),
	CONSTRAINT tessera_unica UNIQUE (tessera)
);
-- ddl-end --
ALTER TABLE negozi.clienti OWNER TO postgres;
-- ddl-end --

-- object: negozi.negozi | type: TABLE --
-- DROP TABLE IF EXISTS negozi.negozi CASCADE;
CREATE TABLE negozi.negozi (
	id_negozio serial NOT NULL,
	nome_negozio text NOT NULL,
	responsabile text NOT NULL,
	indirizzo text NOT NULL,
	CONSTRAINT negozio_pk PRIMARY KEY (id_negozio)
);
-- ddl-end --
ALTER TABLE negozi.negozi OWNER TO postgres;
-- ddl-end --

-- object: negozi.tessere | type: TABLE --
-- DROP TABLE IF EXISTS negozi.tessere CASCADE;
CREATE TABLE negozi.tessere (
	id_tessera serial NOT NULL,
	negozio_emittente integer NOT NULL,
	data_richiesta date NOT NULL DEFAULT now(),
	saldo_punti integer NOT NULL DEFAULT 0,
	archiviata boolean NOT NULL DEFAULT FALSE,
	CONSTRAINT tessere_pk PRIMARY KEY (id_tessera),
	CONSTRAINT saldo_punti_neg CHECK (saldo_punti >= 0)
);
-- ddl-end --
ALTER TABLE negozi.tessere OWNER TO postgres;
-- ddl-end --

-- object: negozi.storico_tessere | type: TABLE --
-- DROP TABLE IF EXISTS negozi.storico_tessere CASCADE;
CREATE TABLE negozi.storico_tessere (
	id_storico serial NOT NULL,
	codice_tessera integer NOT NULL,
	cliente integer,
	saldo_punti integer,
	negozio_emittente integer NOT NULL,
	data_emissione date NOT NULL,
	CONSTRAINT stroico_tessere_pk PRIMARY KEY (id_storico)
);
-- ddl-end --
ALTER TABLE negozi.storico_tessere OWNER TO postgres;
-- ddl-end --

-- object: negozi.trg_archivia_tessere_negozio | type: FUNCTION --
-- DROP FUNCTION IF EXISTS negozi.trg_archivia_tessere_negozio() CASCADE;
CREATE FUNCTION negozi.trg_archivia_tessere_negozio ()
	RETURNS trigger
	LANGUAGE plpgsql
	VOLATILE 
	CALLED ON NULL INPUT
	SECURITY INVOKER
	PARALLEL UNSAFE
	COST 1
	AS $$
BEGIN
	PERFORM negozi.archivia_tessere_negozio (
    		OLD.id_negozio
	);
	RETURN OLD;
END;
$$;
-- ddl-end --
ALTER FUNCTION negozi.trg_archivia_tessere_negozio() OWNER TO postgres;
-- ddl-end --

-- object: trg_archivia_tessere_negozio | type: TRIGGER --
-- DROP TRIGGER IF EXISTS trg_archivia_tessere_negozio ON negozi.negozi CASCADE;
CREATE TRIGGER trg_archivia_tessere_negozio
	BEFORE DELETE 
	ON negozi.negozi
	FOR EACH ROW
	EXECUTE PROCEDURE negozi.trg_archivia_tessere_negozio();
-- ddl-end --

-- object: negozi.prodotti | type: TABLE --
-- DROP TABLE IF EXISTS negozi.prodotti CASCADE;
CREATE TABLE negozi.prodotti (
	id_prodotto serial NOT NULL,
	nome_prodotto text NOT NULL,
	descrizione text,
	immagine_url text,
	CONSTRAINT prodotti_pk PRIMARY KEY (id_prodotto)
);
-- ddl-end --
ALTER TABLE negozi.prodotti OWNER TO postgres;
-- ddl-end --

-- object: negozi.orari | type: TABLE --
-- DROP TABLE IF EXISTS negozi.orari CASCADE;
CREATE TABLE negozi.orari (
	negozio integer NOT NULL,
	dow smallint NOT NULL,
	iod smallint NOT NULL,
	apertura time NOT NULL,
	chiusura time NOT NULL,
	CONSTRAINT dow_1_7 CHECK (dow BETWEEN 1 AND 7),
	CONSTRAINT iod_1_2 CHECK (iod IN (1,2)),
	CONSTRAINT orari_pk PRIMARY KEY (negozio,dow,iod)
);
-- ddl-end --
ALTER TABLE negozi.orari OWNER TO postgres;
-- ddl-end --

-- object: negozi.listino_negozio | type: TABLE --
-- DROP TABLE IF EXISTS negozi.listino_negozio CASCADE;
CREATE TABLE negozi.listino_negozio (
	negozio integer NOT NULL,
	prodotto integer NOT NULL,
	prezzo_listino numeric(10,2) NOT NULL,
	magazzino integer NOT NULL DEFAULT 0,
	CONSTRAINT listino_negozio_pk PRIMARY KEY (negozio,prodotto)
);
-- ddl-end --
ALTER TABLE negozi.listino_negozio OWNER TO postgres;
-- ddl-end --

-- object: negozi.fornitori | type: TABLE --
-- DROP TABLE IF EXISTS negozi.fornitori CASCADE;
CREATE TABLE negozi.fornitori (
	piva char(11) NOT NULL,
	nome_fornitore text NOT NULL,
	indirizzo text,
	email text,
	telefono text,
	CONSTRAINT fornitori_pk PRIMARY KEY (piva)
);
-- ddl-end --
ALTER TABLE negozi.fornitori OWNER TO postgres;
-- ddl-end --

-- object: negozi.magazzino_fornitore | type: TABLE --
-- DROP TABLE IF EXISTS negozi.magazzino_fornitore CASCADE;
CREATE TABLE negozi.magazzino_fornitore (
	piva_fornitore char(11),
	prodotto integer NOT NULL,
	quantita integer,
	prezzo numeric(10,2),
	CONSTRAINT quantita CHECK (quantita>=0),
	CONSTRAINT prezzo CHECK (prezzo>=0),
	CONSTRAINT fornitore_prodotto UNIQUE (piva_fornitore,prodotto)
);
-- ddl-end --
ALTER TABLE negozi.magazzino_fornitore OWNER TO postgres;
-- ddl-end --

-- object: negozi.ordini_fornitori | type: TABLE --
-- DROP TABLE IF EXISTS negozi.ordini_fornitori CASCADE;
CREATE TABLE negozi.ordini_fornitori (
	id_ordine serial NOT NULL,
	fornitore char(11) NOT NULL,
	negozio integer,
	prodotto integer,
	quantita integer,
	data_ordine timestamp NOT NULL DEFAULT now(),
	data_consegna date,
	stato_ordine text NOT NULL,
	CONSTRAINT ordini_fornitori_pk PRIMARY KEY (id_ordine),
	CONSTRAINT check_stato CHECK (stato_ordine IN ('emesso','consegnato','annullato')
)
);
-- ddl-end --
ALTER TABLE negozi.ordini_fornitori OWNER TO postgres;
-- ddl-end --

-- object: negozi.fatture | type: TABLE --
-- DROP TABLE IF EXISTS negozi.fatture CASCADE;
CREATE TABLE negozi.fatture (
	id_fattura serial NOT NULL,
	cliente integer NOT NULL,
	data_fattura date NOT NULL DEFAULT now(),
	sconto_percentuale numeric(3) NOT NULL DEFAULT 0,
	totale_pagato numeric(10,2) NOT NULL DEFAULT 0,
	CONSTRAINT fatture_pk PRIMARY KEY (id_fattura),
	CONSTRAINT totale_check CHECK (totale_pagato >= 0)
);
-- ddl-end --
ALTER TABLE negozi.fatture OWNER TO postgres;
-- ddl-end --

-- object: negozi.dettagli_fattura | type: TABLE --
-- DROP TABLE IF EXISTS negozi.dettagli_fattura CASCADE;
CREATE TABLE negozi.dettagli_fattura (
	fattura integer NOT NULL,
	prodotto integer NOT NULL,
	quantita integer NOT NULL,
	prezzo_unita numeric(10,2) NOT NULL,
	CONSTRAINT dettagli_fattura_pk PRIMARY KEY (fattura,prodotto)
);
-- ddl-end --
ALTER TABLE negozi.dettagli_fattura OWNER TO postgres;
-- ddl-end --

-- object: negozi.ricalcola_totale_fattura | type: FUNCTION --
-- DROP FUNCTION IF EXISTS negozi.ricalcola_totale_fattura(integer) CASCADE;
CREATE FUNCTION negozi.ricalcola_totale_fattura (IN _idfattura integer)
	RETURNS void
	LANGUAGE plpgsql
	VOLATILE 
	CALLED ON NULL INPUT
	SECURITY INVOKER
	PARALLEL UNSAFE
	COST 1
	AS $$
DECLARE
  v_subtot  NUMERIC := 0;
  v_sconto  NUMERIC := 0;
  v_totale  NUMERIC := 0;
BEGIN
  SELECT COALESCE(SUM(quantita * prezzo_unita),0)
    INTO v_subtot
  FROM negozi.dettagli_fattura
  WHERE fattura = _idfattura;

  SELECT COALESCE(sconto_percentuale,0)
    INTO v_sconto
  FROM negozi.fatture
  WHERE id_fattura = _idfattura;

  v_totale := GREATEST(v_subtot * (1 - v_sconto/100.0), 0);

  UPDATE negozi.fatture
     SET totale_pagato = v_totale
   WHERE id_fattura = _idfattura;
END;
$$;
-- ddl-end --
ALTER FUNCTION negozi.ricalcola_totale_fattura(integer) OWNER TO postgres;
-- ddl-end --

-- object: negozi.archivia_tessere_negozio | type: FUNCTION --
-- DROP FUNCTION IF EXISTS negozi.archivia_tessere_negozio(integer) CASCADE;
CREATE FUNCTION negozi.archivia_tessere_negozio (_idnegozio integer)
	RETURNS void
	LANGUAGE plpgsql
	VOLATILE 
	CALLED ON NULL INPUT
	SECURITY INVOKER
	PARALLEL UNSAFE
	COST 1
	AS $$
BEGIN
	INSERT INTO negozi.storico_tessere
	    (codice_tessera, cliente, saldo_punti, negozio_emittente, data_emissione)
	  	SELECT t.id_tessera, c.cliente, t.saldo_punti, t.negozio_emittente, t.data_richiesta
	  	FROM negozi.tessere t
	  	left JOIN negozi.clienti ON c.tessera = t.id_tessera
		WHERE t.negozio_emittente = _idnegozio;
  
	  DELETE FROM negozi.tessere
	 	 WHERE negozio_emittente = _idnegozio;

RETURN;
END;
$$;
-- ddl-end --
ALTER FUNCTION negozi.archivia_tessere_negozio(integer) OWNER TO postgres;
-- ddl-end --

-- object: negozi.trg_aggiorna_magazzino_fornitore | type: FUNCTION --
-- DROP FUNCTION IF EXISTS negozi.trg_aggiorna_magazzino_fornitore() CASCADE;
CREATE FUNCTION negozi.trg_aggiorna_magazzino_fornitore ()
	RETURNS trigger
	LANGUAGE plpgsql
	VOLATILE 
	CALLED ON NULL INPUT
	SECURITY INVOKER
	PARALLEL UNSAFE
	COST 1
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
-- ddl-end --
ALTER FUNCTION negozi.trg_aggiorna_magazzino_fornitore() OWNER TO postgres;
-- ddl-end --

-- object: trg_aggiorna_magazzino | type: TRIGGER --
-- DROP TRIGGER IF EXISTS trg_aggiorna_magazzino ON negozi.ordini_fornitori CASCADE;
CREATE TRIGGER trg_aggiorna_magazzino
	AFTER INSERT 
	ON negozi.ordini_fornitori
	FOR EACH ROW
	EXECUTE PROCEDURE negozi.trg_aggiorna_magazzino_fornitore();
-- ddl-end --

-- object: negozi.aggiorna_magazzino_fornitore | type: FUNCTION --
-- DROP FUNCTION IF EXISTS negozi.aggiorna_magazzino_fornitore(char,integer,integer) CASCADE;
CREATE FUNCTION negozi.aggiorna_magazzino_fornitore (_fornitore char, _prodotto integer, _quantita integer)
	RETURNS void
	LANGUAGE plpgsql
	VOLATILE 
	CALLED ON NULL INPUT
	SECURITY INVOKER
	PARALLEL UNSAFE
	COST 1
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
-- ddl-end --
ALTER FUNCTION negozi.aggiorna_magazzino_fornitore(char,integer,integer) OWNER TO postgres;
-- ddl-end --

-- object: negozi.miglior_fornitore | type: FUNCTION --
-- DROP FUNCTION IF EXISTS negozi.miglior_fornitore(integer) CASCADE;
CREATE FUNCTION negozi.miglior_fornitore (_prodotto integer)
	RETURNS char
	LANGUAGE plpgsql
	VOLATILE 
	CALLED ON NULL INPUT
	SECURITY INVOKER
	PARALLEL UNSAFE
	COST 1
	AS $$
DECLARE 
	v_fornitore CHAR(11);
BEGIN
	SELECT piva_fornitore INTO v_fornitore
		FROM negozi.magazzino_fornitore mf
		WHERE mf.prodotto = _prodotto 
		ORDER BY mf.prezzo ASC
		LIMIT 1;
RETURN v_fornitore;
END;
$$;
-- ddl-end --
ALTER FUNCTION negozi.miglior_fornitore(integer) OWNER TO postgres;
-- ddl-end --

-- object: negozi.v_saldi_punti_300 | type: VIEW --
-- DROP VIEW IF EXISTS negozi.v_saldi_punti_300 CASCADE;
CREATE VIEW negozi.v_saldi_punti_300
AS 
SELECT c.nome, c.cognome, n.nome_negozio, t.saldo_punti, t.data_richiesta
FROM clienti c
LEFT JOIN tessere t ON c.tessera = t.id_tessera
LEFT JOIN negozi n ON n.id_negozio = t.negozio_emittente
WHERE saldo_punti > 300;
-- ddl-end --
ALTER VIEW negozi.v_saldi_punti_300 OWNER TO postgres;
-- ddl-end --

-- object: negozi.tessere_negozio | type: FUNCTION --
-- DROP FUNCTION IF EXISTS negozi.tessere_negozio(integer) CASCADE;
CREATE FUNCTION negozi.tessere_negozio (_id_negozio integer)
	RETURNS TABLE (id_cliente integer, nome text, cognome text, numero_tessera integer, data_richiesta date, saldo_punti integer)
	LANGUAGE plpgsql
	VOLATILE 
	CALLED ON NULL INPUT
	SECURITY INVOKER
	PARALLEL UNSAFE
	COST 1
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
-- ddl-end --
ALTER FUNCTION negozi.tessere_negozio(integer) OWNER TO postgres;
-- ddl-end --

-- object: negozi.livelli_sconto | type: TABLE --
-- DROP TABLE IF EXISTS negozi.livelli_sconto CASCADE;
CREATE TABLE negozi.livelli_sconto (
	sconto_percentuale smallint,
	punti_richiesti integer

);
-- ddl-end --
ALTER TABLE negozi.livelli_sconto OWNER TO postgres;
-- ddl-end --

INSERT INTO negozi.livelli_sconto (sconto_percentuale, punti_richiesti) VALUES (E'5', E'100');
-- ddl-end --
INSERT INTO negozi.livelli_sconto (sconto_percentuale, punti_richiesti) VALUES (E'15', E'200');
-- ddl-end --
INSERT INTO negozi.livelli_sconto (sconto_percentuale, punti_richiesti) VALUES (E'30', E'300');
-- ddl-end --

-- object: negozi.aggiorna_punti_tessera | type: PROCEDURE --
-- DROP PROCEDURE IF EXISTS negozi.aggiorna_punti_tessera(integer,smallint) CASCADE;
CREATE PROCEDURE negozi.aggiorna_punti_tessera (_tessera integer, _sconto_percentuale smallint)
	LANGUAGE plpgsql
	SECURITY INVOKER
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
-- ddl-end --
ALTER PROCEDURE negozi.aggiorna_punti_tessera(integer,smallint) OWNER TO postgres;
-- ddl-end --

-- object: negozi.aggiorna_totale_fattura | type: PROCEDURE --
-- DROP PROCEDURE IF EXISTS negozi.aggiorna_totale_fattura(integer) CASCADE;
CREATE PROCEDURE negozi.aggiorna_totale_fattura (_fattura integer)
	LANGUAGE plpgsql
	SECURITY INVOKER
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
-- ddl-end --
ALTER PROCEDURE negozi.aggiorna_totale_fattura(integer) OWNER TO postgres;
-- ddl-end --

-- object: negozi.trg_aggiorna_punti_tessera | type: FUNCTION --
-- DROP FUNCTION IF EXISTS negozi.trg_aggiorna_punti_tessera() CASCADE;
CREATE FUNCTION negozi.trg_aggiorna_punti_tessera ()
	RETURNS trigger
	LANGUAGE plpgsql
	VOLATILE 
	CALLED ON NULL INPUT
	SECURITY INVOKER
	PARALLEL UNSAFE
	COST 1
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
-- ddl-end --
ALTER FUNCTION negozi.trg_aggiorna_punti_tessera() OWNER TO postgres;
-- ddl-end --

-- object: trg_aggiorna_punti | type: TRIGGER --
-- DROP TRIGGER IF EXISTS trg_aggiorna_punti ON negozi.fatture CASCADE;
CREATE TRIGGER trg_aggiorna_punti
	AFTER UPDATE OF totale_pagato
	ON negozi.fatture
	FOR EACH ROW
	EXECUTE PROCEDURE negozi.trg_aggiorna_punti_tessera();
-- ddl-end --

-- object: negozi.trg_aggiorna_ordine | type: FUNCTION --
-- DROP FUNCTION IF EXISTS negozi.trg_aggiorna_ordine() CASCADE;
CREATE FUNCTION negozi.trg_aggiorna_ordine ()
	RETURNS trigger
	LANGUAGE plpgsql
	VOLATILE 
	CALLED ON NULL INPUT
	SECURITY INVOKER
	PARALLEL UNSAFE
	COST 1
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
-- ddl-end --
ALTER FUNCTION negozi.trg_aggiorna_ordine() OWNER TO postgres;
-- ddl-end --

-- object: trg_aggiorna_ordine | type: TRIGGER --
-- DROP TRIGGER IF EXISTS trg_aggiorna_ordine ON negozi.ordini_fornitori CASCADE;
CREATE TRIGGER trg_aggiorna_ordine
	AFTER UPDATE OF stato_ordine
	ON negozi.ordini_fornitori
	FOR EACH ROW
	EXECUTE PROCEDURE negozi.trg_aggiorna_ordine();
-- ddl-end --

-- object: negozi.aggiorna_ordine | type: FUNCTION --
-- DROP FUNCTION IF EXISTS negozi.aggiorna_ordine(integer,text,text) CASCADE;
CREATE FUNCTION negozi.aggiorna_ordine (_id_ordine integer, _new_stato text, _old_stato text)
	RETURNS void
	LANGUAGE plpgsql
	VOLATILE 
	CALLED ON NULL INPUT
	SECURITY INVOKER
	PARALLEL UNSAFE
	COST 1
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
-- ddl-end --
ALTER FUNCTION negozi.aggiorna_ordine(integer,text,text) OWNER TO postgres;
-- ddl-end --

-- object: negozi.crea_tessera_cliente | type: FUNCTION --
-- DROP FUNCTION IF EXISTS negozi.crea_tessera_cliente(integer,integer) CASCADE;
CREATE FUNCTION negozi.crea_tessera_cliente (_id_cliente integer, _id_negozio integer)
	RETURNS integer
	LANGUAGE plpgsql
	VOLATILE 
	CALLED ON NULL INPUT
	SECURITY INVOKER
	PARALLEL UNSAFE
	COST 1
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
-- ddl-end --
ALTER FUNCTION negozi.crea_tessera_cliente(integer,integer) OWNER TO postgres;
-- ddl-end --

-- object: negozi.lista_ordini_fornitore | type: FUNCTION --
-- DROP FUNCTION IF EXISTS negozi.lista_ordini_fornitore(char) CASCADE;
CREATE FUNCTION negozi.lista_ordini_fornitore (_piva_fornitore char)
	RETURNS TABLE (nome_fornitore text, nome_prodotto text, nome_negozio text, quantita integer, data_ordine date)
	LANGUAGE plpgsql
	VOLATILE 
	CALLED ON NULL INPUT
	SECURITY INVOKER
	PARALLEL UNSAFE
	COST 1
	AS $$
BEGIN
  RETURN QUERY
  SELECT
		f.nome_fornitore, 
		p.nome_prodotto,
		n.nome_negozio,
		of.quantita,
		of.data_ordine
	  FROM negozi.ordini_fornitori of
	  LEFT JOIN negozi.fornitori f ON f.piva = of.fornitore
	  LEFT JOIN negozi.prodotti  p ON p.id_prodotto = of.prodotto
	  LEFT JOIN negozi.negozi n ON n.id_negozio = of.negozio
		WHERE of.fornitore = _piva_fornitore
  ORDER BY of.data_ordine DESC, of.id_ordine DESC;
END;
$$;
-- ddl-end --
ALTER FUNCTION negozi.lista_ordini_fornitore(char) OWNER TO postgres;
-- ddl-end --

-- object: fk_utente | type: CONSTRAINT --
-- ALTER TABLE auth.utente_ruolo DROP CONSTRAINT IF EXISTS fk_utente CASCADE;
ALTER TABLE auth.utente_ruolo ADD CONSTRAINT fk_utente FOREIGN KEY (id_utente)
REFERENCES auth.utenti (id_utente) MATCH SIMPLE
ON DELETE CASCADE ON UPDATE CASCADE;
-- ddl-end --

-- object: fk_ruolo | type: CONSTRAINT --
-- ALTER TABLE auth.utente_ruolo DROP CONSTRAINT IF EXISTS fk_ruolo CASCADE;
ALTER TABLE auth.utente_ruolo ADD CONSTRAINT fk_ruolo FOREIGN KEY (id_ruolo)
REFERENCES auth.ruolo (id_ruolo) MATCH SIMPLE
ON DELETE CASCADE ON UPDATE CASCADE;
-- ddl-end --

-- object: fk_utente | type: CONSTRAINT --
-- ALTER TABLE auth.sessions DROP CONSTRAINT IF EXISTS fk_utente CASCADE;
ALTER TABLE auth.sessions ADD CONSTRAINT fk_utente FOREIGN KEY (utente)
REFERENCES auth.utenti (id_utente) MATCH SIMPLE
ON DELETE CASCADE ON UPDATE CASCADE;
-- ddl-end --

-- object: fk_utente | type: CONSTRAINT --
-- ALTER TABLE auth.reset_token DROP CONSTRAINT IF EXISTS fk_utente CASCADE;
ALTER TABLE auth.reset_token ADD CONSTRAINT fk_utente FOREIGN KEY (utente)
REFERENCES auth.utenti (id_utente) MATCH SIMPLE
ON DELETE CASCADE ON UPDATE CASCADE;
-- ddl-end --

-- object: utente_fk | type: CONSTRAINT --
-- ALTER TABLE negozi.clienti DROP CONSTRAINT IF EXISTS utente_fk CASCADE;
ALTER TABLE negozi.clienti ADD CONSTRAINT utente_fk FOREIGN KEY (utente)
REFERENCES auth.utenti (id_utente) MATCH SIMPLE
ON DELETE CASCADE ON UPDATE CASCADE;
-- ddl-end --

-- object: tessera_fk | type: CONSTRAINT --
-- ALTER TABLE negozi.clienti DROP CONSTRAINT IF EXISTS tessera_fk CASCADE;
ALTER TABLE negozi.clienti ADD CONSTRAINT tessera_fk FOREIGN KEY (tessera)
REFERENCES negozi.tessere (id_tessera) MATCH SIMPLE
ON DELETE SET NULL ON UPDATE NO ACTION;
-- ddl-end --

-- object: tessera_fk | type: CONSTRAINT --
-- ALTER TABLE negozi.tessere DROP CONSTRAINT IF EXISTS tessera_fk CASCADE;
ALTER TABLE negozi.tessere ADD CONSTRAINT tessera_fk FOREIGN KEY (negozio_emittente)
REFERENCES negozi.negozi (id_negozio) MATCH SIMPLE
ON DELETE CASCADE ON UPDATE NO ACTION;
-- ddl-end --

-- object: negozio_fk | type: CONSTRAINT --
-- ALTER TABLE negozi.orari DROP CONSTRAINT IF EXISTS negozio_fk CASCADE;
ALTER TABLE negozi.orari ADD CONSTRAINT negozio_fk FOREIGN KEY (negozio)
REFERENCES negozi.negozi (id_negozio) MATCH SIMPLE
ON DELETE CASCADE ON UPDATE CASCADE;
-- ddl-end --

-- object: negozio_fk | type: CONSTRAINT --
-- ALTER TABLE negozi.listino_negozio DROP CONSTRAINT IF EXISTS negozio_fk CASCADE;
ALTER TABLE negozi.listino_negozio ADD CONSTRAINT negozio_fk FOREIGN KEY (negozio)
REFERENCES negozi.negozi (id_negozio) MATCH SIMPLE
ON DELETE CASCADE ON UPDATE CASCADE;
-- ddl-end --

-- object: prodotto_fk | type: CONSTRAINT --
-- ALTER TABLE negozi.listino_negozio DROP CONSTRAINT IF EXISTS prodotto_fk CASCADE;
ALTER TABLE negozi.listino_negozio ADD CONSTRAINT prodotto_fk FOREIGN KEY (prodotto)
REFERENCES negozi.prodotti (id_prodotto) MATCH SIMPLE
ON DELETE CASCADE ON UPDATE CASCADE;
-- ddl-end --

-- object: piva_fornitore_fk | type: CONSTRAINT --
-- ALTER TABLE negozi.magazzino_fornitore DROP CONSTRAINT IF EXISTS piva_fornitore_fk CASCADE;
ALTER TABLE negozi.magazzino_fornitore ADD CONSTRAINT piva_fornitore_fk FOREIGN KEY (piva_fornitore)
REFERENCES negozi.fornitori (piva) MATCH SIMPLE
ON DELETE CASCADE ON UPDATE CASCADE;
-- ddl-end --

-- object: prodotto_fk | type: CONSTRAINT --
-- ALTER TABLE negozi.magazzino_fornitore DROP CONSTRAINT IF EXISTS prodotto_fk CASCADE;
ALTER TABLE negozi.magazzino_fornitore ADD CONSTRAINT prodotto_fk FOREIGN KEY (prodotto)
REFERENCES negozi.prodotti (id_prodotto) MATCH SIMPLE
ON DELETE CASCADE ON UPDATE CASCADE;
-- ddl-end --

-- object: fornitore_fk | type: CONSTRAINT --
-- ALTER TABLE negozi.ordini_fornitori DROP CONSTRAINT IF EXISTS fornitore_fk CASCADE;
ALTER TABLE negozi.ordini_fornitori ADD CONSTRAINT fornitore_fk FOREIGN KEY (fornitore)
REFERENCES negozi.fornitori (piva) MATCH SIMPLE
ON DELETE NO ACTION ON UPDATE NO ACTION;
-- ddl-end --

-- object: negozio_fk | type: CONSTRAINT --
-- ALTER TABLE negozi.ordini_fornitori DROP CONSTRAINT IF EXISTS negozio_fk CASCADE;
ALTER TABLE negozi.ordini_fornitori ADD CONSTRAINT negozio_fk FOREIGN KEY (negozio)
REFERENCES negozi.negozi (id_negozio) MATCH SIMPLE
ON DELETE NO ACTION ON UPDATE NO ACTION;
-- ddl-end --

-- object: prodotto_fk | type: CONSTRAINT --
-- ALTER TABLE negozi.ordini_fornitori DROP CONSTRAINT IF EXISTS prodotto_fk CASCADE;
ALTER TABLE negozi.ordini_fornitori ADD CONSTRAINT prodotto_fk FOREIGN KEY (prodotto)
REFERENCES negozi.prodotti (id_prodotto) MATCH SIMPLE
ON DELETE NO ACTION ON UPDATE NO ACTION;
-- ddl-end --

-- object: cliente_fk | type: CONSTRAINT --
-- ALTER TABLE negozi.fatture DROP CONSTRAINT IF EXISTS cliente_fk CASCADE;
ALTER TABLE negozi.fatture ADD CONSTRAINT cliente_fk FOREIGN KEY (cliente)
REFERENCES negozi.clienti (id_cliente) MATCH SIMPLE
ON DELETE RESTRICT ON UPDATE CASCADE;
-- ddl-end --

-- object: fattura_fk | type: CONSTRAINT --
-- ALTER TABLE negozi.dettagli_fattura DROP CONSTRAINT IF EXISTS fattura_fk CASCADE;
ALTER TABLE negozi.dettagli_fattura ADD CONSTRAINT fattura_fk FOREIGN KEY (fattura)
REFERENCES negozi.fatture (id_fattura) MATCH SIMPLE
ON DELETE CASCADE ON UPDATE CASCADE;
-- ddl-end --

-- object: prodotto_fk | type: CONSTRAINT --
-- ALTER TABLE negozi.dettagli_fattura DROP CONSTRAINT IF EXISTS prodotto_fk CASCADE;
ALTER TABLE negozi.dettagli_fattura ADD CONSTRAINT prodotto_fk FOREIGN KEY (prodotto)
REFERENCES negozi.prodotti (id_prodotto) MATCH SIMPLE
ON DELETE CASCADE ON UPDATE CASCADE;
-- ddl-end --


