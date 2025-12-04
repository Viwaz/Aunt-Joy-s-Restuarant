// Manager Dashboard - Report Management
// Handles report generation, filtering, and data display

/**
 * Handle form submission for report generation
 * @param {string} action - The action to perform: 'generate_report', 'export_pdf', 'export_excel'
 */
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

    // Remove the input after submission
    actionInput.remove();
}

/**
 * Format currency value with thousands separator
 * @param {number} value - The value to format
 * @returns {string} - Formatted currency string
 */
function formatCurrency(value) {
    return Number(value).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

/**
 * Format date string to readable format
 * @param {string} dateString - The date string to format
 * @returns {string} - Formatted date
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

/**
 * Initialize event listeners on page load
 */
document.addEventListener('DOMContentLoaded', function() {
    // Attach event listeners to buttons
    const generateBtn = document.querySelector('button[name="generate_report"]');
    const pdfBtn = document.querySelector('button[name="export_pdf"]');
    const excelBtn = document.querySelector('button[name="export_excel"]');

    if (generateBtn) {
        generateBtn.addEventListener('click', function(e) {
            e.preventDefault();
            submitReportForm('generate_report');
        });
    }

    if (pdfBtn) {
        pdfBtn.addEventListener('click', function(e) {
            e.preventDefault();
            submitReportForm('export_pdf');
        });
    }

    if (excelBtn) {
        excelBtn.addEventListener('click', function(e) {
            e.preventDefault();
            submitReportForm('export_excel');
        });
    }

    // Format currency values in tables
    formatTableCurrencies();
});

/**
 * Format all currency values in the report tables
 */
function formatTableCurrencies() {
    // Format Summary Cards
    const totalRevenueCard = document.querySelector('.card-green p');
    if (totalRevenueCard && totalRevenueCard.textContent.includes('MWK')) {
        const value = parseFloat(totalRevenueCard.textContent.replace(/[^0-9.-]/g, ''));
        if (!isNaN(value)) {
            totalRevenueCard.textContent = 'MWK ' + formatCurrency(value);
        }
    }

    const avgOrderCard = document.querySelector('.card-purple p');
    if (avgOrderCard && avgOrderCard.textContent.includes('MWK')) {
        const value = parseFloat(avgOrderCard.textContent.replace(/[^0-9.-]/g, ''));
        if (!isNaN(value)) {
            avgOrderCard.textContent = 'MWK ' + formatCurrency(value);
        }
    }

    // Format Best Sellers table
    const bestSellersRows = document.querySelectorAll('table tbody tr');
    bestSellersRows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length >= 4) {
            // Last cell is revenue
            const revenueCell = cells[cells.length - 1];
            const value = parseFloat(revenueCell.textContent.replace(/[^0-9.-]/g, ''));
            if (!isNaN(value)) {
                revenueCell.textContent = 'MWK ' + formatCurrency(value);
            }
        }
    });
}

/**
 * Export report to PDF (triggers form submission)
 */
function exportReportPDF() {
    submitReportForm('export_pdf');
}

/**
 * Export report to Excel/CSV (triggers form submission)
 */
function exportReportExcel() {
    submitReportForm('export_excel');
}

/**
 * Generate and display report on page
 */
function generateReport() {
    submitReportForm('generate_report');
}
