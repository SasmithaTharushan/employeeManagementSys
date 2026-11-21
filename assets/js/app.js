(function () {
    'use strict';

    // Mobile sidebar
    var sidebar = document.getElementById('sidebar');
    document.querySelectorAll('[data-sidebar-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () { sidebar && sidebar.classList.add('open'); });
    });
    document.querySelectorAll('[data-sidebar-close]').forEach(function (el) {
        el.addEventListener('click', function () { sidebar && sidebar.classList.remove('open'); });
    });

    // Dark mode toggle (remembered per browser)
    function syncThemeIcon() {
        var dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        document.querySelectorAll('[data-theme-toggle] i').forEach(function (i) {
            i.className = dark ? 'bi bi-sun' : 'bi bi-moon-stars';
        });
    }
    document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var next = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-bs-theme', next);
            try { localStorage.setItem('theme', next); } catch (e) {}
            syncThemeIcon();
            document.dispatchEvent(new CustomEvent('themechange'));
        });
    });
    syncThemeIcon();

    // Ask before destructive actions: <form data-confirm="Are you sure?">
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!window.confirm(form.getAttribute('data-confirm'))) { e.preventDefault(); }
        });
    });

    // One-click demo login
    document.querySelectorAll('[data-demo-login]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var form = document.querySelector('.auth-card form');
            form.querySelector('[name=email]').value = btn.getAttribute('data-email');
            form.querySelector('[name=password]').value = btn.getAttribute('data-password');
            form.submit();
        });
    });

    // Client-side table filter: <input data-filter="#tableId">
    document.querySelectorAll('[data-filter]').forEach(function (input) {
        var rows = document.querySelectorAll(input.getAttribute('data-filter') + ' tbody tr');
        input.addEventListener('input', function () {
            var term = input.value.toLowerCase();
            rows.forEach(function (row) {
                row.style.display = row.textContent.toLowerCase().indexOf(term) === -1 ? 'none' : '';
            });
        });
    });

    // Live salary preview on the employee form
    var preview = document.querySelector('[data-salary-preview]');
    if (preview) {
        var rates = JSON.parse(preview.getAttribute('data-rates'));
        var fields = ['basic_salary', 'fixed_allowance', 'other_allowance'].map(function (n) {
            return document.querySelector('[name=' + n + ']');
        });
        var fmt = function (n) { return 'Rs. ' + n.toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };
        var apit = function (taxable) {
            var left = taxable - rates.apit.relief, tax = 0;
            rates.apit.bands.forEach(function (b) {
                if (left <= 0) { return; }
                var slice = b[0] === null ? left : Math.min(left, b[0]);
                tax += slice * b[1];
                left -= slice;
            });
            return Math.round(tax * 100) / 100;
        };
        var update = function () {
            var basic = parseFloat(fields[0].value) || 0, fixed = parseFloat(fields[1].value) || 0, other = parseFloat(fields[2].value) || 0;
            var gross = basic + fixed + other, epfBase = basic + fixed;
            var epf8 = epfBase * rates.epf_employee, tax = apit(gross);
            var set = function (k, v) { preview.querySelector('[data-k=' + k + ']').textContent = fmt(v); };
            set('gross', gross); set('epf8', epf8); set('apit', tax); set('net', gross - epf8 - tax);
            set('epf12', epfBase * rates.epf_employer); set('etf', epfBase * rates.etf_employer);
            set('cost', gross + epfBase * (rates.epf_employer + rates.etf_employer));
        };
        fields.forEach(function (f) { f && f.addEventListener('input', update); });
        update();
    }
})();
