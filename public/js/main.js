function toggleMenu() {
    const menu = document.getElementById('navMenu');
    menu.classList.toggle('open');
}

function confirmDelete(msg) {
    return confirm(msg || 'Êtes-vous sûr de vouloir supprimer cet élément ?');
}

function openModal(id) {
    document.getElementById(id).classList.add('active');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

document.addEventListener('DOMContentLoaded', function () {
    // Alert auto-close
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(a => {
        setTimeout(() => { 
            a.style.opacity = '0'; 
            a.style.transition = 'opacity 0.5s'; 
            setTimeout(() => a.remove(), 500); 
        }, 5000);
    });

    // Dropdown toggle for mobile/click
    const dropdownTriggers = document.querySelectorAll('.dropdown > a');
    dropdownTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const parent = this.parentElement;
            const isOpen = parent.classList.contains('active');
            
            // Close all other dropdowns
            document.querySelectorAll('.dropdown').forEach(d => d.classList.remove('active'));
            
            // Toggle current
            if (!isOpen) {
                parent.classList.add('active');
            }
        });
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
            document.querySelectorAll('.dropdown').forEach(d => d.classList.remove('active'));
        }
    });
});
