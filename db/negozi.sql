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
	username text NOT NULL,
	password text NOT NULL,
	attivo boolean NOT NULL DEFAULT FALSE,
	creato timestamp NOT NULL DEFAULT now(),
	ultimo_accesso timestamp,
	CONSTRAINT id_utente_pk PRIMARY KEY (id_utente),
	CONSTRAINT unique_email UNIQUE (email),
	CONSTRAINT unique_username UNIQUE (username)
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
IF NEW.email IS NOT NULL THEN NEW.email := lower(NEW.email); END IF;
$$;
-- ddl-end --
ALTER FUNCTION auth.email_lowercase() OWNER TO postgres;
-- ddl-end --

-- object: email_to_lower | type: TRIGGER --
-- DROP TRIGGER IF EXISTS email_to_lower ON auth.utenti CASCADE;
CREATE TRIGGER email_to_lower
	BEFORE INSERT OR UPDATE
	ON auth.utenti
	FOR EACH STATEMENT
	EXECUTE PROCEDURE auth.email_lowercase();
-- ddl-end --

-- object: negozi.clienti | type: TABLE --
-- DROP TABLE IF EXISTS negozi.clienti CASCADE;
CREATE TABLE negozi.clienti (
	id_cliente serial NOT NULL,
	cf char(16) NOT NULL,
	nome text NOT NULL,
	cognome text NOT NULL,
	utente integer NOT NULL,
	CONSTRAINT clienti_pk PRIMARY KEY (id_cliente),
	CONSTRAINT cf_unique UNIQUE (cf)
);
-- ddl-end --
ALTER TABLE negozi.clienti OWNER TO postgres;
-- ddl-end --

-- object: negozi.negozio | type: TABLE --
-- DROP TABLE IF EXISTS negozi.negozio CASCADE;
CREATE TABLE negozi.negozio (
	id_negozio serial NOT NULL,
	responsabile integer NOT NULL,
	indirizzo text NOT NULL,
	CONSTRAINT negozio_pk PRIMARY KEY (id_negozio)
);
-- ddl-end --
ALTER TABLE negozi.negozio OWNER TO postgres;
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

-- object: negozi.stroico_tessere | type: TABLE --
-- DROP TABLE IF EXISTS negozi.stroico_tessere CASCADE;
CREATE TABLE negozi.stroico_tessere (
	id_storico serial NOT NULL,
	codice_tessera integer NOT NULL,
	negozio_emittente integer NOT NULL,
	data_emissione date NOT NULL,
	CONSTRAINT stroico_tessere_pk PRIMARY KEY (id_storico)
);
-- ddl-end --
ALTER TABLE negozi.stroico_tessere OWNER TO postgres;
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
-- DROP TRIGGER IF EXISTS trg_archivia_tessere_negozio ON negozi.negozio CASCADE;
CREATE TRIGGER trg_archivia_tessere_negozio
	BEFORE DELETE 
	ON negozi.negozio
	FOR EACH ROW
	EXECUTE PROCEDURE negozi.trg_archivia_tessere_negozio();
-- ddl-end --

