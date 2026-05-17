async function getBookings() {
    const response = await fetch('/api/bookings');
    const bookings = await response.json();
    console.log(bookings);
    return bookings;
}

// Создать новое бронирование
async function createBooking(bookingData) {
    const response = await fetch('/api/bookings', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(bookingData)
    });
    
    if (response.ok) {
        const newBooking = await response.json();
        console.log('Created:', newBooking);
        return newBooking;
    } else if (response.status === 409) {
        console.error('Hall already booked at this time');
        throw new Error('Hall already booked');
    }
}

// Обновить бронирование
async function updateBooking(id, updateData) {
    const response = await fetch(`/api/bookings/${id}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(updateData)
    });
    
    if (response.ok) {
        const updated = await response.json();
        console.log('Updated:', updated);
        return updated;
    }
}

// Удалить бронирование
async function deleteBooking(id) {
    const response = await fetch(`/api/bookings/${id}`, {
        method: 'DELETE'
    });
    
    if (response.ok) {
        console.log('Deleted successfully');
        return true;
    }
    return false;
}

// Получить список клиентов
async function getClients() {
    const response = await fetch('/api/clients');
    return await response.json();
}

// Получить список тренеров
async function getTrainers() {
    const response = await fetch('/api/trainers');
    return await response.json();
}

// Получить список залов
async function getHalls() {
    const response = await fetch('/api/halls');
    return await response.json();
}

// Получить список тренировок
async function getWorkouts() {
    const response = await fetch('/api/workouts');
    return await response.json();
}