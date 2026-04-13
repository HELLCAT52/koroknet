let currentSlide = 0;
let slides = [];
let sliderInterval = null;

function initSlider() {
    slides = Array.from(document.querySelectorAll('.slide'));
    const dotsContainer = document.querySelector('.slider-dots');

    if (!slides.length || !dotsContainer) {
        return;
    }

    dotsContainer.innerHTML = '';

    slides.forEach((_, index) => {
        const dot = document.createElement('button');
        dot.type = 'button';
        dot.className = `dot ${index === 0 ? 'active' : ''}`;
        dot.addEventListener('click', () => goToSlide(index));
        dotsContainer.appendChild(dot);
    });

    if (sliderInterval) {
        clearInterval(sliderInterval);
    }

    sliderInterval = setInterval(() => changeSlide(1), 5000);
}

function changeSlide(direction) {
    if (!slides.length) {
        return;
    }

    const next = (currentSlide + direction + slides.length) % slides.length;
    goToSlide(next);
}

function goToSlide(index) {
    if (!slides.length) {
        return;
    }

    slides.forEach((slide, i) => {
        slide.classList.toggle('active', i === index);
    });

    document.querySelectorAll('.dot').forEach((dot, i) => {
        dot.classList.toggle('active', i === index);
    });

    currentSlide = index;
}

class FormValidator {
    static validateLogin(login) {
        return /^[A-Za-z0-9_\-.]{6,50}$/.test(login);
    }

    static validatePassword(password) {
        return password.length >= 8;
    }

    static validateFullName(name) {
        return /^[\p{L}\s\-]{2,100}$/u.test(name);
    }

    static validatePhone(phone) {
        return /^8\(\d{3}\)\d{3}-\d{2}-\d{2}$/.test(phone);
    }

    static validateEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    static validateDate(dateValue) {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(dateValue)) {
            return false;
        }

        const date = new Date(`${dateValue}T00:00:00`);
        return !Number.isNaN(date.getTime());
    }
}

async function apiRequest(url, method = 'GET', data = null) {
    const options = {
        method,
        cache: 'no-store',
        headers: {
            'Content-Type': 'application/json'
        }
    };

    if (data !== null) {
        options.body = JSON.stringify(data);
    }

    const response = await fetch(url, options);

    let payload = null;
    try {
        payload = await response.json();
    } catch (error) {
        throw new Error('Сервер вернул некорректный JSON');
    }

    if (!response.ok) {
        const error = new Error(payload.message || `HTTP ${response.status}`);
        error.payload = payload;
        throw error;
    }

    return payload;
}

function showNotification(message, type = 'success') {
    const previous = document.querySelector('.notification');
    if (previous) {
        previous.remove();
    }

    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.classList.add('notification-hide');
        setTimeout(() => notification.remove(), 250);
    }, 3200);
}

function clearFieldErrors(formElement) {
    if (!formElement) {
        return;
    }

    formElement.querySelectorAll('.error-message').forEach((element) => {
        element.style.display = 'none';
        element.textContent = element.dataset.defaultMessage || element.textContent;
    });

    formElement.querySelectorAll('.error').forEach((element) => {
        element.classList.remove('error');
    });
}

function applyFieldErrors(formElement, fieldErrors = {}) {
    if (!formElement || typeof fieldErrors !== 'object' || fieldErrors === null) {
        return;
    }

    Object.entries(fieldErrors).forEach(([field, message]) => {
        const input = formElement.querySelector(`[name="${field}"]`) || formElement.querySelector(`#${field}`);
        if (input) {
            input.classList.add('error');
        }

        const errorNode = formElement.querySelector(`#${field}Error`) || formElement.querySelector(`[data-error-for="${field}"]`);
        if (errorNode) {
            if (!errorNode.dataset.defaultMessage) {
                errorNode.dataset.defaultMessage = errorNode.textContent;
            }
            errorNode.textContent = String(message);
            errorNode.style.display = 'block';
        }
    });
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
        document.body.classList.add('modal-open');
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
    }
}