-- object: negozi.prodotti | type: TABLE --
-- DROP TABLE IF EXISTS negozi.prodotti CASCADE;
CREATE TABLE negozi.prodotti (
	id_prodotto serial NOT NULL,
	nome text NOT NULL,
	descrizione text,
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

-- object: negozi.cliente_tessera | type: TABLE --
-- DROP TABLE IF EXISTS negozi.cliente_tessera CASCADE;
CREATE TABLE negozi.cliente_tessera (
	cliente integer NOT NULL,
	tessera integer NOT NULL,
	CONSTRAINT cliente_unique UNIQUE (cliente)
);
-- ddl-end --
ALTER TABLE negozi.cliente_tessera OWNER TO postgres;
-- ddl-end --

-- object: negozi.listino_negozio | type: TABLE --
-- DROP TABLE IF EXISTS negozi.listino_negozio CASCADE;
CREATE TABLE negozi.listino_negozio (
	negozio integer NOT NULL,
	prodotto integer NOT NULL,
	prezzo_listino numeric(10,2) NOT NULL,
	CONSTRAINT listino_negozio_pk PRIMARY KEY (negozio,prodotto)
);
-- ddl-end --
ALTER TABLE negozi.listino_negozio OWNER TO postgres;
-- ddl-end --

-- object: negozi.fornitori | type: TABLE --
-- DROP TABLE IF EXISTS negozi.fornitori CASCADE;
CREATE TABLE negozi.fornitori (
	piva char(11) NOT NULL,
	ragione_sociale text NOT NULL,
	indirizzo text,
	email text,
	telefono text,
	attivo boolean NOT NULL DEFAULT true,
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
	negozio integer NOT NULL,
	cliente integer NOT NULL,
	data_fattura date NOT NULL DEFAULT now(),
	punti_sconto numeric(4) NOT NULL DEFAULT 0,
	totale_pagato numeric(10,2) NOT NULL DEFAULT 0,
	valore_scontato numeric(10,2),
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

-- object: negozi.fn_ricalcola_totale_fattura | type: FUNCTION --
-- DROP FUNCTION IF EXISTS negozi.fn_ricalcola_totale_fattura(integer) CASCADE;
CREATE FUNCTION negozi.fn_ricalcola_totale_fattura (IN id_fattura integer)
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
  SELECT COALESCE(SUM(quantita * prezzo_unitario),0)
    INTO v_subtot
  FROM negozi.dettagli_fattura
  WHERE idfattura = _idfattura;

  SELECT COALESCE(sconto_percentuale,0)
    INTO v_sconto
  FROM negozi.fattura
  WHERE idfattura = _idfattura;

  v_totale := GREATEST(v_subtot * (1 - v_sconto/100.0), 0);

  UPDATE negozi.fattura
     SET totale_pagato = v_totale
   WHERE idfattura = _idfattura;
END;
$$;
-- ddl-end --
ALTER FUNCTION negozi.fn_ricalcola_totale_fattura(integer) OWNER TO postgres;
-- ddl-end --

-- object: negozi.trg_df_ricalcola | type: FUNCTION --
-- DROP FUNCTION IF EXISTS negozi.trg_df_ricalcola() CASCADE;
CREATE FUNCTION negozi.trg_df_ricalcola ()
	RETURNS trigger
	LANGUAGE plpgsql
	VOLATILE 
	CALLED ON NULL INPUT
	SECURITY INVOKER
	PARALLEL UNSAFE
	COST 1
	AS $$
BEGIN
  PERFORM negozi.fn_ricalcola_totale_fattura(
    COALESCE(NEW.idfattura, OLD.idfattura)
  );
  RETURN COALESCE(NEW, OLD);
END;
$$;
-- ddl-end --
ALTER FUNCTION negozi.trg_df_ricalcola() OWNER TO postgres;
-- ddl-end --

-- object: trg_df_totale | type: TRIGGER --
-- DROP TRIGGER IF EXISTS trg_df_totale ON negozi.dettagli_fattura CASCADE;
CREATE TRIGGER trg_df_totale
	AFTER INSERT OR DELETE OR UPDATE
	ON negozi.dettagli_fattura
	FOR EACH ROW
	EXECUTE PROCEDURE negozi.trg_df_ricalcola();
-- ddl-end --

-- object: negozi.trg_ft_sconto_ricalcola | type: FUNCTION --
-- DROP FUNCTION IF EXISTS negozi.trg_ft_sconto_ricalcola() CASCADE;
CREATE FUNCTION negozi.trg_ft_sconto_ricalcola ()
	RETURNS trigger
	LANGUAGE plpgsql
	VOLATILE 
	CALLED ON NULL INPUT
	SECURITY INVOKER
	PARALLEL UNSAFE
	COST 1
	AS $$
BEGIN
  PERFORM negozi.fn_ricalcola_totale_fattura(NEW.idfattura);
  RETURN NEW;
END;
$$;
-- ddl-end --
ALTER FUNCTION negozi.trg_ft_sconto_ricalcola() OWNER TO postgres;
-- ddl-end --

-- object: trg_fattura_sconto_totale | type: TRIGGER --
-- DROP TRIGGER IF EXISTS trg_fattura_sconto_totale ON negozi.fatture CASCADE;
CREATE TRIGGER trg_fattura_sconto_totale
	AFTER INSERT OR DELETE OR UPDATE
	ON negozi.fatture
	FOR EACH ROW
	EXECUTE PROCEDURE negozi.trg_ft_sconto_ricalcola();
-- ddl-end --

-- object: negozi.v_lista_tessere_negozio | type: VIEW --
-- DROP VIEW IF EXISTS negozi.v_lista_tessere_negozio CASCADE;
CREATE VIEW negozi.v_lista_tessere_negozio
AS 
SELECT
  c.id_cliente              AS id,
  c.nome,
  c.cognome,
  t.id_tessera          AS numero_tessera,
  COALESCE(t.saldo_punti,0) AS saldo_punti
FROM negozi.cliente_tessera ct
JOIN negozi.tessere t
       	ON t.id_tessera = ct.tessera
JOIN negozi.clienti c
		ON c.id_cliente = ct.cliente;
-- ddl-end --
ALTER VIEW negozi.v_lista_tessere_negozio OWNER TO postgres;
-- ddl-end --

-- object: negozi.aggiorna_punti | type: FUNCTION --
-- DROP FUNCTION IF EXISTS negozi.aggiorna_punti(integer,numeric,numeric) CASCADE;
CREATE FUNCTION negozi.aggiorna_punti (_idcliente integer, _totale_fattura numeric, _sconto numeric)
	RETURNS void
	LANGUAGE plpgsql
	VOLATILE 
	CALLED ON NULL INPUT
	SECURITY INVOKER
	PARALLEL UNSAFE
	COST 1
	AS $$
BEGIN
SELECT codice_tessera INTO v_tessera
		FROM clienti WHERE id_cliente = _idcliente;

IF v_tessera IS NULL THEN
	RETURN;
END IF;

punti_fattura := floor(_totale_fattura)::int;

  UPDATE negozi.tessera
     SET saldo_punti = saldo_punti + - _punti_sconto
   WHERE id_tessera = v_tessera;
END;
$$;
-- ddl-end --
ALTER FUNCTION negozi.aggiorna_punti(integer,numeric,numeric) OWNER TO postgres;
-- ddl-end --

-- object: negozi.trg_aggiorna_punti_tessera | type: FUNCTION --
-- DROP FUNCTION IF EXISTS negozi.trg_aggiorna_punti_tessera() CASCADE;
CREATE FUNCTION negozi.trg_aggiorna_punti_tessera ()
	RETURNS trigger
	LANGUAGE plpgsql
	VOLATILE 
	CALLED ON NULL INPUT
	SECURITY INVOKER
	PARALLEL SAFE
	COST 1
	AS $$
BEGIN
  PERFORM negozi.aggiorna_punti(
    NEW.id_cliente,
    NEW.totale_pagato
	NEW.punti_sconto
  );
  RETURN NEW;
END;
$$;
-- ddl-end --
ALTER FUNCTION negozi.trg_aggiorna_punti_tessera() OWNER TO postgres;
-- ddl-end --

-- object: trg_aggiorna_punti_tessera | type: TRIGGER --
-- DROP TRIGGER IF EXISTS trg_aggiorna_punti_tessera ON negozi.fatture CASCADE;
CREATE TRIGGER trg_aggiorna_punti_tessera
	AFTER INSERT 
	ON negozi.fatture
	FOR EACH ROW
	EXECUTE PROCEDURE negozi.trg_aggiorna_punti_tessera();
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
  -- Salva nello storico tutte le tessere emesse da questo negozio,
  -- catturando anche eventuale cliente che le possiede (via cliente.codice_tessera)
  INSERT INTO negozi.storico_tessere
    (codice_tessera, negozio_emittente, data_emissione)
  SELECT t.id_tessera, t.negozio_emittente, t.data_richiesta
  FROM negozi.tessere t
  WHERE t.negozio_emittente = _idnegozio;

  DELETE FROM negozi.tessere
  WHERE negozio_emittente = _idnegozio;

RETURN;
END;
$$;
-- ddl-end --
ALTER FUNCTION negozi.archivia_tessere_negozio(integer) OWNER TO postgres;
-- ddl-end --

-- object: negozi.trg_agiorna_magazzino_fornitore | type: FUNCTION --
-- DROP FUNCTION IF EXISTS negozi.trg_agiorna_magazzino_fornitore() CASCADE;
CREATE FUNCTION negozi.trg_agiorna_magazzino_fornitore ()
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
ALTER FUNCTION negozi.trg_agiorna_magazzino_fornitore() OWNER TO postgres;
-- ddl-end --

-- object: trigger_aggiorna_magazzino | type: TRIGGER --
-- DROP TRIGGER IF EXISTS trigger_aggiorna_magazzino ON negozi.ordini_fornitori CASCADE;
CREATE TRIGGER trigger_aggiorna_magazzino
	BEFORE INSERT 
	ON negozi.ordini_fornitori
	FOR EACH ROW
	EXECUTE PROCEDURE negozi.trg_agiorna_magazzino_fornitore();
-- ddl-end --

-- object: negozi.aggiorna_magazzino_fornitore | type: FUNCTION --
-- DROP FUNCTION IF EXISTS negozi.aggiorna_magazzino_fornitore(integer,integer,integer) CASCADE;
CREATE FUNCTION negozi.aggiorna_magazzino_fornitore (_fornitore integer, _prodotto integer, _quantita integer)
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
   WHERE mf.fornitore = _fornitore
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
ALTER FUNCTION negozi.aggiorna_magazzino_fornitore(integer,integer,integer) OWNER TO postgres;
-- ddl-end --

-- object: negozi.miglior_fornitore | type: FUNCTION --
-- DROP FUNCTION IF EXISTS negozi.miglior_fornitore(integer,integer) CASCADE;
CREATE FUNCTION negozi.miglior_fornitore (_prodotto integer, _quantita integer)
	RETURNS integer
	LANGUAGE plpgsql
	VOLATILE 
	CALLED ON NULL INPUT
	SECURITY INVOKER
	PARALLEL UNSAFE
	COST 1
	AS $$
DECLARE v_fornitore
BEGIN
	SELECT piva_fornitore INTO v_fornitore
	FROM magazzino_fornitore mf
	WHERE mf.prodotto = _prodotto AND
		  mf.quantita >= _quantita
	ORDER BY mf.prezzo ASC
	LIMIT 1;
RETURN v_fornitore;
END;
$$;
-- ddl-end --
ALTER FUNCTION negozi.miglior_fornitore(integer,integer) OWNER TO postgres;
-- ddl-end --

-- object: negozi.v_lista_ordini_fornitore | type: VIEW --
-- DROP VIEW IF EXISTS negozi.v_lista_ordini_fornitore CASCADE;
CREATE VIEW negozi.v_lista_ordini_fornitore
AS 
SELECT 	of.fornitore,
		f.ragione_sociale, 
		of.prodotto		AS id_prodotto, 
		p.nome			AS nome_prodotto,
		of.quantita, 
		of.data_ordine
FROM negozi.ordini_fornitori of
LEFT JOIN negozi.fornitori f ON f.piva = of.fornitore
LEFT JOIN negozi.prodotti p ON p.id_prodotto = of.prodotto;
-- ddl-end --
ALTER VIEW negozi.v_lista_ordini_fornitore OWNER TO postgres;
-- ddl-end --

-- object: negozi.v_saldi_punti_300 | type: VIEW --
-- DROP VIEW IF EXISTS negozi.v_saldi_punti_300 CASCADE;
CREATE VIEW negozi.v_saldi_punti_300
AS 
SELECT nome, cognome
FROM clienti c
LEFT JOIN cliente_tessera ct ON c.id_cliente = ct.cliente
LEFT JOIN tessere t ON ct.tessera = t.id_tessera
WHERE saldo_punti > 300;
-- ddl-end --
ALTER VIEW negozi.v_saldi_punti_300 OWNER TO postgres;
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
-- ALTER TABLE negozi.tessere DROP CONSTRAINT IF EXISTS tessera_fk CASCADE;
ALTER TABLE negozi.tessere ADD CONSTRAINT tessera_fk FOREIGN KEY (negozio_emittente)
REFERENCES negozi.negozio (id_negozio) MATCH SIMPLE
ON DELETE CASCADE ON UPDATE NO ACTION;
-- ddl-end --

-- object: negozio_fk | type: CONSTRAINT --
-- ALTER TABLE negozi.orari DROP CONSTRAINT IF EXISTS negozio_fk CASCADE;
ALTER TABLE negozi.orari ADD CONSTRAINT negozio_fk FOREIGN KEY (negozio)
REFERENCES negozi.negozio (id_negozio) MATCH SIMPLE
ON DELETE CASCADE ON UPDATE CASCADE;
-- ddl-end --

-- object: cliente_fk | type: CONSTRAINT --
-- ALTER TABLE negozi.cliente_tessera DROP CONSTRAINT IF EXISTS cliente_fk CASCADE;
ALTER TABLE negozi.cliente_tessera ADD CONSTRAINT cliente_fk FOREIGN KEY (cliente)
REFERENCES negozi.clienti (id_cliente) MATCH SIMPLE
ON DELETE CASCADE ON UPDATE CASCADE;
-- ddl-end --

-- object: tessera_fk | type: CONSTRAINT --
-- ALTER TABLE negozi.cliente_tessera DROP CONSTRAINT IF EXISTS tessera_fk CASCADE;
ALTER TABLE negozi.cliente_tessera ADD CONSTRAINT tessera_fk FOREIGN KEY (tessera)
REFERENCES negozi.tessere (id_tessera) MATCH SIMPLE
ON DELETE CASCADE ON UPDATE CASCADE;
-- ddl-end --

-- object: negozio_fk | type: CONSTRAINT --
-- ALTER TABLE negozi.listino_negozio DROP CONSTRAINT IF EXISTS negozio_fk CASCADE;
ALTER TABLE negozi.listino_negozio ADD CONSTRAINT negozio_fk FOREIGN KEY (negozio)
REFERENCES negozi.negozio (id_negozio) MATCH SIMPLE
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
REFERENCES negozi.negozio (id_negozio) MATCH SIMPLE
ON DELETE NO ACTION ON UPDATE NO ACTION;
-- ddl-end --

-- object: prodotto_fk | type: CONSTRAINT --
-- ALTER TABLE negozi.ordini_fornitori DROP CONSTRAINT IF EXISTS prodotto_fk CASCADE;
ALTER TABLE negozi.ordini_fornitori ADD CONSTRAINT prodotto_fk FOREIGN KEY (prodotto)
REFERENCES negozi.prodotti (id_prodotto) MATCH SIMPLE
ON DELETE NO ACTION ON UPDATE NO ACTION;
-- ddl-end --

-- object: negozio_fk | type: CONSTRAINT --
-- ALTER TABLE negozi.fatture DROP CONSTRAINT IF EXISTS negozio_fk CASCADE;
ALTER TABLE negozi.fatture ADD CONSTRAINT negozio_fk FOREIGN KEY (negozio)
REFERENCES negozi.negozio (id_negozio) MATCH SIMPLE
ON DELETE RESTRICT ON UPDATE CASCADE;
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


