<script>
  import { createEventDispatcher } from 'svelte';
  import apiFetch from '../api'; // Use default import
  import { t } from '../i18n'; // Assuming $t is exported as t

  export let orgId; // Required for creating a new resource under an organization
  export let resource = null; // Optional: Pass an existing resource object for editing

  let name = resource?.name ?? '';
  let description = resource?.description ?? '';
  let type = resource?.type ?? ''; // Consider adding a default type if applicable
  let capacity = resource?.capacity ?? 1; // Default capacity to 1
  let isActive = resource?.is_active ?? true; // Default to active

  let isLoading = false;
  let errorMessage = '';
  let successMessage = '';

  const dispatch = createEventDispatcher();

  async function handleSubmit() {
    isLoading = true;
    errorMessage = '';
    successMessage = '';

    if (!name.trim()) {
      errorMessage = $t('resource_form.error_name_required');
      isLoading = false;
      return;
    }

    const data = {
      name: name.trim(),
      description: description.trim(),
      type: type.trim(),
      capacity: parseInt(capacity, 10) || 0, // Ensure capacity is an integer
      is_active: isActive,
    };

    let path;
    let method;

    if (resource && resource.id) {
      // Editing existing resource
      path = `/resources/${resource.id}`;
      method = 'PUT';
      data.organization_id = resource.organization_id; // Ensure org ID is included if needed by backend for PUT
    } else if (orgId) {
      // Creating new resource for a specific organization
      path = `/organizations/${orgId}/resources`;
      method = 'POST';
      data.organization_id = orgId; // Explicitly set org ID for creation
    } else {
        errorMessage = $t('resource_form.error_org_id_missing');
        isLoading = false;
        return;
    }


    try {
      const response = await apiFetch({ path, method, data }); // Pass options as a single object
      if (response.success) {
        successMessage = method === 'POST' ? $t('resource_form.success_create') : $t('resource_form.success_update');
        dispatch('saveSuccess', response.data.resource); // Dispatch the saved/updated resource data

        // Reset form only on successful creation
        if (method === 'POST') {
          name = '';
          description = '';
          type = '';
          capacity = 1;
          isActive = true;
        }
        // Keep messages for a bit before clearing or let parent handle clearing/closing
        // setTimeout(() => { successMessage = ''; }, 3000);
      } else {
        errorMessage = response.data?.message || (method === 'POST' ? $t('resource_form.error_create') : $t('resource_form.error_update'));
      }
    } catch (error) {
      console.error('API Fetch Error:', error);
      errorMessage = $t('resource_form.error_network');
    } finally {
      isLoading = false;
    }
  }

  function handleCancel() {
    dispatch('cancel');
  }
</script>

<form on:submit|preventDefault={handleSubmit} class="resource-form">
  {#if errorMessage}
    <p class="error-message">{@html errorMessage}</p>
  {/if}
  {#if successMessage}
    <p class="success-message">{successMessage}</p>
  {/if}

  <div class="form-group">
    <label for="resource-name">{$t('resource_form.label_name')}</label>
    <input id="resource-name" type="text" bind:value={name} placeholder={$t('resource_form.placeholder_name')} required disabled={isLoading}>
  </div>

  <div class="form-group">
    <label for="resource-description">{$t('resource_form.label_description')}</label>
    <textarea id="resource-description" bind:value={description} placeholder={$t('resource_form.placeholder_description')} disabled={isLoading}></textarea>
  </div>

  <div class="form-group">
    <label for="resource-type">{$t('resource_form.label_type')}</label>
    <input id="resource-type" type="text" bind:value={type} placeholder={$t('resource_form.placeholder_type')} disabled={isLoading}>
     <!-- Consider changing to a <select> if types are predefined -->
  </div>

   <div class="form-group">
    <label for="resource-capacity">{$t('resource_form.label_capacity')}</label>
    <input id="resource-capacity" type="number" min="0" step="1" bind:value={capacity} disabled={isLoading}>
  </div>

  <div class="form-group form-group-checkbox">
    <input id="resource-is-active" type="checkbox" bind:checked={isActive} disabled={isLoading}>
    <label for="resource-is-active">{$t('resource_form.label_is_active')}</label>
  </div>

  <div class="form-actions">
    <button type="submit" class="button button-primary" disabled={isLoading}>
      {#if isLoading}
        {$t('resource_form.button_saving')}...
      {:else if resource}
        {$t('resource_form.button_update')}
      {:else}
        {$t('resource_form.button_create')}
      {/if}
    </button>
    <button type="button" class="button" on:click={handleCancel} disabled={isLoading}>
      {$t('resource_form.button_cancel')}
    </button>
  </div>
</form>

<style>
  .resource-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    padding: 1rem;
    border: 1px solid #ccc;
    border-radius: 4px;
    background-color: #f9f9f9;
    max-width: 500px; /* Adjust as needed */
    margin: 1rem auto; /* Center form */
  }

  .form-group {
    display: flex;
    flex-direction: column;
  }

   .form-group-checkbox {
    flex-direction: row;
    align-items: center;
    gap: 0.5rem;
  }

  .form-group label {
    margin-bottom: 0.25rem;
    font-weight: bold;
  }

  .form-group input[type="text"],
  .form-group input[type="number"],
  .form-group textarea {
    padding: 0.5rem;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 1rem;
  }

   .form-group input[type="checkbox"] {
     width: auto; /* Override default width */
     margin-right: 0.5rem; /* Space between checkbox and label */
   }

  .form-group textarea {
    min-height: 80px;
    resize: vertical;
  }

  .form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    margin-top: 1rem;
  }

  .button {
    padding: 0.6rem 1.2rem;
    border: 1px solid #ccc;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.9rem;
    background-color: #eee;
  }

  .button-primary {
    background-color: #0073aa; /* WordPress blue */
    color: white;
    border-color: #0073aa;
  }

  .button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }

  .error-message {
    color: #dc3232; /* WordPress error red */
    background-color: #fef7f7;
    border: 1px solid #dc3232;
    padding: 0.75rem;
    border-radius: 4px;
    margin-bottom: 1rem;
  }

  .success-message {
    color: #46b450; /* WordPress success green */
    background-color: #f8fdf8;
    border: 1px solid #46b450;
    padding: 0.75rem;
    border-radius: 4px;
    margin-bottom: 1rem;
  }
</style>