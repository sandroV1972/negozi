-- ============================================================================
-- SCRIPT POPOLAMENTO DATABASE - NEGOZI RETRO GAMING & COMPUTER ANNI '80
-- ============================================================================

BEGIN;

-- Pulisci dati esistenti (l'ordine è importante per le foreign key!)
TRUNCATE TABLE negozi.tessere CASCADE;
TRUNCATE TABLE negozi.listino_negozio CASCADE;
TRUNCATE TABLE negozi.magazzino_fornitore CASCADE;
TRUNCATE TABLE negozi.dettagli_fattura CASCADE;
TRUNCATE TABLE negozi.fatture CASCADE;
TRUNCATE TABLE negozi.ordini_fornitori CASCADE;
TRUNCATE TABLE negozi.prodotti CASCADE;
TRUNCATE TABLE negozi.fornitori CASCADE;
TRUNCATE TABLE negozi.orari CASCADE;
TRUNCATE TABLE negozi.negozi CASCADE;
TRUNCATE TABLE negozi.clienti CASCADE;
TRUNCATE TABLE auth.utenti CASCADE;
TRUNCATE TABLE auth.ruolo CASCADE;

-- Reset sequences per garantire che gli ID partano da 1
ALTER SEQUENCE negozi.prodotti_id_prodotto_seq RESTART WITH 1;
ALTER SEQUENCE negozi.negozi_id_negozio_seq RESTART WITH 1;
ALTER SEQUENCE negozi.clienti_id_cliente_seq RESTART WITH 1;
ALTER SEQUENCE negozi.tessere_id_tessera_seq RESTART WITH 1;
ALTER SEQUENCE auth.utenti_id_utente_seq RESTART WITH 1;
ALTER SEQUENCE auth.ruolo_id_ruolo_seq RESTART WITH 1;

-- RUOLI
INSERT INTO auth.ruolo (nome, descrizione) VALUES
  ('manager', 'Gestore catena negozi'),
  ('cliente', 'Cliente finale')
ON CONFLICT (nome) DO NOTHING;

-- MANAGER (password: retro1980) - ruolo 1 = manager
INSERT INTO auth.utenti (ruolo, email, password, attivo) VALUES
  ((SELECT id_ruolo FROM auth.ruolo WHERE nome = 'manager'), 'manager@retrogaming.it', '$2y$12$tVK3xXy63eHfjqSvsig0vOfbqw8LI0luAMSLs.9nN3Yp6pg4FNJA.', TRUE)
ON CONFLICT (email) DO NOTHING;

-- CLIENTI (password: password123) - ruolo 2 = cliente
INSERT INTO auth.utenti (ruolo, email, password, attivo) VALUES
  ((SELECT id_ruolo FROM auth.ruolo WHERE nome = 'cliente'), 'mario.rossi@email.it', '$2y$12$3s0hbFChwthdBVaLLCnnveeJAkj96ifquHpuW/hQJZvKtU43EloXi', TRUE),
  ((SELECT id_ruolo FROM auth.ruolo WHERE nome = 'cliente'), 'laura.bianchi@email.it', '$2y$12$3s0hbFChwthdBVaLLCnnveeJAkj96ifquHpuW/hQJZvKtU43EloXi', TRUE),
  ((SELECT id_ruolo FROM auth.ruolo WHERE nome = 'cliente'), 'paolo.verdi@email.it', '$2y$12$3s0hbFChwthdBVaLLCnnveeJAkj96ifquHpuW/hQJZvKtU43EloXi', TRUE),
  ((SELECT id_ruolo FROM auth.ruolo WHERE nome = 'cliente'), 'anna.neri@email.it', '$2y$12$3s0hbFChwthdBVaLLCnnveeJAkj96ifquHpuW/hQJZvKtU43EloXi', TRUE),
  ((SELECT id_ruolo FROM auth.ruolo WHERE nome = 'cliente'), 'luca.ferrari@email.it', '$2y$12$3s0hbFChwthdBVaLLCnnveeJAkj96ifquHpuW/hQJZvKtU43EloXi', TRUE),
  ((SELECT id_ruolo FROM auth.ruolo WHERE nome = 'cliente'), 'giulia.russo@email.it', '$2y$12$3s0hbFChwthdBVaLLCnnveeJAkj96ifquHpuW/hQJZvKtU43EloXi', TRUE),
  ((SELECT id_ruolo FROM auth.ruolo WHERE nome = 'cliente'), 'marco.colombo@email.it', '$2y$12$3s0hbFChwthdBVaLLCnnveeJAkj96ifquHpuW/hQJZvKtU43EloXi', TRUE),
  ((SELECT id_ruolo FROM auth.ruolo WHERE nome = 'cliente'), 'sara.ricci@email.it', '$2y$12$3s0hbFChwthdBVaLLCnnveeJAkj96ifquHpuW/hQJZvKtU43EloXi', TRUE),
  ((SELECT id_ruolo FROM auth.ruolo WHERE nome = 'cliente'), 'andrea.bruno@email.it', '$2y$12$3s0hbFChwthdBVaLLCnnveeJAkj96ifquHpuW/hQJZvKtU43EloXi', TRUE),
  ((SELECT id_ruolo FROM auth.ruolo WHERE nome = 'cliente'), 'elena.gallo@email.it', '$2y$12$3s0hbFChwthdBVaLLCnnveeJAkj96ifquHpuW/hQJZvKtU43EloXi', TRUE),
  ((SELECT id_ruolo FROM auth.ruolo WHERE nome = 'cliente'), 'fabio.conti@email.it', '$2y$12$3s0hbFChwthdBVaLLCnnveeJAkj96ifquHpuW/hQJZvKtU43EloXi', TRUE),
  ((SELECT id_ruolo FROM auth.ruolo WHERE nome = 'cliente'), 'chiara.romano@email.it', '$2y$12$3s0hbFChwthdBVaLLCnnveeJAkj96ifquHpuW/hQJZvKtU43EloXi', TRUE),
  ((SELECT id_ruolo FROM auth.ruolo WHERE nome = 'cliente'), 'roberto.moretti@email.it', '$2y$12$3s0hbFChwthdBVaLLCnnveeJAkj96ifquHpuW/hQJZvKtU43EloXi', TRUE),
  ((SELECT id_ruolo FROM auth.ruolo WHERE nome = 'cliente'), 'valentina.fontana@email.it', '$2y$12$3s0hbFChwthdBVaLLCnnveeJAkj96ifquHpuW/hQJZvKtU43EloXi', TRUE),
  ((SELECT id_ruolo FROM auth.ruolo WHERE nome = 'cliente'), 'davide.greco@email.it', '$2y$12$3s0hbFChwthdBVaLLCnnveeJAkj96ifquHpuW/hQJZvKtU43EloXi', TRUE)
ON CONFLICT (email) DO NOTHING;

-- DATI CLIENTI (CF validi di 16 caratteri con telefoni)
INSERT INTO negozi.clienti (utente, cf, nome, cognome, telefono) VALUES
  ((SELECT id_utente FROM auth.utenti WHERE email = 'mario.rossi@email.it'), 'RSSMRA80A01H501Z', 'Mario', 'Rossi', '339-1234567'),
  ((SELECT id_utente FROM auth.utenti WHERE email = 'laura.bianchi@email.it'), 'BNCLRA85M42F205W', 'Laura', 'Bianchi', '347-2345678'),
  ((SELECT id_utente FROM auth.utenti WHERE email = 'paolo.verdi@email.it'), 'VRDPLA78C15L219X', 'Paolo', 'Verdi', '338-3456789'),
  ((SELECT id_utente FROM auth.utenti WHERE email = 'anna.neri@email.it'), 'NRENNN82D50A794Y', 'Anna', 'Neri', '340-4567890'),
  ((SELECT id_utente FROM auth.utenti WHERE email = 'luca.ferrari@email.it'), 'FRRLCU83E10D612V', 'Luca', 'Ferrari', '349-5678901'),
  ((SELECT id_utente FROM auth.utenti WHERE email = 'giulia.russo@email.it'), 'RSSGLI86H47H501U', 'Giulia', 'Russo', '346-6789012'),
  ((SELECT id_utente FROM auth.utenti WHERE email = 'marco.colombo@email.it'), 'CLMMRC79L20F205T', 'Marco', 'Colombo', '333-7890123'),
  ((SELECT id_utente FROM auth.utenti WHERE email = 'sara.ricci@email.it'), 'RCCSRA81M52L219S', 'Sara', 'Ricci', '348-8901234'),
  ((SELECT id_utente FROM auth.utenti WHERE email = 'andrea.bruno@email.it'), 'BRNNDR84P11A794R', 'Andrea', 'Bruno', '339-9012345'),
  ((SELECT id_utente FROM auth.utenti WHERE email = 'elena.gallo@email.it'), 'GLLLNE87R48D612Q', 'Elena', 'Gallo', '347-0123456'),
  ((SELECT id_utente FROM auth.utenti WHERE email = 'fabio.conti@email.it'), 'CNTFBA80S19H501P', 'Fabio', 'Conti', '338-1234560'),
  ((SELECT id_utente FROM auth.utenti WHERE email = 'chiara.romano@email.it'), 'RMNCHR88T50F205O', 'Chiara', 'Romano', '340-2345601'),
  ((SELECT id_utente FROM auth.utenti WHERE email = 'roberto.moretti@email.it'), 'MRTRRT76A12L219N', 'Roberto', 'Moretti', '349-3456012'),
  ((SELECT id_utente FROM auth.utenti WHERE email = 'valentina.fontana@email.it'), 'FNTVNT89B51A794M', 'Valentina', 'Fontana', '346-4560123'),
  ((SELECT id_utente FROM auth.utenti WHERE email = 'davide.greco@email.it'), 'GRCDVD81C13D612L', 'Davide', 'Greco', '333-5601234')
ON CONFLICT (cf) DO NOTHING;

-- NEGOZI (5 città)
INSERT INTO negozi.negozi (nome_negozio, responsabile, indirizzo, attivo) VALUES
  ('Retro Gaming Milano', 'Mario Plummer', 'Via Dante 42, 20121 Milano', TRUE),
  ('Retro Gaming Roma', 'Luigi Plummer', 'Corso Vittorio Emanuele 156, 00186 Roma', TRUE),
  ('Retro Gaming Torino', 'Bruno Frank', 'Via Garibaldi 88, 10122 Torino', TRUE),
  ('Retro Gaming Bologna', 'Guybrush Threepwood', 'Piazza Maggiore 15, 40124 Bologna', TRUE),
  ('Retro Gaming Firenze', 'Conan Barbarian', 'Via Mazzini 33, 50123 Firenze', TRUE);
-- ORARI: Lun-Sab 9:30-13:00 (mattina, iod=1)
INSERT INTO negozi.orari (negozio, dow, iod, apertura, chiusura)
SELECT n.id_negozio, g, 1, '09:30:00', '13:00:00'
FROM negozi.negozi n CROSS JOIN generate_series(1, 6) g;

-- ORARI: Lun-Sab 15:30-19:30 (pomeriggio, iod=2)
INSERT INTO negozi.orari (negozio, dow, iod, apertura, chiusura)
SELECT n.id_negozio, g, 2, '15:30:00', '19:30:00'
FROM negozi.negozi n CROSS JOIN generate_series(1, 6) g;

-- ORARI: Domenica 10:00-13:00 (mattina, iod=1)
INSERT INTO negozi.orari (negozio, dow, iod, apertura, chiusura)
SELECT n.id_negozio, 7, 1, '10:00:00', '13:00:00' FROM negozi.negozi n;

-- ORARI: Domenica 16:00-19:00 (pomeriggio, iod=2)
INSERT INTO negozi.orari (negozio, dow, iod, apertura, chiusura)
SELECT n.id_negozio, 7, 2, '16:00:00', '19:00:00' FROM negozi.negozi n;

-- FORNITORI
INSERT INTO negozi.fornitori (piva, nome_fornitore, indirizzo, email, telefono, attivo) VALUES
  ('01234567890', 'Vintage Computer Wholesale SRL', 'Via Industriale 45, Segrate (MI)', 'info@vintagewholesale.it', '02-87654321', TRUE),
  ('09876543210', 'Retro Gaming Italia SPA', 'Viale Europa 123, Roma', 'vendite@retrogamingitalia.it', '06-12345678', TRUE),
  ('11223344556', 'Classic Electronics Distribution', 'Corso Francia 88, Torino', 'ordini@classicelectronics.it', '011-9876543', TRUE),
  ('66554433221', 'Old School Tech Supply', 'Via Bologna 67, Firenze', 'supply@oldschooltech.it', '055-7654321', TRUE)
ON CONFLICT (piva) DO NOTHING;

-- PRODOTTI (31 prodotti vintage: computer, console, accessori e giochi iconici)
INSERT INTO negozi.prodotti (nome_prodotto, descrizione, immagine_url) VALUES
  -- COMPUTER (7)
  ('Commodore 64', 'Il computer più venduto della storia! CPU MOS 6510 a 1MHz, 64KB RAM, chip sonoro SID leggendario. Include alimentatore e cavi. Perfettamente funzionante, testato.', '/images/products/c64.jpg'),
  ('Commodore Amiga 500', 'La rivoluzione multimediale! CPU Motorola 68000 a 7.14MHz, 512KB Chip RAM espandibile, grafica OCS con 4096 colori, audio stereo Paula. Sistema Workbench 1.3 su floppy. Condizioni eccellenti.', '/images/products/amiga500.jpg'),
  ('ZX Spectrum 48K', 'Icona britannica degli anni 80. CPU Zilog Z80A a 3.5MHz, 48KB RAM, tastiera a membrana originale. Uscita RF per TV. Funzionante, con manuale originale.', '/images/products/spectrum.jpg'),
  ('Apple II Europlus', 'Versione europea dell Apple II. CPU MOS 6502 a 1MHz, 48KB RAM, tastiera meccanica, drive 5.25" Disk II incluso. Perfetto per collezionisti.', '/images/products/appleii.jpg'),
  ('Atari 800XL', 'Home computer di lusso. CPU 6502C a 1.79MHz, 64KB RAM, chip grafici GTIA e ANTIC, chip audio POKEY. BASIC integrato. Ottime condizioni.', '/images/products/atari800xl.jpg'),
  ('MSX Sony HitBit HB-75P', 'Standard MSX giapponese. CPU Z80A a 3.58MHz, 32KB RAM, 32KB VROM, MSX-BASIC integrato. Compatibile con migliaia di giochi.', '/images/products/msx.jpg'),
  ('Amstrad CPC 464', 'All-in-one britannico. CPU Z80A, 64KB RAM, monitor a colori CTM644 integrato, lettore cassette incorporato. Sistema completo e funzionante.', '/images/products/cpc464.jpg'),
  ('Commodore Calcolatore 776M', 'Calcolatrice programmabile vintage Commodore. Display a LED rosso, memoria a 4 registri, funzioni scientifiche avanzate. Alimentatore originale incluso. Perfetto per collezionisti.', '/images/products/commodore776M.jpg'),

  -- CONSOLE (3)
  ('Nintendo NES', 'La console che ha salvato il videogioco! 2 controller originali, Zapper light gun, Super Mario Bros/Duck Hunt. CPU Ricoh 2A03, grafica PPU. PAL italiano.', '/images/products/nes.jpg'),
  ('Sega Master System', 'Rivale di Nintendo in Europa. CPU Z80A, grafica superiore al NES, Alex Kidd in Miracle World integrato. 2 controller e Phaser inclusi.', '/images/products/mastersystem.jpg'),
  ('Atari 2600 Jr', 'La pioniera! Console che ha creato l industria. Include joystick e 5 cartucce classiche: Pac-Man, Space Invaders, Pitfall, River Raid, Barnstorming.', '/images/products/atari2600.jpg'),

  -- ACCESSORI (6)
  ('Datasette C1530', 'Lettore cassette ufficiale Commodore per C64/VIC-20. Testine pulite, funzionamento garantito. Include cavi di collegamento.', '/images/products/datasette.jpg'),
  ('Floppy Drive 1541-II', 'Drive 5.25" ufficiale Commodore, versione migliorata e più veloce. Perfettamente funzionante, testato con dischetti di verifica.', '/images/products/drive1541.jpg'),
  ('Monitor 1084S', 'Monitor RGB 14" stereo Philips/Commodore. Ideale per Amiga e C64. Ingresso RGB analogico, composite e S-Video. Immagine nitida, colori vividi.', '/images/products/monitor1084.jpg'),
  ('Joystick Competition Pro', 'Il migliore joystick vintage! Microswitches Zippy professionali, impugnatura ergonomica rossa. Compatibile C64/Amiga/Atari. Indistruttibile.', '/images/products/competitionpro.jpg'),
  ('Floppy Disk 5.25" DD x10', 'Dischetti vergini doppia densità, confezione sigillata. Perfetti per C64/Amiga. Etichette incluse.', '/images/products/floppydisk.jpg'),
  ('Cassette C30 x5', 'Cassette magnetiche nuove per datasette. Qualità premium, basso rumore. Perfette per salvataggi e giochi.', '/images/products/cassette.jpg'),

  -- SOFTWARE E RIVISTE (4)
  ('Epyx Fast Load C64', 'Cartuccia acceleratore caricamenti. Riduce i tempi di 5x! Include desktop utility e sprite editor. Essenziale per ogni C64.', '/images/products/fastload.jpg'),
  ('Compute! Gazette 1985', 'Annata completa rilegata. 12 numeri con centinaia di listati BASIC per C64. Giochi, utility, tutorial. Condizioni ottime.', '/images/products/gazette.jpg'),
  ('The Last Ninja C64', 'Capolavoro assoluto di System 3. Grafica isometrica mozzafiato, colonna sonora epica di Ben Daglish. Cassetta originale con manuale. Raro!', '/images/products/lastninja.jpg'),
  ('Elite C64/Spectrum', 'Simulatore spaziale 3D rivoluzionario. Trading, combattimenti, esplorazione galattica. Include mappa stellare e tastierino comandi. Versioni C64 e Spectrum.', '/images/products/elite.jpg'),

  -- GIOCHI ICONICI C64 (5)
  ('Zak McKracken C64', 'Avventura grafica LucasArts. Giornalista contro alieni! Sistema SCUMM, umorismo brillante, enigmi geniali. Confezione big box originale con tutti i materiali.', '/images/products/zak.jpg'),
  ('Maniac Mansion C64', 'Prima avventura SCUMM di LucasFilm Games. Horror comedy con 7 personaggi giocabili, finali multipli. Box originale con poster e manuale.', '/images/products/maniac.jpg'),
  ('Impossible Mission C64', 'Capolavoro platform/puzzle di Epstein. Sintetizzatore vocale ''Another visitor!'', 8 ore per salvare il mondo. Cassetta originale.', '/images/products/impossible.jpg'),
  ('Turrican C64', 'Capolavoro di Manfred Trenz. Run n gun epico, grafica spettacolare, musica Chris Huelsbeck indimenticabile. Versione cassetta, mint condition.', '/images/products/turrican.jpg'),
  ('International Karate C64', 'Picchiaduro perfetto di Archer Maclean. Grafica fluida, mosse spettacolari, IA avanzata. Include poster movimenti. Floppy originale System 3.', '/images/products/karate.jpg'),

  -- GIOCHI ICONICI AMIGA (5)
  ('Monkey Island Amiga', 'The Secret of Monkey Island! Avventura grafica LucasArts. Pirati, umorismo, enigmi brillanti. 11 floppy, manuale originale, Dial-a-Pirate.', '/images/products/monkey.jpg'),
  ('Lemmings Amiga', 'Puzzle game geniale di DMA Design. Salva i lemmings! 120 livelli, grafica adorabile, musica orecchiabile. Box originale Psygnosis.', '/images/products/lemmings.jpg'),
  ('Speedball 2 Amiga', 'Sport futuristico brutale dei Bitmap Brothers. Grafica metallica, gameplay perfetto. ''Ice cream! Ice cream!'' Versione big box.', '/images/products/speedball2.jpg'),
  ('Shadow of the Beast Amiga', 'Showcase tecnico Amiga. 12 layer parallax, 132 colori su schermo, colonna sonora David Whittaker. Box lungo Psygnosis con poster.', '/images/products/beast.jpg'),
  ('Sensible Soccer Amiga', 'Il miglior calcio in 2D di sempre! Controllo perfetto, 1500+ squadre, edit mode. Versione Sensible Software con aggiornamenti.', '/images/products/sensible.jpg');

-- MAGAZZINO FORNITORI (ogni prodotto fornito da almeno 2-3 fornitori)
-- Fornitori: 01234567890 (Vintage Computer), 09876543210 (Retro Gaming), 11223344556 (Classic Electronics), 66554433221 (Old School Tech)
-- Prezzi migliori distribuiti equamente: ~7-8 prodotti ciascuno
INSERT INTO negozi.magazzino_fornitore (piva_fornitore, prodotto, prezzo, quantita) VALUES
  -- Prodotto 1: Commodore 64 - Miglior: Vintage Computer (01234567890)
  ('01234567890', 1, 115.00, 50), ('11223344556', 1, 125.00, 35), ('66554433221', 1, 120.00, 40),
  -- Prodotto 2: Amiga 500 - Miglior: Retro Gaming (09876543210)
  ('01234567890', 2, 285.00, 30), ('09876543210', 2, 270.00, 25), ('11223344556', 2, 290.00, 20),
  -- Prodotto 3: ZX Spectrum - Miglior: Classic Electronics (11223344556)
  ('01234567890', 3, 88.00, 45), ('09876543210', 3, 85.00, 55), ('11223344556', 3, 80.00, 30),
  -- Prodotto 4: Apple II - Miglior: Old School Tech (66554433221)
  ('11223344556', 4, 355.00, 20), ('01234567890', 4, 360.00, 15), ('66554433221', 4, 345.00, 18),
  -- Prodotto 5: Atari 800XL - Miglior: Vintage Computer (01234567890)
  ('66554433221', 5, 115.00, 35), ('01234567890', 5, 105.00, 28), ('11223344556', 5, 112.00, 22),
  -- Prodotto 6: MSX Sony - Miglior: Retro Gaming (09876543210)
  ('66554433221', 6, 98.00, 40), ('11223344556', 6, 95.00, 32), ('09876543210', 6, 90.00, 25),
  -- Prodotto 7: Amstrad CPC 464 - Miglior: Classic Electronics (11223344556)
  ('11223344556', 7, 135.00, 35), ('01234567890', 7, 145.00, 28), ('66554433221', 7, 142.00, 30),
  -- Prodotto 8: NES Nintendo - Miglior: Old School Tech (66554433221)
  ('09876543210', 8, 98.00, 80), ('01234567890', 8, 100.00, 45), ('66554433221', 8, 92.00, 50),
  -- Prodotto 9: Sega Master System - Miglior: Vintage Computer (01234567890)
  ('09876543210', 9, 78.00, 60), ('66554433221', 9, 76.00, 42), ('01234567890', 9, 70.00, 55),
  -- Prodotto 10: Atari 2600 Jr - Miglior: Retro Gaming (09876543210)
  ('09876543210', 10, 60.00, 40), ('66554433221', 10, 68.00, 50), ('01234567890', 10, 65.00, 35), ('11223344556', 10, 70.00, 28),
  -- Prodotto 11: Datasette C1530 - Miglior: Classic Electronics (11223344556)
  ('01234567890', 11, 26.00, 100), ('09876543210', 11, 28.00, 75), ('11223344556', 11, 22.00, 90),
  -- Prodotto 12: Floppy Drive 1541-II - Miglior: Old School Tech (66554433221)
  ('01234567890', 12, 88.00, 40), ('11223344556', 12, 85.00, 35), ('66554433221', 12, 80.00, 28),
  -- Prodotto 13: Monitor 1084S - Miglior: Vintage Computer (01234567890)
  ('01234567890', 13, 90.00, 25), ('11223344556', 13, 98.00, 30), ('09876543210', 13, 95.00, 22),
  -- Prodotto 14: Joystick Competition Pro - Miglior: Retro Gaming (09876543210)
  ('09876543210', 14, 16.50, 150), ('11223344556', 14, 19.00, 200), ('01234567890', 14, 18.00, 120), ('66554433221', 14, 18.50, 100),
  -- Prodotto 15: Floppy Disk 5.25" - Miglior: Classic Electronics (11223344556)
  ('11223344556', 15, 4.00, 500), ('66554433221', 15, 4.80, 400), ('09876543210', 15, 4.50, 450), ('01234567890', 15, 4.60, 380),
  -- Prodotto 16: Cassette C30 - Miglior: Old School Tech (66554433221)
  ('11223344556', 16, 3.20, 300), ('66554433221', 16, 2.80, 250), ('09876543210', 16, 3.00, 350), ('01234567890', 16, 3.40, 280),
  -- Prodotto 17: Epyx Fast Load - Miglior: Vintage Computer (01234567890)
  ('01234567890', 17, 40.00, 60), ('66554433221', 17, 48.00, 45), ('11223344556', 17, 45.00, 55),
  -- Prodotto 18: Compute! Gazette - Miglior: Retro Gaming (09876543210)
  ('11223344556', 18, 24.00, 80), ('01234567890', 18, 25.00, 60), ('09876543210', 18, 18.00, 70),
  -- Prodotto 19: The Last Ninja C64 - Miglior: Classic Electronics (11223344556)
  ('09876543210', 19, 32.00, 180), ('01234567890', 19, 35.00, 150), ('11223344556', 19, 28.00, 200),
  -- Prodotto 20: Elite C64/Spectrum - Miglior: Old School Tech (66554433221)
  ('09876543210', 20, 14.00, 120), ('66554433221', 20, 10.00, 90), ('01234567890', 20, 12.00, 100),
  -- Prodotto 21: Zak McKracken C64 - Miglior: Vintage Computer (01234567890)
  ('09876543210', 21, 38.00, 25), ('11223344556', 21, 40.00, 20), ('01234567890', 21, 32.00, 28),
  -- Prodotto 22: Maniac Mansion C64 - Miglior: Retro Gaming (09876543210)
  ('09876543210', 22, 28.00, 30), ('01234567890', 22, 35.00, 22), ('11223344556', 22, 33.00, 35),
  -- Prodotto 23: Impossible Mission C64 - Miglior: Classic Electronics (11223344556)
  ('11223344556', 23, 24.00, 22), ('09876543210', 23, 30.00, 18), ('66554433221', 23, 28.00, 25),
  -- Prodotto 24: Turrican C64 - Miglior: Old School Tech (66554433221)
  ('11223344556', 24, 20.00, 40), ('66554433221', 24, 15.00, 32), ('09876543210', 24, 18.00, 45),
  -- Prodotto 25: International Karate C64 - Miglior: Vintage Computer (01234567890)
  ('11223344556', 25, 24.00, 28), ('09876543210', 25, 26.00, 22), ('01234567890', 25, 19.00, 30),
  -- Prodotto 26: Monkey Island Amiga - Miglior: Retro Gaming (09876543210)
  ('09876543210', 26, 35.00, 20), ('01234567890', 26, 42.00, 15), ('66554433221', 26, 40.00, 25),
  -- Prodotto 27: Lemmings Amiga - Miglior: Classic Electronics (11223344556)
  ('09876543210', 27, 32.00, 35), ('11223344556', 27, 26.00, 28), ('01234567890', 27, 30.00, 40),
  -- Prodotto 28: Speedball 2 Amiga - Miglior: Old School Tech (66554433221)
  ('09876543210', 28, 45.00, 18), ('66554433221', 28, 38.00, 15), ('11223344556', 28, 42.00, 22),
  -- Prodotto 29: Shadow of the Beast Amiga - Miglior: Vintage Computer (01234567890)
  ('66554433221', 29, 48.00, 15), ('09876543210', 29, 50.00, 12), ('01234567890', 29, 42.00, 18),
  -- Prodotto 30: Sensible Soccer Amiga - Miglior: Retro Gaming (09876543210)
  ('66554433221', 30, 28.00, 30), ('09876543210', 30, 22.00, 22), ('01234567890', 30, 25.00, 35),
  -- Prodotto 31: Commodore Calcolatore 776M - Miglior: Vintage Computer (01234567890)
  ('01234567890', 31, 55.00, 20), ('09876543210', 31, 62.00, 15), ('66554433221', 31, 60.00, 18);

-- LISTINO PREZZI CON MAGAZZINO (5 negozi x 31 prodotti)
-- Prezzi realistici per mercato retro gaming - DEVONO essere superiori ai costi fornitore!
-- Costi min fornitore: 1=C64(118), 2=Amiga500(275), 3=Spectrum(82), 4=AppleII(345), 5=Atari800XL(108),
--                      6=MSX(92), 7=Amstrad(138), 8=NES(92), 9=MasterSystem(72), 10=Atari2600(62),
--                      11=Datasette(24), 12=Drive1541(82), 13=Monitor1084(92), 14=Joystick(17.50),
--                      15=Floppy3.5(4.20), 16=Floppy5.25(3.00), 17=MouseAmiga(43), 18=TastieraC64(20),
--                      19=CavoRGB(7.50), 20=Alimentatore(11), 21=BubbleBobble(33), 22=LastNinja(30),
--                      23=Turrican(26), 24=Lemmings(16), 25=Speedball2(20), 26=SuperMario3(36),
--                      27=Zelda(28), 28=Sonic(40), 29=MonkeyIsland(42), 30=SensibleSoccer(23)
INSERT INTO negozi.listino_negozio (negozio, prodotto, prezzo_listino, magazzino) VALUES
  -- Milano (negozio 1)
  (1,1,149.99,8),(1,2,349.99,5),(1,3,109.99,12),(1,4,449.99,3),(1,5,139.99,7),(1,6,119.99,6),(1,7,179.99,4),(1,8,129.99,15),
  (1,9,99.99,10),(1,10,89.99,8),(1,11,34.99,25),(1,12,109.99,6),(1,13,129.99,4),(1,14,24.99,40),(1,15,5.99,100),
  (1,16,4.49,80),(1,17,59.99,12),(1,18,29.99,18),(1,19,11.99,50),(1,20,16.99,35),
  (1,21,44.99,8),(1,22,42.99,10),(1,23,37.99,12),(1,24,24.99,15),(1,25,29.99,10),
  (1,26,49.99,6),(1,27,39.99,14),(1,28,54.99,5),(1,29,59.99,4),(1,30,32.99,18),(1,31,79.99,5),
  -- Roma (negozio 2)
  (2,1,159.99,6),(2,2,359.99,4),(2,3,99.99,10),(2,4,429.99,2),(2,5,129.99,5),(2,6,109.99,5),(2,7,189.99,3),(2,8,119.99,12),
  (2,9,94.99,8),(2,10,84.99,6),(2,11,32.99,20),(2,12,114.99,5),(2,13,119.99,3),(2,14,22.99,35),(2,15,5.49,90),
  (2,16,3.99,70),(2,17,54.99,10),(2,18,27.99,15),(2,19,10.99,45),(2,20,15.99,30),
  (2,21,47.99,7),(2,22,44.99,8),(2,23,34.99,10),(2,24,22.99,12),(2,25,27.99,8),
  (2,26,52.99,5),(2,27,42.99,12),(2,28,57.99,4),(2,29,62.99,3),(2,30,34.99,15),(2,31,84.99,4),
  -- Torino (negozio 3)
  (3,1,154.99,5),(3,2,339.99,3),(3,3,104.99,8),(3,4,419.99,2),(3,5,134.99,4),(3,6,114.99,4),(3,7,169.99,3),(3,8,124.99,10),
  (3,9,89.99,7),(3,10,79.99,5),(3,11,36.99,18),(3,12,104.99,4),(3,13,124.99,2),(3,14,26.99,30),(3,15,6.49,80),
  (3,16,4.29,60),(3,17,52.99,8),(3,18,31.99,12),(3,19,12.99,40),(3,20,17.99,25),
  (3,21,49.99,6),(3,22,46.99,7),(3,23,32.99,8),(3,24,21.99,10),(3,25,26.99,7),
  (3,26,47.99,4),(3,27,37.99,10),(3,28,52.99,3),(3,29,57.99,2),(3,30,36.99,12),(3,31,82.99,3),
  -- Bologna (negozio 4)
  (4,1,144.99,4),(4,2,329.99,2),(4,3,114.99,6),(4,4,439.99,1),(4,5,144.99,3),(4,6,124.99,3),(4,7,174.99,2),(4,8,134.99,8),
  (4,9,104.99,5),(4,10,94.99,4),(4,11,29.99,15),(4,12,119.99,3),(4,13,114.99,2),(4,14,23.99,25),(4,15,5.29,70),
  (4,16,3.79,50),(4,17,57.99,6),(4,18,26.99,10),(4,19,9.99,35),(4,20,14.99,20),
  (4,21,42.99,5),(4,22,39.99,5),(4,23,29.99,6),(4,24,19.99,8),(4,25,24.99,5),
  (4,26,54.99,3),(4,27,44.99,8),(4,28,59.99,2),(4,29,54.99,2),(4,30,29.99,10),(4,31,77.99,4),
  -- Firenze (negozio 5)
  (5,1,139.99,5),(5,2,319.99,3),(5,3,107.99,9),(5,4,459.99,2),(5,5,124.99,4),(5,6,104.99,4),(5,7,184.99,3),(5,8,117.99,11),
  (5,9,92.99,7),(5,10,82.99,5),(5,11,33.99,18),(5,12,107.99,4),(5,13,127.99,3),(5,14,21.99,32),(5,15,5.79,85),
  (5,16,4.19,65),(5,17,64.99,9),(5,18,24.99,14),(5,19,8.99,42),(5,20,18.99,28),
  (5,21,45.99,6),(5,22,41.99,7),(5,23,35.99,9),(5,24,27.99,11),(5,25,31.99,7),
  (5,26,48.99,4),(5,27,38.99,11),(5,28,55.99,3),(5,29,56.99,3),(5,30,31.99,14),(5,31,75.99,6);

-- TESSERE (una per cliente)
INSERT INTO negozi.tessere (negozio_emittente, data_richiesta, saldo_punti)
SELECT
  1 + (RANDOM() * 4)::INTEGER,
  CURRENT_DATE - (RANDOM() * 730)::INTEGER,
  (RANDOM() * 500)::INTEGER
FROM generate_series(1, 15);

-- ASSOCIA TESSERE AI CLIENTI (aggiorna campo tessera in clienti)
UPDATE negozi.clienti c
SET tessera = t.id_tessera
FROM (
  SELECT id_cliente, ROW_NUMBER() OVER (ORDER BY id_cliente) as rn
  FROM negozi.clienti
) c2
JOIN (
  SELECT id_tessera, ROW_NUMBER() OVER (ORDER BY id_tessera) as rn
  FROM negozi.tessere
) t ON c2.rn = t.rn
WHERE c.id_cliente = c2.id_cliente;

COMMIT;

-- RIEPILOGO
SELECT '=== DATABASE POPOLATO ===' as info;
SELECT 'Ruoli:' as tipo, COUNT(*) as totale FROM auth.ruolo
UNION ALL SELECT 'Utenti:', COUNT(*) FROM auth.utenti
UNION ALL SELECT 'Clienti:', COUNT(*) FROM negozi.clienti
UNION ALL SELECT 'Negozi:', COUNT(*) FROM negozi.negozi
UNION ALL SELECT 'Fornitori:', COUNT(*) FROM negozi.fornitori
UNION ALL SELECT 'Prodotti:', COUNT(*) FROM negozi.prodotti
UNION ALL SELECT 'Magazzino:', COUNT(*) FROM negozi.magazzino_fornitore
UNION ALL SELECT 'Listino:', COUNT(*) FROM negozi.listino_negozio
UNION ALL SELECT 'Tessere:', COUNT(*) FROM negozi.tessere;

SELECT 'ACCESSO: manager@retrogaming.it / retro1980' as credenziali;
