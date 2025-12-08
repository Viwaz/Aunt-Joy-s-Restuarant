// ===== REPORT MANAGEMENT =====
function submitReportForm(action) {
    const month = document.getElementById('month-select').value;
    const year = document.getElementById('year-select').value;

    if (!month || !year) {
        alert('Please select both month and year');
        return;
    }

    const form = document.getElementById('report-form');
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = action;
    actionInput.value = '1';

    form.appendChild(actionInput);
    form.submit();
    actionInput.remove();
}

// ===== FORMATTING UTILITIES =====
function formatCurrency(value) {
    return Number(value).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function formatTableCurrencies() {
    // Format Summary Cards
    const totalRevenueCard = document.querySelector('.card-green p');
    if (totalRevenueCard?.textContent.includes('MWK')) {
        const value = parseFloat(totalRevenueCard.textContent.replace(/[^0-9.-]/g, ''));
        if (!isNaN(value)) totalRevenueCard.textContent = 'MWK ' + formatCurrency(value);
    }

    const avgOrderCard = document.querySelector('.card-purple p');
    if (avgOrderCard?.textContent.includes('MWK')) {
        const value = parseFloat(avgOrderCard.textContent.replace(/[^0-9.-]/g, ''));
        if (!isNaN(value)) avgOrderCard.textContent = 'MWK ' + formatCurrency(value);
    }

    // Format Best Sellers table
    document.querySelectorAll('table tbody tr').forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length >= 4) {
            const revenueCell = cells[cells.length - 1];
            const value = parseFloat(revenueCell.textContent.replace(/[^0-9.-]/g, ''));
            if (!isNaN(value)) revenueCell.textContent = 'MWK ' + formatCurrency(value);
        }
    });
}

// ===== INITIALIZATION =====
document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('button[name="generate_report"]')?.addEventListener('click', (e) => {
        e.preventDefault();
        submitReportForm('generate_report');
    });

    document.querySelector('button[name="export_pdf"]')?.addEventListener('click', (e) => {
        e.preventDefault();
        submitReportForm('export_pdf');
    });

    document.querySelector('button[name="export_excel"]')?.addEventListener('click', (e) => {
        e.preventDefault();
        submitReportForm('export_excel');
    });

    formatTableCurrencies();
});
