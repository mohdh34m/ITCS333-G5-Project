import { fetchGroups } from './api.js'

let allGroups = []
let currentPage = 1
let groupsPerPage = 8

const list = document.querySelector('.study-cards')

async function fetchData() {
    try {
        const res = await fetchGroups(currentPage, groupsPerPage);
        allGroups = res.data;
        renderGroups(allGroups);
    } catch (error) {
        console.error('Error loading groups:', error);
    }
}

fetchData()

function renderGroups(groups) {
    list.innerHTML = "";

    groups.forEach(renderSingleGroup);

    document.getElementById('prevBtn').disabled = currentPage === 1;
    document.getElementById('nextBtn').disabled = groups.length < groupsPerPage;
    document.getElementById("page").innerText = currentPage;
}

function renderSingleGroup(item) {
    let group = document.createElement("article");
    group.classList.add("card");
    group.innerHTML = `
        <h1>${item.title}</h1>
        <p><i class="bi bi-book"></i> ${item.subject}</p>
        <p class="desc">${item.description.split(" ").slice(0, 3).join(" ")}...</p>
        <p><i class="bi bi-geo-alt"></i> <span>${item.location}</span></p>
        <p><i class="bi bi-calendar"></i> <span>${item.date}</span></p>
        <p><i class="bi bi-alarm"></i> <span>${item.time}</span></p>
        <p><i class="bi bi-people"></i> <span>${item.joined_members} / ${item.max_members} Members</span></p>
        <span class="badge">${item.course}</span>
        <a href="details.html?id=${item.id}" role="button" class="secondary view-btn">View Details</a>
    `;
    list.appendChild(group);
}


document.getElementById("nextBtn").addEventListener('click', () => {
    currentPage++;
    fetchData();
});

document.getElementById("prevBtn").addEventListener('click', () => {
    if (currentPage > 1) {
        currentPage--;
        fetchData();
    }
});

let selectSubject = document.getElementById("subjectSelect");

selectSubject.addEventListener('change', (e) => {
    list.innerHTML = "";

    if (e.target.value === "all") {
        renderGroups(allGroups);
        return;
    }

    const filteredGroups = allGroups.filter(group => group.subject === e.target.value);

    filteredGroups.forEach(renderSingleGroup);
});

let search = document.getElementById("search");

search.addEventListener('input', (e) => {
    list.innerHTML = "";

    const query = e.target.value.trim().toLowerCase();

    if (query === "") {
        renderGroups(allGroups);
        return;
    }

    const filteredGroups = allGroups.filter(group =>
        group.subject.toLowerCase().includes(query) ||
        group.title.toLowerCase().includes(query)
    );

    filteredGroups.forEach(renderSingleGroup);
});

let sort = document.getElementById("sort");

sort.addEventListener('change', (e) => {
    list.innerHTML = "";

    let filteredGroups = [...allGroups];

    if (e.target.value === "title-asc") {
        filteredGroups.sort((a, b) => a.title.localeCompare(b.title));
    } else if (e.target.value === "title-desc") {
        filteredGroups.sort((a, b) => b.title.localeCompare(a.title));
    } else if (e.target.value === "lowest-members") {
        filteredGroups.sort((a, b) => a.joined_members - b.joined_members);
    } else if (e.target.value === "highest-members") {
        filteredGroups.sort((a, b) => b.joined_members - a.joined_members);
    } else if (e.target.value === "date-old") {
        filteredGroups.sort((a, b) => new Date(a.date) - new Date(b.date));
    } else if (e.target.value === "date-new") {
        filteredGroups.sort((a, b) => new Date(b.date) - new Date(a.date));
    } else if (e.target.value === "date-coming") {
        const today = new Date();
        filteredGroups = filteredGroups
            .filter(group => new Date(group.date) >= today)
            .sort((a, b) => new Date(a.date) - new Date(b.date));
    } else if (e.target.value === "most") {
        renderGroups(allGroups);
        return;
    }

    filteredGroups.forEach(renderSingleGroup);
});
