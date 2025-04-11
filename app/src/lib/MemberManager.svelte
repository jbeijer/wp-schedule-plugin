<script>
    import { onMount, createEventDispatcher } from 'svelte';
    import apiFetch from '../api.js';
    import { t } from '../i18n.js';

    export let orgId; // Passed in from AdminApp

    let members = [];
    let isLoading = true;
    let errorMessage = '';
    let successMessage = '';
    const dispatch = createEventDispatcher();

    // State for adding/editing members
    let isAdding = false;
    let editingMember = null; // Store the member being edited
    let userToAdd = ''; // Could be email or ID for searching WP users
    let selectedUserId = null; // ID of the WP user found
    let selectedRole = 'employee';
    let employmentNumber = '';

    async function fetchMembers() {
        if (!orgId) return;
        isLoading = true;
        errorMessage = '';
        try {
            const response = await apiFetch({
                path: `wp-schedule-plugin/v1/organization_members?org_id=${orgId}`
            });
            if (response && response.success && Array.isArray(response.data?.members)) {
                members = response.data.members;
            } else {
                throw new Error(response?.message || $t('memberFetchFailedGeneric'));
            }
        } catch (error) {
            errorMessage = `${$t('memberFetchFailed')}: ${error.message}`;
            console.error('Fetch Members Error:', error);
        } finally {
            isLoading = false;
        }
    }

    // Fetch members when orgId changes
    $: if (orgId) {
        fetchMembers();
        // Reset add/edit state when org changes
        isAdding = false;
        editingMember = null;
    }

    function startAddMember() {
        isAdding = true;
        editingMember = null; // Ensure not in edit mode
        // Reset form fields
        userToAdd = '';
        selectedUserId = null;
        selectedRole = 'employee';
        employmentNumber = '';
        errorMessage = '';
        successMessage = '';
    }

    // TODO: Implement user search functionality (maybe a separate component)
    async function searchUser() {
        // Placeholder: In a real app, this would call a WP REST endpoint
        // to search users by email/name and return potential matches.
        // For now, assume userToAdd is a valid user ID.
        if (userToAdd && !isNaN(parseInt(userToAdd))) {
            selectedUserId = parseInt(userToAdd);
            errorMessage = ''; // Clear previous errors
            console.log('User selected (placeholder):', selectedUserId);
        } else {
            selectedUserId = null;
            errorMessage = $t('userSearchFailed'); // Placeholder message
        }
    }

    async function handleAddMember() {
        if (!selectedUserId) {
            errorMessage = $t('userNotSelected');
            return;
        }
        isLoading = true;
        errorMessage = '';
        successMessage = '';

        const data = {
            org_id: orgId,
            user_id: selectedUserId,
            internal_role: selectedRole,
            employment_number: employmentNumber.trim() || null,
        };

        try {
            const response = await apiFetch({
                path: 'wp-schedule-plugin/v1/organization_members',
                method: 'POST',
                data: data,
            });
            if (response && response.success) {
                successMessage = $t('memberAddSuccess');
                await fetchMembers(); // Refresh list
                isAdding = false; // Close form
            } else {
                throw new Error(response?.message || $t('memberAddFailedGeneric'));
            }
        } catch (error) {
             errorMessage = `${$t('memberAddFailed')}: ${error.message}`;
             console.error('Add Member Error:', error);
        } finally {
            isLoading = false;
             setTimeout(() => { successMessage = ''; errorMessage = ''; }, 4000);
        }
    }

    function startEditMember(member) {
        editingMember = { ...member }; // Clone member data
        isAdding = false; // Ensure not in add mode
        // Pre-fill form (role and employment number)
        selectedRole = editingMember.internal_role;
        employmentNumber = editingMember.employment_number || '';
        errorMessage = '';
        successMessage = '';
    }

     async function handleUpdateMember() {
        if (!editingMember) return;

        isLoading = true;
        errorMessage = '';
        successMessage = '';

        const data = {
            org_id: orgId, // Required for permission check on backend
            internal_role: selectedRole,
            employment_number: employmentNumber.trim() || null,
        };

        try {
            const response = await apiFetch({
                path: `wp-schedule-plugin/v1/organization_members/${editingMember.user_id}`,
                method: 'PUT',
                data: data,
            });
            if (response && response.success) {
                successMessage = $t('memberUpdateSuccess');
                await fetchMembers(); // Refresh list
                editingMember = null; // Close edit form
            } else {
                throw new Error(response?.message || $t('memberUpdateFailedGeneric'));
            }
        } catch (error) {
             errorMessage = `${$t('memberUpdateFailed')}: ${error.message}`;
             console.error('Update Member Error:', error);
        } finally {
            isLoading = false;
             setTimeout(() => { successMessage = ''; errorMessage = ''; }, 4000);
        }
    }

    async function handleDeleteMember(userId) {
        if (!confirm($t('memberDeleteConfirm', { id: userId }))) {
            return;
        }
        isLoading = true;
        errorMessage = '';
        successMessage = '';
        try {
             // org_id is needed as a query param for permission check
            const response = await apiFetch({
                path: `wp-schedule-plugin/v1/organization_members/${userId}?org_id=${orgId}`,
                method: 'DELETE',
            });
            if (response && response.success) {
                successMessage = $t('memberRemoveSuccess');
                await fetchMembers(); // Refresh list
            } else {
                 throw new Error(response?.message || $t('memberRemoveFailedGeneric'));
            }
        } catch (error) {
            errorMessage = `${$t('memberRemoveFailed')}: ${error.message}`;
            console.error('Delete Member Error:', error);
        } finally {
            isLoading = false;
            setTimeout(() => { successMessage = ''; errorMessage = ''; }, 4000);
        }
    }

</script>

