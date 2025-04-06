import { register, init, getLocaleFromNavigator, locale as currentLocale, t as translate, isLoading } from 'svelte-i18n';

// Register locales
// Add other languages here later, e.g.:
// register('en', () => import('./locales/en.json'));
register('sv', () => import('./locales/sv.json'));

/**
 * Initializes the i18n library.
 * @param {object} args - Initialization arguments.
 * @param {string} [args.initialLocale=null] - The initial locale to set, e.g., 'sv'. If null, uses browser preference.
 */
function setupI18n({ initialLocale = null } = {}) {
    // init returns a promise that resolves when the initial locale is loaded
    return init({
        fallbackLocale: 'sv', // Fallback to Swedish if detection fails or language file missing
        initialLocale: initialLocale || getLocaleFromNavigator(), // Use provided locale or detect from browser
    });
}

// Export the setup function and the reactive stores/functions
export { setupI18n, currentLocale, translate as t, isLoading };