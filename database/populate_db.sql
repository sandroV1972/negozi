-- ============================================================================
-- SCRIPT POPOLAMENTO DATABASE - NEGOZI RETRO GAMING & COMPUTER ANNI '80
-- ============================================================================

BEGIN;

-- Aggiungi colonna immagini se non esiste
ALTER TABLE negozi.prodotti ADD COLUMN IF NOT EXISTS immagine_url TEXT;

-- RUOLI
INSERT INTO auth.ruolo (nome, descrizione) VALUES
  ('manager', 'Gestore catena negozi'),
  ('cliente', 'Cliente finale')
ON CONFLICT (nome) DO NOTHING;

-- MANAGER (password: retro1980)
INSERT INTO auth.utenti (email, username, password, attivo) VALUES
  ('manager@retrogaming.it', 'retromanager',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE)
ON CONFLICT (email) DO NOTHING;

INSERT INTO auth.utente_ruolo (id_utente, id_ruolo)
SELECT u.id_utente, r.id_ruolo
FROM auth.utenti u, auth.ruolo r
WHERE u.username = 'retromanager' AND r.nome = 'manager'
ON CONFLICT DO NOTHING;

-- CLIENTI (password: retro1980)
INSERT INTO auth.utenti (email, username, password, attivo) VALUES
  ('mario.rossi@email.it', 'mariorossi', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE),
  ('laura.bianchi@email.it', 'laurabianchi', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE),
  ('paolo.verdi@email.it', 'paoloverdi', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE),
  ('anna.neri@email.it', 'annaneri', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE),
  ('luca.ferrari@email.it', 'lucaferrari', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE),
  ('giulia.russo@email.it', 'giuliarusso', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE),
  ('marco.colombo@email.it', 'marcocolombo', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE),
  ('sara.ricci@email.it', 'sararicci', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE),
  ('andrea.bruno@email.it', 'andreabruno', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE),
  ('elena.gallo@email.it', 'elenagallo', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE),
  ('fabio.conti@email.it', 'fabioconti', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE),
  ('chiara.romano@email.it', 'chiararomano', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE),
  ('roberto.moretti@email.it', 'robertomoretti', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE),
  ('valentina.fontana@email.it', 'valentinafontana', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE),
  ('davide.greco@email.it', 'davidegreco', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE)
ON CONFLICT (email) DO NOTHING;

-- Ruolo cliente
INSERT INTO auth.utente_ruolo (id_utente, id_ruolo)
SELECT u.id_utente, r.id_ruolo FROM auth.utenti u, auth.ruolo r
WHERE u.username IN ('mariorossi', 'laurabianchi', 'paoloverdi', 'annaneri', 'lucaferrari',
                     'giuliarusso', 'marcocolombo', 'sararicci', 'andreabruno', 'elenagallo',
                     'fabioconti', 'chiararomano', 'robertomoretti', 'valentinafontana', 'davidegreco')
AND r.nome = 'cliente' ON CONFLICT DO NOTHING;

