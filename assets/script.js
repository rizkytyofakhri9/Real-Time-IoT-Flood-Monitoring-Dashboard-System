/* ==========================================
   1. UTILS & EVENT LISTENERS
   ========================================== */
document.addEventListener("DOMContentLoaded", function() {
    // Inisialisasi Tanggal Cetak Otomatis
    const opsiTanggal = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
    const tanggalHariIni = new Date().toLocaleDateString('id-ID', opsiTanggal);
    document.querySelectorAll('.tgl-cetak-otomatis').forEach(function(el) {
        el.textContent = tanggalHariIni;
    });
});

/* ==========================================
   2. INTERAKSI KELOLA WARGA
   ========================================== */
function toggleList() {
    var x = document.getElementById("div_daftar_warga");
    if (x) {
        x.style.display = (x.style.display === "none") ? "block" : "none";
    }
}

function pilihWarga(nama) {
    var inputNama = document.getElementById("input_nama");
    if (inputNama) {
        inputNama.value = nama;
        inputNama.readOnly = true;
    }
    var divWarga = document.getElementById("div_daftar_warga");
    if (divWarga) {
        divWarga.style.display = "none";
    }
}

/* ==========================================
   3. CHART.JS MONITORING & PLUGIN ZONA STATUS
   ========================================== */
const zonaStatusModernPlugin = {
    id: 'zonaStatusModern',
    beforeDraw(chart) {
        const { ctx, chartArea: { top, bottom, left, right }, scales: { y } } = chart;
        ctx.save();

        function drawZone(valMin, valMax, color) {
            const yTop = y.getPixelForValue(valMax);
            const yBottom = y.getPixelForValue(valMin);
            const drawTop = Math.max(top, yTop);
            const drawBottom = Math.min(bottom, yBottom);
            const drawHeight = drawBottom - drawTop;

            if (drawHeight > 0) {
                ctx.fillStyle = color;
                ctx.fillRect(left, drawTop, right - left, drawHeight);
            }
        }

        drawZone(0, 30, 'rgba(34, 197, 94, 0.08)');
        drawZone(30, 40, 'rgba(234, 179, 8, 0.12)');
        drawZone(40, y.max, 'rgba(239, 68, 68, 0.12)');

        function drawThresholdLine(val, color, bgLabel, text) {
            const yPos = y.getPixelForValue(val);
            if (yPos >= top && yPos <= bottom) {
                ctx.beginPath();
                ctx.lineWidth = 1.5;
                ctx.strokeStyle = color;
                ctx.setLineDash([6, 4]);
                ctx.moveTo(left, yPos);
                ctx.lineTo(right, yPos);
                ctx.stroke();

                ctx.setLineDash([]);
                ctx.fillStyle = bgLabel;
                const textWidth = ctx.measureText(text).width + 16;
                const rectX = left + 8;
                const rectY = yPos - 11;
                
                ctx.beginPath();
                if (ctx.roundRect) {
                    ctx.roundRect(rectX, rectY, textWidth, 20, 4);
                } else {
                    ctx.rect(rectX, rectY, textWidth, 20);
                }
                ctx.fill();

                ctx.fillStyle = '#ffffff';
                ctx.font = 'bold 10px Arial, sans-serif';
                ctx.fillText(text, rectX + 8, rectY + 13);
            }
        }

        drawThresholdLine(30, '#d97706', '#d97706', 'BATAS WASPADA (30 cm)');
        drawThresholdLine(40, '#dc2626', '#dc2626', 'BATAS BAHAYA (40 cm)');

        ctx.restore();
    }
};

function initMonitoringChart(canvasId, labelsData, levelsData) {
    const canvasEl = document.getElementById(canvasId);
    if (!canvasEl) return;

    const ctx = canvasEl.getContext('2d');
    const gradientFill = ctx.createLinearGradient(0, 0, 0, 300);
    gradientFill.addColorStop(0, 'rgba(14, 165, 233, 0.4)');
    gradientFill.addColorStop(1, 'rgba(14, 165, 233, 0.0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labelsData,
            datasets: [{
                label: 'Ketinggian Air',
                data: levelsData,
                borderColor: '#0284c7',
                borderWidth: 3,
                backgroundColor: gradientFill,
                fill: true,
                tension: 0.35,
                pointRadius: 4,
                pointHoverRadius: 7,
                pointBackgroundColor: '#0284c7',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointHoverBorderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    enabled: true,
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    titleColor: '#94a3b8',
                    titleFont: { size: 11, weight: 'normal' },
                    bodyColor: '#ffffff',
                    bodyFont: { size: 13, weight: 'bold' },
                    padding: 12,
                    cornerRadius: 8,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            const val = context.parsed.y;
                            let status = "AMAN ✅";
                            if (val > 40) status = "BAHAYA 🚨";
                            else if (val > 30) status = "WASPADA ⚠️";
                            return ` Ketinggian: ${val} cm (${status})`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: '#64748b', font: { size: 11, family: 'Arial' } }
                },
                y: {
                    beginAtZero: true,
                    suggestedMax: 45,
                    grace: '10%',
                    grid: { color: 'rgba(226, 232, 240, 0.7)', borderDash: [4, 4] },
                    ticks: {
                        color: '#64748b',
                        font: { size: 11, family: 'Arial' },
                        callback: function(value) { return value + ' cm'; }
                    }
                }
            }
        },
        plugins: [zonaStatusModernPlugin]
    });
}