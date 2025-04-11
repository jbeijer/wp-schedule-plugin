# Plan för Anpassning: WP Schedule Plugin (Fas 0 & 1)

Detta dokument beskriver den anpassade planen för att utveckla WP Schedule Plugin, baserat på den befintliga filstrukturen (`inc/`, `app/src/`) och namngivningen (`wp-schedule-plugin`, `JohanBeijer\WPSchedule`), samtidigt som funktionerna från den detaljerade planen (REST API, databastabeller, i18n etc.) integreras.

## Fas 0: Förbättrad Grundstruktur & Miljö

1.  [COMPLETED] **Plugin Header & Konstanter (plugin.php):**
    *   Verifiera/lägg till `Text Domain: wp-schedule-plugin` och `Domain Path: /languages` i header.
    *   Definiera konstanter `WP_SCHEDULE_PLUGIN_PATH` och `WP_SCHEDULE_PLUGIN_URL`.

2.  [COMPLETED] **Aktiverings-/Avaktiverings-hooks (plugin.php):**
    *   Implementera `register_activation_hook` och `register_deactivation_hook`.
    *   Skapa `wp_schedule_plugin_activate()`:
        *   Lägg till WP-roll `schema_user` (capability `access_schemaplugin`).
        *   Lägg till capability `access_schemaplugin` till 'administrator'.
        *   Anropa databastabellskapande metod (från Fas 1).
        *   Sätt en option-flagga (`wp_schedule_plugin_activated`).
    *   Skapa `wp_schedule_plugin_deactivate()` (initialt tom).

3.  [COMPLETED] **Textdomänladdning (plugin.php & languages/):**
    *   Skapa `languages/` katalog.
    *   Skapa funktion `wp_schedule_plugin_load_textdomain()`.
    *   Anropa `load_plugin_textdomain('wp-schedule-plugin', ..., .../languages/)`.
    *   Hooka till `plugins_loaded`.

4.  [COMPLETED] **Admin App i18n & API Setup (Svelte - app/src/ & package.json):**
    *   Lägg till `svelte-i18n` och `@wordpress/api-fetch` i `package.json` (devDependencies). Planera att köra `npm install --legacy-peer-deps` igen.
    *   Skapa `app/src/locales/` och `app/src/locales/sv.json` med teststrängar.
    *   Skapa `app/src/i18n.js`: Konfigurera `svelte-i18n` (register, init, etc.). Exportera `setupI18n` och `$t`.
    *   Modifiera `app/src/admin.js`: Importera och anropa `setupI18n` med locale från `window.wpScheduleAdminData`.

5.  [COMPLETED] **Vite Konfiguration (vite.config.js):**
    *   Säkerställ att `build.rollupOptions.output.format: 'iife'` är satt.

6.  [COMPLETED] **Enqueue & Localize (inc/admin.php):**
    *   I `enqueue_admin_assets()`:
        *   Fortsätt använda `\Kucrut\Vite\enqueue_asset`.
        *   Uppdatera `wp_localize_script` (`wpScheduleAdminData`) att inkludera:
            *   `apiUrl: esc_url_raw(get_rest_url(null, 'wp-schedule-plugin/v1/'))`
            *   `nonce: wp_create_nonce('wp_rest')`
            *   `userLocale: get_user_locale()`
            *   `i18n: []` (initialt tom).

7.  [COMPLETED] **API Setup (PHP - inc/ & plugin.php):**
    *   Skapa `inc/class-database.php` (Klass `Database`, namespace `JohanBeijer\WPSchedule`). Metoder: `__construct`, `get_table_name`, `create_or_update_tables`.
    *   Skapa `inc/class-apihandlers.php` (Klass `ApiHandlers`, namespace `JohanBeijer\WPSchedule`).
        *   Metoder: `__construct(Database $db)`, `register_routes`, `permission_check_callback`, `api_response`, `verify_nonce`.
        *   Implementera test-endpoint `GET wp-schedule-plugin/v1/test`.
    *   I `plugin.php` (`initialize_plugin`): Kräv klassfiler, instansiera `Database` & `ApiHandlers`, hooka `register_routes` till `rest_api_init`.

