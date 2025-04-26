import { fetchGroups } from './api.js'

let allGroups = []
let currentPage = 1
let groupsPerPage = 8

async function fetchData() {
    try {
        const res = await fetchGroups()
        allGroups = res
        renderGroupsByPage(allGroups);
    }
    catch (error) {
        console.error('Error loading groups:', error);
    }
}
fetchData()

const list = document.querySelector('.study-cards');

function renderGroupsByPage(groups) {
    list.innerHTML = "";

    const start = (currentPage - 1) * groupsPerPage;
    const end = start + groupsPerPage;
    const paginatedGroups = groups.slice(start, end);

    paginatedGroups.forEach(item => {
        let group = document.createElement("article");
        group.classList.add("card");
        group.innerHTML = `
            <h1>${item.title}</h1>
            <p><i class="bi bi-book"></i> ${item.subject}</p>
            <p class="desc">${item.description}</p>
            <p><i class="bi bi-geo-alt"></i> <span>${item.location}</span></p>
            <p><i class="bi bi-calendar"></i> <span>${item.date}</span></p>
            <p><i class="bi bi-alarm"></i> <span>${item.time}</span></p>
            <p><i class="bi bi-people"></i> <span>${item.members} Members</span></p>
            <span class="badge">${item.courseCode}</span>
            <a href="details.html?id=${item.id}" role="button" class="secondary view-btn">View Details</a>
        `;
        list.appendChild(group);
    });

    document.getElementById('prevBtn').disabled = currentPage === 1;
    document.getElementById('nextBtn').disabled = currentPage >= Math.ceil(groups.length / groupsPerPage);
    document.getElementById("page").innerText = currentPage
}


document.getElementById("nextBtn").addEventListener('click', () => {
    if (currentPage < Math.ceil(allGroups.length / groupsPerPage)) {
        currentPage++;
        renderGroupsByPage(allGroups);
    }
});

document.getElementById("prevBtn").addEventListener('click', () => {
    if (currentPage > 1) {
        currentPage--;
        renderGroupsByPage(allGroups);
    }
});



let selectSubject = document.getElementById("subjectSelect")

selectSubject.addEventListener('change', (e) => {

    list.innerHTML = "";

    if (e.target.value === "all") {
        renderGroupsByPage(allGroups)
    }
    const filteredGroups = allGroups.filter(group => group.subject === e.target.value)

    filteredGroups.forEach(item => {
        let group = document.createElement("article");
        let study_group = `
                        <h1>${item.title}</h1>
                        <p><i class="bi bi-book"></i> ${item.subject}</p>
                        <p class="desc">${item.description}</p>
                        <p><i class="bi bi-geo-alt"></i> <span>${item.location}</span></p>
                        <p><i class="bi bi-calendar"></i> <span>${item.date}</span></p>
                        <p><i class="bi bi-alarm"></i> <span>${item.time}</span></p>
                        <p><i class="bi bi-people"></i> <span>${item.members} Members</span></p>
                        <span class="badge">${item.courseCode}</span>
                        <a href="details.html?id=${item.id}" role="button" class="secondary view-btn">View Details</a>
                `
        group.classList.add("card")
        group.innerHTML = study_group
        list.appendChild(group);
    });
})

let search = document.getElementById("search")

search.addEventListener('input', (e) => {

    list.innerHTML = "";

    if (e.target.value === "") {
        renderGroupsByPage(allGroups)
        return
    }

    const filteredGroups = allGroups.filter(group => group.subject.includes(e.target.value) || group.title.includes(e.target.value))
    filteredGroups.forEach(item => {
        let group = document.createElement("article");
        let study_group = `
                        <h1>${item.title}</h1>
                        <p><i class="bi bi-book"></i> ${item.subject}</p>
                        <p class="desc">${item.description}</p>
                        <p><i class="bi bi-geo-alt"></i> <span>${item.location}</span></p>
                        <p><i class="bi bi-calendar"></i> <span>${item.date}</span></p>
                        <p><i class="bi bi-alarm"></i> <span>${item.time}</span></p>
                        <p><i class="bi bi-people"></i> <span>${item.members} Members</span></p>
                        <span class="badge">${item.courseCode}</span>
                        <a href="details.html?id=${item.id}" role="button" class="secondary view-btn">View Details</a>
                `
        group.classList.add("card")
        group.innerHTML = study_group
        list.appendChild(group);
    });
})

let sort = document.getElementById("sort")

sort.addEventListener('change', (e) => {
    list.innerHTML = "";

    if (e.target.value == "title-asc") {
        let filteredGroups = allGroups.sort((a, b) => {
            return a.title.localeCompare(b.title);
        })
        renderSortedGroups(filteredGroups)
    } else if (e.target.value == "title-desc") {
        let filteredGroups = allGroups.sort((a, b) => {
            return b.title.localeCompare(a.title);
        })
        renderSortedGroups(filteredGroups)
    } else if (e.target.value == "lowest-members") {
        let filteredGroups = allGroups.sort((a, b) => {
            return a.members - b.members
        })
        renderSortedGroups(filteredGroups)
    } else if (e.target.value == "highest-members") {
        let filteredGroups = allGroups.sort((a, b) => {
            return b.members - a.members
        })
        renderSortedGroups(filteredGroups)
    } else if (e.target.value == "date-old") {
        let filteredGroups = allGroups.sort((a, b) => {
            return new Date(a.date) - new Date(b.date)
        });
        renderSortedGroups(filteredGroups)
    } else if (e.target.value == "date-new") {
        let filteredGroups = allGroups.sort((a, b) => {
            return new Date(b.date) - new Date(a.date)
        });
        renderSortedGroups(filteredGroups)
    } else if (e.target.value == "date-coming") {
        const today = new Date();
        const upcomingGroups = allGroups
            .filter(group => new Date(group.date) >= today)
            .sort((a, b) => new Date(a.date) - new Date(b.date));
        renderSortedGroups(upcomingGroups)
    } else if (e.target.value === "most") {
        renderGroupsByPage(allGroups)
    }


    function renderSortedGroups(Groups) {
        Groups.forEach(item => {
            let group = document.createElement("article");
            let study_group = `
                            <h1>${item.title}</h1>
                            <p><i class="bi bi-book"></i> ${item.subject}</p>
                            <p class="desc">${item.description}</p>
                            <p><i class="bi bi-geo-alt"></i> <span>${item.location}</span></p>
                            <p><i class="bi bi-calendar"></i> <span>${item.date}</span></p>
                            <p><i class="bi bi-alarm"></i> <span>${item.time}</span></p>
                            <p><i class="bi bi-people"></i> <span>${item.members} Members</span></p>
                            <span class="badge">${item.courseCode}</span>
                            <a href="details.html?id=${item.id}" role="button" class="secondary view-btn">View Details</a>
                    `
            group.classList.add("card")
            group.innerHTML = study_group
            list.appendChild(group);
        });
    }
})

const form = document.querySelector('form');

form.addEventListener('submit', function (event) {
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
        form.submit();
    }
});

