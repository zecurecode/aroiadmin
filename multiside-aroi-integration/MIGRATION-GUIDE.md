# Migration Guide - Fra gammel functions.php til MultiSide Aroi Plugin

## 🎯 Oversikt

Denne guiden hjelper deg med å migrere fra det gamle systemet (functions.php) til den nye **MultiSide Aroi Integration** pluginen.

---

## ⚠️ Viktig: Før du starter

### Backup først!
```bash
# Backup gammel functions.php
cp functions.php functions.php.backup

# Backup database
mysqldump -u adminaroi -p admin_aroi > backup_admin_aroi.sql
```

### Test på staging først!
❌ **IKKE test dette direkte i produksjon**
✅ Test på staging/dev-miljø først

---

## 📋 Steg-for-steg migrering

### Steg 1: Installer pluginen

1. Last opp `multiside-aroi-integration` til `/wp-content/plugins/`
2. **IKKE AKTIVER ENNÅ!**

### Steg 2: Verifiser database

Sjekk at disse tabellene eksisterer:

```sql
-- Sjekk _apningstid tabell
SELECT AvdID, Navn FROM _apningstid;

-- For Malvik, sjekk at det finnes:
SELECT AvdID, Navn, ManStart, ManStopp FROM _apningstid WHERE AvdID = 15;

-- Hvis Malvik mangler, legg til:
-- (Dette må du gjøre hvis du ser "No opening hours found" i loggen)
```

### Steg 3: Aktiver WordPress debug logging

I `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Steg 4: Erstatt functions.php

```bash
# Backup gammel fil
mv functions.php functions.php.OLD

