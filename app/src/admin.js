import { setupI18n } from './i18n'; // Import the setup function
import AdminApp from './AdminApp.svelte';
import "./app.css";

// Function to initialize and mount the app
async function initializeApp() {
    // Extract locale from localized data (e.g., 'sv' from 'sv_SE')
    const userLocale = window.wpScheduleAdminData?.userLocale?.split('_')[0] || 'sv';

    try {
        // Initialize i18n and wait for it to load the locale
        await setupI18n({ initialLocale: userLocale });

        // Mount the Svelte app *after* i18n is ready
        const app = new AdminApp({
            target: document.getElementById("wp-schedule-admin-app"),
            // Pass localized data as props if needed by AdminApp directly
            // props: {
            //     adminData: window.wpScheduleAdminData || {}
            // }
        });
        return app; // Return the app instance if needed elsewhere

    } catch (error) {
        console.error("Failed to initialize i18n or mount the app:", error);
        // Optionally display an error message to the user in the target div
        const targetElement = document.getElementById("wp-schedule-admin-app");
        if (targetElement) {
            targetElement.innerHTML = '<p style="color: red;">Error loading application interface. Please check the console.</p>';
        }
    }
}

// Initialize the app
const app = initializeApp();

export default app;