-- ============================================================================
-- SCRIPT POPOLAMENTO NEGOZI RETRO GAMING - VERSIONE SEMPLIFICATA
-- Password per tutti: retro1980
-- ============================================================================

-- Aggiungi colonna immagini
ALTER TABLE negozi.prodotti ADD COLUMN IF NOT EXISTS immagine_url TEXT;

-- RUOLI
INSERT INTO auth.ruolo (nome, descrizione) VALUES
  ('manager', 'Gestore catena negozi'),
  ('cliente', 'Cliente finale')
ON CONFLICT (nome) DO NOTHING;

-- MANAGER
INSERT INTO auth.utenti (email, username, password, attivo)
VALUES ('manager@retrogaming.it', 'retromanager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE)
ON CONFLICT (email) DO UPDATE SET attivo = TRUE;

-- Ruolo manager
INSERT INTO auth.utente_ruolo (id_utente, id_ruolo)
SELECT
  (SELECT id_utente FROM auth.utenti WHERE email = 'manager@retrogaming.it'),
  (SELECT id_ruolo FROM auth.ruolo WHERE nome = 'manager')
WHERE NOT EXISTS (
  SELECT 1 FROM auth.utente_ruolo ur
  JOIN auth.utenti u ON ur.id_utente = u.id_utente
  WHERE u.email = 'manager@retrogaming.it'
);

-- CLIENTI
DO $$
DECLARE
  v_user_id INT;
  v_role_id INT;
BEGIN
  SELECT id_ruolo INTO v_role_id FROM auth.ruolo WHERE nome = 'cliente';

  -- Cliente 1: Mario Rossi
  INSERT INTO auth.utenti (email, username, password, attivo)
  VALUES ('mario.rossi@email.it', 'mariorossi', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE)
  ON CONFLICT (email) DO UPDATE SET attivo = TRUE
  RETURNING id_utente INTO v_user_id;

  INSERT INTO auth.utente_ruolo (id_utente, id_ruolo) VALUES (v_user_id, v_role_id) ON CONFLICT DO NOTHING;
  INSERT INTO negozi.clienti (utente, cf, nome, cognome) VALUES (v_user_id, 'RSSMRA80A01H501Z', 'Mario', 'Rossi') ON CONFLICT DO NOTHING;

  -- Cliente 2: Laura Bianchi
  INSERT INTO auth.utenti (email, username, password, attivo)
  VALUES ('laura.bianchi@email.it', 'laurabianchi', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE)
  ON CONFLICT (email) DO UPDATE SET attivo = TRUE
  RETURNING id_utente INTO v_user_id;

  INSERT INTO auth.utente_ruolo (id_utente, id_ruolo) VALUES (v_user_id, v_role_id) ON CONFLICT DO NOTHING;
  INSERT INTO negozi.clienti (utente, cf, nome, cognome) VALUES (v_user_id, 'BNCLRA85M42F205W', 'Laura', 'Bianchi') ON CONFLICT DO NOTHING;

  -- Cliente 3: Paolo Verdi
  INSERT INTO auth.utenti (email, username, password, attivo)
  VALUES ('paolo.verdi@email.it', 'paoloverdi', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE)
  ON CONFLICT (email) DO UPDATE SET attivo = TRUE
  RETURNING id_utente INTO v_user_id;

  INSERT INTO auth.utente_ruolo (id_utente, id_ruolo) VALUES (v_user_id, v_role_id) ON CONFLICT DO NOTHING;
  INSERT INTO negozi.clienti (utente, cf, nome, cognome) VALUES (v_user_id, 'VRDPLA78C15L219X', 'Paolo', 'Verdi') ON CONFLICT DO NOTHING;
END $$;

-- NEGOZI
INSERT INTO negozi.negozio (responsabile, indirizzo) VALUES
  ((SELECT id_utente FROM auth.utenti WHERE email = 'manager@retrogaming.it'), 'Via Dante 42, 20121 Milano'),
  ((SELECT id_utente FROM auth.utenti WHERE email = 'manager@retrogaming.it'), 'Corso Vittorio Emanuele 156, 00186 Roma'),
  ((SELECT id_utente FROM auth.utenti WHERE email = 'manager@retrogaming.it'), 'Via Garibaldi 88, 10122 Torino');

-- ORARI (dow=1-7, iod=1 mattina, iod=2 pomeriggio)
INSERT INTO negozi.orari (negozio, dow, iod, apertura, chiusura)
SELECT n.id_negozio, d, 1, '09:00:00', '13:00:00'
FROM negozi.negozio n
CROSS JOIN generate_series(1, 6) d;

INSERT INTO negozi.orari (negozio, dow, iod, apertura, chiusura)
SELECT n.id_negozio, d, 2, '14:00:00', '19:00:00'
FROM negozi.negozio n
CROSS JOIN generate_series(1, 6) d;

-- FORNITORI
INSERT INTO negozi.fornitori (piva, ragione_sociale, indirizzo, email, telefono, attivo) VALUES
  ('01234567890', 'Vintage Computer Wholesale SRL', 'Via Industriale 45, Segrate (MI)', 'info@vintagewholesale.it', '02-87654321', TRUE),
  ('09876543210', 'Retro Gaming Italia SPA', 'Viale Europa 123, Roma', 'vendite@retrogamingitalia.it', '06-12345678', TRUE)
ON CONFLICT (piva) DO NOTHING;

-- PRODOTTI
INSERT INTO negozi.prodotti (nome, descrizione, immagine_url) VALUES
  ('Commodore 64', 'Il computer più venduto! CPU MOS 6510, 64KB RAM. Testato e funzionante.', '/images/products/c64.jpg'),
  ('Commodore Amiga 500', 'CPU Motorola 68000, 512KB RAM. Sistema Workbench 1.3 incluso.', '/images/products/amiga500.jpg'),
  ('ZX Spectrum 48K', 'Computer Sinclair. CPU Z80A, 48KB RAM, tastiera gomma originale.', '/images/products/spectrum.jpg'),
  ('Nintendo NES', 'La console leggendaria! 2 controller + Zapper + Super Mario Bros.', '/images/products/nes.jpg'),
  ('Sega Master System', 'Console 8-bit Sega. Include controller e Alex Kidd built-in.', '/images/products/mastersystem.jpg'),
  ('Joystick Competition Pro', 'Il migliore! Microswitches robusti, impugnatura ergonomica.', '/images/products/joystick.jpg'),
  ('Floppy Disk 5.25" x10', 'Dischetti vergini doppia densità, confezione sigillata.', '/images/products/floppy.jpg'),
  ('The Last Ninja C64', 'Capolavoro isometrico di System 3. Versione cassetta originale.', '/images/products/lastninja.jpg');

-- MAGAZZINO FORNITORI
INSERT INTO negozi.magazzino_fornitore (piva_fornitore, prodotto, prezzo, quantita) VALUES
  ('01234567890', 1, 120.00, 50),
  ('01234567890', 2, 280.00, 30),
  ('01234567890', 3, 85.00, 45),
  ('01234567890', 7, 4.50, 500),
  ('09876543210', 4, 95.00, 80),
  ('09876543210', 5, 75.00, 60),
  ('09876543210', 6, 18.00, 150),
  ('09876543210', 8, 8.00, 200);

-- LISTINO PREZZI
INSERT INTO negozi.listino_negozio (negozio, prodotto, prezzo_listino) VALUES
  -- Milano (negozio 1)
  (1, 1, 229.99), (1, 2, 549.99), (1, 3, 159.99), (1, 4, 189.99),
  (1, 5, 149.99), (1, 6, 35.99), (1, 7, 8.99), (1, 8, 16.99),
  -- Roma (negozio 2)
  (2, 1, 219.99), (2, 2, 529.99), (2, 3, 154.99), (2, 4, 179.99),
  (2, 5, 144.99), (2, 6, 34.99), (2, 7, 8.49), (2, 8, 15.99),
  -- Torino (negozio 3)
  (3, 1, 209.99), (3, 2, 499.99), (3, 3, 149.99), (3, 4, 169.99),
  (3, 5, 134.99), (3, 6, 32.99), (3, 7, 7.99), (3, 8, 14.99);

-- TESSERE FEDELTÀ
INSERT INTO negozi.tessere (negozio_emittente, data_richiesta, saldo_punti, archiviata) VALUES
  (1, CURRENT_DATE - 100, 150, FALSE),
  (1, CURRENT_DATE - 200, 280, FALSE),
  (2, CURRENT_DATE - 150, 320, FALSE);

-- ASSOCIAZIONE CLIENTI-TESSERE
INSERT INTO negozi.cliente_tessera (cliente, tessera)
SELECT c.id_cliente, t.id_tessera
FROM negozi.clienti c
JOIN LATERAL (
  SELECT id_tessera
  FROM negozi.tessere
  WHERE id_tessera NOT IN (SELECT tessera FROM negozi.cliente_tessera)
  ORDER BY id_tessera
  LIMIT 1
) t ON TRUE
WHERE c.id_cliente NOT IN (SELECT cliente FROM negozi.cliente_tessera);

-- RIEPILOGO
SELECT '╔══════════════════════════════════════════════════════╗' as " ";
SELECT '║  DATABASE NEGOZI RETRO GAMING - POPOLATO!           ║' as " ";
SELECT '╠══════════════════════════════════════════════════════╣' as " ";
SELECT '║  CREDENZIALI DI ACCESSO:                             ║' as " ";
SELECT '║                                                      ║' as " ";
SELECT '║  Manager: manager@retrogaming.it / retro1980         ║' as " ";
SELECT '║  Clienti: mario.rossi@email.it / retro1980           ║' as " ";
SELECT '║           laura.bianchi@email.it / retro1980         ║' as " ";
SELECT '║           paolo.verdi@email.it / retro1980           ║' as " ";
SELECT '╠══════════════════════════════════════════════════════╣' as " ";
SELECT ('║  Prodotti inseriti: ' || LPAD(COUNT(*)::TEXT, 26, ' ') || ' ║') as " " FROM negozi.prodotti;
SELECT ('║  Negozi aperti:     ' || LPAD(COUNT(*)::TEXT, 26, ' ') || ' ║') as " " FROM negozi.negozio;
SELECT ('║  Clienti registrati:' || LPAD(COUNT(*)::TEXT, 26, ' ') || ' ║') as " " FROM negozi.clienti;
SELECT ('║  Tessere emesse:    ' || LPAD(COUNT(*)::TEXT, 26, ' ') || ' ║') as " " FROM negozi.tessere;
SELECT '╚══════════════════════════════════════════════════════╝' as " ";
