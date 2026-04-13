// Слайдер
let currentSlide = 0;
let slides = [];
let dotsContainer = null;
let slideInterval = null;

function initSlider() {
    slides = document.querySelectorAll('.slide');
    dotsContainer = document.querySelector('.slider-dots');
    
    if (!slides.length || !dotsContainer) return;
    
    dotsContainer.innerHTML = '';
    
    slides.forEach((_, index) => {
        const dot = document.createElement('div');
        dot.classList.add('dot');
        if (index === 0) dot.classList.add('active');
        dot.onclick = () => goToSlide(index);
        dotsContainer.appendChild(dot);
    });
    
    if (slideInterval) {
        clearInterval(slideInterval);
    }
    
    slideInterval = setInterval(() => {
        changeSlide(1);
    }, 3000);
}

function changeSlide(direction) {
    if (!slides.length) return;
    
    currentSlide += direction;
    if (currentSlide >= slides.length) currentSlide = 0;
    if (currentSlide < 0) currentSlide = slides.length - 1;
    goToSlide(currentSlide);
}

function goToSlide(index) {
    if (!slides.length) return;
    
    slides.forEach((slide, i) => {
        slide.classList.remove('active');
        if (i === index) slide.classList.add('active');
    });
    
    const dots = document.querySelectorAll('.dot');
    dots.forEach((dot, i) => {
        if (i === index) {
            dot.classList.add('active');
        } else {
            dot.classList.remove('active');
        }
    });
    
    currentSlide = index;
}

// Валидация форм
class FormValidator {
    static validateLogin(login) {
        const regex = /^[a-zA-Z0-9]{6,}$/;
        return regex.test(login);
    }
    
    static validatePassword(password) {
        return password.length >= 8;
    }
    
    static validateFullName(name) {
        const regex = /^[а-яА-ЯёЁ\s]+$/u;
        return regex.test(name);
    }
    
    static validatePhone(phone) {
        const regex = /^8\(\d{3}\)\d{3}-\d{2}-\d{2}$/;
        return regex.test(phone);
    }
    
    static validateEmail(email) {
        const regex = /^[^\s@]+@([^\s@]+\.)+[^\s@]+$/;
        return regex.test(email);
    }
    
    static validateDate(date) {
        const regex = /^\d{2}\.\d{2}\.\d{4}$/;
        if (!regex.test(date)) return false;
        
        const [day, month, year] = date.split('.');
        const dateObj = new Date(year, month - 1, day);
        return dateObj.getDate() == day && 
               dateObj.getMonth() == month - 1 && 
               dateObj.getFullYear() == year;
    }
}

// API запросы
async function apiRequest(url, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
        }
    };
    
    if (data) {
        options.body = JSON.stringify(data);
    }
    
    try {
        const response = await fetch(url, options);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    } catch (error) {
        console.error('API Request Error:', error);
        throw error;
    }
}

// Уведомления
function showNotification(message, type = 'success') {
    const oldNotifications = document.querySelectorAll('.notification');
    oldNotifications.forEach(notif => notif.remove());
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    Object.assign(notification.style, {
        position: 'fixed',
        top: '20px',
        right: '20px',
        padding: '12px 20px',
        background: type === 'success' ? '#4caf50' : type === 'error' ? '#f44336' : '#2196f3',
        color: 'white',
        borderRadius: '8px',
        zIndex: '2000',
        animation: 'slideIn 0.3s ease',
        boxShadow: '0 2px 10px rgba(0,0,0,0.2)',
        fontFamily: 'Inter, sans-serif',
        fontSize: '14px',
        fontWeight: '500'
    });
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Модальные окна
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Выход
function logout() {
    sessionStorage.clear();
    showNotification('Вы вышли из системы', 'success');
    setTimeout(() => {
        window.location.href = 'index.html';
    }, 1000);
}

// Форматирование
function formatDate(dateString) {
    if (!dateString) return '—';
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return dateString;
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        return `${day}.${month}.${year}`;
    } catch (error) {
        return dateString;
    }
}

function formatDateTime(dateString) {
    if (!dateString) return '—';
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return dateString;
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return `${day}.${month}.${year} ${hours}:${minutes}`;
    } catch (error) {
        return dateString;
    }
}

