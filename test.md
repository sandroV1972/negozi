# Piano di Test - Sistema Gestione Negozi Retro Gaming

## 1. Test Autenticazione e Autorizzazione

### 1.1 Registrazione Utenti
- [x] Inserimento nuovo utente con email valida
- [x] Registrazione con email già esistente (deve fallire)
- [x] Registrazione con CF già esistente (deve fallire)
- [x] Verifica che l'email venga convertita in lowercase (trigger `email_to_lower`)

### 1.2 Login/Logout
- [x] Login con credenziali corrette (manager@retrogaming.it / retro1980)
- [x] Login con credenziali errate (deve fallire)
- [x] Login con email maiuscola (deve funzionare grazie al trigger)
- [x] Logout e verifica sessione terminata
- [x] Accesso a pagine protette senza login (deve reindirizzare)

### 1.3 Ruoli e Permessi
- [x] Verifica accesso manager a tutte le funzionalità
- [x] Verifica accesso cliente limitato alla propria dashboard
- [x] Tentativo di accesso cliente a pagine manager (deve fallire)

---

## 2. Test Gestione Entità

### 2.1 Negozi (CRUD)
- [x] **CREATE**: Creazione nuovo negozio con tutti i campi
- [x] **READ**: Visualizzazione lista negozi
- [x] **UPDATE**: Modifica nome, responsabile, indirizzo negozio
- [x] **DELETE**: Eliminazione negozio (verifica cascade su orari, listino, tessere)

### 2.2 Fornitori (CRUD)
- [x] **CREATE**: Creazione nuovo fornitore con P.IVA valida (11 caratteri)
- [x] **CREATE**: Tentativo con P.IVA duplicata (deve fallire)
- [x] **READ**: Visualizzazione lista fornitori con statistiche (num prodotti, totale disponibile)
- [x] **UPDATE**: Modifica ragione sociale, indirizzo, email, telefono
- [ ] **DELETE**: Eliminazione fornitore (verifica vincoli con ordini esistenti)

### 2.3 Prodotti (CRUD)
- [x] **CREATE**: Creazione nuovo prodotto con nome, descrizione, immagine
- [x] **READ**: Visualizzazione catalogo prodotti
- [x] **UPDATE**: Modifica descrizione e immagine prodotto
- [ ] **DELETE**: Eliminazione prodotto (verifica cascade su listino e magazzino)
- [ ] Assegnazione di un prodotto a un fornitore con un prezzo

### 2.4 Clienti (CRUD)
- [x] **CREATE**: Creazione nuovo cliente con CF valido (16 caratteri)
- [x] **CREATE**: Tentativo con CF duplicato (deve fallire)
- [x] **READ**: Visualizzazione lista clienti
- [x] **UPDATE**: Modifica nome, cognome, telefono cliente
- [x] **DELETE**: Eliminazione cliente (verifica gestione tessera associata)
- [ ] **DELETE**: Verificare come gestire tessere a eliminazione cliente

---

## 3. Test Listino Negozio

### 3.1 Gestione Listino
- [x] Aggiunta prodotto al listino con prezzo iniziale
- [x] Modifica prezzo di un prodotto nel listino
- [x] Rimozione prodotto dal listino
- [ ] Verifica che i prezzi listino siano superiori ai costi fornitore

### 3.2 Visualizzazione
- [x] Verifica visualizzazione corretta magazzino disponibile
- [x] Verifica ordinamento prodotti per nome

---

## 4. Test Ordini ai Fornitori

### 4.1 Creazione Ordini
- [x] Ordine prodotto con quantità valida
- [x] Verifica selezione automatica miglior fornitore (funzione `miglior_fornitore`)
- [x] Ordine con quantità superiore a disponibilità fornitore (deve fallire)
- [x] Verifica decremento magazzino fornitore dopo ordine (trigger `trg_aggiorna_magazzino`)

### 4.2 Stato Ordini
- [x] Verifica stato iniziale "emesso"
- [x] Cambio stato da "emesso" a "consegnato"
  - [x] Verifica incremento magazzino negozio (trigger `trg_aggiorna_ordine`)
- [x] Cambio stato da "emesso" a "annullato"
  - [x] Verifica ripristino magazzino fornitore (trigger `trg_aggiorna_ordine`)
- [ ] Tentativo cambio stato non valido (deve fallire per CHECK constraint)

