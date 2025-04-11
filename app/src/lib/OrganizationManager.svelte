<script>
    import { onMount } from 'svelte';
    import apiFetch from '../api.js'; // Assuming api.js is in the parent directory
    import { t } from '../i18n.js';

    let organizations = [];
    let isLoading = true;
    let errorMessage = '';
    let successMessage = '';

    let newOrgName = '';
    let newOrgParentId = null; // Use null for root/no parent

    async function fetchOrganizations() {
        isLoading = true;
        errorMessage = '';
        successMessage = '';
        try {
            const response = await apiFetch({ path: 'wp-schedule-plugin/v1/organizations' });
            if (response && response.success && Array.isArray(response.data?.organizations)) {
                organizations = response.data.organizations;
            } else {
                throw new Error(response?.message || $t('orgFetchFailedGeneric'));
            }
        } catch (error) {
            errorMessage = `${$t('orgFetchFailed')}: ${error.message}`;
            console.error('Fetch Organizations Error:', error);
        } finally {
            isLoading = false;
        }
    }

    async function createOrganization() {
        isLoading = true;
        errorMessage = '';
        successMessage = '';
        if (!newOrgName.trim()) {
            errorMessage = $t('orgNameRequired');
            isLoading = false;
            return;
        }

        const data = {
            name: newOrgName.trim(),
            // Ensure parent_org_id is null or a positive integer
            parent_org_id: newOrgParentId && parseInt(newOrgParentId, 10) > 0 ? parseInt(newOrgParentId, 10) : null,
        };

        try {
            const response = await apiFetch({
                path: 'wp-schedule-plugin/v1/organizations',
                method: 'POST',
                data: data,
            });

            if (response && response.success) {
                successMessage = $t('orgCreateSuccess');
                newOrgName = ''; // Clear form
                newOrgParentId = null;
                await fetchOrganizations(); // Refresh list
            } else {
                throw new Error(response?.message || $t('orgCreateFailedGeneric'));
            }
        } catch (error) {
            errorMessage = `${$t('orgCreateFailed')}: ${error.message}`;
            console.error('Create Organization Error:', error);
        } finally {
            isLoading = false;
            setTimeout(() => { successMessage = ''; errorMessage = ''; }, 4000);
        }
    }

    onMount(() => {
        fetchOrganizations();
    });

    // Placeholder for selecting an org to manage members
    export let selectedOrgId = null;

</script>

<div class="organization-manager">
    <h2>{$t('manageOrganizations')}</h2>

    {#if isLoading}
        <p>{$t('loading')}...</p>
    {:else if errorMessage}
        <p class="error-message">{errorMessage}</p>
    {/if}
    {#if successMessage}
        <p class="success-message">{successMessage}</p>
    {/if}

    <div class="org-list">
        <h3>{$t('existingOrganizations')}</h3>
        {#if organizations.length === 0 && !isLoading}
            <p>{$t('noOrganizationsFound')}</p>
        {:else}
            <ul>
                {#each organizations as org (org.org_id)}
                    <li>
                        <button
                            on:click={() => selectedOrgId = org.org_id}
                            class:selected={selectedOrgId === org.org_id}
                            title={$t('selectOrgTooltip', { name: org.name })}
                        >
                            {org.name} (ID: {org.org_id}) {#if org.parent_org_id}- Parent: {org.parent_org_id}{/if}
                        </button>
                        <!-- Add Edit/Delete buttons later -->
                    </li>
                {/each}
            </ul>
        {/if}
    </div>

    <div class="add-org-form">
        <h3>{$t('addOrganization')}</h3>
        <form on:submit|preventDefault={createOrganization}>
            <div class="form-field">
                <label for="new-org-name">{$t('orgNameLabel')}:</label>
                <input type="text" id="new-org-name" bind:value={newOrgName} required disabled={isLoading}>
            </div>
            <div class="form-field">
                 <label for="new-org-parent">{$t('orgParentLabel')}:</label>
                 <select id="new-org-parent" bind:value={newOrgParentId} disabled={isLoading}>
                     <option value={null}>-- {$t('orgParentNone')} --</option>
                     {#each organizations as org (org.org_id)}
                         <option value={org.org_id}>{org.name} (ID: {org.org_id})</option>
                     {/each}
                 </select>
                 <p class="description">{$t('orgParentDescription')}</p>
            </div>
            <button type="submit" class="button button-primary" disabled={isLoading || !newOrgName.trim()}>
                {#if isLoading}{$t('saving')}{:else}{$t('add')}{/if}
            </button>
        </form>
    </div>
</div>

<style>
    .organization-manager {
        margin-bottom: 2em;
        padding: 1em;
        border: 1px solid #ccd0d4;
        background-color: #f6f7f7;
    }
    .org-list ul {
        list-style: none;
        padding: 0;
        max-height: 200px;
        overflow-y: auto;
        border: 1px solid #ccd0d4;
        background: white;
        margin-bottom: 1em;
    }
    .org-list li button {
        display: block;
        width: 100%;
        padding: 8px 12px;
        text-align: left;
        background: none;
        border: none;
        border-bottom: 1px solid #eee;
        cursor: pointer;
    }
     .org-list li:last-child button {
        border-bottom: none;
    }
    .org-list li button:hover {
        background-color: #f0f0f1;
    }
     .org-list li button.selected {
        background-color: #007cba;
        color: white;
        font-weight: bold;
    }
    .add-org-form {
        margin-top: 1.5em;
        padding-top: 1.5em;
        border-top: 1px dashed #ccd0d4;
    }
    .form-field {
        margin-bottom: 1em;
    }
    .form-field label {
        display: block;
        margin-bottom: 0.3em;
        font-weight: bold;
    }
    .form-field input[type="text"],
    .form-field select {
        width: 100%;
        max-width: 400px;
        padding: 6px;
    }
    .description {
        font-size: 0.9em;
        color: #555;
        margin-top: 0.3em;
    }
    .error-message {
        color: #dc3232;
        border-left: 4px solid #dc3232;
        padding: 10px;
        background-color: #fef7f7;
        margin-bottom: 1em;
    }
    .success-message {
        color: #006505;
        border-left: 4px solid #46b450;
        padding: 10px;
        background-color: #f7fef7;
        margin-bottom: 1em;
    }
    /* Basic WP button styling */
    .button {
        display: inline-block;
        text-decoration: none;
        font-size: 13px;
        line-height: 2.15384615;
        min-height: 30px;
        margin: 0;
        padding: 0 10px;
        cursor: pointer;
        border-width: 1px;
        border-style: solid;
        -webkit-appearance: none;
        border-radius: 3px;
        white-space: nowrap;
        box-sizing: border-box;
    }
    .button-primary {
        background: #007cba;
        border-color: #007cba;
        color: #fff;
        text-decoration: none;
        text-shadow: none;
    }
    .button:disabled {
        color: #a0a5aa !important;
        border-color: #dcdcde !important;
        background: #f6f7f7 !important;
        box-shadow: none !important;
        text-shadow: none !important;
        cursor: default;
    }
</style>