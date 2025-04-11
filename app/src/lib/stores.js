import { writable } from 'svelte/store';

// Store for the currently selected organization ID
export const selectedOrgId = writable(null);

// Store for the list of organizations (shared between components)
export const organizations = writable([]);