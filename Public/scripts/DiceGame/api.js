const API_URL = '/api/dice';

export const sendAction = async (action, data = {}) => {
    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action, ...data})
        });
        return await response.json();
    } catch (error) {
        console.error("Network error:", error);
        return {success: false, error};
    }
};