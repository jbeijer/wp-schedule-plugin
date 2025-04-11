<script>
  import { onMount } from 'svelte';
  import { createEventDispatcher } from 'svelte';
  import { FullCalendar } from 'fullcalendar-svelte'; // Use named import
  import dayGridPlugin from '@fullcalendar/daygrid';
  import timeGridPlugin from '@fullcalendar/timegrid';
  import interactionPlugin from '@fullcalendar/interaction';
  import apiFetch from '../api.js'; // Use default import and add .js extension
  import { t } from 'svelte-i18n'; // Assuming i18n setup provides t

  export let orgId;

  const dispatch = createEventDispatcher();

  let calendarOptions = {
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: 'timeGridWeek',
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,timeGridDay'
    },
    events: [], // Initially empty, will be fetched
    // locale: 'sv', // Optional: Set locale if needed
    // firstDay: 1, // Optional: Start week on Monday
    dateClick: handleDateClick,
    eventClick: handleEventClick,
    // Add other options as needed, e.g., eventDrop, eventResize
  };

  function handleDateClick(arg) {
    console.log('Date clicked:', arg.dateStr);
    // Potentially dispatch an event or open a modal to create a new shift
    // dispatch('dateSelect', { date: arg.dateStr });
  }

  function handleEventClick(arg) {
    console.log('Event clicked:', arg.event.id, arg.event.title);
    // Potentially dispatch an event or open a modal to edit the shift
    // dispatch('eventSelect', { shiftId: arg.event.id });
  }

  async function fetchShifts() {
    if (!orgId) return;
    console.log(`Fetching shifts for orgId: ${orgId}`);
    // TODO: Implement actual API call
    // try {
    //   const shifts = await apiFetch(`/organizations/${orgId}/shifts`); // Adjust API endpoint
    //   calendarOptions = { ...calendarOptions, events: mapShiftsToEvents(shifts) };
    // } catch (error) {
    //   console.error('Error fetching shifts:', error);
    //   // Handle error (e.g., show a message)
    // }
  }

  // Helper function to map API shift data to FullCalendar event objects
  // function mapShiftsToEvents(shifts) {
  //   return shifts.map(shift => ({
  //     id: shift.id,
  //     title: shift.title || 'Unnamed Shift', // Adjust based on your shift data structure
  //     start: shift.start_time,
  //     end: shift.end_time,
  //     // Add other properties like color, resourceId etc.
  //   }));
  // }

  onMount(() => {
    fetchShifts();
  });

  $: if (orgId) {
    fetchShifts();
  }

</script>

<div class="shift-calendar-container">
  <FullCalendar options={calendarOptions} />
</div>

<style>
  .shift-calendar-container {
    min-height: 600px; /* Ensure calendar has some height */
    /* Add any other necessary styling */
  }

  /* You might need to import FullCalendar's core CSS */
  /* @import '@fullcalendar/core/main.css'; */
  /* @import '@fullcalendar/daygrid/main.css'; */
  /* @import '@fullcalendar/timegrid/main.css'; */
  /* Check FullCalendar docs for correct CSS imports if needed */

</style>