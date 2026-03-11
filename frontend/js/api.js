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
    const role = localStorage.getItem('userRole');
    if (user_id) {
        const fetchUpdates = async () => {
            try {
                const notifications = await api.get(`/notifications?user_id=${user_id}`);
                const unread = notifications.filter(n => {
                    if (role === 'hod' && n.type === 'od_request' && n.od_status === 'Pending') return true;
                    if (!n.is_read) return true;
                    return false;
                });
                
                // Update UI Badge
                if (unread.length > 0) {
                    // Try to find Notif link by ID
                    let notifLink = document.getElementById('notifNav');
                    
                    // If not found by ID, try to find by link text
                    if (!notifLink) {
                        const links = document.querySelectorAll('.nav-links a');
                        for (let a of links) {
                            if (a.textContent.includes('Notification') || a.href.includes('notifications.html')) {
                                notifLink = a;
                                break;
                            }
                        }
                    }

                    if (notifLink) {
                        // Keep the original text but add count if not already present
                        const baseText = notifLink.textContent.split(' (')[0];
                        notifLink.textContent = `${baseText} (${unread.length})`;
                        notifLink.style.color = 'red';
                        notifLink.style.fontWeight = 'bold';
                    }

                    if (Notification.permission === 'granted' && unread.length === 1 && !localStorage.getItem('lastNotifMsg')) {
                        new Notification('New Scheduling Update', { body: unread[0].message });
                        localStorage.setItem('lastNotifMsg', unread[0].message); // naive debounce
                    }
                } else {
                    // Reset if 0
                    let notifLink = document.getElementById('notifNav');
                    if (!notifLink) {
                        const links = document.querySelectorAll('.nav-links a');
                        for (let a of links) {
                            if (a.textContent.includes('Notification') || a.href.includes('notifications.html')) {
                                notifLink = a;
                                break;
                            }
                        }
                    }
                    if (notifLink) {
                        const baseText = notifLink.textContent.split(' (')[0];
                        notifLink.textContent = baseText;
                        notifLink.style.color = '';
                        notifLink.style.fontWeight = '';
                    }
                }
            } catch (ignore) {}
        };

        // Fetch immediately then poll every 10s
        fetchUpdates();
        setInterval(fetchUpdates, 10000);
    }
}

if (window.Notification && Notification.permission !== 'denied' && Notification.permission !== 'granted') {
    window.Notification.requestPermission();
}
checkSimulatedNotifications();
checkAuth();
