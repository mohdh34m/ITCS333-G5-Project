import { fetchDetailGroup } from "./api.js";

async function loadGroupDetails() {
  const params = new URLSearchParams(window.location.search);
  const groupId = params.get('id');

  const container = document.querySelector('.details-section');

  if (!groupId) {
    container.innerHTML = '<p>No group selected.</p>';
    return;
  }

  try {
    const group = await fetchDetailGroup(groupId);
    console.log("Group details:", group);

    if (group.error) {
      container.innerHTML = `<p>${group.error}</p>`;
      return;
    }

    container.innerHTML = `
      <h1>${group.title}</h1>
      <p><i class="bi bi-book"></i> Subject: ${group.subject}</p>
      <p><i class="bi bi-geo-alt"></i> Location: ${group.location}</p>
      <p><i class="bi bi-calendar"></i> Date: ${group.date}</p>
      <p><i class="bi bi-alarm"></i> Time: ${group.time}</p>
      <p><i class="bi bi-people"></i> Members: ${group.joined_members} / ${group.members}</p>
      <p>Description: ${group.description}</p>
      <span class="badge">${group.course}</span>

      <footer style="margin-top: 1rem;">
        <button id="joinBtn" class="primary"><i class="bi bi-person-plus"></i> Join Group</button>
        <a href="edit-form.html?id=${group.id}" role="button" class="contrast"><i class="bi bi-pencil"></i> Edit</a>
        <button id="deleteBtn" class="secondary"><i class="bi bi-trash"></i> Delete</button>
      </footer>
    `;

    const deleteBtn = document.getElementById('deleteBtn');
    deleteBtn.addEventListener('click', async () => {
      if (confirm("Are you sure you want to delete this group?")) {
        try {
          const res = await fetch(`https://7cbed157-b5cc-4909-a26d-40b57225158a-00-1m4vxcbdg8ocm.picard.replit.dev/api/delete_group.php?id=${groupId}`, {
            method: 'DELETE'
          });
          const result = await res.json();
          alert(result.message);
          window.location.href = "index.html";
        } catch (error) {
          alert("Something went wrong");
        }
      }
    });
  } catch (error) {
    console.error('Error:', error);
  }

  document.getElementById('comment-form').addEventListener('submit', async (e) => {
    e.preventDefault();

    const textarea = document.getElementById('comment');
    const commentText = textarea.value.trim();

    if (!commentText) return alert("Please write a comment.");

    const payload = {
      user_id: "Anonymous",
      group_id: groupId,
      comment: commentText
    };

    try {
      const res = await fetch('https://7cbed157-b5cc-4909-a26d-40b57225158a-00-1m4vxcbdg8ocm.picard.replit.dev/api/create_comment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });

      const result = await res.json();

      if (result.message) {
        textarea.value = '';
        loadComments(groupId);
      } else {
        alert("Error: " + result.error);
      }
    } catch (err) {
      console.error(err);
      alert("Failed to post comment");
    }
  });



  async function loadComments(groupId) {
    const res = await fetch(`https://7cbed157-b5cc-4909-a26d-40b57225158a-00-1m4vxcbdg8ocm.picard.replit.dev/api/get_comments.php?group_id=${groupId}`);
    const comments = await res.json();

    const container = document.getElementById('comments-container');
    container.innerHTML = '';

    if (comments.length === 0) {
      container.innerHTML = '<p>No comments yet.</p>';
      return;
    }

    comments.forEach(comment => {
      container.innerHTML += `
      <article>
        <header>
          <strong>${comment.name}</strong> &bull; <small>${new Date(comment.created_at).toLocaleDateString()}</small>
        </header>
        <p>${comment.comment}</p>
      </article>
    `;
    });
  }

  const joinBtn = document.getElementById('joinBtn');
  const modal = document.getElementById('joinModal');
  const cancelBtn = document.getElementById('cancelJoin');

  joinBtn.addEventListener('click', () => {
    modal.classList.remove('hidden');
  });

  cancelBtn.addEventListener('click', () => {
    modal.classList.add('hidden');
  });


  document.getElementById('joinForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const name = document.getElementById('joinName').value.trim();
    const studentId = document.getElementById('studentId').value.trim();

    if (!name || !studentId) return alert("Please fill in both fields");

    try {
      const res = await fetch('https://7cbed157-b5cc-4909-a26d-40b57225158a-00-1m4vxcbdg8ocm.picard.replit.dev/api/join_group.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          name,
          student_id: studentId,
          group_id: groupId
        })
      });

      const result = await res.json();
      if (result.message) {
        alert(result.message);
        modal.classList.add('hidden');
        loadGroupDetails();
      } else {
        alert("Error: " + result.error);
      }
    } catch (err) {
      console.error(err);
      alert("Failed to join group");
    }
  });
  loadComments(groupId);
}

loadGroupDetails();
