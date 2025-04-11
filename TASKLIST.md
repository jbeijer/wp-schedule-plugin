# Tasklista: WP Schedule Plugin

Denna fil bryter ner planen från `PLAN-FÖR_ANPASNING.md` till konkreta uppgifter.

## Fas 0: Förbättrad Grundstruktur & Miljö

### PHP Setup (plugin.php, inc/, languages/)
- [x] **plugin.php:** Verifiera/lägg till `Text Domain: wp-schedule-plugin` i header.
- [x] **plugin.php:** Verifiera/lägg till `Domain Path: /languages` i header.
- [x] **plugin.php:** Definiera konstant `WP_SCHEDULE_PLUGIN_PATH`.
- [x] **plugin.php:** Definiera konstant `WP_SCHEDULE_PLUGIN_URL`.
- [x] **plugin.php:** Implementera `register_activation_hook`.
- [x] **plugin.php:** Implementera `register_deactivation_hook`.
- [x] **plugin.php:** Skapa funktion `wp_schedule_plugin_activate()`.
    - [ ] Inuti `wp_schedule_plugin_activate()`: Lägg till roll `schema_user` (med cap `access_schemaplugin`).
    - [x] Inuti `wp_schedule_plugin_activate()`: Lägg till cap `access_schemaplugin` till roll 'administrator'.
    - [ ] Inuti `wp_schedule_plugin_activate()`: Anropa databastabellskapande metod (från Fas 1). <!-- Placeholder exists -->
    - [x] Inuti `wp_schedule_plugin_activate()`: Sätt option-flagga `wp_schedule_plugin_activated`.
    - [x] Inuti `wp_schedule_plugin_activate()`: Lägg till `flush_rewrite_rules()`.
- [x] **plugin.php:** Skapa funktion `wp_schedule_plugin_deactivate()` (initialt tom).
- [x] **plugin.php:** Skapa funktion `wp_schedule_plugin_load_textdomain()` (integrated into initialize_plugin).
    - [x] Inuti `initialize_plugin()`: Anropa `load_plugin_textdomain`.
- [x] **plugin.php:** Hooka `initialize_plugin` till `plugins_loaded`.
- [x] Skapa katalog `languages/`.

### JavaScript/Svelte Setup (app/src/, package.json, vite.config.js)
- [x] **package.json:** Lägg till `svelte-i18n` som dev dependency.
- [x] **package.json:** Lägg till `@wordpress/api-fetch` som dev dependency.
- [x] Kör `npm install --legacy-peer-deps`.
- [x] Skapa katalog `app/src/locales/`.
- [x] Skapa fil `app/src/locales/sv.json` med initiala strängar.
- [x] Skapa fil `app/src/i18n.js`.
    - [x] Inuti `i18n.js`: Importera från `svelte-i18n`.
    - [x] Inuti `i18n.js`: Importera `sv.json`.
    - [x] Inuti `i18n.js`: Registrera 'sv' locale.
    - [x] Inuti `i18n.js`: Definiera och exportera `setupI18n` funktion (returns promise).
    - [x] Inuti `i18n.js`: Exportera `$t` store och `isLoading`.
- [x] **app/src/admin.js:** Importera `setupI18n`.
- [x] **app/src/admin.js:** Anropa `setupI18n` med locale från `window.wpApiSettings` (await promise).
- [x] **vite.config.js:** Säkerställ att `build.rollupOptions.output.format: 'iife'` är satt.

### Enqueue & API Setup (inc/admin.php, inc/class-*.php, plugin.php)
- [x] **inc/admin.php:** Modifiera `enqueue_admin_assets`.
    - [x] Inuti `enqueue_admin_assets`: Uppdatera `wp_localize_script` att använda `wpApiSettings` med `root`, `nonce`, `userLocale`.
- [x] Skapa fil `inc/class-database.php`.
    - [x] Inuti `class-database.php`: Definiera klass `Database` (namespace `JohanBeijer\WPSchedule`).
    - [x] Inuti `class-database.php`: Lägg till `__construct`.
    - [x] Inuti `class-database.php`: Lägg till `get_table_name`.
    - [x] Inuti `class-database.php`: Lägg till stub för `create_or_update_tables`.
