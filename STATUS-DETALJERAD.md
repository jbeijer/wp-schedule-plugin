# Statusöversikt: WP Schedule Plugin

Denna sammanfattning bygger på TASKLIST.md, PLAN.md och PLAN-FÖR_ANPASNING.md. Den visar vad som är gjort och vad som återstår, med tydlig markering av klara och öppna punkter.

**Legend:**  
- [x] = Klar  
- [ ] = Ej klar / återstår

---

## Fas 0: Förbättrad Grundstruktur & Miljö

### PHP Setup (plugin.php, inc/, languages/)
- [x] plugin.php: Text Domain och Domain Path i header
- [x] plugin.php: Definiera konstanter
- [x] plugin.php: Aktiverings-/avaktiverings-hooks
- [x] plugin.php: Funktion wp_schedule_plugin_activate()
  - [ ] Lägg till roll `schema_user` (med cap `access_schemaplugin`)
  - [x] Lägg till cap `access_schemaplugin` till 'administrator'
  - [ ] Anropa databastabellskapande metod (från Fas 1)
  - [x] Sätt option-flagga
  - [x] Lägg till flush_rewrite_rules()
- [x] plugin.php: Funktion wp_schedule_plugin_deactivate()
- [x] plugin.php: Funktion wp_schedule_plugin_load_textdomain()
- [x] plugin.php: Hooka initialize_plugin till plugins_loaded
- [x] Skapa katalog languages/

### JavaScript/Svelte Setup (app/src/, package.json, vite.config.js)
- [x] package.json: svelte-i18n och @wordpress/api-fetch
- [x] npm install --legacy-peer-deps
- [x] Skapa app/src/locales/ och sv.json
- [x] Skapa app/src/i18n.js (import, register, setupI18n, $t, isLoading)
- [x] app/src/admin.js: Importera och anropa setupI18n
- [x] vite.config.js: build.rollupOptions.output.format: 'iife'

### Enqueue & API Setup (inc/admin.php, inc/class-*.php, plugin.php)
- [x] inc/admin.php: enqueue_admin_assets och wp_localize_script
- [x] inc/class-database.php: klass och metoder (stubs)
- [x] inc/class-apihandlers.php: klass och metoder (stubs, test-endpoint)
- [x] plugin.php: Kräv och instansiera klasser, hooka register_api_routes

### Svelte App Update & i18n Workflow (app/src/AdminApp.svelte)
- [x] AdminApp.svelte: i18n, apiFetch, onMount, rendering
- [ ] Plan: Kör npm run build
- [x] Plan: Verifiera admin-sida (laddning, API-anrop, svensk text)
- [ ] Plan: Generera .pot-fil
- [ ] Plan: Skapa sv_SE.po och .mo-filer

---

## Fas 1: Organisationer & Roller

### Databas (inc/class-database.php)
- [ ] create_or_update_tables med SQL för organizations och organization_members
- [ ] CRUD-metoder för organisationer och medlemmar

### API (inc/class-apihandlers.php)
- [ ] get_validated_org_id hjälpfunktion
- [ ] Endpoints: POST/GET/GET(id)/PUT/DELETE /organizations

### Frontend (app/src/)
- [ ] stores.js: selectedOrgId
- [ ] components/: OrganizationList.svelte, OrganizationForm.svelte
- [ ] AdminApp.svelte: Integrera komponenter
- [ ] UI-text: Använd $t()

---

## Fas 2: Resurser (Resources)

### Databas (inc/class-database.php)
- [ ] create_or_update_tables: resources-tabell
- [ ] CRUD-metoder för resurser

### API (inc/class-apihandlers.php)
- [ ] Endpoints: POST/GET/GET(id)/PUT/DELETE /resources

### Frontend (app/src/)
- [ ] stores.js: selectedOrgUserRole
- [ ] components/: ResourceList.svelte, ResourceForm.svelte
- [ ] AdminApp.svelte: Integrera komponenter
- [ ] sv.json: översättningar för resurser
- [ ] UI-text: Använd $t()

---

## Fas 3: Skift (Shifts)

### Databas (inc/class-database.php)
- [ ] create_or_update_tables: shifts-tabell
- [ ] CRUD-metoder för skift

### API (inc/class-apihandlers.php)
- [ ] Endpoints: POST/GET/GET(id)/PUT/DELETE /shifts

### Frontend (app/src/)
- [ ] Lägg till kalenderbibliotek
- [ ] components/: ShiftCalendarView.svelte, ShiftForm.svelte, ShiftDetailModal.svelte
- [ ] AdminApp.svelte: Integrera komponenter
- [ ] sv.json: översättningar för skift
- [ ] UI-text: Använd $t()

---

## Fas 4: Medlemmar (Member Management UI)

### API (inc/class-apihandlers.php)
- [ ] Handlers för GET/POST/PUT/DELETE /organization_members
- [ ] (Valfri) GET /users

### Frontend (app/src/)
- [ ] components/: MemberList.svelte, MemberForm.svelte
- [ ] AdminApp.svelte: Integrera komponenter
- [ ] sv.json: översättningar för medlemmar
- [ ] UI-text: Använd $t()

---

## Fas 5: Hierarki UI (Organization Hierarchy UI)

### API (inc/class-apihandlers.php)
- [ ] GET/PUT /organizations: parent_org_id, validering, behörighet

### Databas (inc/class-database.php)
- [ ] get_organization_descendants
- [ ] update_organization: parent_org_id

### Frontend (app/src/)
- [ ] Lägg till tree view-bibliotek
- [ ] OrganizationList.svelte: trädstruktur, dra-och-släpp
- [ ] OrganizationForm.svelte: föräldra-dropdown, filtrering, behörighet
- [ ] sv.json: översättningar för hierarki
- [ ] UI-text: Använd $t()

---

## Fas 6: Polish, Testning, Dokumentation

### UI/UX Polish
- [ ] Konsistens, laddningsindikatorer, felhantering, responsivitet, tillgänglighet, validering

### Testning
- [ ] PHPUnit, JS/Svelte-tester, integrationstester

### Dokumentation
- [ ] README, kodkommentarer, användarguide, utvecklardokumentation

### i18n Finalisering
- [ ] Regenerera .pot, översätt sv_SE.po, kompilera .mo, granska strängar

### Kodkvalitet & Standarder
- [ ] PHPCS, ESLint/Prettier, manuell kodgranskning

---

## Sammanfattning

- Fas 0 (grundstruktur) är till största delen klar, men några små punkter återstår (t.ex. rollskapande, .pot/.po-filer).
- Fas 1–5 (funktionalitet) är påbörjade men har många öppna punkter, särskilt på backend (databas, API) och frontend (komponenter, integration).
- Fas 6 (polish, testning, dokumentation) är inte påbörjad.

Vill du att jag ska lägga till en visuell översikt (t.ex. mermaid-diagram) eller fördjupa i någon specifik fas?