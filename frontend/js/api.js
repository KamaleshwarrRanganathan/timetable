// api.js - Helper to simulate backend requests using localStorage
// This allows the app to work without a Node.js/MySQL server for the mini-project demo.

// Send all traffic to our new dedicated local server running on port 8000
const API_BASE_URL = 'http://localhost:8000/api/index.php?route=';

const api = {
    async request(endpoint, options = {}) {
        let url = `${API_BASE_URL}${endpoint}`;
        
        // If the endpoint already has query parameters (e.g. /profile?user_id=1)
        // Ensure we properly append them without breaking the route parameter
        if (endpoint.includes('?')) {
            const parts = endpoint.split('?');
            url = `${API_BASE_URL}${parts[0]}&${parts[1]}`;
        }

        const headers = { 'Content-Type': 'application/json' };
        
        const token = localStorage.getItem('token');
        if (token) headers['Authorization'] = `Bearer ${token}`;

        const config = {
            method: options.method || 'GET',
            headers,
        };

        if (options.body) {
            config.body = options.body;
        }

        try {
            const response = await fetch(url, config);
            const data = await response.json();
            
            if (!response.ok) {
                // Return backend error message
                throw new Error(data.message || data.error || 'Server error occurred');
            }
            
            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw new Error(`Failed to fetch from ${url}. Is Apache running?`);
        }
    },

    get(endpoint) { return this.request(endpoint, { method: 'GET' }); },
    post(endpoint, body) { return this.request(endpoint, { method: 'POST', body: JSON.stringify(body) }); },
    put(endpoint, body) { return this.request(endpoint, { method: 'PUT', body: JSON.stringify(body) }); },
    delete(endpoint) { return this.request(endpoint, { method: 'DELETE' }); }
};

// Protect routes
function checkAuth() {
    const token = localStorage.getItem('token');
    const userRole = localStorage.getItem('userRole');
    
    if (!token && !window.location.pathname.endsWith('index.html') && window.location.pathname !== '/') {
        window.location.href = 'index.html';
    }
    
    // Role based protection
    const path = window.location.pathname;
    if (path.includes('admin-dashboard') || path.includes('add-subject') || path.includes('add-teacher')) {
        if (userRole !== 'admin' && userRole !== 'hod') {
            window.location.href = 'timetable.html';
            return { token, userRole };
        }
    }

    return { token, userRole };
}

function logout() {
    localStorage.clear();
    window.location.href = 'index.html';
}

// Start simulation notifications if logged in
function checkSimulatedNotifications() {
    const user_id = localStorage.getItem('userId');
    if (user_id) {
        setInterval(async () => {
            try {
                const notifications = await api.get(`/notifications?user_id=${user_id}`);
                const unread = notifications.filter(n => !n.is_read);
                if (unread.length > 0) {
                    if (Notification.permission === 'granted') {
                        new Notification('New Scheduling Update', { body: unread[0].message });
                    }
                }
            } catch (ignore) {}
        }, 10000);
    }
}

if (Notification.permission !== 'denied' && Notification.permission !== 'granted') {
    Notification.requestPermission();
}
checkSimulatedNotifications();
checkAuth();