-- DATI CLIENTI (CF validi di 16 caratteri)
INSERT INTO negozi.clienti (utente, cf, nome, cognome) VALUES
  ((SELECT id_utente FROM auth.utenti WHERE username = 'mariorossi'), 'RSSMRA80A01H501Z', 'Mario', 'Rossi'),
  ((SELECT id_utente FROM auth.utenti WHERE username = 'laurabianchi'), 'BNCLRA85M42F205W', 'Laura', 'Bianchi'),
  ((SELECT id_utente FROM auth.utenti WHERE username = 'paoloverdi'), 'VRDPLA78C15L219X', 'Paolo', 'Verdi'),
  ((SELECT id_utente FROM auth.utenti WHERE username = 'annaneri'), 'NRENNN82D50A794Y', 'Anna', 'Neri'),
  ((SELECT id_utente FROM auth.utenti WHERE username = 'lucaferrari'), 'FRRLCU83E10D612V', 'Luca', 'Ferrari'),
  ((SELECT id_utente FROM auth.utenti WHERE username = 'giuliarusso'), 'RSSGLI86H47H501U', 'Giulia', 'Russo'),
  ((SELECT id_utente FROM auth.utenti WHERE username = 'marcocolombo'), 'CLMMRC79L20F205T', 'Marco', 'Colombo'),
  ((SELECT id_utente FROM auth.utenti WHERE username = 'sararicci'), 'RCCSRA81M52L219S', 'Sara', 'Ricci'),
  ((SELECT id_utente FROM auth.utenti WHERE username = 'andreabruno'), 'BRNNDR84P11A794R', 'Andrea', 'Bruno'),
  ((SELECT id_utente FROM auth.utenti WHERE username = 'elenagallo'), 'GLLLNE87R48D612Q', 'Elena', 'Gallo'),
  ((SELECT id_utente FROM auth.utenti WHERE username = 'fabioconti'), 'CNTFBA80S19H501P', 'Fabio', 'Conti'),
  ((SELECT id_utente FROM auth.utenti WHERE username = 'chiararomano'), 'RMNCHR88T50F205O', 'Chiara', 'Romano'),
  ((SELECT id_utente FROM auth.utenti WHERE username = 'robertomoretti'), 'MRTRRT76A12L219N', 'Roberto', 'Moretti'),
  ((SELECT id_utente FROM auth.utenti WHERE username = 'valentinafontana'), 'FNTVNT89B51A794M', 'Valentina', 'Fontana'),
  ((SELECT id_utente FROM auth.utenti WHERE username = 'davidegreco'), 'GRCDVD81C13D612L', 'Davide', 'Greco')
ON CONFLICT (cf) DO NOTHING;

-- NEGOZI (5 città)
INSERT INTO negozi.negozio (responsabile, indirizzo) VALUES
  ((SELECT id_utente FROM auth.utenti WHERE username = 'retromanager'), 'Via Dante 42, 20121 Milano'),
  ((SELECT id_utente FROM auth.utenti WHERE username = 'retromanager'), 'Corso Vittorio Emanuele 156, 00186 Roma'),
  ((SELECT id_utente FROM auth.utenti WHERE username = 'retromanager'), 'Via Garibaldi 88, 10122 Torino'),
  ((SELECT id_utente FROM auth.utenti WHERE username = 'retromanager'), 'Piazza Maggiore 15, 40124 Bologna'),
  ((SELECT id_utente FROM auth.utenti WHERE username = 'retromanager'), 'Via Mazzini 33, 50123 Firenze');

-- ORARI: Lun-Sab 9:30-19:30
INSERT INTO negozi.orari (negozio, giorno_settimana, ora_apertura, ora_chiusura, chiuso)
SELECT n.id_negozio, g, '09:30:00', '19:30:00', FALSE
FROM negozi.negozio n CROSS JOIN generate_series(1, 6) g;

-- ORARI: Domenica 10:00-18:00
INSERT INTO negozi.orari (negozio, giorno_settimana, ora_apertura, ora_chiusura, chiuso)
SELECT n.id_negozio, 0, '10:00:00', '18:00:00', FALSE FROM negozi.negozio n;

-- FORNITORI
INSERT INTO negozi.fornitori (piva, ragione_sociale, indirizzo, email, telefono, attivo) VALUES
  ('01234567890', 'Vintage Computer Wholesale SRL', 'Via Industriale 45, Segrate (MI)', 'info@vintagewholesale.it', '02-87654321', TRUE),
  ('09876543210', 'Retro Gaming Italia SPA', 'Viale Europa 123, Roma', 'vendite@retrogamingitalia.it', '06-12345678', TRUE),
  ('11223344556', 'Classic Electronics Distribution', 'Corso Francia 88, Torino', 'ordini@classicelectronics.it', '011-9876543', TRUE),
  ('66554433221', 'Old School Tech Supply', 'Via Bologna 67, Firenze', 'supply@oldschooltech.it', '055-7654321', TRUE)
ON CONFLICT (piva) DO NOTHING;