- [x] Skapa fil `inc/class-apihandlers.php`.
    - [x] Inuti `class-apihandlers.php`: Definiera klass `ApiHandlers` (namespace `JohanBeijer\WPSchedule`).
    - [x] Inuti `class-apihandlers.php`: Lägg till `__construct(Database $db)`.
    - [x] Inuti `class-apihandlers.php`: Lägg till `register_routes`.
    - [x] Inuti `class-apihandlers.php`: Lägg till `permission_check_callback`.
    - [x] Inuti `class-apihandlers.php`: Lägg till `api_response`.
    - [x] Inuti `class-apihandlers.php`: Lägg till `verify_nonce`.
    - [x] Inuti `class-apihandlers.php`: Implementera test-endpoint `GET wp-schedule-plugin/v1/test` i `register_routes`.
- [x] **plugin.php:** Kräv `inc/class-database.php`.
- [x] **plugin.php:** Kräv `inc/class-apihandlers.php`.
- [x] **plugin.php:** Instansiera `Database`.
- [x] **plugin.php:** Instansiera `ApiHandlers`.
- [x] **plugin.php:** Hooka `register_api_routes` (som anropar `$api_handlers->register_routes()`) till `rest_api_init`.

### Svelte App Update & i18n Workflow (app/src/AdminApp.svelte)
- [x] **app/src/AdminApp.svelte:** Importera `$t` från `./i18n.js`.
- [x] **app/src/AdminApp.svelte:** Ersätt statisk text med `$t('nyckel')`.
- [x] **app/src/AdminApp.svelte:** Importera konfigurerad `apiFetch` från `./api.js`.
- [x] **app/src/AdminApp.svelte:** Modifiera `onMount` att anropa `GET /test` endpoint via `apiFetch`.
- [x] **app/src/AdminApp.svelte:** Modifiera rendering för att vänta på i18n initiering.
- [ ] **Plan:** Kör `npm run build`.
- [x] **Plan:** Verifiera admin-sida (laddning, API-anrop, svensk text).
- [ ] **Plan:** Generera `.pot`-fil (`wp i18n make-pot ...`).
- [ ] **Plan:** Skapa `sv_SE.po` och `.mo`-filer.

## Fas 1: Organisationer & Roller

### Databas (inc/class-database.php)
- [ ] **class-database.php:** Implementera `create_or_update_tables` med SQL för `organizations` och `organization_members`.
- [ ] **class-database.php:** Implementera `create_organization`.
- [ ] **class-database.php:** Implementera `get_organization`.
- [ ] **class-database.php:** Implementera `get_organizations`.
- [ ] **class-database.php:** Implementera `update_organization`.
- [ ] **class-database.php:** Implementera `add_member`.
- [ ] **class-database.php:** Implementera `get_member`.
- [ ] **class-database.php:** Implementera `get_organization_members`.
- [ ] **class-database.php:** Implementera `update_member`.
- [ ] **class-database.php:** Implementera `remove_member`.
- [ ] **class-database.php:** Implementera `get_user_internal_role`.
- [ ] **class-database.php:** Implementera `get_user_memberships`.

### API (inc/class-apihandlers.php)
- [ ] **class-apihandlers.php:** Implementera `get_validated_org_id` hjälpfunktion.
- [ ] **class-apihandlers.php:** Implementera endpoint `POST /organizations`.
- [ ] **class-apihandlers.php:** Implementera endpoint `GET /organizations`.
- [ ] **class-apihandlers.php:** Implementera endpoint `GET /organizations/{org_id}`.
- [ ] **class-apihandlers.php:** Implementera endpoint `PUT /organizations/{org_id}`.
- [ ] **class-apihandlers.php:** Implementera endpoint `DELETE /organizations/{org_id}`.

### Frontend (app/src/)
- [ ] Skapa fil `app/src/stores.js`.
    - [ ] Inuti `stores.js`: Definiera store för `selectedOrgId`.
- [ ] Skapa katalog `app/src/components/`.
- [ ] Skapa fil `app/src/components/OrganizationList.svelte`.
    - [ ] Inuti `OrganizationList.svelte`: Hämta och visa organisationer via `apiFetch`.
    - [ ] Inuti `OrganizationList.svelte`: Hantera val av organisation (uppdatera store).
