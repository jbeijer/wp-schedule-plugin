<script>
  import StatistikWidget from './StatistikWidget.svelte';
  import KommandePassList from './KommandePassList.svelte';
  import ShiftsPerWeekChart from './ShiftsPerWeekChart.svelte';
  import QuickLinks from './QuickLinks.svelte';
  import OrganizationSelector from './OrganizationSelector.svelte';

  import { selectedOrgId, organizations } from './stores.js';

  // Compute the selected organization object
  $: selectedOrg = $organizations.find(org =>
    // Support both org.id and org.org_id for compatibility
    (org.id ?? org.org_id) == $selectedOrgId
  );
  // Log $selectedOrgId and selectedOrg every time they change
  $: {
    console.log('Dashboard: $selectedOrgId =', $selectedOrgId);
    console.log('Dashboard: selectedOrg =', selectedOrg);
  }
</script>

<!-- Dashboard Main Layout -->
<div class="dashboard">
  <!-- Selected organization name or "Ingen organisation vald" -->
  {#if $selectedOrgId && selectedOrg}
    <h2 class="dashboard__org-heading">{selectedOrg.name}</h2>
  {:else}
    <h2 class="dashboard__org-heading dashboard__org-heading--empty">Ingen organisation vald</h2>
  {/if}
  <!-- Organization Selector at the top -->
  <OrganizationSelector />

  {#if $selectedOrgId && selectedOrg}
    <!-- KPI/Statistik Widgets -->
    <section class="dashboard__stats">
      <StatistikWidget orgId={$selectedOrgId} />
    </section>

    <!-- Quick Links -->
    <section class="dashboard__quicklinks">
      <QuickLinks />
    </section>

    <!-- Shifts Per Week Chart -->
    <section class="dashboard__chart">
      <ShiftsPerWeekChart orgId={$selectedOrgId} />
    </section>

    <!-- Upcoming Shifts Table -->
    <section class="dashboard__upcoming">
      <KommandePassList orgId={$selectedOrgId} />
    </section>
  {/if}
</div>

<style>
.dashboard {
  display: flex;
  flex-direction: column;
  gap: 2rem;
  padding: 2rem;
}
.dashboard__org-heading {
  font-size: 2rem;
  font-weight: bold;
  margin-bottom: 0.5rem;
  color: #1a237e;
}
.dashboard__org-heading--empty {
  color: #b00;
  font-size: 1.3rem;
  font-weight: normal;
}
.dashboard__stats {
  /* KPI widgets styling */
}
.dashboard__quicklinks {
  /* Quick links styling */
}
.dashboard__chart {
  /* Chart section styling */
}
.dashboard__upcoming {
  /* Upcoming shifts table styling */
}
</style>