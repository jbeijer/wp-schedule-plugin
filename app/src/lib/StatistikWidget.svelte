<script>
  import { onMount } from 'svelte';
  export let orgId = null;
  import apiFetch from '../api.js';

  let stats = {
    upcoming_shifts: 0,
    resources: 0,
    coverage: 0,
    open_shifts: 0
  };
  let isLoading = true;
  let error = '';

  // Fetch dashboard stats for the selected org
  async function fetchStats() {
    if (!orgId) {
      stats = { upcoming_shifts: 0, resources: 0, coverage: 0, open_shifts: 0 };
      return;
    }
    isLoading = true;
    error = '';
    try {
      const data = await apiFetch({ path: `wp-schedule-plugin/v1/dashboard-stats?org_id=${orgId}` });
      stats = data;
    } catch (e) {
      error = 'Kunde inte hämta statistik.';
      console.error(e);
    } finally {
      isLoading = false;
    }
  }

  // Fetch on mount and whenever orgId changes
  $: if (orgId) {
    fetchStats();
  }
</script>

<div class="statistik-widget">
  {#if isLoading}
    <p>Laddar statistik...</p>
  {:else if error}
    <p class="error">{error}</p>
  {:else}
    <div class="kpi-box">
      <div>
        <strong>{stats.upcoming_shifts}</strong>
        <span>Kommande pass</span>
      </div>
      <div>
        <strong>{stats.resources}</strong>
        <span>Resurser</span>
      </div>
      <div>
        <strong>{stats.coverage}%</strong>
        <span>Täckningsgrad</span>
      </div>
      <div>
        <strong>{stats.open_shifts}</strong>
        <span>Lediga pass</span>
      </div>
    </div>
  {/if}
</div>

<style>
.statistik-widget {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 1.5rem;
}
.kpi-box {
  display: flex;
  gap: 2rem;
}
.kpi-box > div {
  background: #f6f8fa;
  border-radius: 8px;
  padding: 1rem 2rem;
  min-width: 120px;
  text-align: center;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.kpi-box strong {
  font-size: 2rem;
  color: #0073aa;
}
.kpi-box span {
  display: block;
  font-size: 0.95rem;
  color: #444;
  margin-top: 0.25rem;
}
.error {
  color: #c00;
}
</style>