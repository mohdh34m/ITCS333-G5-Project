import { fetchAllGroups } from './api.js';

const form = document.getElementById('edit-form');

const params = new URLSearchParams(window.location.search);
const groupId = params.get('id');

async function loadGroup() {
  const groups = await fetchAllGroups();
  const group = groups.find(g => g.id == groupId);

  if (!group) {
    alert("Group not found");
    window.location.href = "index.html";
    return;
  }

  document.getElementById('group-id').value = group.id;
  document.getElementById('title').value = group.title;
  document.getElementById('subject').value = group.subject;
  document.getElementById('location').value = group.location;
  document.getElementById('date').value = group.date;
  document.getElementById('time').value = group.time;
  document.getElementById('members').value = group.max_members;
  document.getElementById('description').value = group.description;
  document.getElementById('course').value = group.course;
}

form.addEventListener('submit', async (e) => {
  e.preventDefault();

  const data = {
    id: document.getElementById('group-id').value,
    title: document.getElementById('title').value,
    subject: document.getElementById('subject').value,
    location: document.getElementById('location').value,
    date: document.getElementById('date').value,
    time: document.getElementById('time').value,
    members: document.getElementById('members').value,
    description: document.getElementById('description').value,
    course: document.getElementById('course').value,
  };

  try {
    const res = await fetch('https://7cbed157-b5cc-4909-a26d-40b57225158a-00-1m4vxcbdg8ocm.picard.replit.dev/api/edit_group.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });

    const result = await res.json();
    alert(result.message || "Updated");
    window.location.href = "details.html?id=" + data.id;
  } catch (err) {
    alert("Update failed");
    console.error(err);
  }
});

loadGroup();
