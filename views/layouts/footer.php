</div> <!-- end content-wrapper -->
</div> <!-- end app-layout -->

<script src="<?= BASE_URL ?>public/js/main.js?v=<?= time() ?>"></script>
<script src="<?= BASE_URL ?>public/js/live_sync.js?v=<?= time() ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.js-char-limit').forEach(input => {
            const limit = parseInt(input.getAttribute('data-limit'));
            const counter = document.createElement('div');
            counter.className = 'text-sm mt-1 char-counter';
            counter.style.cssText = 'color: var(--text-muted); font-size: 0.8rem; text-align: right;';
            input.parentNode.insertBefore(counter, input.nextSibling);

            const updateCounter = () => {
                const current = input.value.length;
                counter.textContent = `${current} / ${limit}`;

                const form = input.closest('form');
                let submitBtn = null;
                const customBtnSelector = input.getAttribute('data-submit-btn');
                if (customBtnSelector) {
                    submitBtn = document.querySelector(customBtnSelector);
                } else if (form) {
                    submitBtn = form.querySelector('button[type="submit"]');
                }

                if (current > limit) {
                    counter.innerHTML = `${current} / ${limit} <span style="color:var(--danger)">⚠️ Exceeded limit</span>`;
                    counter.style.color = 'var(--danger)';
                    input.style.borderColor = 'var(--danger)';
                    if (submitBtn) submitBtn.disabled = true;
                } else {
                    counter.style.color = 'var(--text-muted)';
                    input.style.borderColor = '';

                    if (submitBtn) {
                        // Check if any js-char-limit sharing this button is exceeded
                        let anyExceeded = false;
                        if (form) {
                            const allLimits = Array.from(form.querySelectorAll('.js-char-limit'));
                            anyExceeded = allLimits.some(inp => inp.value.length > parseInt(inp.getAttribute('data-limit')));
                        } else {
                            anyExceeded = current > limit;
                        }
                        submitBtn.disabled = anyExceeded;
                    }
                }
            };
            input.addEventListener('input', updateCounter);
            updateCounter();
        });
    });
</script>
</body>

</html>