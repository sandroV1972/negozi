-- ============================================================================
-- SCRIPT POPOLAMENTO DATABASE - NEGOZI RETRO GAMING & COMPUTER ANNI '80
-- ============================================================================
-- Progetto: Gestione Catena Negozi
-- Tema: Computer e Videogiochi Vintage (1980-1990)
-- Versione: 2.0 - Adattato alla struttura reale del database
-- ============================================================================

BEGIN;

-- ============================================================================
-- 1. AGGIUNGI COLONNA PER IMMAGINI PRODOTTI (se non esiste già)
-- ============================================================================
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = 'negozi'
        AND table_name = 'prodotti'
        AND column_name = 'immagine_url'
    ) THEN
        ALTER TABLE negozi.prodotti
        ADD COLUMN immagine_url TEXT;

        COMMENT ON COLUMN negozi.prodotti.immagine_url IS
        'URL o percorso immagine prodotto (es: /images/products/commodore64.jpg)';
    END IF;
END $$;

-- ============================================================================
-- 2. RUOLI
-- ============================================================================
INSERT INTO auth.ruolo (nome, descrizione) VALUES
  ('manager', 'Gestore catena negozi - Accesso completo al sistema'),
  ('cliente', 'Cliente finale - Può acquistare prodotti e gestire tessera fedeltà')
ON CONFLICT (nome) DO NOTHING;

-- ============================================================================
-- 3. UTENTI - MANAGER
-- ============================================================================
-- Password: retro1980 (per tutti gli utenti di test)
-- Hash generato con: password_hash('retro1980', PASSWORD_DEFAULT)

INSERT INTO auth.utenti (email, username, password, attivo) VALUES
  ('manager@retrogaming.it', 'retromanager',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE)
ON CONFLICT (email) DO NOTHING;

-- Assegna ruolo manager
INSERT INTO auth.utente_ruolo (id_utente, id_ruolo)
SELECT u.id_utente, r.id_ruolo
FROM auth.utenti u, auth.ruolo r
WHERE u.username = 'retromanager' AND r.nome = 'manager'
ON CONFLICT DO NOTHING;

