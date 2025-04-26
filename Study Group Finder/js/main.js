import { fetchGroups } from './api.js'

let allGroups = []

async function fetchData() {
    try {
        const res = await fetchGroups()
        allGroups = res
        renderGroups()
    }
    catch (error) {
        console.error('Error loading groups:', error);
    }
}
fetchData()

function renderGroups() {
    let list = document.querySelector('.study-cards')

    allGroups.forEach(item => {
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
                    <a href="details.html" role="button" class="secondary view-btn">View Details</a>
            `
        group.classList.add("card")
        group.innerHTML = study_group
        list.appendChild(group);
    });

    let selectSubject = document.getElementById("subjectSelect")

    selectSubject.addEventListener('change', (e) => {

        list.innerHTML = "";

        if (e.target.value === "all") {
            renderGroups()
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
                        <a href="details.html" role="button" class="secondary view-btn">View Details</a>
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
            renderGroups()
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
                        <a href="details.html" role="button" class="secondary view-btn">View Details</a>
                `
            group.classList.add("card")
            group.innerHTML = study_group
            list.appendChild(group);
        });
    })
}