function logout() {
    sessionStorage.clear();
    window.location.href = 'index.html';
}

function formatDate(dateString) {
    if (!dateString) {
        return '-';
    }

    const date = new Date(`${dateString}T00:00:00`);
    if (Number.isNaN(date.getTime())) {
        return dateString;
    }

    return `${String(date.getDate()).padStart(2, '0')}.${String(date.getMonth() + 1).padStart(2, '0')}.${date.getFullYear()}`;
}

function formatDateTime(dateString) {
    if (!dateString) {
        return '-';
    }

    const date = new Date(dateString);
    if (Number.isNaN(date.getTime())) {
        return dateString;
    }

    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');

    return `${day}.${month}.${year} ${hours}:${minutes}`;
}

function getStatusText(status) {
    const statuses = {
        new: 'Новая',
        in_progress: 'Идет обучение',
        completed: 'Обучение завершено'
    };

    return statuses[status] || status;
}

function getReviewStatusText(status) {
    const statuses = {
        pending: 'На модерации',
        approved: 'Опубликован',
        rejected: 'Отклонен'
    };

    return statuses[status] || status;
}

function getPaymentMethodText(method) {
    return method === 'cash' ? 'Наличными' : method === 'transfer' ? 'Переводом' : method;
}

function escapeHtml(value) {
    if (value === null || value === undefined) {
        return '';
    }

    const element = document.createElement('div');
    element.textContent = String(value);
    return element.innerHTML;
}

function checkAuth() {
    const currentPage = window.location.pathname.split('/').pop() || 'index.html';
    const publicPages = ['index.html', 'login.html', 'register.html', 'reviews.html'];

    if (publicPages.includes(currentPage)) {
        return true;
    }

    const userId = sessionStorage.getItem('user_id');
    if (!userId) {
        window.location.href = 'login.html';
        return false;
    }

    if (currentPage === 'admin.html' && sessionStorage.getItem('user_role') !== 'admin') {
        window.location.href = 'applications.html';
        return false;
    }

    return true;
}

function initPhoneMasks() {
    const inputs = document.querySelectorAll('input[type="tel"], input[name="phone"]');

    inputs.forEach((input) => {
        input.addEventListener('input', (event) => {
            let value = event.target.value.replace(/\D/g, '');
            if (value.startsWith('7')) {
                value = `8${value.slice(1)}`;
            }
            if (!value.startsWith('8')) {
                value = `8${value}`;
            }

            value = value.slice(0, 11);

            let masked = '8';
            if (value.length > 1) {
                masked += `(${value.slice(1, 4)}`;
            }
            if (value.length >= 4) {
                masked += `)${value.slice(4, 7)}`;
            }
            if (value.length >= 7) {
                masked += `-${value.slice(7, 9)}`;
            }
            if (value.length >= 9) {
                masked += `-${value.slice(9, 11)}`;
            }

            event.target.value = masked;
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('.slider')) {
        initSlider();
    }

    initPhoneMasks();

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            document.querySelectorAll('.modal').forEach((modal) => {
                if (modal.style.display === 'flex') {
                    modal.style.display = 'none';
                }
            });
            document.body.classList.remove('modal-open');
        }
    });

    window.addEventListener('click', (event) => {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
            document.body.classList.remove('modal-open');
        }
    });
});

if (!['index.html', 'login.html', 'register.html', 'reviews.html'].includes(window.location.pathname.split('/').pop() || 'index.html')) {
    checkAuth();
}

window.FormValidator = FormValidator;
window.apiRequest = apiRequest;
window.showNotification = showNotification;
window.clearFieldErrors = clearFieldErrors;
window.applyFieldErrors = applyFieldErrors;
window.openModal = openModal;
window.closeModal = closeModal;
window.logout = logout;
window.formatDate = formatDate;
window.formatDateTime = formatDateTime;
window.getStatusText = getStatusText;
window.getReviewStatusText = getReviewStatusText;
window.getPaymentMethodText = getPaymentMethodText;
window.escapeHtml = escapeHtml;
window.changeSlide = changeSlide;
window.goToSlide = goToSlide;
