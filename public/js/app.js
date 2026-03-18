window.AppUI = (function () {
    function toast(message, type) {
        var container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
        var el = document.createElement('div');
        el.className = 'toast ' + (type === 'error' ? 'toast-error' : 'toast-success');
        var msg = document.createElement('div');
        msg.className = 'toast-message';
        msg.textContent = message;
        var close = document.createElement('button');
        close.className = 'toast-close';
        close.textContent = '×';
        close.onclick = function () {
            if (el.parentNode) el.parentNode.removeChild(el);
        };
        el.appendChild(msg);
        el.appendChild(close);
        container.appendChild(el);
        setTimeout(function () {
            if (el.parentNode) el.parentNode.removeChild(el);
        }, 3500);
    }

    function bindCopyButtons() {
        var btn = document.querySelector('[data-copy-target]');
        if (!btn) return;
        btn.addEventListener('click', function () {
            var target = btn.getAttribute('data-copy-target');
            var input = document.querySelector(target);
            if (!input) return;
            input.select();
            input.setSelectionRange(0, 99999);
            var ok = false;
            try {
                ok = document.execCommand('copy');
            } catch (e) {
                ok = false;
            }
            if (ok) toast('Link berhasil disalin', 'success');
            else toast('Gagal menyalin link', 'error');
        });
    }

    function bindConfirmButtons() {
        var modalBackdrop = document.getElementById('confirm-modal-backdrop');
        if (!modalBackdrop) return;
        var titleEl = modalBackdrop.querySelector('[data-confirm-title]');
        var bodyEl = modalBackdrop.querySelector('[data-confirm-body]');
        var confirmBtn = modalBackdrop.querySelector('[data-confirm-yes]');
        var cancelBtn = modalBackdrop.querySelector('[data-confirm-no]');
        document.body.addEventListener('click', function (e) {
            var trigger = e.target.closest('[data-confirm]');
            if (!trigger) return;
            var message = trigger.getAttribute('data-confirm') || 'Yakin melakukan aksi ini?';
            var title = trigger.getAttribute('data-confirm-title') || 'Konfirmasi';
            var formSel = trigger.getAttribute('data-confirm-form');
            var form = formSel ? document.querySelector(formSel) : trigger.closest('form');
            if (!form) return;
            e.preventDefault();
            titleEl.textContent = title;
            bodyEl.textContent = message;
            modalBackdrop.style.display = 'flex';
            confirmBtn.onclick = function () {
                modalBackdrop.style.display = 'none';
                form.submit();
            };
            cancelBtn.onclick = function () {
                modalBackdrop.style.display = 'none';
            };
        });
    }

    function bindPeriodSelector() {
        var select = document.getElementById('owner-period-select');
        if (!select) return;
        select.addEventListener('change', function () {
            var url = select.getAttribute('data-base-url');
            if (!url) return;
            var params = new URLSearchParams(window.location.search);
            params.set('period', select.value);
            window.location.href = url + '?' + params.toString();
        });
    }

    function bindSystemClock() {
        var el = document.getElementById('system-clock');
        if (!el) return;
        var parent = el.closest('.dashboard-clock');
        var format = parent ? parent.getAttribute('data-clock-format') || '24h' : '24h';

        function updateClock() {
            var now = new Date();
            var options = {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };
            options.hour12 = format === '12h';
            el.textContent = now.toLocaleTimeString('id-ID', options);
        }

        updateClock();
        setInterval(updateClock, 1000);
    }

    function init() {
        bindCopyButtons();
        bindConfirmButtons();
        bindPeriodSelector();
        bindSystemClock();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    return {
        toast: toast
    };
})();