8.  [COMPLETED] **Initial Svelte App Uppdatering (app/src/AdminApp.svelte):**
    *   Importera `$t` från `../i18n.js`. Ersätt statisk text med `$t('nyckel')`.
    *   Importera `apiFetch` från `@wordpress/api-fetch`.
    *   Modifiera `onMount` att anropa `GET /test`-endpointen via `apiFetch({ path: 'wp-schedule-plugin/v1/test' })` och visa resultat/status. `@wordpress/api-fetch` hanterar nonce och API-prefix automatiskt.

9.  [COMPLETED] **i18n Workflow Start (Plan):**
    *   Kör `npm run build`.
    *   Besök admin-sida, verifiera laddning, API-anrop, svensk text.
    *   Generera `.pot`-fil (`wp i18n make-pot ...`).
    *   Skapa `sv_SE.po` och `.mo`-filer.

## Fas 1: Organisationer & Roller

1.  [COMPLETED] **Databasschema (inc/class-database.php):**
    *   Implementera `create_or_update_tables()` med SQL för `organizations` och `organization_members` (använd `get_table_name`). Använd `dbDelta()`.
    *   Säkerställ att `wp_schedule_plugin_activate()` anropar denna metod.

2.  [COMPLETED] **Databasmetoder (inc/class-database.php):**
    *   Implementera CRUD-metoder för organisationer och medlemmar inom `Database`-klassen.

3.  [COMPLETED] **API Endpoints (inc/class-apihandlers.php):**
    *   Implementera REST endpoints för organisationer (`POST`, `GET`, `GET /{id}`, `PUT /{id}`, `DELETE /{id}`) inom `ApiHandlers`-klassen (namespace `wp-schedule-plugin/v1`).
    *   Implementera `get_validated_org_id()` hjälpfunktion.
    *   Använd korrekta behighetskontroller.

4.  **Frontend (Svelte - app/src/):**
    *   Skapa `app/src/stores.js` (t.ex. `selectedOrgId`).
    *   Skapa `app/src/components/` katalog.
    *   Skapa `app/src/components/OrganizationList.svelte` (hämtar/visar orgs via `apiFetch`, hanterar val).
    *   Skapa `app/src/components/OrganizationForm.svelte` (skapa/redigera via `apiFetch`).
    *   Integrera komponenterna i `app/src/AdminApp.svelte`.
    *   Använd `$t()` för all UI-text.

**(NÄSTA STEG)** ## Fas 2: Resurser (Resources)

Denna fas introducerar konceptet resurser (t.ex. rum, utrustning) som tillhör specifika organisationer och kan schemaläggas senare.

1.  **Databasschema (`inc/class-database.php`):**
    *   **Modifiera `create_or_update_tables()`:**
        *   Lägg till SQL-definition för ny tabell: `{$this->get_table_name('resources')}`.
        *   **Kolumner:** `resource_id` (PK AI), `org_id` (NN, Index, FK -> organizations), `name` (NN), `description` (NULL), `type` (NULL, Index), `capacity` (NULL), `is_active` (NN, DEFAULT 1, Index), `created_at`, `updated_at`.
        *   Lägg till `FOREIGN KEY (org_id) ... ON DELETE CASCADE`.
        *   Säkerställ att `dbDelta()` anropas efter den nya tabellen.

2.  **Databasmetoder (`inc/class-database.php`):**
    *   Implementera `create_resource($org_id, $name, $data = [])`.
    *   Implementera `get_resource($resource_id)`.
    *   Implementera `get_resources($args = [])` (filtrering på `org_id`, `type`, `is_active`).
    *   Implementera `update_resource($resource_id, $data)`.
    *   Implementera `delete_resource($resource_id)`.
    *   *(Valfri)* Implementera `get_organization_resources($org_id, $args = [])`.

