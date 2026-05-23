(function () {
    // Small local chart fallback so the demo still works without internet.
    // It supports the pie chart used in result.php.
    if (typeof window.Chart === 'undefined') {
        window.Chart = function (canvas, config) {
            if (!canvas || !canvas.getContext || !config || !config.data) return;

            var ctx = canvas.getContext('2d');
            var data = (config.data.datasets && config.data.datasets[0] && config.data.datasets[0].data) || [];
            var colors = (config.data.datasets && config.data.datasets[0] && config.data.datasets[0].backgroundColor) || [];
            var labels = config.data.labels || [];
            var total = data.reduce(function (sum, value) { return sum + Number(value || 0); }, 0);
            var width = canvas.width || canvas.clientWidth || 400;
            var height = canvas.height || 260;
            var radius = Math.min(width, height) / 2.8;
            var centerX = width / 2;
            var centerY = height / 2.5;
            var start = -Math.PI / 2;

            canvas.width = width;
            canvas.height = height;
            ctx.clearRect(0, 0, width, height);

            if (total <= 0) {
                ctx.font = '14px Arial';
                ctx.fillText('No allocation data', centerX - 55, centerY);
                return;
            }

            data.forEach(function (value, index) {
                var slice = (Number(value || 0) / total) * Math.PI * 2;
                ctx.beginPath();
                ctx.moveTo(centerX, centerY);
                ctx.arc(centerX, centerY, radius, start, start + slice);
                ctx.closePath();
                ctx.fillStyle = colors[index] || '#cbd5e1';
                ctx.fill();
                start += slice;
            });

            var legendY = centerY + radius + 24;
            labels.forEach(function (label, index) {
                var x = 22 + (index % 2) * (width / 2);
                var y = legendY + Math.floor(index / 2) * 24;
                ctx.fillStyle = colors[index] || '#cbd5e1';
                ctx.fillRect(x, y - 10, 12, 12);
                ctx.fillStyle = '#334155';
                ctx.font = '12px Arial';
                ctx.fillText(label, x + 18, y);
            });
        };
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-confirm]').forEach(function (element) {
            element.addEventListener('click', function (event) {
                if (!confirm(element.getAttribute('data-confirm'))) {
                    event.preventDefault();
                }
            });
        });
    });
})();