### 4.3 Visualizzazione Ordini
- [x] Lista ordini per fornitore (funzione `lista_ordini_fornitore`)
- [x] Verifica visualizzazione: nome fornitore, prodotto, negozio, quantità, data, stato
- [x] Verifica ordinamento per data decrescente

### 4.4 Test Miglior Fornitore
- [x] Verifica che `miglior_fornitore(id_prodotto)` restituisca il fornitore con prezzo più basso
- [x] Test con prodotto fornito da un solo fornitore
- [x] Test con prodotto fornito da più fornitori con prezzi diversi
- [x] Test con prodotto non presente in magazzino fornitori (deve restituire NULL)

---

## 5. Test Tessere Fedeltà

### 5.1 Creazione Tessere
- [x] Creazione tessera per cliente senza tessera (funzione `crea_tessera_cliente`)
- [x] Tentativo creazione tessera per cliente che già ne possiede una (deve restituire NULL)
- [x] Tentativo creazione tessera per cliente inesistente (deve restituire NULL)
- [x] Verifica associazione tessera al negozio emittente
- [x] Verifica saldo punti iniziale = 0

### 5.2 Gestione Punti
- [x] Verifica accumulo punti dopo acquisto (1 punto per euro speso)
- [x] Verifica trigger `trg_aggiorna_punti` su UPDATE fattura
- [x] Test con acquisto di €150 → verifica +150 punti
- [ ] Verifica constraint `saldo_punti >= 0`

### 5.3 Livelli Sconto
- [x] Verifica livelli: 100 punti = 5%, 200 punti = 15%, 300 punti = 30%
- [x] Applicazione sconto 5% con 100+ punti
- [x] Applicazione sconto 15% con 200+ punti
- [x] Applicazione sconto 30% con 300+ punti
- [x] Verifica decremento punti dopo applicazione sconto (procedura `aggiorna_punti_tessera`)

### 5.4 Vista Saldi Elevati
- [x] Verifica vista `v_saldi_punti_300` mostra solo clienti con >300 punti
- [x] Verifica campi: nome, cognome, nome_negozio, saldo_punti, data_richiesta

---

## 6. Test Acquisti Clienti (Fatture)

### 6.1 Creazione Fattura
- [x] Creazione fattura per cliente esistente
- [ ] Aggiunta dettagli fattura (prodotto, quantità, prezzo unitario)
- [ ] Verifica calcolo automatico totale (procedura `aggiorna_totale_fattura`)

### 6.2 Applicazione Sconti
- [x] Fattura senza sconto (cliente senza punti sufficienti)
- [x] Fattura con sconto 5% (cliente con 100+ punti)
- [x] Fattura con sconto 15% (cliente con 200+ punti)
- [x] Fattura con sconto 30% (cliente con 300+ punti)
- [x] Verifica formula: totale_netto = totale_lordo * (1 - sconto/100)

### 6.3 Aggiornamento Magazzino
- [x] Verifica decremento magazzino negozio dopo vendita
- [x] Tentativo vendita con quantità superiore a disponibilità (deve fallire)

### 6.4 Aggiornamento Punti
- [x] Verifica che dopo UPDATE di totale_pagato vengano aggiunti punti
- [x] Verifica punti = floor(totale_pagato)
- [x] Cliente senza tessera: nessun aggiornamento punti

---

## 7. Test Archiviazione Tessere

### 7.1 Archiviazione per Cancellazione Negozio
- [ ] Eliminazione negozio con tessere attive
- [ ] Verifica trigger `trg_archivia_tessere_negozio` eseguito
- [ ] Verifica inserimento record in `storico_tessere`:
  - [ ] codice_tessera
  - [ ] cliente (se associato)
  - [ ] saldo_punti
  - [ ] negozio_emittente
  - [ ] data_emissione
- [ ] Verifica eliminazione tessere dalla tabella `tessere`
- [ ] Verifica che clienti abbiano `tessera = NULL` dopo archiviazione


### 7.3 Gestione Cliente con Tessera
- [ ] Eliminazione cliente con tessera: verifica tessera impostata a NULL (FK ON DELETE SET NULL)
- [ ] Verifica che la tessera rimanga ma senza cliente associato

---

## 8. Test Orari Negozio

### 8.1 Gestione Orari
- [ ] Inserimento orario mattina (iod=1) per un giorno
- [ ] Inserimento orario pomeriggio (iod=2) per un giorno
- [ ] Modifica orario esistente
- [ ] Eliminazione orario
- [ ] Verifica constraint `dow BETWEEN 1 AND 7`
- [ ] Verifica constraint `iod IN (1,2)`