3.  **API Endpoints (`inc/class-apihandlers.php`):**
    *   **Förbättra `GET /organizations/{org_id}`:** Lägg till `current_user_role` i svaret om användaren är medlem.
    *   **Registrera Nya Resurs-Routes:**
        *   `POST /resources`: Skapa resurs. Behörighet: `scheduler`/`org_admin` i `org_id`.
        *   `GET /resources`: Hämta resurser. Behörighet: `employee` i `org_id` (query param).
        *   `GET /resources/{resource_id}`: Hämta specifik resurs. Behörighet: `employee` i resursens `org_id`.
        *   `PUT /resources/{resource_id}`: Uppdatera resurs. Behörighet: `scheduler`/`org_admin` i resursens `org_id`.
        *   `DELETE /resources/{resource_id}`: Ta bort resurs. Behörighet: `org_admin` i resursens `org_id` ELLER `manage_options`.

4.  **Frontend (Svelte - `app/src/`):**
    *   **Stores (`app/src/stores.js`):** Lägg till store `selectedOrgUserRole`.
    *   **Components (`app/src/components/`):**
        *   Skapa `ResourceList.svelte`: Visar resurser för vald org, knappar baserat på roll.
        *   Skapa `ResourceForm.svelte`: Formulär för att skapa/redigera resurs.
    *   **Integration (`app/src/AdminApp.svelte`):** Lägg till sektion/tab för resurser, visa `ResourceList`, hantera modal för `ResourceForm`.
    *   **i18n (`app/src/locales/sv.json`):** Lägg till svenska översättningar för resurs-relaterad text.

## Fas 3: Skift (Shifts)

Denna fas fokuserar på att skapa, visa, uppdatera och radera skift, samt koppla dem till organisationer och valfritt till användare och resurser.

1.  **Databasschema (`inc/class-database.php`):**
    *   **Modifiera `create_or_update_tables()`:**
        *   Lägg till SQL-definition för `{$this->get_table_name('shifts')}`.
        *   **Kolumner:** `shift_id` (PK AI), `org_id` (NN, Index, FK -> organizations), `resource_id` (NULL, Index, FK -> resources), `user_id` (NULL, Index, FK -> users), `start_time` (DATETIME, NN, Index), `end_time` (DATETIME, NN, Index), `title` (NULL), `notes` (NULL), `status` (ENUM('pending', 'confirmed', 'cancelled'), NN, DEFAULT 'pending', Index), `created_at`, `updated_at`.
        *   Lägg till FK-constraints: `org_id` (CASCADE), `resource_id` (SET NULL), `user_id` (SET NULL).
        *   Lägg till relevanta index.
        *   Säkerställ att `dbDelta()` anropas.

2.  **Databasmetoder (`inc/class-database.php`):**
    *   Implementera `create_shift($org_id, $start_time, $end_time, $data = [])`.
    *   Implementera `get_shift($shift_id)`.
    *   Implementera `get_shifts($args = [])` (filtrering på `org_id`, datumintervall, `resource_id`, `user_id`, `status`).
    *   Implementera `update_shift($shift_id, $data)`.
    *   Implementera `delete_shift($shift_id)`.

3.  **API Endpoints (`inc/class-apihandlers.php`):**
    *   Registrera nya skift-routes under `wp-schedule-plugin/v1`.
    *   `POST /shifts`: Skapa skift. Behörighet: `scheduler`/`org_admin` i `org_id`.
    *   `GET /shifts`: Hämta skift. Behörighet: `employee` i `org_id` (query param). Filtrera.
    *   `GET /shifts/{shift_id}`: Hämta specifikt skift. Behörighet: `employee` i skiftets `org_id`.
    *   `PUT /shifts/{shift_id}`: Uppdatera skift. Behörighet: `scheduler`/`org_admin` i skiftets `org_id`.
    *   `DELETE /shifts/{shift_id}`: Ta bort skift. Behörighet: `scheduler`/`org_admin` i skiftets `org_id`.