- [ ] Skapa fil `app/src/components/OrganizationForm.svelte`.
    - [ ] Inuti `OrganizationForm.svelte`: Skapa formulär för namn.
    - [ ] Inuti `OrganizationForm.svelte`: Hantera create/edit via `apiFetch`.
- [ ] **app/src/AdminApp.svelte:** Integrera `OrganizationList` och `OrganizationForm`.
- [ ] **Alla Svelte-komponenter:** Säkerställ att all UI-text använder `$t()`.

## Fas 2: Resurser (Resources)

### Databas (inc/class-database.php)
- [ ] **class-database.php:** Modifiera `create_or_update_tables` för att lägga till `resources`-tabellen.
- [ ] **class-database.php:** Implementera `create_resource`.
- [ ] **class-database.php:** Implementera `get_resource`.
- [ ] **class-database.php:** Implementera `get_resources` (med filtrering).
- [ ] **class-database.php:** Implementera `update_resource`.
- [ ] **class-database.php:** Implementera `delete_resource`.
- [ ] **class-database.php:** *(Valfri)* Implementera `get_organization_resources`.

### API (inc/class-apihandlers.php)
- [ ] **class-apihandlers.php:** Förbättra `GET /organizations/{org_id}` endpoint att returnera `current_user_role`.
- [ ] **class-apihandlers.php:** Implementera endpoint `POST /resources`.
- [ ] **class-apihandlers.php:** Implementera endpoint `GET /resources`.
- [ ] **class-apihandlers.php:** Implementera endpoint `GET /resources/{resource_id}`.
- [ ] **class-apihandlers.php:** Implementera endpoint `PUT /resources/{resource_id}`.
- [ ] **class-apihandlers.php:** Implementera endpoint `DELETE /resources/{resource_id}`.

### Frontend (app/src/)
- [ ] **app/src/stores.js:** Lägg till store `selectedOrgUserRole`.
- [ ] **app/src/stores.js:** Uppdatera `selectedOrgUserRole` när organisation väljs.
- [ ] Skapa fil `app/src/components/ResourceList.svelte`.
    - [ ] Inuti `ResourceList.svelte`: Hämta och visa resurser via `apiFetch`.
    - [ ] Inuti `ResourceList.svelte`: Visa/dölj knappar baserat på `selectedOrgUserRole`.
- [ ] Skapa fil `app/src/components/ResourceForm.svelte`.
    - [ ] Inuti `ResourceForm.svelte`: Skapa formulär (Name, Desc, Type, Capacity, Active).
    - [ ] Inuti `ResourceForm.svelte`: Hantera create/edit via `apiFetch`.
- [ ] **app/src/AdminApp.svelte:** Integrera `ResourceList` och `ResourceForm` (t.ex. i ny tab/sektion).
- [ ] **app/src/locales/sv.json:** Lägg till svenska översättningar för resurs-relaterad text.
- [ ] **Alla Svelte-komponenter (Fas 2):** Säkerställ att all UI-text använder `$t()`.

## Fas 3: Skift (Shifts)

### Databas (inc/class-database.php)
- [ ] **class-database.php:** Modifiera `create_or_update_tables` för att lägga till `shifts`-tabellen.
- [ ] **class-database.php:** Implementera `create_shift`.
- [ ] **class-database.php:** Implementera `get_shift`.
- [ ] **class-database.php:** Implementera `get_shifts` (med filtrering).
- [ ] **class-database.php:** Implementera `update_shift`.
- [ ] **class-database.php:** Implementera `delete_shift`.

### API (inc/class-apihandlers.php)
- [ ] **class-apihandlers.php:** Implementera endpoint `POST /shifts`.
- [ ] **class-apihandlers.php:** Implementera endpoint `GET /shifts`.
- [ ] **class-apihandlers.php:** Implementera endpoint `GET /shifts/{shift_id}`.
- [ ] **class-apihandlers.php:** Implementera endpoint `PUT /shifts/{shift_id}`.
- [ ] **class-apihandlers.php:** Implementera endpoint `DELETE /shifts/{shift_id}`.

