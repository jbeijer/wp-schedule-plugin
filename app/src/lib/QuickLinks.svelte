<script>
  // TODO: Replace with real navigation or event handlers as routing is implemented
  function createShift() {
    // Placeholder: Open create shift dialog or navigate
    alert('Skapa nytt pass – funktionalitet ej implementerad.');
  }
  function addMember() {
    alert('Lägg till medlem – funktionalitet ej implementerad.');
  }
  function addResource() {
    alert('Lägg till resurs – funktionalitet ej implementerad.');
  }
  function viewSchedule() {
    alert('Visa schema – funktionalitet ej implementerad.');
  }
</script>

<div class="quick-links">
  <button on:click={createShift}>Skapa nytt pass</button>
  <button on:click={addMember}>Lägg till medlem</button>
  <button on:click={addResource}>Lägg till resurs</button>
  <button on:click={viewSchedule}>Visa schema</button>
</div>

<style>
.quick-links {
  display: flex;
  gap: 1rem;
  margin-bottom: 1rem;
}
.quick-links button {
  background: #0073aa;
  color: #fff;
  border: none;
  border-radius: 4px;
  padding: 0.6rem 1.2rem;
  font-size: 1rem;
  cursor: pointer;
  transition: background 0.2s;
}
.quick-links button:hover {
  background: #005177;
}
</style>