document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('form');

  form.addEventListener('submit', async function (event) {
    event.preventDefault();

    let errors = [];

    const groupName = document.getElementById('group-name').value;
    if (groupName.trim().length < 4) {
        errors.push("Group Name must be at least 4 characters long.");
    }

    const subject = document.getElementById('subject').value;
    if (subject === "") {
        errors.push("You must select a Subject.");
    }

    const course = document.getElementById('course').value;
    if (!/^[A-Z]{3,6}\d{2,4}$/i.test(course.trim())) {
        errors.push("Course Code must look like 'MATH102' (3-6 letters + 2-4 numbers).");
    }

    const description = document.getElementById('description').value;
    if (description.trim().length < 10) {
        errors.push("Description must be at least 10 characters long.");
    }

    const location = document.getElementById('location').value;
    if (location.trim().length < 3) {
        errors.push("Location must be at least 3 characters long.");
    }

    const date = document.getElementById('date').value;
    const selectedDate = new Date(date);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    if (selectedDate < today) {
        errors.push("Date must be today or a future date.");
    }

    const time = document.getElementById('time').value;
    if (!time) {
        errors.push("Please select a time.");
    }

    const members = document.getElementById('members').value;
    if (parseInt(members) < 4) {
        errors.push("Minimum members must be at least 4.");
    }

    const title = document.getElementById('group-name').value;

    const data = {
      title,
      subject,
      course,
      description,
      location,
      date,
      time,
      members
    };

    try {
      const res = await fetch("https://7cbed157-b5cc-4909-a26d-40b57225158a-00-1m4vxcbdg8ocm.picard.replit.dev/api/create_group.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data)
      });

      const result = await res.json();
      alert(result.message);
      window.location.href = "index.html";
    } catch (error) {
        alert("Failed to create group: " + error);
        console.log(error)
    }
  });
});
