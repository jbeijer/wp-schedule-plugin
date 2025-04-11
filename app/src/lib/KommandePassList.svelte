<script>
  import { onMount } from 'svelte';
  import apiFetch from '../api.js';

  export let orgId;

  // Enforce required prop at runtime
  if (orgId === undefined) {
    throw new Error('KommandePassList: orgId prop is required');
  }

  let shifts = [];
  let isLoading = true;
  let error = '';

  // Get today's date in YYYY-MM-DD
  function today() {
    const d = new Date();
    return d.toISOString().slice(0, 10);
  }

  async function fetchShifts() {
    if (!orgId) {
      shifts = [];
      isLoading = false;
      error = 'Ingen organisation vald. Välj en organisation för att visa kommande pass.';
      return;
    }
    isLoading = true;
    error = '';
    try {
      // Fetch up to 10 upcoming shifts, starting today or later, for the selected org
      const data = await apiFetch({
        path: `wp-schedule-plugin/v1/shifts?org_id=${orgId}&start_date=${today()}&number=10`
      });
      shifts = data.shifts || [];
    } catch (e) {
      error = 'Kunde inte hämta kommande pass.';
      console.error(e);
      shifts = [];
    } finally {
      isLoading = false;
    }
  }

  onMount(fetchShifts);

  // Refetch when orgId changes
  $: if (orgId) fetchShifts();

  // Format date/time for display
  function fmt(dt) {
    if (!dt) return '';
    const d = new Date(dt);
    return d.toLocaleString('sv-SE', { dateStyle: 'short', timeStyle: 'short' });
  }
</script>

<div class="kommande-pass-list">
  <h3>Kommande pass</h3>
  {#if isLoading}
    <p>Laddar kommande pass...</p>
  {:else if error}
    <p class="error">{error}</p>
  {:else if shifts.length === 0}
    <p>Inga kommande pass.</p>
  {:else}
    <table>
      <thead>
        <tr>
          <th>Start</th>
          <th>Slut</th>
          <th>Resurs</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        {#each shifts as shift}
          <tr>
            <td>{fmt(shift.start_time)}</td>
            <td>{fmt(shift.end_time)}</td>
            <td>{shift.resource_name || '-'}</td>
            <td>{shift.status || '-'}</td>
          </tr>
        {/each}
      </tbody>
    </table>
  {/if}
</div>

<style>
.kommande-pass-list {
  margin-top: 1rem;
}
.kommande-pass-list table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 0.5rem;
}
.kommande-pass-list th,
.kommande-pass-list td {
  padding: 0.5rem 0.75rem;
  border-bottom: 1px solid #e0e0e0;
  text-align: left;
}
.kommande-pass-list th {
  background: #f6f8fa;
  font-weight: 600;
}
.error {
  color: #c00;
}
</style>