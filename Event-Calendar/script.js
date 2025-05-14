// State management
const state = {
    events: [],
    currentPage: 1,
    eventsPerPage: 6,
    totalEvents: 0,
    searchQuery: '',
    categoryFilter: '',
    dateFilter: '',
    sortBy: '',
    editingEventId: null
};

// DOM elements
const elements = {
    eventsGrid: document.getElementById('events-grid'),
    statusMessage: document.getElementById('status-message'),
    searchInput: document.getElementById('search-input'),
    categoryFilter: document.getElementById('category-filter'),
    dateFilter: document.getElementById('date-filter'),
    sortEvents: document.getElementById('sort-events'),
    prevPage: document.getElementById('prev-page'),
    nextPage: document.getElementById('next-page'),
    addEventBtn: document.getElementById('add-event-btn'),
    eventForm: document.getElementById('event-form'),
    formTitle: document.getElementById('form-title'),
    submitBtn: document.getElementById('submit-btn'),
    cancelBtn: document.getElementById('cancel-btn'),
    eventsListing: document.getElementById('events-listing'),
    createEvent: document.getElementById('create-event'),
    eventDetail: document.getElementById('event-detail'),
    detailTitle: document.getElementById('detail-title'),
    detailDate: document.getElementById('detail-date'),
    detailLocation: document.getElementById('detail-location'),
    detailCategory: document.getElementById('detail-category'),
    detailDescription: document.getElementById('detail-description'),
    editBtn: document.getElementById('edit-btn'),
    deleteBtn: document.getElementById('delete-btn'),
    backBtn: document.getElementById('back-btn'),
    commentForm: document.getElementById('comment-form'),
    commentsList: document.getElementById('comments-list'),
    titleInput: document.getElementById('title'),
    dateInput: document.getElementById('date'),
    locationInput: document.getElementById('location'),
    categoryInput: document.getElementById('category'),
    descriptionInput: document.getElementById('description'),
    commentInput: document.getElementById('comment'),
    formErrors: document.getElementById('form-errors')
};

// API base URL
const API_URL = 'https://6e71b06b-16e0-4151-bf22-3ea5235dd595-00-3j7esfgdp6gf4.sisko.replit.dev/api';

// Sanitize input to prevent XSS
function sanitizeInput(input) {
    const div = document.createElement('div');
    div.textContent = input;
    return div.innerHTML;
}

// Show loading state
function showLoading() {
    elements.statusMessage.textContent = 'Loading events...';
    elements.statusMessage.classList.add('loading');
}

// Show error state
function showError(message) {
    elements.statusMessage.textContent = message;
    elements.statusMessage.classList.add('error');
}

// Clear status message
function clearStatus() {
    elements.statusMessage.textContent = '';
    elements.statusMessage.classList.remove('loading', 'error');
}

// Fetch events from API
async function fetchEvents() {
    showLoading();
    try {
        const params = new URLSearchParams({
            page: state.currentPage,
            search: sanitizeInput(state.searchQuery),
            category: state.categoryFilter,
            date: state.dateFilter,
            sort: state.sortBy
        });
        const response = await fetch(`${API_URL}/events?${params}`);
        if (!response.ok) throw new Error('Failed to fetch events');
        const data = await response.json();
        state.events = data.events;
        state.totalEvents = data.total;
        renderEvents();
    } catch (error) {
        showError('Error loading events: ' + error.message);
    }
}

// Render events
function renderEvents() {
    clearStatus();
    elements.eventsGrid.innerHTML = state.events.length
        ? state.events.map(event => `
            <article class="event-card" data-id="${event.id}">
                <h2>${sanitizeInput(event.title)}</h2>
                <p>Date: <time datetime="${event.date}">${new Date(event.date).toLocaleDateString()}</time></p>
                <p>Location: ${sanitizeInput(event.location || 'TBD')}</p>
                <p>Category: ${sanitizeInput(event.category.charAt(0).toUpperCase() + event.category.slice(1))}</p>
            </article>
        `).join('')
        : '<p>No events found.</p>';

    elements.prevPage.disabled = state.currentPage === 1;
    elements.nextPage.disabled = state.currentPage * state.eventsPerPage >= state.totalEvents;

    document.querySelectorAll('.event-card').forEach(card => {
        card.addEventListener('click', () => showEventDetail(card.dataset.id));
    });
}

