-- ============================================================================
-- SCRIPT POPOLAMENTO DATABASE - NEGOZI RETRO GAMING & COMPUTER ANNI '80
-- ============================================================================

BEGIN;

-- Pulisci dati esistenti (l'ordine è importante per le foreign key!)
TRUNCATE TABLE negozi.cliente_tessera CASCADE;
TRUNCATE TABLE negozi.tessere CASCADE;
TRUNCATE TABLE negozi.listino_negozio CASCADE;
TRUNCATE TABLE negozi.magazzino_fornitore CASCADE;
TRUNCATE TABLE negozi.dettagli_fattura CASCADE;
TRUNCATE TABLE negozi.fatture CASCADE;
TRUNCATE TABLE negozi.ordini_fornitori CASCADE;
TRUNCATE TABLE negozi.prodotti CASCADE;
TRUNCATE TABLE negozi.fornitori CASCADE;
TRUNCATE TABLE negozi.orari CASCADE;
TRUNCATE TABLE negozi.negozio CASCADE;
TRUNCATE TABLE negozi.clienti CASCADE;
TRUNCATE TABLE auth.utente_ruolo CASCADE;
TRUNCATE TABLE auth.utenti CASCADE;
TRUNCATE TABLE auth.ruolo CASCADE;

-- Reset sequences per garantire che gli ID partano da 1
ALTER SEQUENCE negozi.prodotti_id_prodotto_seq RESTART WITH 1;
ALTER SEQUENCE negozi.negozio_id_negozio_seq RESTART WITH 1;
ALTER SEQUENCE negozi.clienti_id_cliente_seq RESTART WITH 1;
ALTER SEQUENCE negozi.tessere_id_tessera_seq RESTART WITH 1;
ALTER SEQUENCE auth.utenti_id_utente_seq RESTART WITH 1;
ALTER SEQUENCE auth.ruolo_id_ruolo_seq RESTART WITH 1;

-- RUOLI
INSERT INTO auth.ruolo (nome, descrizione) VALUES
  ('manager', 'Gestore catena negozi'),
  ('cliente', 'Cliente finale')
ON CONFLICT (nome) DO NOTHING;

-- MANAGER (password: retro1980)
INSERT INTO auth.utenti (email, password, attivo) VALUES
  ('manager@retrogaming.it', '$2y$12$tVK3xXy63eHfjqSvsig0vOfbqw8LI0luAMSLs.9nN3Yp6pg4FNJA.', TRUE)
ON CONFLICT (email) DO NOTHING;

INSERT INTO auth.utente_ruolo (id_utente, id_ruolo)
SELECT u.id_utente, r.id_ruolo
FROM auth.utenti u, auth.ruolo r
WHERE u.email = 'manager@retrogaming.it' AND r.nome = 'manager'
ON CONFLICT DO NOTHING;

-- CLIENTI (password: retro1980)
INSERT INTO auth.utenti (email, password, attivo) VALUES
  ('mario.rossi@email.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE),
  ('laura.bianchi@email.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE),
  ('paolo.verdi@email.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE),
  ('anna.neri@email.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE),
  ('luca.ferrari@email.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE),
  ('giulia.russo@email.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE),
  ('marco.colombo@email.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE),
  ('sara.ricci@email.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE),
  ('andrea.bruno@email.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE),
  ('elena.gallo@email.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE),
  ('fabio.conti@email.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE),
  ('chiara.romano@email.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE),
  ('roberto.moretti@email.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE),
  ('valentina.fontana@email.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE),
  ('davide.greco@email.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE)
ON CONFLICT (email) DO NOTHING;

-- Ruolo cliente
INSERT INTO auth.utente_ruolo (id_utente, id_ruolo)
SELECT u.id_utente, r.id_ruolo FROM auth.utenti u, auth.ruolo r
WHERE u.email IN ('mario.rossi@email.it', 'laura.bianchi@email.it', 'paolo.verdi@email.it', 'anna.neri@email.it', 'luca.ferrari@email.it',
                  'giulia.russo@email.it', 'marco.colombo@email.it', 'sara.ricci@email.it', 'andrea.bruno@email.it', 'elena.gallo@email.it',
                  'fabio.conti@email.it', 'chiara.romano@email.it', 'roberto.moretti@email.it', 'valentina.fontana@email.it', 'davide.greco@email.it')
AND r.nome = 'cliente' ON CONFLICT DO NOTHING;

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
INSERT INTO negozi.negozio (nome_negozio, responsabile, indirizzo) VALUES
  ('Retro Gaming Milano', 'Mario Plummer', 'Via Dante 42, 20121 Milano'),
  ('Retro Gaming Roma', 'Luigi Plummer', 'Corso Vittorio Emanuele 156, 00186 Roma'),
  ('Retro Gaming Torino', 'Bruno Frank', 'Via Garibaldi 88, 10122 Torino'),
  ('Retro Gaming Bologna', 'Guybrush Threepwood', 'Piazza Maggiore 15, 40124 Bologna'),
  ('Retro Gaming Firenze', 'Conan Barbarian', 'Via Mazzini 33, 50123 Firenze');
-- ORARI: Lun-Sab 9:30-13:00 (mattina, iod=1)
INSERT INTO negozi.orari (negozio, dow, iod, apertura, chiusura)
SELECT n.id_negozio, g, 1, '09:30:00', '13:00:00'
FROM negozi.negozio n CROSS JOIN generate_series(1, 6) g;

-- ORARI: Lun-Sab 15:30-19:30 (pomeriggio, iod=2)
INSERT INTO negozi.orari (negozio, dow, iod, apertura, chiusura)
SELECT n.id_negozio, g, 2, '15:30:00', '19:30:00'
FROM negozi.negozio n CROSS JOIN generate_series(1, 6) g;

-- ORARI: Domenica 10:00-13:00 (mattina, iod=1)
INSERT INTO negozi.orari (negozio, dow, iod, apertura, chiusura)
SELECT n.id_negozio, 7, 1, '10:00:00', '13:00:00' FROM negozi.negozio n;

-- ORARI: Domenica 16:00-19:00 (pomeriggio, iod=2)
INSERT INTO negozi.orari (negozio, dow, iod, apertura, chiusura)
SELECT n.id_negozio, 7, 2, '16:00:00', '19:00:00' FROM negozi.negozio n;

-- FORNITORI
INSERT INTO negozi.fornitori (piva, nome_fornitore, indirizzo, email, telefono, attivo) VALUES
  ('01234567890', 'Vintage Computer Wholesale SRL', 'Via Industriale 45, Segrate (MI)', 'info@vintagewholesale.it', '02-87654321', TRUE),
  ('09876543210', 'Retro Gaming Italia SPA', 'Viale Europa 123, Roma', 'vendite@retrogamingitalia.it', '06-12345678', TRUE),
  ('11223344556', 'Classic Electronics Distribution', 'Corso Francia 88, Torino', 'ordini@classicelectronics.it', '011-9876543', TRUE),
  ('66554433221', 'Old School Tech Supply', 'Via Bologna 67, Firenze', 'supply@oldschooltech.it', '055-7654321', TRUE)
ON CONFLICT (piva) DO NOTHING;

-- PRODOTTI (30 prodotti vintage: computer, console, accessori e giochi iconici)
INSERT INTO negozi.prodotti (nome_prodotto, descrizione, immagine_url) VALUES
  -- COMPUTER (7)
  ('Commodore 64', 'Il computer più venduto della storia! CPU MOS 6510 a 1MHz, 64KB RAM, chip sonoro SID leggendario. Include alimentatore e cavi. Perfettamente funzionante, testato.', '/images/products/c64.jpg'),
  ('Commodore Amiga 500', 'La rivoluzione multimediale! CPU Motorola 68000 a 7.14MHz, 512KB Chip RAM espandibile, grafica OCS con 4096 colori, audio stereo Paula. Sistema Workbench 1.3 su floppy. Condizioni eccellenti.', '/images/products/amiga500.jpg'),
  ('ZX Spectrum 48K', 'Icona britannica degli anni 80. CPU Zilog Z80A a 3.5MHz, 48KB RAM, tastiera a membrana originale. Uscita RF per TV. Funzionante, con manuale originale.', '/images/products/spectrum.jpg'),
  ('Apple II Europlus', 'Versione europea dell Apple II. CPU MOS 6502 a 1MHz, 48KB RAM, tastiera meccanica, drive 5.25" Disk II incluso. Perfetto per collezionisti.', '/images/products/appleii.jpg'),
  ('Atari 800XL', 'Home computer di lusso. CPU 6502C a 1.79MHz, 64KB RAM, chip grafici GTIA e ANTIC, chip audio POKEY. BASIC integrato. Ottime condizioni.', '/images/products/atari800xl.jpg'),
  ('MSX Sony HitBit HB-75P', 'Standard MSX giapponese. CPU Z80A a 3.58MHz, 32KB RAM, 32KB VROM, MSX-BASIC integrato. Compatibile con migliaia di giochi.', '/images/products/msx.jpg'),
  ('Amstrad CPC 464', 'All-in-one britannico. CPU Z80A, 64KB RAM, monitor a colori CTM644 integrato, lettore cassette incorporato. Sistema completo e funzionante.', '/images/products/cpc464.jpg'),

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
INSERT INTO negozi.magazzino_fornitore (piva_fornitore, prodotto, prezzo, quantita) VALUES
  -- Prodotto 1: Commodore 64 (3 fornitori)
  ('01234567890', 1, 120.00, 50), ('11223344556', 1, 125.00, 35), ('66554433221', 1, 118.00, 40),
  -- Prodotto 2: Amiga 500 (3 fornitori)
  ('01234567890', 2, 280.00, 30), ('66554433221', 2, 275.00, 25), ('11223344556', 2, 290.00, 20),
  -- Prodotto 3: Atari 2600 (3 fornitori)
  ('01234567890', 3, 85.00, 45), ('09876543210', 3, 82.00, 55), ('66554433221', 3, 88.00, 30),
  -- Prodotto 4: Amiga 1200 (3 fornitori)
  ('11223344556', 4, 350.00, 20), ('01234567890', 4, 360.00, 15), ('66554433221', 4, 345.00, 18),
  -- Prodotto 5: Amiga 2000 (3 fornitori)
  ('66554433221', 5, 110.00, 35), ('01234567890', 5, 115.00, 28), ('11223344556', 5, 108.00, 22),
  -- Prodotto 6: Amiga 4000 (3 fornitori)
  ('66554433221', 6, 95.00, 40), ('11223344556', 6, 98.00, 32), ('01234567890', 6, 92.00, 25),
  -- Prodotto 7: Commodore 128 (3 fornitori)
  ('11223344556', 7, 140.00, 35), ('01234567890', 7, 145.00, 28), ('66554433221', 7, 138.00, 30),
  -- Prodotto 8: NES Nintendo (3 fornitori)
  ('09876543210', 8, 95.00, 80), ('01234567890', 8, 100.00, 45), ('66554433221', 8, 92.00, 50),
  -- Prodotto 9: SNES Super Nintendo (3 fornitori)
  ('09876543210', 9, 75.00, 60), ('66554433221', 9, 78.00, 42), ('11223344556', 9, 72.00, 55),
  -- Prodotto 10: Sega Mega Drive (4 fornitori)
  ('09876543210', 10, 65.00, 40), ('66554433221', 10, 68.00, 50), ('01234567890', 10, 62.00, 35), ('11223344556', 10, 70.00, 28),
  -- Prodotto 11: Joystick Competition Pro (3 fornitori)
  ('01234567890', 11, 25.00, 100), ('09876543210', 11, 28.00, 75), ('11223344556', 11, 24.00, 90),
  -- Prodotto 12: Monitor Commodore 1084 (3 fornitori)
  ('01234567890', 12, 95.00, 40), ('11223344556', 12, 92.00, 35), ('66554433221', 12, 98.00, 28),
  -- Prodotto 13: Floppy Drive 1541 (3 fornitori)
  ('01234567890', 13, 85.00, 25), ('11223344556', 13, 82.00, 30), ('09876543210', 13, 88.00, 22),
  -- Prodotto 14: Cartuccia multigioco C64 (4 fornitori)
  ('09876543210', 14, 18.00, 150), ('11223344556', 14, 19.00, 200), ('01234567890', 14, 17.50, 120), ('66554433221', 14, 18.50, 100),
  -- Prodotto 15: Floppy disk 3.5 (4 fornitori)
  ('11223344556', 15, 4.50, 500), ('66554433221', 15, 4.80, 400), ('09876543210', 15, 4.20, 450), ('01234567890', 15, 4.60, 380),
  -- Prodotto 16: Floppy disk 5.25 (4 fornitori)
  ('11223344556', 16, 3.20, 300), ('66554433221', 16, 3.50, 250), ('09876543210', 16, 3.00, 350), ('01234567890', 16, 3.40, 280),
  -- Prodotto 17: Mouse Amiga (3 fornitori)
  ('01234567890', 17, 45.00, 60), ('66554433221', 17, 48.00, 45), ('11223344556', 17, 43.00, 55),
  -- Prodotto 18: Tastiera C64 ricambio (3 fornitori)
  ('11223344556', 18, 22.00, 80), ('01234567890', 18, 25.00, 60), ('09876543210', 18, 20.00, 70),
  -- Prodotto 19: Cavo video RGB (3 fornitori)
  ('09876543210', 19, 8.00, 180), ('01234567890', 19, 9.00, 150), ('11223344556', 19, 7.50, 200),
  -- Prodotto 20: Alimentatore universale (3 fornitori)
  ('09876543210', 20, 12.00, 120), ('66554433221', 20, 14.00, 90), ('01234567890', 20, 11.00, 100),
  -- Prodotto 21: Bubble Bobble C64 (3 fornitori)
  ('09876543210', 21, 35.00, 25), ('11223344556', 21, 38.00, 20), ('66554433221', 21, 33.00, 28),
  -- Prodotto 22: The Last Ninja C64 (3 fornitori)
  ('09876543210', 22, 32.00, 30), ('01234567890', 22, 35.00, 22), ('11223344556', 22, 30.00, 35),
  -- Prodotto 23: Turrican Amiga (3 fornitori)
  ('11223344556', 23, 28.00, 22), ('09876543210', 23, 30.00, 18), ('66554433221', 23, 26.00, 25),
  -- Prodotto 24: Lemmings Amiga (3 fornitori)
  ('11223344556', 24, 18.00, 40), ('66554433221', 24, 20.00, 32), ('09876543210', 24, 16.00, 45),
  -- Prodotto 25: Speedball 2 Amiga (3 fornitori)
  ('11223344556', 25, 22.00, 28), ('09876543210', 25, 24.00, 22), ('01234567890', 25, 20.00, 30),
  -- Prodotto 26: Super Mario Bros 3 NES (3 fornitori)
  ('09876543210', 26, 38.00, 20), ('01234567890', 26, 42.00, 15), ('66554433221', 26, 36.00, 25),
  -- Prodotto 27: Zelda Link to Past SNES (3 fornitori)
  ('09876543210', 27, 30.00, 35), ('11223344556', 27, 33.00, 28), ('01234567890', 27, 28.00, 40),
  -- Prodotto 28: Sonic Mega Drive (3 fornitori)
  ('09876543210', 28, 42.00, 18), ('66554433221', 28, 45.00, 15), ('11223344556', 28, 40.00, 22),
  -- Prodotto 29: Monkey Island Amiga (3 fornitori)
  ('66554433221', 29, 45.00, 15), ('09876543210', 29, 48.00, 12), ('11223344556', 29, 42.00, 18),
  -- Prodotto 30: Sensible Soccer Amiga (3 fornitori)
  ('66554433221', 30, 25.00, 30), ('09876543210', 30, 28.00, 22), ('01234567890', 30, 23.00, 35);

-- LISTINO PREZZI CON MAGAZZINO (5 negozi x 30 prodotti)
-- Formato: (negozio, prodotto, prezzo_listino, magazzino)
INSERT INTO negozi.listino_negozio (negozio, prodotto, prezzo_listino, magazzino) VALUES
  -- Milano (negozio 1) - Prezzi premium, magazzino ampio
  (1,1,229.99,8),(1,2,549.99,5),(1,3,159.99,12),(1,4,689.99,3),(1,5,209.99,7),(1,6,179.99,6),(1,7,269.99,4),(1,8,189.99,15),
  (1,9,149.99,10),(1,10,129.99,8),(1,11,49.99,25),(1,12,189.99,6),(1,13,169.99,4),(1,14,35.99,40),(1,15,8.99,100),
  (1,16,6.49,80),(1,17,89.99,12),(1,18,42.99,18),(1,19,16.99,50),(1,20,23.99,35),
  (1,21,69.99,8),(1,22,64.99,10),(1,23,54.99,12),(1,24,39.99,15),(1,25,44.99,10),
  (1,26,74.99,6),(1,27,59.99,14),(1,28,84.99,5),(1,29,89.99,4),(1,30,49.99,18),
  -- Roma (negozio 2) - Prezzi medi, magazzino medio
  (2,1,219.99,6),(2,2,529.99,4),(2,3,154.99,10),(2,4,659.99,2),(2,5,199.99,5),(2,6,174.99,5),(2,7,259.99,3),(2,8,179.99,12),
  (2,9,144.99,8),(2,10,124.99,6),(2,11,47.99,20),(2,12,179.99,5),(2,13,164.99,3),(2,14,34.99,35),(2,15,8.49,90),
  (2,16,5.99,70),(2,17,84.99,10),(2,18,39.99,15),(2,19,15.99,45),(2,20,21.99,30),
  (2,21,64.99,7),(2,22,59.99,8),(2,23,49.99,10),(2,24,36.99,12),(2,25,41.99,8),
  (2,26,69.99,5),(2,27,54.99,12),(2,28,79.99,4),(2,29,84.99,3),(2,30,46.99,15),
  -- Torino (negozio 3) - Prezzi competitivi, magazzino variabile
  (3,1,209.99,5),(3,2,499.99,3),(3,3,149.99,8),(3,4,629.99,2),(3,5,189.99,4),(3,6,169.99,4),(3,7,249.99,3),(3,8,169.99,10),
  (3,9,134.99,7),(3,10,114.99,5),(3,11,44.99,18),(3,12,169.99,4),(3,13,154.99,2),(3,14,32.99,30),(3,15,7.99,80),
  (3,16,5.49,60),(3,17,79.99,8),(3,18,37.99,12),(3,19,14.99,40),(3,20,19.99,25),
  (3,21,59.99,6),(3,22,54.99,7),(3,23,44.99,8),(3,24,34.99,10),(3,25,39.99,7),
  (3,26,64.99,4),(3,27,49.99,10),(3,28,74.99,3),(3,29,79.99,2),(3,30,44.99,12),
  -- Bologna (negozio 4) - Prezzi competitivi, magazzino ridotto
  (4,1,209.99,4),(4,2,499.99,2),(4,3,149.99,6),(4,4,629.99,1),(4,5,189.99,3),(4,6,169.99,3),(4,7,249.99,2),(4,8,169.99,8),
  (4,9,134.99,5),(4,10,114.99,4),(4,11,44.99,15),(4,12,169.99,3),(4,13,154.99,2),(4,14,32.99,25),(4,15,7.99,70),
  (4,16,5.49,50),(4,17,79.99,6),(4,18,37.99,10),(4,19,14.99,35),(4,20,19.99,20),
  (4,21,59.99,5),(4,22,54.99,5),(4,23,44.99,6),(4,24,34.99,8),(4,25,39.99,5),
  (4,26,64.99,3),(4,27,49.99,8),(4,28,74.99,2),(4,29,79.99,2),(4,30,44.99,10),
  -- Firenze (negozio 5) - Prezzi intermedi, magazzino medio
  (5,1,214.99,5),(5,2,519.99,3),(5,3,152.99,9),(5,4,649.99,2),(5,5,194.99,4),(5,6,172.99,4),(5,7,254.99,3),(5,8,174.99,11),
  (5,9,139.99,7),(5,10,119.99,5),(5,11,46.99,18),(5,12,174.99,4),(5,13,159.99,3),(5,14,33.99,32),(5,15,8.49,85),
  (5,16,5.99,65),(5,17,84.99,9),(5,18,39.99,14),(5,19,15.99,42),(5,20,21.99,28),
  (5,21,62.99,6),(5,22,57.99,7),(5,23,47.99,9),(5,24,35.99,11),(5,25,40.99,7),
  (5,26,67.99,4),(5,27,52.99,11),(5,28,77.99,3),(5,29,82.99,3),(5,30,45.99,14);

-- TESSERE (una per cliente)
INSERT INTO negozi.tessere (negozio_emittente, data_richiesta, saldo_punti)
SELECT
  1 + (RANDOM() * 4)::INTEGER,
  CURRENT_DATE - (RANDOM() * 730)::INTEGER,
  (RANDOM() * 500)::INTEGER
FROM generate_series(1, 15);

-- ASSOCIA TESSERE AI CLIENTI (una tessera per cliente)
INSERT INTO negozi.cliente_tessera (cliente, tessera)
SELECT c.id_cliente, t.id_tessera
FROM (
  SELECT id_cliente, ROW_NUMBER() OVER (ORDER BY id_cliente) as rn
  FROM negozi.clienti
) c
JOIN (
  SELECT id_tessera, ROW_NUMBER() OVER (ORDER BY id_tessera) as rn
  FROM negozi.tessere
) t ON c.rn = t.rn;

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
