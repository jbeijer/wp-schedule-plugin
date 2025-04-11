<script>
    import { onMount } from 'svelte';
    import apiFetch from './api.js'; // Import configured apiFetch instance
    import { t, isLoading as i18nIsLoading } from './i18n.js'; // Import translation function and loading state

    // Import the new component
    import Dashboard from './lib/Dashboard.svelte';
    // import OrganizationManager from './lib/OrganizationManager.svelte';
    // import MemberManager from './lib/MemberManager.svelte';
    // import ResourceList from './lib/ResourceList.svelte';
    // import ResourceForm from './lib/ResourceForm.svelte';
    // import ScheduleComponent from "./lib/ScheduleComponent.svelte";
    // Reactive state variables
    // let scheduleItems = []; // No longer needed directly here
    let statusMessage = '';
    let isLoading = true; // Still useful for overall loading state
    let initialLoadComplete = false;
    let testApiResult = null;
    let selectedOrgId = null; // To track which org is selected
    let showResourceForm = false; // Control visibility of the resource form
    let resourceToEdit = null; // Holds the resource object for editing, or null for adding
    let resourceListKey = 0; // Key to force ResourceList refresh

    // Define types based on vite-env.d.ts for better type safety
    // let adminData; // No longer needed directly, apiFetch uses wpApiSettings

    // Function to test the API endpoint
    async function testApiCall() {
        isLoading = true;
        statusMessage = $t('loading'); // Use translated string
        testApiResult = null;
        try {
            const response = await apiFetch({ path: 'wp-schedule-plugin/v1/test' }); // Removed leading slash
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
        // Nonce & apiUrl are handled by apiFetch automatically via wpApiSettings

        // No initial data load needed here anymore, OrganizationManager handles its own
        // Initial load attempt is done, even if testApiCall starts loading again
        initialLoadComplete = true;
        isLoading = false; // Set loading to false initially

        // Test API call can still be useful for debugging
        // testApiCall();
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

    // --- Resource Management Handlers ---

    function handleAddResourceClick() {
        resourceToEdit = null;
        showResourceForm = true;
    }

    function handleEditResource(event) {
        resourceToEdit = event.detail;
        showResourceForm = true;
    }

    async function handleDeleteResource(event) {
        const resource = event.detail;
        if (confirm($t('confirmDeleteResource', { name: resource.name }))) {
            isLoading = true;
            statusMessage = $t('loading');
            try {
                await apiFetch({
                    path: `wp-schedule-plugin/v1/resources/${resource.id}`,
                    method: 'DELETE',
                });
                statusMessage = $t('resourceDeleteSuccess');
                resourceListKey++; // Force refresh
            } catch (error) {
                statusMessage = `${$t('resourceDeleteFailed')}: ${error.message}`;
                console.error('Resource Delete Error:', error);
            } finally {
                isLoading = false;
                // Clear message after delay?
                setTimeout(() => { if (statusMessage === $t('resourceDeleteSuccess')) statusMessage = ''; }, 3000);
            }
        }
    }

    function handleResourceSaveSuccess() {
        showResourceForm = false;
        resourceToEdit = null;
        resourceListKey++; // Force refresh
        // Status message is handled within ResourceForm, but we could add one here too
        // statusMessage = resourceToEdit ? $t('resourceUpdateSuccess') : $t('resourceCreateSuccess');
        // setTimeout(() => { statusMessage = ''; }, 3000);
    }

    function handleResourceCancel() {
        showResourceForm = false;
        resourceToEdit = null;
    }

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
        {#if initialLoadComplete && !$i18nIsLoading}
            <!-- Dashboard is now the default admin view -->
            <Dashboard />
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