-- ============================================================================
-- 4. UTENTI - CLIENTI (15 clienti nostalgici anni '80)
-- ============================================================================
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

-- Assegna ruolo cliente a tutti
INSERT INTO auth.utente_ruolo (id_utente, id_ruolo)
SELECT u.id_utente, r.id_ruolo
FROM auth.utenti u, auth.ruolo r
WHERE u.username IN ('mariorossi', 'laurabianchi', 'paoloverdi', 'annaneri', 'lucaferrari',
                     'giuliarusso', 'marcocolombo', 'sararicci', 'andreabruno', 'elenagallo',
                     'fabioconti', 'chiararomano', 'robertomoretti', 'valentinafontana', 'davidegreco')
AND r.nome = 'cliente'
ON CONFLICT DO NOTHING;

-- ============================================================================
-- 5. DATI CLIENTI NEL NEGOZI SCHEMA (con CF italiano fittizio)
-- ============================================================================
INSERT INTO negozi.clienti (utente, cf, nome, cognome)
VALUES
  ((SELECT id_utente FROM auth.utenti WHERE username = 'mariorossi'), 'RSSMRA80A01H501Z', 'Mario', 'Rossi'),
  ((SELECT id_utente FROM auth.utenti WHERE username = 'laurabianchi'), 'BNCL RA85M42F205W', 'Laura', 'Bianchi'),
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

-- ============================================================================
-- 6. NEGOZI (5 negozi in diverse città italiane)
-- ============================================================================
-- responsabile = id_utente del manager
INSERT INTO negozi.negozio (responsabile, indirizzo) VALUES
  ((SELECT id_utente FROM auth.utenti WHERE username = 'retromanager'), 'Via Dante 42, 20121 Milano - Zona Centro'),
  ((SELECT id_utente FROM auth.utenti WHERE username = 'retromanager'), 'Corso Vittorio Emanuele 156, 00186 Roma - Zona Pantheon'),
  ((SELECT id_utente FROM auth.utenti WHERE username = 'retromanager'), 'Via Garibaldi 88, 10122 Torino - Zona Porta Palazzo'),
  ((SELECT id_utente FROM auth.utenti WHERE username = 'retromanager'), 'Piazza Maggiore 15, 40124 Bologna - Centro Storico'),
  ((SELECT id_utente FROM auth.utenti WHERE username = 'retromanager'), 'Via Mazzini 33, 50123 Firenze - Zona Duomo');

-- ============================================================================
-- 7. ORARI NEGOZI
-- ============================================================================
-- Tutti i negozi: Lun-Sab 9:30-19:30, Domenica 10:00-18:00
INSERT INTO negozi.orari (negozio, giorno_settimana, ora_apertura, ora_chiusura, chiuso)
SELECT n.id_negozio, g, '09:30:00', '19:30:00', FALSE
FROM negozi.negozio n
CROSS JOIN generate_series(1, 6) g; -- Lunedì-Sabato

INSERT INTO negozi.orari (negozio, giorno_settimana, ora_apertura, ora_chiusura, chiuso)
SELECT n.id_negozio, 0, '10:00:00', '18:00:00', FALSE
FROM negozi.negozio n; -- Domenica

-- ============================================================================
-- 8. FORNITORI (4 fornitori specializzati)
-- ============================================================================
INSERT INTO negozi.fornitori (piva, ragione_sociale, indirizzo, email, telefono, attivo) VALUES
  ('01234567890', 'Vintage Computer Wholesale SRL', 'Via Industriale 45, 20090 Segrate (MI)', 'info@vintagewholesale.it', '02-87654321', TRUE),
  ('09876543210', 'Retro Gaming Italia SPA', 'Viale Europa 123, 00144 Roma (RM)', 'vendite@retrogamingitalia.it', '06-12345678', TRUE),
  ('11223344556', 'Classic Electronics Distribution', 'Corso Francia 88, 10143 Torino (TO)', 'ordini@classicelectronics.it', '011-9876543', TRUE),
  ('66554433221', 'Old School Tech Supply', 'Via Bologna 67, 50127 Firenze (FI)', 'supply@oldschooltech.it', '055-7654321', TRUE)
ON CONFLICT (piva) DO NOTHING;

-- ============================================================================
-- 9. PRODOTTI - COMPUTER E VIDEOGIOCHI ANNI '80
-- ============================================================================
INSERT INTO negozi.prodotti (nome, descrizione, immagine_url) VALUES
  -- COMPUTER ICONICI
  ('Commodore 64',
   'Il computer più venduto della storia! CPU MOS 6510 a 1MHz, 64KB RAM. Include alimentatore e cavi. Condizioni eccellenti, testato e funzionante. Perfetto per i nostalgici del BASIC e dei giochi leggendari.',
   '/images/products/commodore64.jpg'),

  ('Commodore Amiga 500',
   'Rivoluzionario home computer con grafica e audio avanzati. CPU Motorola 68000 a 7MHz, 512KB RAM espandibile. Sistema operativo Workbench 1.3 incluso. Perfetto per grafica, musica e gaming.',
   '/images/products/amiga500.jpg'),

  ('ZX Spectrum 48K',
   'Il leggendario computer britannico Sinclair. CPU Z80A a 3.5MHz, 48KB RAM, tastiera in gomma originale. Include manuale e alcuni giochi su cassetta. Un pezzo di storia dell''informatica!',
   '/images/products/zxspectrum.jpg'),

  ('Apple II Europlus',
   'Computer Apple II con tastiera italiana. CPU 6502 a 1MHz, 48KB RAM. Include monitor monocromatico e drive per floppy disk. Fantastico per programmazione e produttività vintage.',
   '/images/products/appleii.jpg'),

  ('Atari 800XL',
   'Home computer Atari con 64KB RAM. Grafica GTIA avanzata, suono Pokey. Ottimo per gaming e programmazione BASIC. Include alimentatore e cavi RF.',
   '/images/products/atari800xl.jpg'),

  ('MSX Sony HitBit HB-75P',
   'Computer MSX standard giapponese. CPU Z80A, 32KB RAM. Compatibile con migliaia di giochi e programmi MSX. Include cartuccia BASIC.',
   '/images/products/msx.jpg'),

  ('Amstrad CPC 464',
   'Computer britannico completo con monitor integrato e lettore cassette. CPU Z80, 64KB RAM. Include tastiera QWERTY e software.',
   '/images/products/cpc464.jpg'),

  -- CONSOLE VIDEOGIOCHI
  ('Nintendo Entertainment System (NES)',
   'La console che ha salvato l''industria videoludica! Include 2 controller, Zapper gun e Super Mario Bros. Revisionate e testate. PAL europeo.',
   '/images/products/nes.jpg'),

  ('Sega Master System',
   'Console 8-bit Sega con grafica superiore. Include controller, cavi e Alex Kidd built-in. Compatibile con centinaia di giochi.',
   '/images/products/mastersystem.jpg'),

  ('Atari 2600',
   'La console che ha iniziato tutto! Include joystick, paddle e 5 giochi classici: Pac-Man, Space Invaders, Pitfall, River Raid, Enduro.',
   '/images/products/atari2600.jpg'),

  -- PERIFERICHE
  ('Datasette Commodore 1530',
   'Lettore di cassette originale Commodore per C64 e VIC-20. Perfettamente funzionante, ideale per caricare i giochi vintage.',
   '/images/products/datasette.jpg'),

  ('Floppy Drive 1541-II',
   'Drive per floppy disk 5.25" Commodore. Velocità migliorata rispetto al 1541 originale. Include cavi seriali.',
   '/images/products/drive1541.jpg'),

  ('Monitor Commodore 1084S',
   'Monitor RGB/Composite 14". Perfetto per Amiga, C64 e altre macchine vintage. Immagine nitida e colori brillanti.',
   '/images/products/monitor1084.jpg'),

  ('Joystick Competition Pro',
   'Il joystick più amato dai gamer! Microswitches robusti, impugnatura ergonomica. Compatibile con C64, Amiga, Atari.',
   '/images/products/competitionpro.jpg'),

  -- ACCESSORI E SOFTWARE
  ('Pacco 10 Floppy Disk 5.25"',
   'Dischetti vergini doppia densità (DD) per Commodore e Apple. Confezione sigillata, mai utilizzati.',
   '/images/products/floppydisk.jpg'),

  ('Cassette Vergini C30 (Pacco da 5)',
   'Cassette audio per datasette. Ottima qualità, ideali per salvare programmi e giochi.',
   '/images/products/cassette.jpg'),

  ('Cartuccia Epyx Fast Load per C64',
   'Accelera caricamenti fino a 5x! Include utility e monitor per C64. Essenziale per ogni utente Commodore.',
   '/images/products/fastload.jpg'),

  ('Manuale "Compute! Gazette" - Raccolta 1985',
   '12 numeri rilegati della famosa rivista. Centinaia di listati BASIC, trucchi e recensioni. Perfetto per collezionisti.',
   '/images/products/gazette.jpg'),

  -- GIOCHI LEGGENDARI
  ('The Last Ninja (C64)',
   'Capolavoro isometrico di System 3. Grafica spettacolare e colonna sonora memorabile. Versione originale su cassetta.',
   '/images/products/lastninja.jpg'),

  ('Elite (BBC/C64/Spectrum)',
   'Il simulatore spaziale definitivo! Trading, combattimento e esplorazione in 3D wireframe. Include mappa stellare.',
   '/images/products/elite.jpg');

-- ============================================================================
-- 10. MAGAZZINO FORNITORI (Chi fornisce cosa e a che prezzo)
-- ============================================================================
-- Fornitore 1: Specializzato in Commodore
INSERT INTO negozi.magazzino_fornitore (piva_fornitore, prodotto, prezzo_acquisto, quantita_disponibile) VALUES
  ('01234567890', 1, 120.00, 50),   -- Commodore 64
  ('01234567890', 2, 280.00, 30),   -- Amiga 500
  ('01234567890', 11, 25.00, 100),  -- Datasette
  ('01234567890', 12, 95.00, 40),   -- Drive 1541
  ('01234567890', 13, 85.00, 25),   -- Monitor 1084
  ('01234567890', 17, 45.00, 60),   -- Fast Load
  ('01234567890', 19, 8.50, 200);   -- Last Ninja

-- Fornitore 2: Console e giochi
INSERT INTO negozi.magazzino_fornitore (piva_fornitore, prodotto, prezzo_acquisto, quantita_disponibile) VALUES
  ('09876543210', 8, 95.00, 80),    -- NES
  ('09876543210', 9, 75.00, 60),    -- Master System
  ('09876543210', 10, 65.00, 40),   -- Atari 2600
  ('09876543210', 14, 18.00, 150),  -- Competition Pro
  ('09876543210', 19, 8.00, 180),   -- Last Ninja
  ('09876543210', 20, 12.00, 120);  -- Elite

-- Fornitore 3: Computer britannici e accessori
INSERT INTO negozi.magazzino_fornitore (piva_fornitore, prodotto, prezzo_acquisto, quantita_disponibile) VALUES
  ('11223344556', 3, 85.00, 45),    -- ZX Spectrum
  ('11223344556', 7, 140.00, 35),   -- Amstrad CPC
  ('11223344556', 14, 19.00, 200),  -- Competition Pro
  ('11223344556', 15, 4.50, 500),   -- Floppy disk
  ('11223344556', 16, 3.20, 300),   -- Cassette
  ('11223344556', 18, 22.00, 80),   -- Gazette
  ('11223344556', 20, 11.50, 150);  -- Elite

-- Fornitore 4: Apple, Atari, MSX
INSERT INTO negozi.magazzino_fornitore (piva_fornitore, prodotto, prezzo_acquisto, quantita_disponibile) VALUES
  ('66554433221', 4, 350.00, 20),   -- Apple II
  ('66554433221', 5, 110.00, 35),   -- Atari 800XL
  ('66554433221', 6, 95.00, 40),    -- MSX Sony
  ('66554433221', 10, 68.00, 50),   -- Atari 2600
  ('66554433221', 15, 4.80, 400),   -- Floppy disk
  ('66554433221', 16, 3.50, 250);   -- Cassette

-- ============================================================================
-- 11. LISTINO PREZZI PER NEGOZIO (Prezzi al pubblico)
-- ============================================================================
-- Prezzi con markup variabile per negozio (Milano più caro, Bologna/Torino più economico)

-- Negozio 1: Milano (markup +85-95%)
INSERT INTO negozi.listino_negozio (negozio, prodotto, prezzo_vendita) VALUES
  (1, 1, 229.99), (1, 2, 549.99), (1, 3, 159.99), (1, 4, 689.99), (1, 5, 209.99),
  (1, 6, 179.99), (1, 7, 269.99), (1, 8, 189.99), (1, 9, 149.99), (1, 10, 129.99),
  (1, 11, 49.99), (1, 12, 189.99), (1, 13, 169.99), (1, 14, 35.99), (1, 15, 8.99),
  (1, 16, 6.49), (1, 17, 89.99), (1, 18, 42.99), (1, 19, 16.99), (1, 20, 23.99);

-- Negozio 2: Roma (markup +80-90%)
INSERT INTO negozi.listino_negozio (negozio, prodotto, prezzo_vendita) VALUES
  (2, 1, 219.99), (2, 2, 529.99), (2, 3, 154.99), (2, 4, 659.99), (2, 5, 199.99),
  (2, 6, 174.99), (2, 7, 259.99), (2, 8, 179.99), (2, 9, 144.99), (2, 10, 124.99),
  (2, 11, 47.99), (2, 12, 179.99), (2, 13, 164.99), (2, 14, 34.99), (2, 15, 8.49),
  (2, 16, 5.99), (2, 17, 84.99), (2, 18, 39.99), (2, 19, 15.99), (2, 20, 21.99);

-- Negozio 3: Torino (markup +70-80%)
INSERT INTO negozi.listino_negozio (negozio, prodotto, prezzo_vendita) VALUES
  (3, 1, 209.99), (3, 2, 499.99), (3, 3, 149.99), (3, 4, 629.99), (3, 5, 189.99),
  (3, 6, 169.99), (3, 7, 249.99), (3, 8, 169.99), (3, 9, 134.99), (3, 10, 114.99),
  (3, 11, 44.99), (3, 12, 169.99), (3, 13, 154.99), (3, 14, 32.99), (3, 15, 7.99),
  (3, 16, 5.49), (3, 17, 79.99), (3, 18, 37.99), (3, 19, 14.99), (3, 20, 19.99);

-- Negozio 4: Bologna (markup +70-80%)
INSERT INTO negozi.listino_negozio (negozio, prodotto, prezzo_vendita) VALUES
  (4, 1, 209.99), (4, 2, 499.99), (4, 3, 149.99), (4, 4, 629.99), (4, 5, 189.99),
  (4, 6, 169.99), (4, 7, 249.99), (4, 8, 169.99), (4, 9, 134.99), (4, 10, 114.99),
  (4, 11, 44.99), (4, 12, 169.99), (4, 13, 154.99), (4, 14, 32.99), (4, 15, 7.99),
  (4, 16, 5.49), (4, 17, 79.99), (4, 18, 37.99), (4, 19, 14.99), (4, 20, 19.99);

-- Negozio 5: Firenze (markup +75-85%)
INSERT INTO negozi.listino_negozio (negozio, prodotto, prezzo_vendita) VALUES
  (5, 1, 214.99), (5, 2, 519.99), (5, 3, 152.99), (5, 4, 649.99), (5, 5, 194.99),
  (5, 6, 172.99), (5, 7, 254.99), (5, 8, 174.99), (5, 9, 139.99), (5, 10, 119.99),
  (5, 11, 46.99), (5, 12, 174.99), (5, 13, 159.99), (5, 14, 33.99), (5, 15, 8.49),
  (5, 16, 5.99), (5, 17, 84.99), (5, 18, 39.99), (5, 19, 15.99), (5, 20, 21.99);

-- ============================================================================
-- 12. TESSERE FEDELTÀ
-- ============================================================================
-- Prima crea le tessere
INSERT INTO negozi.tessere (negozio_emittente, data_richiesta, saldo_punti, archiviata)
SELECT
  (1 + (RANDOM() * 4)::INTEGER) as negozio_casuale,
  CURRENT_DATE - (RANDOM() * 730)::INTEGER as data_casuale,
  (RANDOM() * 500)::INTEGER as punti_casuali,
  FALSE
FROM generate_series(1, 15); -- 15 tessere per 15 clienti

-- Poi associa le tessere ai clienti
INSERT INTO negozi.cliente_tessera (cliente, tessera)
SELECT
  c.id_cliente,
  t.id_tessera
FROM negozi.clienti c
JOIN LATERAL (
  SELECT id_tessera
  FROM negozi.tessere
  WHERE id_tessera NOT IN (SELECT tessera FROM negozi.cliente_tessera)
  ORDER BY id_tessera
  LIMIT 1
) t ON TRUE
WHERE c.id_cliente NOT IN (SELECT cliente FROM negozi.cliente_tessera);

COMMIT;

-- ============================================================================
-- VERIFICA DATI INSERITI
-- ============================================================================
SELECT '=== RIEPILOGO POPOLAMENTO DATABASE ===' as info;

SELECT 'Ruoli inseriti:' as tabella, COUNT(*) as totale FROM auth.ruolo
UNION ALL
SELECT 'Utenti inseriti:', COUNT(*) FROM auth.utenti
UNION ALL
SELECT 'Clienti inseriti:', COUNT(*) FROM negozi.clienti
UNION ALL
SELECT 'Negozi aperti:', COUNT(*) FROM negozi.negozio
UNION ALL
SELECT 'Fornitori attivi:', COUNT(*) FROM negozi.fornitori
UNION ALL
SELECT 'Prodotti catalogo:', COUNT(*) FROM negozi.prodotti
UNION ALL
SELECT 'Righe magazzino:', COUNT(*) FROM negozi.magazzino_fornitore
UNION ALL
SELECT 'Righe listino:', COUNT(*) FROM negozi.listino_negozio
UNION ALL
SELECT 'Tessere emesse:', COUNT(*) FROM negozi.tessere
UNION ALL
SELECT 'Clienti con tessera:', COUNT(*) FROM negozi.cliente_tessera;

-- ============================================================================
-- INFO ACCESSO
-- ============================================================================
SELECT '
╔═══════════════════════════════════════════════════════════════╗
║  CREDENZIALI DI ACCESSO                                       ║
╠═══════════════════════════════════════════════════════════════╣
║  MANAGER:                                                     ║
║    Email: manager@retrogaming.it                              ║
║    Password: retro1980                                        ║
╠═══════════════════════════════════════════════════════════════╣
║  CLIENTI (esempi):                                            ║
║    Email: mario.rossi@email.it                                ║
║    Email: laura.bianchi@email.it                              ║
║    Password: retro1980 (per tutti)                            ║
╠═══════════════════════════════════════════════════════════════╣
║  TEMA: Computer e Videogiochi Vintage Anni ''80               ║
║  Database: negozi_db                                          ║
║  Negozi: Milano, Roma, Torino, Bologna, Firenze              ║
║  Prodotti: 20 (Computer, Console, Periferiche, Giochi)       ║
╚═══════════════════════════════════════════════════════════════╝
' as "INFO" AZIONI";