# Kopier ny versjon
cp /path/to/MIGRATION-functions.php functions.php
```

### Steg 5: Aktiver plugin

1. Gå til WordPress Admin → Plugins
2. Aktiver **"MultiSide Aroi Integration"**
3. Du vil se varsler om konfigurasjon

### Steg 6: Verifiser konfigurasjon

1. Gå til **Admin → Aroi Config**
2. Sjekk at:
   - ✅ Site ID er detektert korrekt
   - ✅ PCKasse License vises
   - ✅ Location name er korrekt
   - ✅ SMS credentials er konfigurert

### Steg 7: Test checkout-siden

1. Gå til checkout med en vare i handlekurven
2. **Forventet resultat:**
   - ✅ "Når vil du hente bestillingen?" med tidsluker
   - ✅ Åpningstider vises korrekt
   - ❌ Ingen dupliserte felter

3. **Hvis du ser duplikater:**
   - Den gamle functions.php er fortsatt aktiv
   - Sørg for at du har erstattet filen korrekt

### Steg 8: Test ordre-opprettelse

1. **Legg inn en testordre (uten å betale)**
2. Sjekk database:
   ```sql
   SELECT * FROM orders WHERE ordreid = [DIN_TEST_ORDRE_ID];
   ```
3. **Forventet:**
   - paid = 0
   - curl = 0
   - sms = 0

### Steg 9: Test betaling-flyt

1. **Marker testordre som betalt** (eller betal med testbetaling)
2. Sjekk `wp-content/debug.log` for:
   ```
   MultiSide Aroi: Payment complete triggered for order X
   MultiSide Aroi: Order X marked as PAID
   MultiSide Aroi: PCKasse send SUCCESS - Order: X - HTTP: 200
   MultiSide Aroi: SMS sent SUCCESS - Order: X - Phone: +47XXXXXXXX
   ```

3. Sjekk database igjen:
   ```sql
   SELECT paid, curl, sms FROM orders WHERE ordreid = [DIN_TEST_ORDRE_ID];
   ```
4. **Forventet:**
   - paid = 1
   - curl = 200 eller 201
   - sms = 1

### Steg 10: Verifiser SMS ble sendt

1. Sjekk testtelefonnummer for SMS
2. **Forventet melding:**
   ```
   Takk for din ordre. Vi vil gjøre din bestilling klar så fort vi kan.
   Vi sender deg en ny SMS når maten er klar til henting.
   Ditt referansenummer er [ORDER_ID]
   ```
3. **Avsender skal være:** "Aroi [Lokasjonsnavn]" (f.eks. "Aroi Malvik")

---

## 🔍 Feilsøking

### Problem 1: "Ingen tidsluker i dropdown"

**Årsak:** Åpningstider ikke funnet for site

**Løsning:**
1. Sjekk debug.log:
   ```
   MultiSide Aroi: No opening hours found for site_id X
   MultiSide Aroi: Available in _apningstid: AvdID=1 (Namsos), ...
   ```
2. Se hvilken AvdID som trengs
3. Legg til manglende entry i `_apningstid` tabell

### Problem 2: "Duplikate pickup time-felter"

**Årsak:** Både gammel functions.php OG plugin er aktive

**Løsning:**
1. Bekreft at du har erstattet functions.php
2. Sjekk at functions.php IKKE inneholder:
   ```php
   add_action('woocommerce_before_order_notes', 'action_woocommerce_before_order_notes'
   ```
3. Clear WordPress cache
4. Hard refresh browser (Ctrl+F5)

### Problem 3: "Site ID kunne ikke detekteres"

**Årsak:** Plugin kan ikke matche current site med database

**Løsning for Multisite:**
```php
// I wp-config.php eller mu-plugin:
add_filter('multiside_aroi_site_id', function($site_id) {
    $blog_id = get_current_blog_id();
    $mapping = array(
        1 => 7,   // Blog 1 → Namsos
        2 => 4,   // Blog 2 → Lade
        3 => 15,  // Blog 3 → Malvik
        // osv...
    );
    return $mapping[$blog_id] ?? $site_id;
});
```

**Løsning for Single Site:**
```php
// I wp-config.php:
define('AROI_SITE_ID', 15); // For Malvik
```

### Problem 4: "PCKasse-lisens mangler"

**Årsak:** Ingen license i database for site

**Løsning:**
```sql
-- Sjekk om lisens finnes
SELECT siteid, license FROM users WHERE siteid = 15;

-- Hvis ikke, legg til
UPDATE users SET license = 14946 WHERE siteid = 15;
-- Eller
INSERT INTO users (username, siteid, license) VALUES ('malvik', 15, 14946);
```

### Problem 5: "SMS sendes ikke"

**Sjekk debug.log for:**
```
MultiSide Aroi: SMS send failed - HTTP XXX
```

**Løsning:**
1. Verifiser SMS credentials i database:
   ```sql
   SELECT * FROM settings WHERE setting_key LIKE 'sms_%';
   ```
2. Test Teletopia API manuelt:
   ```bash
   curl "https://api1.teletopiasms.no/gateway/v3/plain?username=XXX&password=XXX&recipient=+4712345678&text=Test"
   ```

---

## 📊 Hva er forskjellig?

| Funksjonalitet | Gammel (functions.php) | Ny (Plugin) |
|----------------|------------------------|-------------|
| **Site ID** | Hardkodet per blog | Automatisk detektert |
| **PCKasse License** | Hardkodet i switch | Hentes fra database |
| **SMS Avsender** | "AroiAsia" | "Aroi [Lokasjon]" |
| **Åpningstider** | Hardkodet mapping | Direkte fra _apningstid |
| **Ordre-hooks** | functions.php | Plugin-klasser |
| **Debug** | Ingen logging | Omfattende logging |
| **Admin UI** | Ingen | Aroi Config side |

---

## ✅ Sjekkliste for fullført migrering

- [ ] Plugin aktivert
- [ ] Gammel functions.php erstattet med ny versjon
- [ ] Checkout viser pickup time uten duplikater
- [ ] Admin → Aroi Config viser korrekt konfigurasjon
- [ ] Testordre opprettet i database (paid=0)
- [ ] Testordre betalt, markert som paid=1
- [ ] PCKasse mottok ordre (curl=200/201)
- [ ] SMS sendt til kunde (sms=1)
- [ ] SMS har riktig avsender-navn
- [ ] Debug logging aktivert og fungerer
- [ ] Alle sites testet (hvis multisite)

---

## 🔄 Rollback-plan

Hvis noe går galt:

### 1. Deaktiver plugin
```
WordPress Admin → Plugins → Deactivate "MultiSide Aroi Integration"
```

### 2. Gjenopprett gammel functions.php
```bash
cp functions.php.OLD functions.php
```

### 3. Gjenopprett database (hvis nødvendig)
```bash
mysql -u adminaroi -p admin_aroi < backup_admin_aroi.sql
```

### 4. Test at alt fungerer igjen

---

## 📞 Support

Hvis du støter på problemer:

1. **Sjekk debug.log** først: `wp-content/debug.log`
2. **Verifiser database-tilkobling**: Gå til Aroi Config
3. **Sjekk konfigurasjon**: Admin → Aroi Config
4. **Send debug log** til utvikler hvis problemet vedvarer

---

## 🎉 Fullført migrering

Når alle sjekklister er ✅:

1. **Deaktiver debug logging** (produksjon):
   ```php
   define('WP_DEBUG', false);
   ```

2. **Fjern gammel functions.php.OLD**:
   ```bash
   rm functions.php.OLD
   ```

3. **Dokumenter konfigurasjon**:
   - Hvilken Site ID brukes
   - Hvilken PCKasse-lisens
   - AvdID i _apningstid

4. **Monitor i produksjon**:
   - Sjekk første reelle ordre
   - Verifiser SMS mottas
   - Verifiser PCKasse mottar ordre

---

**Lykke til med migreringen! 🚀**
