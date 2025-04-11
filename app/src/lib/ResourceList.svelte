X <script>
  import { onMount, createEventDispatcher } from 'svelte';
  import apiFetch from '../api'; // Use default import
  import { t } from '../i18n'; // Assuming t is a store exported from i18n.js

  export let orgId;

  let resources = [];
  let isLoading = true;
  let errorMessage = '';

  const dispatch = createEventDispatcher();

  async function fetchResources() {
    if (!orgId) {
      resources = [];
      isLoading = false;
      errorMessage = '';
      return;
    }
    isLoading = true;
    errorMessage = '';
    try {
      // Use apiFetch which should handle adding the nonce and base path
      // apiFetch returns the parsed JSON directly or throws an error
      const response = await apiFetch({ path: `wp-schedule-plugin/v1/resources?org_id=${orgId}` });

      if (response && response.success && response.data && Array.isArray(response.data.resources)) {
        resources = response.data.resources;
      } else {
        console.warn('API response for resources was not successful or data.resources is not an array:', response);
        resources = [];
      }
    } catch (error) {
      console.error('Error fetching resources:', error);
      errorMessage = $t('resourceList.error.networkError');
      resources = []; // Clear resources on error
    } finally {
      isLoading = false;
    }
  }

  onMount(fetchResources);

  // Reactive statement to refetch when orgId changes
  $: if (orgId) fetchResources();

  function handleEdit(resource) {
    dispatch('editResource', resource);
  }

  function handleDelete(resourceId) {
    dispatch('deleteResource', resourceId);
  }
</script>

<style>
  .wp-list-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1em;
  }
  .wp-list-table th,
  .wp-list-table td {
    padding: 8px 10px;
    border: 1px solid #ccd0d4;
    text-align: left;
  }
  .wp-list-table th {
    background-color: #f6f7f7;
    font-weight: 600;
  }
  .wp-list-table tbody tr:nth-child(odd) {
    background-color: #f6f7f7;
  }
  .wp-list-table tbody tr:hover {
    background-color: #eef4ff;
  }
  .actions button {
    margin-right: 5px;
    cursor: pointer;
  }
  .loading-message,
  .error-message,
  .no-resources-message {
    margin-top: 1em;
    padding: 10px;
    border: 1px solid transparent;
    border-radius: 4px;
  }
  .loading-message {
    background-color: #eef4ff;
    border-color: #d1e0ff;
    color: #0052cc;
  }
  .error-message {
    background-color: #ffebe6;
    border-color: #ffc9c4;
    color: #de350b;
  }
   .no-resources-message {
    background-color: #f0f0f0;
    border-color: #e0e0e0;
    color: #50575e;
  }
</style>

{#if isLoading}
  <div class="loading-message">{$t('resourceList.loading')}</div>
{:else if errorMessage}
  <div class="error-message">{errorMessage}</div>
{:else if resources.length === 0}
   <div class="no-resources-message">{$t('resourceList.noResources')}</div>
{:else}
  <table class="wp-list-table widefat fixed striped">
    <thead>
      <tr>
        <th>{$t('resourceList.header.id')}</th>
        <th>{$t('resourceList.header.name')}</th>
        <th>{$t('resourceList.header.type')}</th>
        <th>{$t('resourceList.header.capacity')}</th>
        <th>{$t('resourceList.header.isActive')}</th>
        <th>{$t('resourceList.header.description')}</th>
        <th>{$t('resourceList.header.actions')}</th>
      </tr>
    </thead>
    <tbody>
      {#each resources as resource (resource.resource_id)}
        <tr>
          <td>{resource.resource_id}</td>
          <td>{resource.name}</td>
          <td>{resource.type}</td>
          <td>{resource.capacity}</td>
          <td>{resource.is_active ? $t('common.yes') : $t('common.no')}</td>
          <td>{resource.description || '-'}</td>
          <td class="actions">
            <button class="button button-secondary" on:click={() => handleEdit(resource)}>
              {$t('common.edit')}
            </button>
            <button class="button button-link-delete" on:click={() => handleDelete(resource.resource_id)}>
              {$t('common.delete')}
            </button>
          </td>
        </tr>
      {/each}
    </tbody>
  </table>
{/if}