4.  **Frontend (Svelte - `app/src/`):**
    *   **Components (`app/src/components/`):**
        *   Skapa `ShiftCalendarView.svelte`: Integrera kalenderbibliotek, hämta/visa skift, hantera interaktioner.
        *   Skapa `ShiftForm.svelte`: Formulär för skapa/redigera skift (tider, titel, anteckningar, användare, resurs, status). Hämta användare/resurser för dropdowns. Anropa API.
        *   Skapa `ShiftDetailModal.svelte`: Visa detaljer, tillåt redigera/ta bort baserat på roll.
    *   **Integration (`app/src/AdminApp.svelte`):** Lägg till huvudvy "Schema", integrera `ShiftCalendarView`, hantera `ShiftForm`/`ShiftDetailModal`.
    *   **i18n (`app/src/locales/sv.json`):** Lägg till översättningar för skift-relaterade termer.

## Fas 4: Medlemmar (Member Management UI)

Denna fas fokuserar på att skapa användargränssnittet inom Svelte-appen för att hantera medlemmar (lägga till, visa, redigera, ta bort) inom den valda organisationen, med hjälp av databasmetoder och API-endpoints som planerats i Fas 1.

1.  [COMPLETED] **API Enhancements/Implementation (`inc/class-apihandlers.php`):**
    *   **Implementera `GET /organization_members`:** (Endpoint definierad i Fas 1). Hämta medlemmar för en org, inkludera användardata. Behörighet: `employee` i `org_id`.
    *   **Implementera `POST /organization_members`:** (Endpoint definierad i Fas 1). Lägg till medlem. Behörighet: `scheduler`/`org_admin` i `org_id`.
    *   **Implementera `PUT /organization_members/{user_id}`:** (Endpoint definierad i Fas 1). Uppdatera medlem (roll, anst.nr). Behörighet: `scheduler`/`org_admin` i `org_id`.
    *   **Implementera `DELETE /organization_members/{user_id}`:** (Endpoint definierad i Fas 1). Ta bort medlem. Behörighet: `org_admin` i `org_id`. Överväg att förhindra själv-borttagning.
    *   **(Valfri) Implementera `GET /users`:** Ny endpoint för att söka WP-användare. Behörighet: `scheduler`/`org_admin` eller `manage_options`.

2.  [COMPLETED] **Frontend (Svelte - `app/src/`):**
    *   **Components (`app/src/components/`):**
        *   Skapa `MemberList.svelte`: Visa medlemmar för vald org, hantera paginering/sökning, visa knappar baserat på roll.
        *   Skapa `MemberForm.svelte`: Formulär för att lägga till/redigera medlem (användarsökning, roll, anst.nr). Anropa API.
    *   **Integration (`app/src/AdminApp.svelte`):** Lägg till "Medlemmar"-tab/sektion, visa `MemberList`, hantera modal för `MemberForm`.
    *   **i18n (`app/src/locales/sv.json`):** Lägg till översättningar för medlemshantering.

## Fas 5: Hierarki UI (Organization Hierarchy UI)

Denna fas fokuserar på att visualisera och hantera förälder-barn-relationer mellan organisationer.

1.  **API Enhancements (`inc/class-apihandlers.php`):**
    *   **Modifiera `GET /organizations`:** Säkerställ att svaret inkluderar `parent_org_id`.
    *   **Modifiera `PUT /organizations/{org_id}`:**
        *   Tillåt `parent_org_id` i request body.
        *   **Lägg till Validering:** Förhindra cykliska beroenden (kontrollera att ny förälder inte är en descendant eller samma org).
        *   **Behörighet:** Begränsa ändring av `parent_org_id` till `manage_options`.

