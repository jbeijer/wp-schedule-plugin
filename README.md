# WP Schedule Plugin

WordPress plugin for scheduling employees and resources within organizations.

## Features (Implemented)

*   Organization Management (CRUD via REST API & Admin UI)
*   Member Management (CRUD via REST API & Admin UI, linking WP Users to Orgs with roles)
*   REST API structure (`wp-schedule-plugin/v1`) with authentication & basic permission checks.
*   Admin UI built with Svelte & Vite.
*   Internationalization (i18n) setup with Swedish translation.

## Usage

The admin panel can be found under the "Schemaläggning" menu item in the WordPress admin area.
## Setup

1.  Run `composer install`
2.  Run `npm install`
3.  Run `npm run dev` for development or `npm run build` for production.
4.  Activate the plugin in WordPress.