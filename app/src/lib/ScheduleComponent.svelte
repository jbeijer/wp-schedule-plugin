<script>
    import { createEventDispatcher } from 'svelte';

    /**
     * @typedef {object} ScheduleEvent
     * @property {number|string} id - Unique identifier for the event
     * @property {string} title - Title of the event
     * @property {string} date - Date of the event (YYYY-MM-DD)
     * @property {string} time - Time of the event (HH:MM)
     * @property {string} description - Description of the event
     */

    /**
     * @typedef {object} ScheduleSettings
     * @property {any} [key] - Placeholder for any settings properties
     */

    /**
     * @typedef {object} ScheduleData
     * @property {ScheduleEvent[]} events - Array of schedule events
     * @property {ScheduleSettings} settings - Schedule settings
     */

    /** @type {ScheduleData} */
    export let data = { events: [], settings: {} };
    export let isAdmin = false;

    const dispatch = createEventDispatcher();

    let newEvent = {
        title: '',
        date: '',
        time: '',
        description: ''
    };

    function addEvent() {
        // Basic validation
        if (!newEvent.title || !newEvent.date || !newEvent.time) {
            alert('Please fill in Title, Date, and Time for the new event.');
            return;
        }

        // Ensure events array exists
        const currentEvents = Array.isArray(data.events) ? data.events : [];

        // Add new event to the array
        const updatedEvents = [...currentEvents, {
            id: Date.now(), // Simple unique ID generation
            ...newEvent
        }];

        // Update the data prop (important for reactivity if passed down)
        data = {
            ...data,
            events: updatedEvents
        };

        // Reset the form
        newEvent = {
            title: '',
            date: '',
            time: '',
            description: ''
        };

        // Dispatch save event if in admin mode
        if (isAdmin) {
            // Dispatch the updated data object
            dispatch('save', data);
        }
    }

    function deleteEvent(idToDelete) {
         // Ensure events array exists
        const currentEvents = Array.isArray(data.events) ? data.events : [];

        // Filter out the event to delete
        const updatedEvents = currentEvents.filter(event => event.id !== idToDelete);

        // Update the data prop
        data = {
            ...data,
            events: updatedEvents
        };

        // Dispatch save event if in admin mode
        if (isAdmin) {
             // Dispatch the updated data object
            dispatch('save', data);
        }
    }
</script>

<div class="schedule-component">
    {#if isAdmin}
        <div class="event-form">
            <h3>Lägg till ny händelse</h3>
            <div class="form-row">
                <label for="event-title">Titel:</label>
                <input id="event-title" type="text" bind:value={newEvent.title} required />
            </div>

            <div class="form-row">
                <label for="event-date">Datum:</label>
                <input id="event-date" type="date" bind:value={newEvent.date} required />
            </div>

            <div class="form-row">
                <label for="event-time">Tid:</label>
                <input id="event-time" type="time" bind:value={newEvent.time} required />
            </div>

            <div class="form-row">
                <label for="event-description">Beskrivning:</label>
                <textarea id="event-description" bind:value={newEvent.description}></textarea>
            </div>

            <button class="add-button" on:click={addEvent}>Lägg till Händelse</button>
        </div>
    {/if}

    <div class="events-list">
        <h3>Schemalagda händelser</h3>

        {#if !data || !Array.isArray(data.events) || data.events.length === 0}
            <p>Inga händelser schemalagda.</p>
        {:else}
            <ul>
                {#each data.events as event (event.id)}
                    <li class="event-item">
                        <div class="event-header">
                            <strong>{event.title || 'Ingen titel'}</strong>
                            {#if isAdmin}
                                <button class="delete-button" on:click={() => deleteEvent(event.id)} aria-label="Ta bort {event.title}">Ta bort</button>
                            {/if}
                        </div>
                        <div class="event-details">
                            {#if event.date || event.time}
                                <span>{event.date || ''} {event.time || ''}</span>
                            {/if}
                            {#if event.description}
                                <p>{event.description}</p>
                            {/if}
                        </div>
                    </li>
                {/each}
            </ul>
        {/if}
    </div>
</div>

<style>
    .schedule-component {
        display: flex;
        flex-direction: column;
        gap: 20px; /* Space between form and list */
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; /* WP default font */
    }

    .event-form {
        background-color: #f6f7f7; /* Lighter WP background */
        padding: 20px;
        border: 1px solid #c3c4c7;
        border-radius: 4px;
        margin-bottom: 20px;
    }

    .event-form h3 {
        margin-top: 0;
        margin-bottom: 15px;
        font-size: 1.2em;
        color: #1d2327;
    }

    .form-row {
        margin-bottom: 15px;
    }

    label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600; /* Slightly bolder labels */
        color: #3c434a;
    }

    input[type="text"],
    input[type="date"],
    input[type="time"],
    textarea {
        width: 100%;
        padding: 8px 10px;
        border: 1px solid #8c8f94; /* WP input border */
        border-radius: 3px;
        box-sizing: border-box; /* Include padding and border in element's total width and height */
        background-color: #fff;
        color: #2c3338;
    }

    textarea {
        min-height: 100px;
        resize: vertical; /* Allow vertical resize */
    }

    .add-button {
        background-color: #2271b1; /* WP primary button blue */
        color: white;
        border: 1px solid #2271b1;
        padding: 8px 15px;
        border-radius: 3px;
        cursor: pointer;
        font-size: 1em;
        text-decoration: none;
        transition: background-color 0.1s ease-in-out;
    }
    .add-button:hover {
        background-color: #1e639a; /* Darker blue on hover */
    }

    .events-list h3 {
         margin-top: 0;
        margin-bottom: 15px;
        font-size: 1.2em;
        color: #1d2327;
    }

    .events-list ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .event-item {
        border: 1px solid #dcdcde; /* Lighter border for items */
        border-radius: 4px;
        margin-bottom: 10px;
        padding: 15px;
        background-color: #fff;
    }

    .event-header {
        display: flex;
        justify-content: space-between;
        align-items: center; /* Align items vertically */
        margin-bottom: 10px;
        border-bottom: 1px solid #e5e5e5; /* Separator */
        padding-bottom: 10px;
    }

     .event-header strong {
        font-size: 1.1em;
        color: #1d2327;
     }

    .delete-button {
        background-color: #d63638; /* WP delete red */
        color: white;
        border: 1px solid #d63638;
        padding: 4px 10px;
        border-radius: 3px;
        font-size: 0.9em; /* Slightly smaller */
        cursor: pointer;
        line-height: 1.5; /* Adjust line height */
        transition: background-color 0.1s ease-in-out;
    }
     .delete-button:hover {
        background-color: #b02a2c; /* Darker red on hover */
    }

    .event-details span {
        display: block;
        font-size: 0.9em;
        color: #50575e; /* WP secondary text color */
        margin-bottom: 5px;
    }

    .event-details p {
        margin: 0;
        font-size: 1em;
        color: #3c434a;
        line-height: 1.5;
    }
</style>