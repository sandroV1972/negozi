# Progetto “Negozi” — Documentazione

> **Versione:** 1.0  
> **Autore:** Alessandro Valenti 
> **Corso:** Basi di Dati — Laboratorio - Progetto 2025
> **Tecnologie:** PostgreSQL, PHP, HTML/CSS, JavaScript, Bootstrap

---

## Introduzione
Obiettivo: sviluppare un’applicazione per la gestione di una catena di negozi, con funzionalità per clienti e manager. I clienti possono accedere tramite login al negozio online che presenta la lista dei prodotti che possono essee acquistati scegliendo per ogni prodotto da quale negozio acquistare e la quantità. Allo stesso modo i/il manager accede tramite login e accede a una dashboard di gestione: negozi, clienti, prodotti, fornitori.

# Analisi dei requisiti

## 1. Scopo e contesto
Realizzare un’applicazione per la gestione di una **catena di negozi**, con funzionalità per **clienti** e **manager**: i clienti consultano i prodotti, li acquistano e possono applicare sconti su fattura grazie ai punti accumulati nella tesera fedeltà; le tessere sono rilasciate dai negozi ma sono valide per acquisti nel sito e quindi in tutti i negozi; i manager gestiscono anagrafiche e processi (prodotti, prezzi per negozio, negozi, clieti, fornitori, ordini ai fornitori, carte fedeltà).

## 2. Stakeholder e attori

### Attori primari:
- **Cliente** (utente finale): i clienti (dati utente e dati personali) sono aggiunti dal mananager. Il clinete quindi accede con credenziali iniziali, la username corrisponde alla email e una password default. Il cliente puo cambiare la password in qualsiasi momento, una volta entrato nel sito con le credenziali visualizza prodotti, effettua acquisti, può applicare uno sconto disponibile a seconda dei punti accumulati per un massimo di 100euro, e vedere il saldo punti della propria tessera. Solo il manager puo eliminare il cliente.
- **Manager** (gestore catena): accede con credenziali (il sistema nasce con le credenziali di amministratore), può cambiare password; crea e gestisce utenze clienti; inserisce e gestisce i prodotti (con prezzi differenziati per negozio), gestisce i negozi, i fornitori; inserisce ordini ai fornitori tramite il magazzino negozio per rifornimenti a uno specifico negozio per uno specifico prodotto. L'ordine viene indirizzato automaticamente al negozio che per quel prodotto garantisce il prezzo migliore.

### Stackholders:
- **Negozi** ogni punto vendità è una entità autonoma nella catena e viene identificato da un codice, ogni negozio ha un responsabile, orari di apertura (gestiti separatamente) e indirizzo. E' il negozio che rilascia la tessera fedeltà al cliente, sempre a gestione del manager, dalla lista dei propri tesserati, scegliendo tra i clienti che non possiedono ancora una tessera fedelta. Se non esistono clienti senza tessera la funzione viene disabilitata. 
- **Fornitori** sono i punti di rifornimento dei prodotti. Sono identificati da un numero di PIVA (11 caratteri numerici che possono cominciare con 0). Ogni fonrnitore fornisce più prodotti e ogni prodotto può essere venduto da più fornitori. Sono coinvolti negli **ordini**  e alla fine di ogni ordine al fornitore viene aggiornata la quantità a disposizione (diminuita della quantita richiesta), l'ordine riceve una data d'ordine e una presunta data di arrivo dell'ordine (viene generata automaticamente 7gg dalla dat d'ordine), il negozio vede la lista di ordini in attesa e puo segnarli come arrivati, a quel punto la quantita ordinata aumenta il magazzino del negozio.

### Altre Entita
- **Fatture** le fatture hanno un numero unico progressivo, il nome del cliente, una data, uno sconto percentuale applicato grazie ai punti del cliente (deve essere esplicitamente chiesto dal cliente) e il totale pagato (lordo meno lo sconto). Il dettaglio delle fatture vengono salvati separatamente, codice prodotto ordinato, da quale negozio, quantita e il costo lordo senza sconti.

## 3. Requisiti (dominio)
- **Negozio**: codice identificativo, responsabile , indirizzo.
- **Orari Negozio**: negozio, giorno (1-7), AMPM apertura, chiusura
- **Prodotto**: codice univoco, nome, descrizione testuale (modalità di utilizzo, allergeni per i prodotti alimentari, produttori,etc.), prezzo; ogni negozio attinge da qui per le info di base del prezzo dei prodotti poi ogni negozio può applicare le sue promozioni al prezzo di ogni singolo prodotto
## DELETE
  - **Promozioni**: codice negozio, codice prodotto, sconto percentuale, attivo/disattivo, data fine promozione
##
- **Fornitore**: identificato da **partita IVA**, con nome e indirizzo; può fornire più prodotti; 
- **Magazzino Fornitore**: per ogni coppia (fornitore, prodotto) esiste **prezzo** e **disponibilità (pezzi)**. 
- **Ordine a fornitore**: numero identificativo, fornitore, id_prid_utenteodotto, quantità e **data di consegna**. Il dettaglio dei prodotti e quantità per ordine sono memorizzate in una relazione diversa (da rivalutare)
- **Cliente**: codice fiscale, nome, cognome; (**al massimo una tessera fedeltà** ). 
- **Tessera**: codice univoco, CF collegato (univoco),  data richiesta e negozio che l’ha rilasciata, saldo punti.
- **Fattura**: codice negozio, codice univoco fattura, data acquisto, elenco prodotti con relativa quantità e prezzo di acquisto (dato da prezzo - sconto promozioni), **eventuale sconto percentuale** (da uso punti fedeltà), **totale pagato**; l'elenco prodotti viene memorizzato come elenco (record) separato in una relazione diversa con FK codice fattura

