<script>
    import ScheduleComponent from "./lib/ScheduleComponent.svelte";

    // Hämta data från WordPress
    // Default to an empty structure if data is not available or in SSR context
    const scheduleData =
        typeof window !== "undefined" &&
        typeof window.wpScheduleFrontendData !== "undefined" &&
        window.wpScheduleFrontendData?.scheduleData
            ? window.wpScheduleFrontendData.scheduleData
            : { events: [], settings: {} };
</script>

<main>
    <div class="schedule-container">
        <h2>Schema</h2>
        <!-- Pass the potentially empty data to the component -->
        <ScheduleComponent data={scheduleData} />
    </div>
</main>

<style>
    .schedule-container {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    h2 {
        color: #333;
        margin-top: 0;
        margin-bottom: 20px;
    }
</style>