-- PRODOTTI (20 prodotti vintage)
INSERT INTO negozi.prodotti (nome, descrizione, immagine_url) VALUES
  ('Commodore 64', 'Il computer più venduto! CPU MOS 6510, 64KB RAM. Testato e funzionante.', '/images/products/c64.jpg'),
  ('Commodore Amiga 500', 'Rivoluzionario! CPU 68000, 512KB RAM. Sistema Workbench incluso.', '/images/products/amiga500.jpg'),
  ('ZX Spectrum 48K', 'Leggendario Sinclair. CPU Z80A, tastiera gomma originale.', '/images/products/spectrum.jpg'),
  ('Apple II Europlus', 'Apple II italiano. CPU 6502, 48KB RAM, drive incluso.', '/images/products/appleii.jpg'),
  ('Atari 800XL', 'Home computer 64KB. Grafica GTIA, suono Pokey.', '/images/products/atari800xl.jpg'),
  ('MSX Sony HitBit', 'Standard MSX. CPU Z80A, 32KB RAM, BASIC incluso.', '/images/products/msx.jpg'),
  ('Amstrad CPC 464', 'Monitor integrato, lettore cassette. CPU Z80, 64KB.', '/images/products/cpc464.jpg'),
  ('Nintendo NES', 'La console leggendaria! 2 controller + Zapper + Super Mario.', '/images/products/nes.jpg'),
  ('Sega Master System', 'Console 8-bit Sega. Alex Kidd built-in.', '/images/products/mastersystem.jpg'),
  ('Atari 2600', 'La prima console! 5 giochi inclusi.', '/images/products/atari2600.jpg'),
  ('Datasette C1530', 'Lettore cassette Commodore originale.', '/images/products/datasette.jpg'),
  ('Floppy Drive 1541-II', 'Drive 5.25" Commodore veloce.', '/images/products/drive1541.jpg'),
  ('Monitor 1084S', 'Monitor RGB 14" per Amiga/C64.', '/images/products/monitor1084.jpg'),
  ('Joystick Competition Pro', 'Il migliore! Microswitches robusti.', '/images/products/competitionpro.jpg'),
  ('Floppy Disk 5.25" x10', 'Dischetti vergini DD, sigillati.', '/images/products/floppydisk.jpg'),
  ('Cassette C30 x5', 'Cassette per datasette.', '/images/products/cassette.jpg'),
  ('Epyx Fast Load C64', 'Accelera caricamenti 5x!', '/images/products/fastload.jpg'),
  ('Compute! Gazette 1985', '12 numeri rilegati, listati BASIC.', '/images/products/gazette.jpg'),
  ('The Last Ninja C64', 'Capolavoro System 3. Cassetta originale.', '/images/products/lastninja.jpg'),
  ('Elite Multi-platform', 'Simulatore spaziale 3D. Con mappa.', '/images/products/elite.jpg');

-- MAGAZZINO FORNITORI
INSERT INTO negozi.magazzino_fornitore (piva_fornitore, prodotto, prezzo_acquisto, quantita_disponibile) VALUES
  ('01234567890', 1, 120.00, 50), ('01234567890', 2, 280.00, 30), ('01234567890', 11, 25.00, 100),
  ('01234567890', 12, 95.00, 40), ('01234567890', 13, 85.00, 25), ('01234567890', 17, 45.00, 60),
  ('09876543210', 8, 95.00, 80), ('09876543210', 9, 75.00, 60), ('09876543210', 10, 65.00, 40),
  ('09876543210', 14, 18.00, 150), ('09876543210', 19, 8.00, 180), ('09876543210', 20, 12.00, 120),
  ('11223344556', 3, 85.00, 45), ('11223344556', 7, 140.00, 35), ('11223344556', 14, 19.00, 200),
  ('11223344556', 15, 4.50, 500), ('11223344556', 16, 3.20, 300), ('11223344556', 18, 22.00, 80),
  ('66554433221', 4, 350.00, 20), ('66554433221', 5, 110.00, 35), ('66554433221', 6, 95.00, 40),
  ('66554433221', 10, 68.00, 50), ('66554433221', 15, 4.80, 400), ('66554433221', 16, 3.50, 250);

