import { fetchGroups } from './api.js';

async function loadGroupDetails() {
  const params = new URLSearchParams(window.location.search);
  const groupId = params.get('id');

  const container = document.querySelector('.details-section');

  if (!groupId) {
    container.innerHTML = '<p>No group selected.</p>';
    return;
  }

  try {
    const groups = await fetchGroups();
    const group = groups.find(g => g.id == groupId);

    if (!group) {
      container.innerHTML = '<p>Group not found.</p>';
      return;
    }

    container.innerHTML = `
      <h1>${group.title}</h1>
      <p><i class="bi bi-book"></i> Subject: ${group.subject}</p>
      <p><i class="bi bi-geo-alt"></i> Location: ${group.location}</p>
      <p><i class="bi bi-calendar"></i> Date: ${group.date}</p>
      <p><i class="bi bi-alarm"></i> Time: ${group.time}</p>
      <p><i class="bi bi-people"></i> Members: ${group.members}</p>
      <p>Description: ${group.description}</p>
      <span class="badge">${group.courseCode}</span>
    `;
  } catch (error) {
    console.error('Error:', error);
    container.innerHTML = '<p>Something went wrong loading the group details.</p>';
  }
}

loadGroupDetails();