## 4. Requisiti funzionali (espliciti)
### RF-01 — Accesso e credenziali
- **Login/Logout** per cliente e manager; entrambi possono **modificare la password**.

### RF-02 — Gestione anagrafiche (manager)
- CRUD di **prodotti** (con prezzi per negozio), **negozi**, **clienti**, **fornitori**.

### RF-03 — Catalogo e acquisto (cliente)
- Visualizza prodotti disponibili, **seleziona** quelli da acquistare, **conferma** l’acquisto, **applica uno sconto** se disponibile; consulta saldo punti tessera.

### RF-04 — Rifornimento da fornitori (manager)
- Possibilità di **inserire ordini** presso fornitori per aumentare disponibilità prodotti a un particolare negozio (aggiorna automaticamente il magazzino), aumentare il magazzino fornitori. 

### RF-05 — Logica interna al DB (strutture attive)
- **Punti fedeltà**: +1 punto per ogni euro speso; saldo sempre aggiornato.
- **Sconti a soglia**: 100 pt → 5%; 200 pt → 15%; 300 pt → 30%; **tetto massimo sconto 100 €**; lo sconto è **a scelta del cliente** e si applica sul **totale della fattura**; i punti utilizzati **vengono scalati**.
- **Storico tessere**: se un negozio viene eliminato, **mantenere** in una tabella di storico le tessere da esso emesse, con data di emissione e saldo punti alla chiusura. [aggiornare alla chiusura della tessera]
- **Disponibilità fornitori**: aggiornare la disponibilità del prodotto presso il fornitore dopo un ordine.
- **Ordini “economici”**: quando serve rifornire una quantità, **scegliere automaticamente** il fornitore **più economico** tra quelli con disponibilità sufficiente.
- **Liste e viste informative**:
  - **Lista tesserati** per negozio (clienti a cui il negozio ha emesso la tessera).
  - **Storico ordini** per fornitore.
  - **Elenco clienti** con saldo punti **> 300**.

## 5. Vincoli e requisiti non funzionali
- **Tecnologie**: PostgreSQL, PL/pgSQL, PHP, HTML, JavaScript.

## 6. Casi d’uso (sintesi)
- **UC-01** Login/Logout (cliente, manager) e cambio password.
- **UC-02** Gestione anagrafiche (manager): negozi, prodotti (con prezzi per negozio), clienti, fornitori.
- **UC-03** Consultazione catalogo (cliente): ricerca/filtri e dettaglio prodotto.
- **UC-04** Acquisto (cliente): composizione carrello, conferma fattura, applicazione sconto disponibile.
- **UC-05** Rifornimento negozio (manager): richiesta rifornimento con selezione automatica del **fornitore più economico**: codice univoco, data acquisto, elenco prodotti con relativo prezzo.


## 7. Tracciabilità requisiti → componenti DB
- **Punti/Sconti/Tetti** → trigger su inserimento fattura, funzione calcolo soglia sconto, check tetto 100 €.
- **Storico tessere** → trigger ON DELETE su negozio → inserimento in tabella storico.
- **Disponibilità fornitori** → trigger su ordine a fornitore per decremento stock.
- **Fornitore più economico** → procedura/funzione che seleziona `MIN(costo)` tra fornitori con disponibilità ≥ quantità.
- **Liste/viste** → viste/materializzate per tesserati per negozio, ordini per fornitore, clienti con saldo > 300.

## 8. Ipotesi e decisioni progettuali (da motivare)
- **Arrotondamenti** fra euro spesi e punti (gestione centesimi). Si arrotonda all'aeuro più basso.
- **Sconto selezionabile**: un solo sconto per fattura (come implicato) e a discrezione del cliente applicato al totale della fattura.
- **Tempistiche**: aggiornamento punti in fase di emissione fattura; decurtazione contestuale quando si applica lo sconto. Al paamento dell'acquisot viene creata la fattura e contestualmente decurtati i punti in caso di sconto e aumentati per valore dell'ordine.
- **Ordini a fornitore**: stati (emesso, consegnato), data consegna, impatto su stock negozio vs stock fornitore.


## Schema Conettuale (ER)

## schema Logico (relazionale)

## Descrizione delle Funzioni/Trigger/viste/viste materializzate/funzionalità

## Prove di funzionamento


### Note di progettazione logica
- **Prezzi/costi storicizzati**: `RIGA_FATTURA.prezzounitario` e `RIGA_ORD_FORN.costounitario` fissano i valori al momento del documento.  
- **Integrità “tetto sconto”**: il limite 100€ e la decurtazione punti saranno implementati via **trigger/funzioni**, non come puro vincolo statico.  
- **Allineamento scorte**: trigger su **RIGA_FATTURA** (decremento `SCORTA_NEGOZIO`) e su **ORDINE_FORNITORE → consegnato** (decremento `FORNITURA.disponibilita` e incremento `SCORTA_NEGOZIO`).  
- **Vista/Materializzata** per elenco clienti con saldo > 300; vista per tesserati per negozio; vista per storico ordini per fornitore.


