<script>
  import { createEventDispatcher } from 'svelte';
  import apiFetch from '../api.js';

  const dispatch = createEventDispatcher();

  let name = '';
  let error = '';
  let loading = false;

  async function handleSubmit(e) {
    e.preventDefault();
    error = '';
    loading = true;
    try {
      const res = await apiFetch({
        path: 'wp-schedule-plugin/v1/organizations',
        method: 'POST',
        data: { name }
      });
      if (res && res.id) {
        dispatch('close');
      } else {
        error = 'Kunde inte skapa organisation.';
      }
    } catch (e) {
      error = 'Kunde inte skapa organisation.';
    } finally {
      loading = false;
    }
  }

  function handleCancel() {
    dispatch('close');
  }
</script>

<form on:submit|preventDefault={handleSubmit} class="org-form">
  <label>
    Namn på organisation:
    <input type="text" bind:value={name} required />
  </label>
  {#if error}
    <div class="error">{error}</div>
  {/if}
  <div class="actions">
    <button type="submit" disabled={loading}>{loading ? 'Skapar...' : 'Skapa'}</button>
    <button type="button" on:click={handleCancel} disabled={loading}>Avbryt</button>
  </div>
</form>

<style>
  .org-form {
    display: flex;
    flex-direction: column;
    gap: 1em;
    background: #fff;
    padding: 1.5em;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    max-width: 350px;
    margin: 1em auto;
  }
  .actions {
    display: flex;
    gap: 1em;
  }
  .error {
    color: #b00;
    font-size: 0.95em;
  }
</style>