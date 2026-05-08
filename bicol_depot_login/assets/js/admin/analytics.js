/* =========================================================
   OptimaFlow - Admin Analytics Page JS
   ========================================================= */

document.addEventListener('DOMContentLoaded', function () {
    if (typeof analyticsData === 'undefined') {
        console.error('analyticsData not found. Check the PHP page.');
        return;
    }

    // Theme colors for charts
    const colors = {
        primary: '#00539C',
        primaryDark: '#003566',
        accent: '#FFD662',
        success: '#2ECC71',
        warning: '#F39C12',
        danger: '#DC3545',
        gray: '#6B7280'
    };

    const palette = [colors.primary, colors.accent, colors.success, colors.warning, colors.danger, colors.gray];

    // Chart 1: Products by Category (Doughnut)
    if (analyticsData.categories && analyticsData.categories.length > 0) {
        new Chart(document.getElementById('categoryChart'), {
            type: 'doughnut',
            data: {
                labels: analyticsData.categories.map(c => c.category),
                datasets: [{
                    data: analyticsData.categories.map(c => c.count),
                    backgroundColor: palette
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }

    // Chart 2: Orders by Status (Pie)
    if (analyticsData.statuses && analyticsData.statuses.length > 0) {
        const statusColorMap = {
            pending: colors.warning,
            approved: colors.primary,
            fulfilled: colors.success,
            cancelled: colors.danger
        };
        new Chart(document.getElementById('statusChart'), {
            type: 'pie',
            data: {
                labels: analyticsData.statuses.map(s => s.status.charAt(0).toUpperCase() + s.status.slice(1)),
                datasets: [{
                    data: analyticsData.statuses.map(s => s.count),
                    backgroundColor: analyticsData.statuses.map(s => statusColorMap[s.status] || colors.gray)
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }

    // Chart 3: Stock Levels (Horizontal Bar)
    if (analyticsData.stock && analyticsData.stock.length > 0) {
        new Chart(document.getElementById('stockChart'), {
            type: 'bar',
            data: {
                labels: analyticsData.stock.map(s => s.name),
                datasets: [{
                    label: 'Stock Quantity',
                    data: analyticsData.stock.map(s => s.quantity),
                    backgroundColor: analyticsData.stock.map(s => s.quantity < 5 ? colors.danger : colors.primary)
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } }
            }
        });
    }

    // Chart 4: Reservations Over Time (Line)
    if (analyticsData.timeline && analyticsData.timeline.length > 0) {
        new Chart(document.getElementById('timelineChart'), {
            type: 'line',
            data: {
                labels: analyticsData.timeline.map(t => t.date),
                datasets: [{
                    label: 'Reservations',
                    data: analyticsData.timeline.map(t => t.count),
                    borderColor: colors.primary,
                    backgroundColor: 'rgba(0, 83, 156, 0.1)',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    // Chart 5: Top 5 Selling Products (Bar)
    if (analyticsData.topProducts && analyticsData.topProducts.length > 0) {
        new Chart(document.getElementById('topProductsChart'), {
            type: 'bar',
            data: {
                labels: analyticsData.topProducts.map(p => p.name),
                datasets: [{
                    label: 'Units Sold',
                    data: analyticsData.topProducts.map(p => p.total_sold),
                    backgroundColor: colors.primary
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } }
            }
        });
    }

    // Chart 6: Revenue by Category (Bar)
    if (analyticsData.revenue && analyticsData.revenue.length > 0) {
        new Chart(document.getElementById('revenueChart'), {
            type: 'bar',
            data: {
                labels: analyticsData.revenue.map(r => r.category),
                datasets: [{
                    label: 'Revenue (Peso)',
                    data: analyticsData.revenue.map(r => r.revenue),
                    backgroundColor: colors.accent
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } }
            }
        });
    }
});