function getStatusText(status) {
    const statusMap = {
        'new': 'Новая',
        'in_progress': 'Идет обучение',
        'completed': 'Обучение завершено'
    };
    return statusMap[status] || status;
}

function getPaymentMethodText(method) {
    const methodMap = {
        'cash': 'Наличными',
        'transfer': 'Перевод по номеру телефона'
    };
    return methodMap[method] || method;
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Адаптивная функция для определения типа устройства
function getDeviceType() {
    const width = window.innerWidth;
    if (width <= 768) return 'mobile';
    if (width <= 1024) return 'tablet';
    return 'desktop';
}

// Адаптивная навигация
function initAdaptiveNavigation() {
    const nav = document.querySelector('.nav-menu');
    if (!nav) return;
    
    const deviceType = getDeviceType();
    
    if (deviceType === 'mobile') {
        // На мобильных добавляем скролл для навигации
        nav.style.overflowX = 'auto';
        nav.style.whiteSpace = 'nowrap';
        nav.style.display = 'flex';
        nav.style.flexWrap = 'nowrap';
        nav.style.justifyContent = 'flex-start';
        
        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.style.flex = 'none';
            link.style.padding = '12px 20px';
        });
    } else {
        nav.style.overflowX = 'visible';
        nav.style.whiteSpace = 'normal';
        nav.style.display = 'flex';
        nav.style.flexWrap = 'wrap';
        nav.style.justifyContent = 'center';
    }
}

// Инициализация
document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('.slider')) {
        initSlider();
    }
    
    initAdaptiveNavigation();
    
    // Адаптация при изменении размера окна
    window.addEventListener('resize', () => {
        initAdaptiveNavigation();
    });
    
    // Закрытие модальных окон
    window.onclick = (event) => {
        if (event.target.classList && event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    };
    
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (modal.style.display === 'flex') {
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                }
            });
        }
    });
    
    // Маски ввода
    const phoneInputs = document.querySelectorAll('input[type="tel"], input[name="phone"]');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                if (value.length <= 1) value = '8';
                else if (value.length <= 4) value = `8(${value.slice(1,4)}`;
                else if (value.length <= 7) value = `8(${value.slice(1,4)})${value.slice(4,7)}`;
                else if (value.length <= 9) value = `8(${value.slice(1,4)})${value.slice(4,7)}-${value.slice(7,9)}`;
                else value = `8(${value.slice(1,4)})${value.slice(4,7)}-${value.slice(7,9)}-${value.slice(9,11)}`;
            }
            e.target.value = value.slice(0, 16);
        });
    });
    
    const dateInputs = document.querySelectorAll('input[name="start_date"], #start_date');
    dateInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.slice(0,2) + '.' + value.slice(2);
            }
            if (value.length >= 5) {
                value = value.slice(0,5) + '.' + value.slice(5,9);
            }
            e.target.value = value.slice(0,10);
        });
    });
});

// Проверка авторизации
function checkAuth() {
    const userId = sessionStorage.getItem('user_id');
    const publicPages = ['index.html', 'login.html', 'register.html'];
    const currentPage = window.location.pathname.split('/').pop();
    
    if (!userId && !publicPages.includes(currentPage)) {
        showNotification('Пожалуйста, авторизуйтесь', 'error');
        setTimeout(() => {
            window.location.href = 'login.html';
        }, 1500);
        return false;
    }
    
    if (currentPage === 'admin.html') {
        const userRole = sessionStorage.getItem('user_role');
        if (userRole !== 'admin') {
            showNotification('Доступ запрещен', 'error');
            setTimeout(() => {
                window.location.href = 'applications.html';
            }, 1500);
            return false;
        }
    }
    
    return true;
}

// Запуск проверки
if (!['index.html', 'login.html', 'register.html'].includes(window.location.pathname.split('/').pop())) {
    checkAuth();
}

// Глобальные функции
window.apiRequest = apiRequest;
window.showNotification = showNotification;
window.openModal = openModal;
window.closeModal = closeModal;
window.logout = logout;
window.formatDate = formatDate;
window.formatDateTime = formatDateTime;
window.getStatusText = getStatusText;
window.getPaymentMethodText = getPaymentMethodText;
window.escapeHtml = escapeHtml;
window.FormValidator = FormValidator;
window.changeSlide = changeSlide;
window.goToSlide = goToSlide;