/**
 * WP Anti-Spam Comment — Admin Script
 * Animated counters for spam statistics
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        // Animated number counters
        const counters = document.querySelectorAll('.wpasc-stat-number[data-count]');
        const hasIntersectionObserver = 'IntersectionObserver' in window;
        const observer = hasIntersectionObserver
            ? new IntersectionObserver(
                function (entries, currentObserver) {
                    entries.forEach(function (entry) {
                        if (!entry.isIntersecting) {
                            return;
                        }

                        if (typeof entry.target.wpascStartAnimation === 'function') {
                            entry.target.wpascStartAnimation();
                            delete entry.target.wpascStartAnimation;
                        }
                        currentObserver.unobserve(entry.target);
                    });
                },
                { threshold: 0.3 }
            )
            : null;

        counters.forEach(function (counter) {
            const target = parseInt(counter.getAttribute('data-count'), 10) || 0;

            if (target === 0) {
                counter.textContent = '0';
                return;
            }

            const duration = 1200; // ms
            const startTime = performance.now();
            let hasStarted = false;

            function easeOutCubic(t) {
                return 1 - Math.pow(1 - t, 3);
            }

            function animate(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const easedProgress = easeOutCubic(progress);
                const currentValue = Math.round(easedProgress * target);

                counter.textContent = currentValue.toLocaleString();

                if (progress < 1) {
                    requestAnimationFrame(animate);
                }
            }

            function startAnimation() {
                if (hasStarted) {
                    return;
                }

                hasStarted = true;
                requestAnimationFrame(animate);
            }

            if (observer) {
                counter.wpascStartAnimation = startAnimation;
                observer.observe(counter);
                return;
            }

            startAnimation();
        });

        // Settings saved success feedback
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('settings-updated') === 'true') {
            const saveBtn = document.querySelector('.wpasc-save-btn');
            if (saveBtn) {
                const originalText = saveBtn.value;
                saveBtn.value = '✓ Saved!';
                saveBtn.style.background = 'linear-gradient(135deg, #10B981, #06B6D4)';
                setTimeout(function () {
                    saveBtn.value = originalText;
                    saveBtn.style.background = '';
                }, 2000);
            }
        }
    });
})();