### Frontend (app/src/)
- [ ] **package.json:** Lägg till kalenderbibliotek (t.ex. `fullcalendar-svelte`) som dependency. Kör `npm install --legacy-peer-deps`.
- [ ] Skapa fil `app/src/components/ShiftCalendarView.svelte`.
    - [ ] Inuti `ShiftCalendarView.svelte`: Integrera kalenderbibliotek.
    - [ ] Inuti `ShiftCalendarView.svelte`: Hämta och visa skift via `apiFetch`.
    - [ ] Inuti `ShiftCalendarView.svelte`: Hantera interaktioner (klick).
- [ ] Skapa fil `app/src/components/ShiftForm.svelte`.
    - [ ] Inuti `ShiftForm.svelte`: Skapa formulär (tider, titel, anteckningar, användare, resurs, status).
    - [ ] Inuti `ShiftForm.svelte`: Hämta användare/resurser för dropdowns via `apiFetch`.
    - [ ] Inuti `ShiftForm.svelte`: Hantera create/edit via `apiFetch`.
- [ ] Skapa fil `app/src/components/ShiftDetailModal.svelte`.
    - [ ] Inuti `ShiftDetailModal.svelte`: Visa skiftdetaljer.
    - [ ] Inuti `ShiftDetailModal.svelte`: Tillåt redigera/ta bort baserat på roll.
- [ ] **app/src/AdminApp.svelte:** Integrera `ShiftCalendarView`, `ShiftForm`, `ShiftDetailModal` i en huvudvy "Schema".
- [ ] **app/src/locales/sv.json:** Lägg till svenska översättningar för skift-relaterad text.
- [ ] **Alla Svelte-komponenter (Fas 3):** Säkerställ att all UI-text använder `$t()`.

## Fas 4: Medlemmar (Member Management UI)

### API (inc/class-apihandlers.php)
- [ ] **class-apihandlers.php:** Implementera handler `get_organization_members_handler` för `GET /organization_members`.
- [ ] **class-apihandlers.php:** Implementera handler `add_organization_member_handler` för `POST /organization_members`.
- [ ] **class-apihandlers.php:** Implementera handler `update_organization_member_handler` för `PUT /organization_members/{user_id}`.
- [ ] **class-apihandlers.php:** Implementera handler `remove_organization_member_handler` för `DELETE /organization_members/{user_id}`.
- [ ] **class-apihandlers.php:** *(Valfri)* Implementera endpoint `GET /users` för att söka WP-användare.

### Frontend (app/src/)
- [ ] Skapa fil `app/src/components/MemberList.svelte`.
    - [ ] Inuti `MemberList.svelte`: Hämta och visa medlemmar via `apiFetch`.
    - [ ] Inuti `MemberList.svelte`: Hantera paginering/sökning (om API stödjer).
    - [ ] Inuti `MemberList.svelte`: Visa/dölj knappar baserat på `selectedOrgUserRole`.
- [ ] Skapa fil `app/src/components/MemberForm.svelte`.
    - [ ] Inuti `MemberForm.svelte`: Skapa formulär (användarsökning, roll, anst.nr).
    - [ ] Inuti `MemberForm.svelte`: Hantera create/edit via `apiFetch`.
- [ ] **app/src/AdminApp.svelte:** Integrera `MemberList` och `MemberForm` (t.ex. i ny tab/sektion "Medlemmar").
- [ ] **app/src/locales/sv.json:** Lägg till svenska översättningar för medlemshantering.
- [ ] **Alla Svelte-komponenter (Fas 4):** Säkerställ att all UI-text använder `$t()`.

## Fas 5: Hierarki UI (Organization Hierarchy UI)

### API (inc/class-apihandlers.php)
- [ ] **class-apihandlers.php:** Modifiera `GET /organizations` att inkludera `parent_org_id`.
- [ ] **class-apihandlers.php:** Modifiera `PUT /organizations/{org_id}`:
    - [ ] Tillåt `parent_org_id` i request body.
    - [ ] Implementera validering för cykliska beroenden.
    - [ ] Begränsa ändring av `parent_org_id` till `manage_options`.

