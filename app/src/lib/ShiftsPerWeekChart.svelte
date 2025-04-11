  <script>
  import { onMount } from 'svelte';
  export let orgId = null;
  import apiFetch from '../api.js';

  let chartData = [];
  let isLoading = true;
  let error = '';

  // Fetch weekly shift summary for the selected org
  async function fetchChartData() {
    if (!orgId) {
      chartData = [];
      return;
    }
    isLoading = true;
    error = '';
    try {
      const data = await apiFetch({ path: `wp-schedule-plugin/v1/shifts/summary?group_by=week&org_id=${orgId}` });
      chartData = data;
    } catch (e) {
      error = 'Kunde inte hämta stapeldiagram-data.';
      console.error(e);
    } finally {
      isLoading = false;
    }
  }

  // Fetch on mount and whenever orgId changes
  $: if (orgId) {
    fetchChartData();
  }

  // Find max for scaling bars
  $: maxShifts = chartData.length ? Math.max(...chartData.map(d => d.shifts)) : 1;
</script>

<div class="shifts-per-week-chart">
  {#if isLoading}
    <p>Laddar diagram...</p>
  {:else if error}
    <p class="error">{error}</p>
  {:else if chartData.length === 0}
    <p>Ingen data att visa.</p>
  {:else}
    <div class="bar-chart">
      {#each chartData as week}
        <div class="bar-group">
          <div
            class="bar"
            style="height: {Math.round((week.shifts / maxShifts) * 120)}px"
            title="{week.week}: {week.shifts} pass"
          ></div>
          <div class="bar-label">{week.week}</div>
        </div>
      {/each}
    </div>
  {/if}
</div>

<style>
.shifts-per-week-chart {
  margin-top: 1rem;
}
.bar-chart {
  display: flex;
  align-items: flex-end;
  gap: 1rem;
  height: 140px;
  padding-bottom: 1.5rem;
}
.bar-group {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 40px;
}
.bar {
  width: 32px;
  background: #0073aa;
  border-radius: 4px 4px 0 0;
  transition: height 0.3s;
  margin-bottom: 0.5rem;
}
.bar-label {
  font-size: 0.85rem;
  color: #444;
  text-align: center;
  word-break: break-all;
}
.error {
  color: #c00;
}
</style>