// Show event detail view
async function showEventDetail(id) {
    try {
        const response = await fetch(`${API_URL}/events/${id}`);
        if (!response.ok) throw new Error('Failed to fetch event details');
        const event = await response.json();
        elements.detailTitle.textContent = event.title;
        elements.detailDate.textContent = new Date(event.date).toLocaleDateString();
        elements.detailLocation.textContent = event.location || 'TBD';
        elements.detailCategory.textContent = event.category.charAt(0).toUpperCase() + event.category.slice(1);
        elements.detailDescription.textContent = event.description || 'No description available';

        const commentsResponse = await fetch(`${API_URL}/events/${id}/comments`);
        if (!commentsResponse.ok) throw new Error('Failed to fetch comments');
        const comments = await commentsResponse.json();
        elements.commentsList.innerHTML = comments.length
            ? comments.map(comment => `<p>${sanitizeInput(comment.comment_text)}</p>`).join('')
            : '<p>No comments yet.</p>';

        elements.eventsListing.classList.add('hidden');
        elements.createEvent.classList.add('hidden');
        elements.eventDetail.classList.remove('hidden');
        elements.editBtn.dataset.id = id;
        elements.deleteBtn.dataset.id = id;
        window.location.hash = `event-detail/${id}`;
    } catch (error) {
        showError('Error loading event: ' + error.message);
    }
}

// Show create/edit event form
function showCreateEvent(eventId = null) {
    elements.eventForm.reset();
    elements.formErrors.innerHTML = '';
    elements.formTitle.textContent = eventId ? 'Edit Event' : 'Create New Event';
    elements.submitBtn.textContent = eventId ? 'Update Event' : 'Create Event';
    state.editingEventId = eventId;

    if (eventId) {
        fetch(`${API_URL}/events/${eventId}`)
            .then(response => response.json())
            .then(event => {
                elements.titleInput.value = event.title;
                elements.dateInput.value = event.date;
                elements.locationInput.value = event.location || '';
                elements.categoryInput.value = event.category;
                elements.descriptionInput.value = event.description || '';
            });
    }

    elements.eventsListing.classList.add('hidden');
    elements.eventDetail.classList.add('hidden');
    elements.createEvent.classList.remove('hidden');
    window.location.hash = 'create-event';
}

// Show main listing
function showMainListing() {
    elements.eventsListing.classList.remove('hidden');
    elements.createEvent.classList.add('hidden');
    elements.eventDetail.classList.add('hidden');
    window.location.hash = 'events-listing';
    fetchEvents();
}

// Validate form client-side
function validateForm() {
    const errors = [];
    if (!elements.titleInput.value.trim()) errors.push('Event title is required.');
    if (!elements.dateInput.value) errors.push('Date is required.');
    if (elements.dateInput.value && new Date(elements.dateInput.value) < new Date().setHours(0, 0, 0, 0)) {
        errors.push('Date cannot be in the past.');
    }
    if (elements.locationInput.value.length > 100) errors.push('Location must be under 100 characters.');
    if (!elements.categoryInput.value) errors.push('Category is required.');
    if (elements.descriptionInput.value.length > 500) errors.push('Description must be under 500 characters.');
    elements.formErrors.innerHTML = errors.length ? errors.map(err => `<p>${err}</p>`).join('') : '';
    return errors.length === 0;
}

// Real-time form validation
function setupRealTimeValidation() {
    const fields = ['title', 'date', 'location', 'category', 'description'];
    fields.forEach(field => {
        const input = elements.eventForm.querySelector(`#${field}`);
        input.addEventListener('blur', validateForm);
    });
}

