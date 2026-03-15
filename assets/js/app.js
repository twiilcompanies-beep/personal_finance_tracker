document.addEventListener('DOMContentLoaded', () => {
    // 1. Theme Toggling
    const themeToggleBtn = document.getElementById('theme-toggle');
    const htmlElement = document.documentElement;
    const themeIcon = document.getElementById('theme-icon');

    // Check local storage for theme
    const savedTheme = localStorage.getItem('theme') || 'light';
    setTheme(savedTheme);

    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', () => {
            const currentTheme = htmlElement.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            setTheme(newTheme);
        });
    }

    function setTheme(theme) {
        htmlElement.setAttribute('data-bs-theme', theme);
        localStorage.setItem('theme', theme);
        
        if (themeIcon) {
            if (theme === 'dark') {
                themeIcon.classList.remove('bi-moon');
                themeIcon.classList.add('bi-sun');
            } else {
                themeIcon.classList.remove('bi-sun');
                themeIcon.classList.add('bi-moon');
            }
        }

        // Update charts if they exist
        if (typeof Chart !== 'undefined' && window.myCharts) {
            updateChartsTheme(theme);
        }
    }

    function updateChartsTheme(theme) {
        const textColor = theme === 'dark' ? '#e2e8f0' : '#64748b';
        const gridColor = theme === 'dark' ? '#334155' : '#e2e8f0';
        
        window.myCharts.forEach(chart => {
            if(chart.options.scales) {
                if(chart.options.scales.x) {
                    chart.options.scales.x.ticks.color = textColor;
                    chart.options.scales.x.grid.color = gridColor;
                }
                if(chart.options.scales.y) {
                    chart.options.scales.y.ticks.color = textColor;
                    chart.options.scales.y.grid.color = gridColor;
                }
            }
            if(chart.options.plugins && chart.options.plugins.legend) {
                chart.options.plugins.legend.labels.color = textColor;
            }
            chart.update();
        });
    }

    // 2. Sidebar Toggle for Mobile
    const sidebarToggler = document.getElementById('sidebar-toggler');
    const sidebar = document.getElementById('sidebar');
    
    if (sidebarToggler && sidebar) {
        sidebarToggler.addEventListener('click', () => {
            sidebar.classList.toggle('show');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth < 992 && 
                !sidebar.contains(e.target) && 
                !sidebarToggler.contains(e.target) && 
                sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
            }
        });
    }

    // 3. Form Validation (Bootstrap native + custom)
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // 4. Initialize Charts if canvas exists
    initCharts();
});

// Function to initialize Chart.js
function initCharts() {
    window.myCharts = [];
    const trendCtx = document.getElementById('expenseTrendChart');
    const categoryCtx = document.getElementById('categoryChart');
    
    const theme = document.documentElement.getAttribute('data-bs-theme') || 'light';
    const textColor = theme === 'dark' ? '#e2e8f0' : '#64748b';
    const gridColor = theme === 'dark' ? '#334155' : '#e2e8f0';

    if (trendCtx) {
        const trendChart = new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [
                    {
                        label: 'Income',
                        data: [3000, 3200, 3100, 3500, 3400, 3800],
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Expenses',
                        data: [2100, 1900, 2400, 2200, 2600, 2300],
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { color: textColor } }
                },
                scales: {
                    x: { ticks: { color: textColor }, grid: { color: gridColor } },
                    y: { ticks: { color: textColor }, grid: { color: gridColor } }
                }
            }
        });
        window.myCharts.push(trendChart);
    }

    if (categoryCtx) {
        const categoryChart = new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: ['Food', 'Transport', 'Bills', 'Shopping', 'Other'],
                datasets: [{
                    data: [35, 15, 25, 15, 10],
                    backgroundColor: [
                        '#6366f1', // Primary
                        '#10b981', // Success
                        '#f59e0b', // Warning
                        '#ef4444', // Danger
                        '#8b5cf6'  // Purple
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: { position: 'bottom', labels: { color: textColor, padding: 20 } }
                }
            }
        });
        window.myCharts.push(categoryChart);
    }
}