### Databas (inc/class-database.php)
- [ ] **class-database.php:** Implementera `get_organization_descendants`.
- [ ] **class-database.php:** Säkerställ att `update_organization` hanterar `parent_org_id`.

### Frontend (app/src/)
- [ ] **package.json:** Lägg till tree view-bibliotek (t.ex. `svelte-tree-view`) som dependency. Kör `npm install --legacy-peer-deps`.
- [ ] **package.json:** *(Valfri)* Lägg till DND-bibliotek (t.ex. `svelte-dnd-action`). Kör `npm install --legacy-peer-deps`.
- [ ] Modifiera `app/src/components/OrganizationList.svelte`:
    - [ ] Hämta platt lista inkl. `parent_org_id`.
    - [ ] Implementera klient-logik för att bygga trädstruktur.
    - [ ] Visa organisationer som trädvy.
    - [ ] *(Valfri)* Implementera dra-och-släpp för att ändra förälder.
- [ ] Modifiera `app/src/components/OrganizationForm.svelte`:
    - [ ] Lägg till "Föräldraorganisation"-dropdown.
    - [ ] Filtrera bort aktuell org och descendants från valbara föräldrar.
    - [ ] Visa/aktivera fältet baserat på behörighet (`manage_options`).
- [ ] **app/src/locales/sv.json:** Lägg till översättningar för hierarki-relaterad text.
- [ ] **Alla Svelte-komponenter (Fas 5):** Säkerställ att all UI-text använder `$t()`.

## Fas 6: Polish, Testning, Dokumentation

### UI/UX Polish (Svelte - `app/src/`)
- [ ] **Konsistensgranskning:** Gå igenom alla admin-vyer (layout, stilar, formulär, terminologi).
- [ ] **Laddnings- & Fel-tillstånd:** Implementera tydliga visuella indikatorer och felmeddelanden.
- [ ] **Responsivitet:** Testa och justera admin-gränssnittet för olika skärmstorlekar.
- [ ] **Tillgänglighet (a11y):** Utför grundläggande kontroller (tangentbord, kontrast, ARIA, labels).
- [ ] **Indatavalidering:** Lägg till klient-sidans validering i formulär.

### Testning
- [ ] **PHPUnit Setup:** Sätt upp testmiljö (`wp-cli scaffold plugin-tests`).
- [ ] **PHP Unit Tests:** Skriv tester för `Database`-metoder (mocka `$wpdb`).
- [ ] **PHP Unit Tests:** Skriv tester för `ApiHandlers`-logik (mocka `Database`).
- [ ] **PHP Integration Tests:** Skriv tester för REST API endpoints (`WP_Test_REST_Controller_Testcase`).
- [ ] **JS/Svelte Test Setup:** Sätt upp Vitest/Jest med Svelte Testing Library.
- [ ] **JS/Svelte Unit Tests:** Skriv tester för hjälpfunktioner/stores.
- [ ] **JS/Svelte Component Tests:** Skriv tester för nyckelkomponenter (listor, formulär, kalender).

### Dokumentation
- [ ] **README.md:** Utöka med detaljerad setup, funktioner, användning.
- [ ] **Inline Code Comments:** Granska och lägg till PHPDoc/JSDoc.
- [ ] **Användarguide (`docs/`):** Skapa enkel guide för admin/scheduler.
- [ ] **Utvecklardokumentation (`docs/`):** Dokumentera API, DB-schema, hooks/filter.

### i18n Finalisering
- [ ] Regenerera `.pot`-fil (`wp i18n make-pot ...`).
- [ ] Säkerställ att `sv_SE.po` är fullständigt översatt.
- [ ] Kompilera `sv_SE.mo`.
- [ ] Granska alla användarvisade strängar (PHP & Svelte).

### Kodkvalitet & Standarder
- [ ] **PHPCS:** Konfigurera och kör med WP-standarder. Åtgärda fel.
- [ ] **ESLint/Prettier:** Konfigurera och kör för JS/Svelte. Åtgärda fel.
- [ ] **Manuell Kodgranskning:** Fokus på säkerhet, prestanda, underhållbarhet.