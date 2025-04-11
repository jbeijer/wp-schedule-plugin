<script>
  import { onMount } from 'svelte';
  import apiFetch from '../api.js';
  import { selectedOrgId, organizations as organizationsStore } from './stores.js';
  import { get } from 'svelte/store';
  import OrganizationForm from './OrganizationForm.svelte';

  // New: prop to control if user can create organizations (default true for backward compatibility)
  export let canCreateOrganization = true;

  let showCreateForm = false;
  let organizations = [];
  let localSelectedOrgId = null;
  let loading = true;
  // Track if user has made a selection to avoid overwriting
  let userHasSelectedOrg = false;
  let initialLoadDone = false;
  let error = null;
  let errorDetails = null; // for logging

  // Retry logic
  async function fetchOrganizations() {
    loading = true;
    error = null;
    errorDetails = null;
    try {
      const result = await apiFetch({ path: 'wp-schedule-plugin/v1/organizations' });
      console.log('API response for organizations:', result);
      // Support both { data: { organizations: [...] } } and array (legacy/fallback)
      if (result && result.data && Array.isArray(result.data.organizations)) {
        organizations = result.data.organizations;
      } else if (Array.isArray(result)) {
        organizations = result;
      } else {
        organizations = [];
      }
      organizationsStore.set(organizations);

      // Only set selectedOrgId and localSelectedOrgId on first load, and only if user hasn't selected
      if (!userHasSelectedOrg && !initialLoadDone && organizations.length > 0) {
        localSelectedOrgId = String(organizations[0].org_id);
        selectedOrgId.set(String(organizations[0].org_id));
        console.log('Initial load: set localSelectedOrgId and selectedOrgId to', organizations[0].org_id);
        initialLoadDone = true;
      }
    } catch (e) {
      error = 'Kunde inte hämta organisationer. Kontrollera din internetanslutning eller försök igen.';
      errorDetails = e;
      organizations = [];
      // Robust logging
      console.error('Fel vid hämtning av organisationer:', e);
    } finally {
      loading = false;
    }
  }

  onMount(() => {
    fetchOrganizations();
  });
  
  // Removed reactive sync between $selectedOrgId and localSelectedOrgId per instructions.

  function handleRetry() {
    fetchOrganizations();
  }
</script>

<style>
.selector-container {
  margin-bottom: 1.5rem;
  padding: 1rem 0;
  border-bottom: 1px solid #eee;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}
.selector-label {
  font-weight: bold;
  margin-bottom: 0.5rem;
}
.selector-select {
  min-width: 220px;
  padding: 0.4rem 0.7rem;
  font-size: 1rem;
}
.selector-message {
  color: #b00;
  font-size: 1rem;
  margin-top: 0.5rem;
}
.loader {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 0.5rem;
}
.spinner {
  width: 18px;
  height: 18px;
  border: 3px solid #ccc;
  border-top: 3px solid #333;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}
.create-org-btn, .retry-btn {
  margin-top: 0.7rem;
  padding: 0.4rem 1.1rem;
  font-size: 1rem;
  border-radius: 4px;
  border: 1px solid #bbb;
  background: #f7f7f7;
  cursor: pointer;
  transition: background 0.2s;
}
.create-org-btn:hover, .retry-btn:hover {
  background: #e0e0e0;
}
</style>

<div class="selector-container">
  <span class="selector-label">Organisation</span>

  {#if loading}
    <div class="loader">
      <span class="spinner"></span>
      <span>Laddar organisationer...</span>
    </div>
  {:else if error}
    <span class="selector-message">{error}</span>
    <button class="retry-btn" on:click={handleRetry}>Ladda om</button>
    {#if errorDetails}
      <!-- For debugging, not shown to user. -->
      <script>
        // Log error details to console for developers
        if (errorDetails) {
          console.error('Detaljerat fel:', errorDetails);
        }
      </script>
    {/if}
  {:else if organizations.length === 0}
    <span class="selector-message">Du har inte tillgång till några organisationer.</span>
    {#if canCreateOrganization}
      <button class="create-org-btn" on:click={() => showCreateForm = true}>Skapa organisation</button>
      {#if showCreateForm}
        <OrganizationForm on:close={() => showCreateForm = false} />
      {/if}
    {/if}
  {:else}
    {#if organizations.length > 0}
      {@html (() => { console.log('organizations before render:', organizations); return ''; })()}
      <select
        class="selector-select"
        bind:value={localSelectedOrgId}
        on:change={(event) => {
          const select = /** @type {HTMLSelectElement} */ (event.target);
          localSelectedOrgId = select.value;
          userHasSelectedOrg = true;
          console.log('User changed dropdown: localSelectedOrgId =', localSelectedOrgId);
          selectedOrgId.set(String(select.value));
          console.log('User changed dropdown: selectedOrgId =', select.value);
        }}
      >
        {#each organizations as org}
          {@html (() => { console.log('rendering option:', { org_id: org.org_id, name: org.name }); return ''; })()}
          <option value={String(org.org_id)}>{org.name}</option>
        {/each}
      </select>
    {/if}
  {/if}
</div>