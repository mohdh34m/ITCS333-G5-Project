document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('form');
    
    if (form) {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            
            let errors = [];
            
            const groupName = document.getElementById('group-name');
            if (groupName.value.trim().length < 4) {
                errors.push("Group Name must be at least 4 characters long.");
            }
            
            const subject = document.getElementById('subject');
            if (subject.value === "") {
                errors.push("You must select a Subject.");
            }
            
            const course = document.getElementById('course');
            if (!/^[A-Z]{3,6}\d{2,4}$/i.test(course.value.trim())) {
                errors.push("Course Code must look like 'MATH102' (3-6 letters + 2-4 numbers).");
            }
            
            const description = document.getElementById('description');
            if (description.value.trim().length < 10) {
                errors.push("Description must be at least 10 characters long.");
            }
            
            const location = document.getElementById('location');
            if (location.value.trim().length < 3) {
                errors.push("Location must be at least 3 characters long.");
            }
            
            const date = document.getElementById('date');
            const selectedDate = new Date(date.value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            if (selectedDate < today) {
                errors.push("Date must be today or a future date.");
            }
            
            const time = document.getElementById('time');
            if (!time.value) {
                errors.push("Please select a time.");
            }
            
            const members = document.getElementById('members');
            if (parseInt(members.value) < 4) {
                errors.push("Maximum members must be at least 4.");
            }
            
            if (errors.length > 0) {
                alert(errors.join("\n"));
            } else {
                console.log("Form is valid! Submitting...");
                form.submit();
            }
        });
    }
});