-- LISTINO PREZZI (5 negozi x 20 prodotti)
INSERT INTO negozi.listino_negozio (negozio, prodotto, prezzo_vendita) VALUES
  -- Milano (negozio 1)
  (1,1,229.99),(1,2,549.99),(1,3,159.99),(1,4,689.99),(1,5,209.99),(1,6,179.99),(1,7,269.99),(1,8,189.99),
  (1,9,149.99),(1,10,129.99),(1,11,49.99),(1,12,189.99),(1,13,169.99),(1,14,35.99),(1,15,8.99),
  (1,16,6.49),(1,17,89.99),(1,18,42.99),(1,19,16.99),(1,20,23.99),
  -- Roma (negozio 2)
  (2,1,219.99),(2,2,529.99),(2,3,154.99),(2,4,659.99),(2,5,199.99),(2,6,174.99),(2,7,259.99),(2,8,179.99),
  (2,9,144.99),(2,10,124.99),(2,11,47.99),(2,12,179.99),(2,13,164.99),(2,14,34.99),(2,15,8.49),
  (2,16,5.99),(2,17,84.99),(2,18,39.99),(2,19,15.99),(2,20,21.99),
  -- Torino (negozio 3)
  (3,1,209.99),(3,2,499.99),(3,3,149.99),(3,4,629.99),(3,5,189.99),(3,6,169.99),(3,7,249.99),(3,8,169.99),
  (3,9,134.99),(3,10,114.99),(3,11,44.99),(3,12,169.99),(3,13,154.99),(3,14,32.99),(3,15,7.99),
  (3,16,5.49),(3,17,79.99),(3,18,37.99),(3,19,14.99),(3,20,19.99),
  -- Bologna (negozio 4)
  (4,1,209.99),(4,2,499.99),(4,3,149.99),(4,4,629.99),(4,5,189.99),(4,6,169.99),(4,7,249.99),(4,8,169.99),
  (4,9,134.99),(4,10,114.99),(4,11,44.99),(4,12,169.99),(4,13,154.99),(4,14,32.99),(4,15,7.99),
  (4,16,5.49),(4,17,79.99),(4,18,37.99),(4,19,14.99),(4,20,19.99),
  -- Firenze (negozio 5)
  (5,1,214.99),(5,2,519.99),(5,3,152.99),(5,4,649.99),(5,5,194.99),(5,6,172.99),(5,7,254.99),(5,8,174.99),
  (5,9,139.99),(5,10,119.99),(5,11,46.99),(5,12,174.99),(5,13,159.99),(5,14,33.99),(5,15,8.49),
  (5,16,5.99),(5,17,84.99),(5,18,39.99),(5,19,15.99),(5,20,21.99);

-- TESSERE (una per cliente)
INSERT INTO negozi.tessere (negozio_emittente, data_richiesta, saldo_punti)
SELECT
  1 + (RANDOM() * 4)::INTEGER,
  CURRENT_DATE - (RANDOM() * 730)::INTEGER,
  (RANDOM() * 500)::INTEGER
FROM generate_series(1, 15);

-- ASSOCIA TESSERE AI CLIENTI
INSERT INTO negozi.cliente_tessera (cliente, tessera)
SELECT c.id_cliente, t.id_tessera FROM negozi.clienti c
JOIN LATERAL (
  SELECT id_tessera FROM negozi.tessere
  WHERE id_tessera NOT IN (SELECT tessera FROM negozi.cliente_tessera)
  ORDER BY id_tessera LIMIT 1
) t ON TRUE
WHERE c.id_cliente NOT IN (SELECT cliente FROM negozi.cliente_tessera);

COMMIT;

-- RIEPILOGO
SELECT '=== DATABASE POPOLATO ===' as info;
SELECT 'Ruoli:' as tipo, COUNT(*) as totale FROM auth.ruolo
UNION ALL SELECT 'Utenti:', COUNT(*) FROM auth.utenti
UNION ALL SELECT 'Clienti:', COUNT(*) FROM negozi.clienti
UNION ALL SELECT 'Negozi:', COUNT(*) FROM negozi.negozio
UNION ALL SELECT 'Fornitori:', COUNT(*) FROM negozi.fornitori
UNION ALL SELECT 'Prodotti:', COUNT(*) FROM negozi.prodotti
UNION ALL SELECT 'Magazzino:', COUNT(*) FROM negozi.magazzino_fornitore
UNION ALL SELECT 'Listino:', COUNT(*) FROM negozi.listino_negozio
UNION ALL SELECT 'Tessere:', COUNT(*) FROM negozi.tessere
UNION ALL SELECT 'Clienti tessera:', COUNT(*) FROM negozi.cliente_tessera;

SELECT 'ACCESSO: manager@retrogaming.it / retro1980' as credenziali;