2.  **Database Enhancements (`inc/class-database.php`):**
    *   Implementera `get_organization_descendants($org_id)`: Hämtar ID:n för alla underliggande organisationer (rekursivt).
    *   Säkerställ att `update_organization` hanterar uppdatering av `parent_org_id`.

3.  **Frontend (Svelte - `app/src/`):**
    *   **Components (`app/src/components/`):**
        *   Modifiera `OrganizationList.svelte`:
            *   Hämta platt lista av orgs inkl. `parent_org_id`.
            *   Implementera klient-logik för att bygga/visa trädstruktur (t.ex. med `svelte-tree-view`).
            *   *(Valfri)* Lägg till dra-och-släpp för att ändra förälder.
        *   Modifiera `OrganizationForm.svelte`:
            *   Lägg till "Föräldraorganisation"-dropdown.
            *   Filtrera bort aktuell org och dess descendants från valbara föräldrar.
            *   Visa/aktivera fältet baserat på behörighet (`manage_options`).
    *   **i18n (`app/src/locales/sv.json`):** Lägg till översättningar ("parentOrganization", "selectParent", etc.).

## Fas 6: Polish, Testning, Dokumentation

Denna fas är avgörande för att säkerställa pluginets kvalitet, stabilitet och användbarhet.

1.  **UI/UX Polish (Svelte - `app/src/`):**
    *   **Konsistensgranskning:** Gå igenom alla admin-vyer och säkerställ konsekvent layout, stilar, formulärhantering och terminologi (via `$t()`).
    *   **Laddnings- & Fel-tillstånd:** Implementera tydliga visuella indikatorer (spinners, etc.) under API-anrop och visa användarvänliga felmeddelanden.
    *   **Responsivitet:** Testa och justera admin-gränssnittet för olika skärmstorlekar.
    *   **Tillgänglighet (a11y):** Utför grundläggande kontroller (tangentbordsnavigering, kontrast, ARIA, labels).
    *   **Indatavalidering:** Lägg till klient-sidans validering i formulär som komplement till server-sidans.

2.  **Testning:**
    *   **PHP Unit/Integrationstester:**
        *   Sätt upp PHPUnit (`wp-cli scaffold plugin-tests`).
        *   Skriv enhetstester för `Database`-metoder (mocka `$wpdb`).
        *   Skriv enhetstester för `ApiHandlers`-logik (mocka `Database`).
        *   Skriv integrationstester för REST API endpoints (`WP_Test_REST_Controller_Testcase`).
    *   **JavaScript/Svelte-tester:**
        *   Sätt upp Vitest/Jest med Svelte Testing Library.
        *   Skriv enhetstester för hjälpfunktioner/stores.
        *   Skriv komponenttester för nyckelkomponenter (listor, formulär, kalender).

3.  **Dokumentation:**
    *   **README.md:** Utöka med detaljerad setup, funktionsöversikt, användningsinstruktioner.
    *   **Inline-kodkommentarer:** Granska PHP och JS/Svelte för tydlighet. Lägg till PHPDoc/JSDoc.
    *   **Användarguide (`docs/`):** Skapa enkel dokumentation för administratörer/schemaläggare.
    *   **Utvecklardokumentation (`docs/`):** Dokumentera REST API, databasschema, hooks/filter.

4.  **i18n Finalisering:**
    *   Regenerera `.pot`-fil.
    *   Säkerställ att `sv_SE.po` är fullständigt översatt och kompilera `.mo`.
    *   Granska alla användarvisade strängar i PHP och Svelte.

5.  **Kodkvalitet & Standarder:**
    *   Konfigurera och kör PHPCS (WordPress standarder). Åtgärda fel.
    *   Konfigurera och kör ESLint/Prettier (JS/Svelte). Åtgärda fel.
    *   Utför manuell kodgranskning (säkerhet, prestanda, underhållbarhet).