// Event listeners
document.addEventListener('DOMContentLoaded', () => {
    setupRealTimeValidation();

    elements.searchInput.addEventListener('input', () => {
        state.searchQuery = elements.searchInput.value;
        state.currentPage = 1;
        fetchEvents();
    });

    elements.categoryFilter.addEventListener('change', () => {
        state.categoryFilter = elements.categoryFilter.value;
        state.currentPage = 1;
        fetchEvents();
    });

    elements.dateFilter.addEventListener('change', () => {
        state.dateFilter = elements.dateFilter.value;
        state.currentPage = 1;
        fetchEvents();
    });

    elements.sortEvents.addEventListener('change', () => {
        state.sortBy = elements.sortEvents.value;
        fetchEvents();
    });

    elements.prevPage.addEventListener('click', () => {
        if (state.currentPage > 1) {
            state.currentPage--;
            fetchEvents();
        }
    });

    elements.nextPage.addEventListener('click', () => {
        if (state.currentPage * state.eventsPerPage < state.totalEvents) {
            state.currentPage++;
            fetchEvents();
        }
    });

    elements.addEventBtn.addEventListener('click', () => showCreateEvent());

    elements.cancelBtn.addEventListener('click', showMainListing);

    elements.backBtn.addEventListener('click', showMainListing);

    elements.eventForm.addEventListener('submit', async e => {
        e.preventDefault();
        if (!validateForm()) return;

        const formData = {
            title: sanitizeInput(elements.titleInput.value),
            date: elements.dateInput.value,
            location: sanitizeInput(elements.locationInput.value) || null,
            category: elements.categoryInput.value,
            description: sanitizeInput(elements.descriptionInput.value) || null
        };

        try {
            const url = state.editingEventId ? `${API_URL}/events/${state.editingEventId}` : `${API_URL}/events`;
            const method = state.editingEventId ? 'PUT' : 'POST';
            const response = await fetch(url, {
                method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.errors?.join(', ') || 'Failed to save event');
            alert(state.editingEventId ? 'Event updated successfully!' : 'Event created successfully!');
            showMainListing();
        } catch (error) {
            elements.formErrors.innerHTML = `<p>Error: ${error.message}</p>`;
        }
    });

    elements.editBtn.addEventListener('click', () => showCreateEvent(elements.editBtn.dataset.id));

    elements.deleteBtn.addEventListener('click', async () => {
        if (!confirm('Are you sure you want to delete this event?')) return;
        try {
            const response = await fetch(`${API_URL}/events/${elements.deleteBtn.dataset.id}`, {
                method: 'DELETE'
            });
            if (!response.ok) throw new Error('Failed to delete event');
            alert('Event deleted successfully!');
            showMainListing();
        } catch (error) {
            showError('Error deleting event: ' + error.message);
        }
    });

    elements.commentForm.addEventListener('submit', async e => {
        e.preventDefault();
        const commentText = sanitizeInput(elements.commentInput.value.trim());
        if (!commentText) return;

        try {
            const response = await fetch(`${API_URL}/events/${elements.editBtn.dataset.id}/comments`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ comment_text: commentText })
            });
            if (!response.ok) throw new Error('Failed to add comment');
            elements.commentForm.reset();
            showEventDetail(elements.editBtn.dataset.id);
        } catch (error) {
            showError('Error adding comment: ' + error.message);
        }
    });

    window.addEventListener('hashchange', () => {
        const hash = window.location.hash.slice(1);
        elements.eventsListing.classList.add('hidden');
        elements.createEvent.classList.add('hidden');
        elements.eventDetail.classList.add('hidden');
        if (hash.startsWith('event-detail/')) {
            showEventDetail(hash.split('/')[1]);
        } else if (hash === 'create-event') {
            showCreateEvent();
        } else {
            showMainListing();
        }
    });

    // Initialize
    if (window.location.hash) {
        window.dispatchEvent(new Event('hashchange'));
    } else {
        fetchEvents();
    }
});