<div class="member-manager">
    {#if isLoading && !members.length}
        <p>{$t('loading')}...</p>
    {:else if errorMessage}
        <p class="error-message">{errorMessage}</p>
    {/if}
    {#if successMessage}
        <p class="success-message">{successMessage}</p>
    {/if}

    {#if !isAdding && !editingMember}
        <button class="button button-primary add-member-button" on:click={startAddMember}>
            {$t('addMember')}
        </button>
    {/if}

    <!-- Add Member Form -->
    {#if isAdding}
        <div class="member-form add-form">
            <h3>{$t('addMember')}</h3>
             <!-- User Search Placeholder -->
            <div class="form-field">
                <label for="user-search">{$t('findUserLabel')}:</label>
                <input type="text" id="user-search" bind:value={userToAdd} placeholder={$t('userSearchPlaceholder')} disabled={isLoading}>
                <button class="button" on:click={searchUser} disabled={isLoading || !userToAdd.trim()}>{$t('searchUserButton')}</button>
                {#if selectedUserId} <span class="user-found">{$t('userFound', { id: selectedUserId })}</span> {/if}
            </div>

            {#if selectedUserId}
                <div class="form-field">
                    <label for="member-role">{$t('memberRoleLabel')}:</label>
                    <select id="member-role" bind:value={selectedRole} disabled={isLoading}>
                        <option value="employee">{$t('roleEmployee')}</option>
                        <option value="scheduler">{$t('roleScheduler')}</option>
                        <option value="org_admin">{$t('roleOrgAdmin')}</option>
                    </select>
                </div>
                <div class="form-field">
                    <label for="employment-number">{$t('employmentNumberLabel')}:</label>
                    <input type="text" id="employment-number" bind:value={employmentNumber} disabled={isLoading}>
                </div>
                <div class="form-actions">
                    <button class="button button-primary" on:click={handleAddMember} disabled={isLoading}>
                        {#if isLoading}{$t('saving')}{:else}{$t('addMemberConfirm')}{/if}
                    </button>
                    <button class="button" on:click={() => isAdding = false} disabled={isLoading}>{$t('cancel')}</button>
                </div>
            {/if}
        </div>
    {/if}

     <!-- Edit Member Form -->
    {#if editingMember}
        <div class="member-form edit-form">
            <h3>{$t('editMemberTitle', { name: editingMember.display_name || `User ${editingMember.user_id}` })}</h3>
             <div class="form-field">
                <label for="edit-member-role">{$t('memberRoleLabel')}:</label>
                <select id="edit-member-role" bind:value={selectedRole} disabled={isLoading}>
                    <option value="employee">{$t('roleEmployee')}</option>
                    <option value="scheduler">{$t('roleScheduler')}</option>
                    <option value="org_admin">{$t('roleOrgAdmin')}</option>
                </select>
            </div>
            <div class="form-field">
                <label for="edit-employment-number">{$t('employmentNumberLabel')}:</label>
                <input type="text" id="edit-employment-number" bind:value={employmentNumber} disabled={isLoading}>
            </div>
            <div class="form-actions">
                <button class="button button-primary" on:click={handleUpdateMember} disabled={isLoading}>
                     {#if isLoading}{$t('saving')}{:else}{$t('saveChanges')}{/if}
                </button>
                <button class="button" on:click={() => editingMember = null} disabled={isLoading}>{$t('cancel')}</button>
            </div>
        </div>
    {/if}

    <!-- Member List -->
    {#if !isAdding && !editingMember}
        <table class="wp-list-table widefat fixed striped members">
            <thead>
                <tr>
                    <th scope="col">{$t('memberName')}</th>
                    <th scope="col">{$t('memberEmail')}</th>
                    <th scope="col">{$t('memberRole')}</th>
                    <th scope="col">{$t('employmentNumber')}</th>
                    <th scope="col">{$t('memberActions')}</th>
                </tr>
            </thead>
            <tbody>
                {#each members as member (member.membership_id)}
                    <tr>
                        <td>{member.display_name || `User ${member.user_id}`}</td>
                        <td>{member.user_email || '-'}</td>
                        <td>{$t(`role${member.internal_role.charAt(0).toUpperCase() + member.internal_role.slice(1)}`)}</td>
                        <td>{member.employment_number || '-'}</td>
                        <td>
                            <button class="button button-small" on:click={() => startEditMember(member)}>{$t('edit')}</button>
                            <button class="button button-small button-link-delete" on:click={() => handleDeleteMember(member.user_id)}>{$t('remove')}</button>
                        </td>
                    </tr>
                {:else}
                    <tr>
                        <td colspan="5">{$t('noMembersFound')}</td>
                    </tr>
                {/each}
            </tbody>
        </table>
    {/if}
</div>

<style>
    .member-manager {
        margin-top: 1.5em;
    }
    .add-member-button {
        margin-bottom: 1em;
    }
    .member-form {
        padding: 1em;
        border: 1px solid #ccd0d4;
        margin-bottom: 1em;
        background-color: #fdfdfd;
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
        max-width: 350px;
        padding: 6px;
        margin-right: 5px; /* Space before search button */
    }
     .user-found {
        margin-left: 10px;
        font-style: italic;
        color: green;
    }
    .form-actions {
        margin-top: 1.5em;
    }
    .form-actions button {
        margin-right: 10px;
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
    .button-small {
        min-height: 24px;
        line-height: 2.1;
        padding: 0 8px;
        font-size: 11px;
    }
    .button-link-delete {
        color: #dc3232;
        border-color: transparent;
        background: none;
        box-shadow: none;
        text-decoration: underline;
        padding: 0;
        cursor: pointer;
    }
    .button-link-delete:hover {
        color: #a00;
    }
    /* Ensure table styles are applied */
    .wp-list-table {
        margin-top: 1em;
    }
</style>