### 8.2 Cascade su Eliminazione Negozio
- [ ] Verifica eliminazione automatica orari quando si elimina un negozio

---

## 9. Test Magazzino Fornitori

### 9.1 Gestione Stock
- [x] Verifica quantità iniziali per ogni fornitore/prodotto
- [x] Verifica constraint `quantita >= 0`
- [x] Verifica constraint `prezzo >= 0`
- [x] Tentativo inserimento quantità negativa (deve fallire)

### 9.2 Distribuzione Prezzi
- [x] Verifica che ogni fornitore abbia ~7-8 prodotti con prezzo migliore
- [x] Verifica che tutti i 30 prodotti abbiano almeno 2-3 fornitori

---

## 10. Test Integrità Referenziale

### 10.1 Foreign Keys - CASCADE
- [ ] DELETE negozio → DELETE orari associati
- [ ] DELETE negozio → DELETE listino associato
- [ ] DELETE negozio → DELETE tessere associate (dopo archiviazione)
- [ ] DELETE prodotto → DELETE da listino_negozio
- [ ] DELETE prodotto → DELETE da magazzino_fornitore
- [ ] DELETE fornitore → DELETE da magazzino_fornitore
- [ ] DELETE fattura → DELETE dettagli_fattura
- [ ] DELETE utente → DELETE sessioni, reset_token, utente_ruolo

### 10.2 Foreign Keys - RESTRICT/NO ACTION
- [ ] DELETE cliente con fatture → deve fallire (RESTRICT)
- [ ] DELETE fornitore con ordini → deve fallire (NO ACTION)
- [ ] DELETE negozio con ordini → deve fallire (NO ACTION)

### 10.3 Foreign Keys - SET NULL
- [ ] DELETE tessera → cliente.tessera = NULL

---

## 11. Test Funzioni e Procedure

### 11.1 Funzioni
| Funzione | Test |
|----------|------|
| `miglior_fornitore(id_prodotto)` | Restituisce P.IVA fornitore con prezzo minimo |
| `crea_tessera_cliente(id_cliente, id_negozio)` | Crea tessera e restituisce ID |
| `lista_ordini_fornitore(piva)` | Restituisce tabella ordini con stato |
| `tessere_negozio(id_negozio)` | Restituisce lista tessere del negozio |
| `archivia_tessere_negozio(id_negozio)` | Archivia e elimina tessere |

### 11.2 Procedure
| Procedura | Test |
|-----------|------|
| `aggiorna_punti_tessera(tessera, sconto)` | Decrementa punti per sconto applicato |
| `aggiorna_totale_fattura(fattura)` | Ricalcola totale con sconto |

### 11.3 Trigger
| Trigger | Evento | Test |
|---------|--------|------|
| `email_to_lower` | INSERT/UPDATE utenti | Email convertita in lowercase |
| `trg_aggiorna_magazzino` | INSERT ordini_fornitori | Decrementa magazzino fornitore |
| `trg_aggiorna_ordine` | UPDATE stato_ordine | Aggiorna magazzino negozio/fornitore |
| `trg_aggiorna_punti` | UPDATE totale_pagato | Aggiunge punti tessera |
| `trg_archivia_tessere_negozio` | DELETE negozi | Archivia tessere prima di delete |

---

## 12. Test API PHP

### 12.1 Endpoint API
- [ ] `api/ordini_fornitore.php?piva=XXX` - Ritorna JSON ordini
- [ ] `api/ordini_negozio.php` - (se presente) Ritorna ordini per negozio

### 12.2 Sicurezza API
- [ ] Verifica controllo sessione (solo manager)
- [ ] Verifica sanitizzazione parametri
- [ ] Verifica gestione errori (JSON con campo error)

---

## 13. Test Performance (Opzionali)

### 13.1 Query Complesse
- [ ] Lista ordini con JOIN su 4 tabelle
- [ ] Calcolo miglior fornitore su tutti i prodotti
- [ ] Vista v_saldi_punti_300 con molti clienti

### 13.2 Indici
- [ ] Verifica indici su chiavi esterne
- [ ] Verifica indici su campi di ricerca frequente

---

## Note

- Eseguire i test in un database di sviluppo/test, non in produzione
- Ripristinare i dati iniziali con `populate_db.sql` dopo ogni sessione di test
- Documentare eventuali bug trovati con screenshot e query eseguite
