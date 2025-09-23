# Aroi Laravel Integration Plugin

WordPress multisite plugin som integrerer WooCommerce med Aroi Laravel admin-systemet. Erstatter direkte database-tilkoblinger med Laravel API-kall.

## Oversikt

Dette plugin'et erstatter funksjonaliteten i de gamle `functions.php` og `adminfunctions.php` filene som brukte direkte MySQL-tilkoblinger til admin_aroi databasen. I stedet bruker plugin'et Laravel API-endepunkter for all kommunikasjon med backend-systemet.

## Installasjon

1. Last opp plugin-mappen til `/wp-content/plugins/`
2. Aktiver plugin'et fra WordPress admin
3. Gå til Settings > Aroi Laravel for å konfigurere API-tilkoblingen
4. Erstatt eksisterende `functions.php` med den nye versjonen

## Konfigurasjon

### API Innstillinger

- **Laravel API Base URL**: Standard er `https://aroiasia.no/laravel-admin/api/v1`
- Test tilkoblingen via admin-panelet

### Multisite Support

Plugin'et støtter WordPress multisite og identifiserer automatisk korrekt lokasjon basert på `get_current_blog_id()`.

## Funksjonalitet

### Erstattede Funksjoner

| Gammel Funksjon | Ny Implementasjon | Beskrivelse |
|----------------|------------------|-------------|
| `your_order_details()` | `Aroi_Order_Handler::handle_new_order()` | Sender nye bestillinger til Laravel |
| `orderIsPaid()` | `Aroi_Order_Handler::handle_payment_complete()` | Markerer ordre som betalt |
| `gettid_function()` | `Aroi_Laravel_API::get_delivery_time()` | Henter leveringstid fra Laravel |
| `getOpen()`, `getClose()` | `Aroi_Laravel_API::get_opening_hours()` | Henter åpningstider fra Laravel |
| `isItOpenNow()` | `Aroi_Laravel_API::is_open_now()` | Sjekker om lokasjon er åpen |
| Database tilkoblinger | Laravel API kall | Alle MySQL-kall erstattet med API |

### Laravel API Endepunkter

Plugin'et bruker følgende Laravel API endepunkter:

- `GET /api/v1/wordpress/location/{siteId}` - Lokasjonsinformasjon
- `GET /api/v1/wordpress/location/{siteId}/delivery-time` - Leveringstid
- `GET /api/v1/wordpress/location/{siteId}/opening-hours` - Åpningstider
- `GET /api/v1/wordpress/location/{siteId}/is-open` - Status (åpen/stengt)
- `POST /api/v1/orders` - Opprett bestilling
- `POST /api/v1/orders/mark-paid` - Marker som betalt

### WooCommerce Integration

- Automatisk sending av nye bestillinger til Laravel
- Hentetidspunkt basert på åpningstider og leveringstid
- Åpningstider-visning på checkout
- Email og admin integrasjon for hentetidspunkter

### Shortcodes

- `[aroi_opening_hours site="7" format="simple"]` - Vis åpningstider
- `[aroi_delivery_time site="7"]` - Vis leveringstid

## Migrasjon fra Gammel System

### Steg 1: Installer Plugin

1. Last opp plugin-mappen
2. Aktiver plugin'et
3. Konfigurer API URL

### Steg 2: Erstatt functions.php

Erstatt innholdet i `functions.php` med innholdet fra `aroi-new-functions.php`.

### Steg 3: Fjern adminfunctions.php

Filen `adminfunctions.php` er ikke lenger nødvendig og kan fjernes.

### Steg 4: Test Funksjonalitet

- Test ordre-opprettelse
- Test hentetidspunkt-visning
- Test åpningstider-shortcodes
- Verifiser API-tilkobling i admin

## Site ID Mapping

Plugin'et håndterer automatisk mapping mellom WordPress site ID og Aroi lokasjoner:

| Site ID | Lokasjon | Lisens |
|---------|----------|---------|
| 7 | Namsos | 6714 |
| 4 | Lade | 12381 |
| 6 | Moan | 5203 |
| 5 | Gramyra | 6715 |
| 10 | Frosta | 14780 |
| 11 | Hell | N/A |
| 13 | Steinkjer | 30221 |
| 15 | Malvik | 14946 |

## Backup og Sikkerhet

### Hard-kodede Verdier Fjernet

- Database credentials er fjernet fra WordPress
- Lisens-mapping er flyttet til Laravel
- API-tilgang er konfigurert sentralt

### Feilhåndtering

Plugin'et inneholder omfattende feilhåndtering:
- Fallback til lokale verdier hvis API ikke er tilgjengelig
- Logging av API-feil
- Graceful degradation av funksjonalitet

## Utvikling og Debugging

### Debug Informasjon

Admin-panelet viser:
- Gjeldende site ID
- API base URL
- WooCommerce status
- API tilkoblingstest

### Logging

API-feil logges til WordPress error log. Aktivér WP_DEBUG for detaljert logging.

### Testing

Bruk "Test Connection" knappen i admin-panelet for å verifisere API-tilkobling.

## Support og Vedlikehold

### Kompatibilitet

- WordPress 5.0+
- WooCommerce 5.0+
- PHP 7.4+
- Laravel admin system

### Oppdateringer

Plugin'et er designet for å være bakoverkompatibelt med eksisterende WooCommerce-konfigurasjon.

## Tekniske Detaljer

### Klasse-struktur

- `AroiLaravelIntegration` - Hovedklasse
- `Aroi_Laravel_API` - API kommunikasjon
- `Aroi_Order_Handler` - Bestillingslogikk
- `Aroi_Checkout_Manager` - Checkout-funksjonalitet
- `Aroi_Admin_Interface` - Admin-grensesnitt
- `Aroi_Site_Manager` - Site-håndtering

### Hooks og Filters

Plugin'et bruker standard WordPress og WooCommerce hooks:
- `woocommerce_new_order`
- `woocommerce_payment_complete`
- `woocommerce_before_order_notes`
- `woocommerce_checkout_update_order_meta`

## Fremtidige Forbedringer

- Caching av API-svar for bedre ytelse
- Offline-modus med lokale fallback-verdier
- Utvidet admin-grensesnitt for lokasjonshåndtering
- Integrasjon med WordPress bruker-roller basert på lokasjoner