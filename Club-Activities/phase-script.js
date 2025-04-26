



const API_URL = "https://jsonplaceholder.typicode.com/posts";


let clubs = []; 
let currentPage = 1;
const itemsPerPage = 5;
let filteredClubs = [];


const clubList = document.getElementById('club-list');
const loadingIndicator = document.getElementById('loading');
const errorMessage = document.getElementById('error-message');
const searchInput = document.getElementById('search');
const sortSelect = document.getElementById('sort');
const paginationContainer = document.getElementById('pagination');
const detailView = document.getElementById('detail-view');
const form = document.getElementById('club-form');


async function fetchClubs() {
    try {
        showLoading();
        const response = await fetch(API_URL);
        if (!response.ok) throw new Error('Network response was not ok');
        const data = await response.json();
        clubs = data.slice(0, 30); 
        filteredClubs = clubs;
        renderClubs();
    } catch (error) {
        showError(error.message);
    } finally {
        hideLoading();
    }
}


function showLoading() {
    loadingIndicator.style.display = 'block';
}

function hideLoading() {
    loadingIndicator.style.display = 'none';
}

function showError(message) {
    errorMessage.textContent = message;
    errorMessage.style.display = 'block';
}


function renderClubs() {
    clubList.innerHTML = '';
    const paginatedClubs = paginate(filteredClubs, currentPage, itemsPerPage);

    paginatedClubs.forEach(club => {
        const item = document.createElement('div');
        item.className = 'club-item';
        item.innerHTML = `
            <h3>${club.title}</h3>
            <p>${club.body.substring(0, 50)}...</p>
            <button onclick="showDetails(${club.id})">View Details</button>
        `;
        clubList.appendChild(item);
    });

    renderPagination();
}


function paginate(array, pageNumber, pageSize) {
    const start = (pageNumber - 1) * pageSize;
    return array.slice(start, start + pageSize);
}

function renderPagination() {
    paginationContainer.innerHTML = '';
    const totalPages = Math.ceil(filteredClubs.length / itemsPerPage);

    for (let i = 1; i <= totalPages; i++) {
        const btn = document.createElement('button');
        btn.textContent = i;
        btn.className = (i === currentPage) ? 'active' : '';
        btn.addEventListener('click', () => {
            currentPage = i;
            renderClubs();
        });
        paginationContainer.appendChild(btn);
    }
}


searchInput.addEventListener('input', (e) => {
    const query = e.target.value.toLowerCase();
    filteredClubs = clubs.filter(club => club.title.toLowerCase().includes(query));
    currentPage = 1;
    renderClubs();
});


sortSelect.addEventListener('change', (e) => {
    const value = e.target.value;
    if (value === 'asc') {
        filteredClubs.sort((a, b) => a.title.localeCompare(b.title));
    } else if (value === 'desc') {
        filteredClubs.sort((a, b) => b.title.localeCompare(a.title));
    }
    renderClubs();
});


function showDetails(id) {
    const club = clubs.find(c => c.id === id);
    if (club) {
        detailView.innerHTML = `
            <h2>${club.title}</h2>
            <p>${club.body}</p>
        `;
    }
}


form.addEventListener('submit', (e) => {
    e.preventDefault(); 
    validateForm();
});

function validateForm() {
    const nameInput = document.getElementById('club-name');
    const descriptionInput = document.getElementById('club-description');
    let valid = true;

    clearFormErrors();

    if (nameInput.value.trim() === '') {
        showFormError(nameInput, 'Club name is required');
        valid = false;
    }
    if (descriptionInput.value.trim().length < 10) {
        showFormError(descriptionInput, 'Description must be at least 10 characters');
        valid = false;
    }

    if (valid) {
        alert('Form is valid! (But not submitted)');
    }
}

function showFormError(input, message) {
    const errorElement = document.createElement('div');
    errorElement.className = 'form-error';
    errorElement.textContent = message;
    input.parentElement.appendChild(errorElement);
}

function clearFormErrors() {
    const errors = document.querySelectorAll('.form-error');
    errors.forEach(error => error.remove());
}


fetchClubs();
