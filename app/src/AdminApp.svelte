<script>
    import { onMount } from 'svelte';
    import apiFetch from '@wordpress/api-fetch';
    import { t, isLoading as i18nIsLoading } from './i18n.js'; // Import translation function and loading state

    import ScheduleComponent from "./lib/ScheduleComponent.svelte";

    // Reactive state variables
    let scheduleItems = []; // Placeholder for actual data later
    let statusMessage = '';
    let isLoading = true; // Start as true until mount check completes
    let initialLoadComplete = false; // Flag to track if initial data load attempt finished
    let testApiResult = null; // To store result from test API call

    // Define types based on vite-env.d.ts for better type safety
    /** @type {import('../../vite-env').WpScheduleAdminData | undefined} */
    let adminData;

    // Function to test the API endpoint
    async function testApiCall() {
        isLoading = true;
        statusMessage = $t('loading'); // Use translated string
        testApiResult = null;
        try {
            const response = await apiFetch({ path: 'wp-schedule-plugin/v1/test' });
            testApiResult = response; // Store the successful response
            statusMessage = `${$t('testApiResult')}: ${JSON.stringify(response)}`;
            console.log('API Test Success:', response);
        } catch (error) {
            testApiResult = { error: true, message: error.message }; // Store error info
            statusMessage = `${$t('fetchError')}: ${error.message}`;
            console.error('API Test Error:', error);
        } finally {
            isLoading = false;
            // Optionally clear status message after a delay
            // setTimeout(() => { if (statusMessage.startsWith($t('testApiResult'))) statusMessage = ''; }, 5000);
        }
    }

    onMount(() => {
        // Check if the global variable exists and has necessary data
        if (typeof window !== 'undefined' && typeof window.wpScheduleAdminData !== 'undefined') {
            adminData = window.wpScheduleAdminData;
            // Nonce & apiUrl are handled by apiFetch automatically if localized correctly

            // Placeholder: Load initial data if needed (e.g., organizations)
            // scheduleItems = adminData.initialScheduleItems || [];
            // Initial load attempt is done, even if testApiCall starts loading again
            initialLoadComplete = true;
            isLoading = false; // Temporarily set to false before API call

            // Perform the test API call on mount
            testApiCall(); // This will set isLoading=true again

        } else {
            console.error('wpScheduleAdminData is not defined on window or missing required properties.');
            statusMessage = $t('configError'); // Use translated string
            isLoading = false; // Loading finished (with error)
            initialLoadComplete = true;
        }
    });

    // Placeholder for future save/update logic using apiFetch
    // async function saveSomething(data) {
    //     statusMessage = $t('saving');
    //     isLoading = true;
    //     try {
    //         const result = await apiFetch({
    //             path: 'wp-schedule-plugin/v1/your-endpoint',
    //             method: 'POST', // or PUT
    //             data: data,
    //         });
    //         statusMessage = $t('saveSuccess');
    //         // Update local state based on result
    //     } catch (error) {
    //         statusMessage = `${$t('saveError')}: ${error.message}`;
    //         console.error('Save Error:', error);
    //     } finally {
    //         isLoading = false;
    //     }
    // }

    // Placeholder: handleSave might be needed later for ScheduleComponent events
    // function handleSave(event) {
    //     const data = event.detail;
    //     // saveSomething(data);
    // }

</script>

    <div class="wp-schedule-admin-container">
        <h1>{$t('adminTitle')}</h1>
        <!-- Removed static description -->

        <!-- Show loading indicator only when actively loading -->
        {#if isLoading}
            <p>{$t('loading')}</p>
        {/if}

        <!-- Show status message if it exists -->
        {#if statusMessage}
             <div class="status-message" class:error={testApiResult?.error || statusMessage.startsWith($t('fetchError')) || statusMessage.startsWith($t('configError')) || statusMessage.startsWith($t('saveError'))}>
                 {statusMessage}
             </div>
        {/if}

        <!-- Show main content area after initial load attempt, even if subsequent loads happen -->
        {#if initialLoadComplete}
            <!-- Example Button to re-run test -->
            <button on:click={testApiCall} disabled={isLoading}>{$t('testApiButton')}</button>

            <!-- Render the component -->
            <!-- This ScheduleComponent is now just a placeholder visually -->
            <!-- We will replace/remove this in later phases -->
            <ScheduleComponent
                data={{ events: scheduleItems, settings: {} }}
                isAdmin={true}
            />
        {/if}

    </div>

<style>
    .wp-schedule-admin-container {
        padding: 20px;
        max-width: 1200px;
        background-color: #fff;
        border: 1px solid #c3c4c7;
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
        margin-top: 20px;
    }

    .status-message {
        margin-top: 15px;
        margin-bottom: 15px;
        padding: 10px;
        background-color: #f0f0f0;
        border-left: 4px solid #0073aa; /* WP blue */
        font-style: italic;
    }

     .status-message.error { /* More specific error styling */
        border-left-color: #dc3232; /* WP red for errors */
        background-color: #fef7f7;
        color: #9f2121;
    }

    h1 {
        color: #1d2327;
        font-size: 23px;
        font-weight: 400;
        margin: 0 0 20px;
        padding: 0;
    }

    button {
        margin-bottom: 15px;
        /* Add WP button styles if desired */
    }
</style>