<?php
session_start();
// Proteksi halaman: Jika tidak login atau bukan warga, tendang ke login.php
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'warga') {
    header("Location: login.php");
    exit;
}

include 'koneksi.php';

// 1. Ambil 1 data terbaru dari database
$query_last = "SELECT level_air, status, waktu FROM tabel_banjir ORDER BY id DESC LIMIT 1";
$res_last = mysqli_query($koneksi, $query_last);
$data_last = mysqli_fetch_assoc($res_last);

$level_sekarang = $data_last ? $data_last['level_air'] : 0;
$status_sekarang = $data_last ? $data_last['status'] : "Aman";

// 2. Hitung status Online/Offline sistem untuk warga
$status_sistem = "Offline"; 
$warna_status = "#e74c3c"; 

if ($data_last) {
    date_default_timezone_set('Asia/Jakarta');
    $waktu_data = strtotime($data_last['waktu']); 
    $waktu_sekarang = time();                     
    
    $selisih_detik = $waktu_sekarang - $waktu_data;
    
    if ($selisih_detik <= 315) {
        $status_sistem = "Online";
        $warna_status = "#2ecc71"; 
    }
}

// 3. Ambil data historis grafik garis untuk warga
$query_chart = "SELECT level_air, waktu FROM tabel_banjir ORDER BY id DESC LIMIT 10";
$res_chart = mysqli_query($koneksi, $query_chart);
$arr_level = []; $arr_waktu = [];
while($row = mysqli_fetch_assoc($res_chart)){
    $arr_level[] = $row['level_air'];
    $arr_waktu[] = date('H:i', strtotime($row['waktu']));
}
$arr_level = array_reverse($arr_level);
$arr_waktu = array_reverse($arr_waktu);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="5"> 
    <title>Dashboard Warga - Monitoring Banjir</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; display: flex; background-color: #f4f7f6; }
        .sidebar { width: 220px; background-color: #2c3e50; color: white; min-height: 100vh; padding: 20px 0; }
        .sidebar h3 { text-align: center; margin-bottom: 30px; letter-spacing: 1px; }
        .sidebar a { display: block; color: #a9b7c6; padding: 12px 25px; text-decoration: none; font-size: 15px; }
        .sidebar a:hover, .sidebar a.active { background-color: #34495e; color: white; border-left: 4px solid #2196F3; }
        
        .main-content { flex: 1; padding: 25px; }
        .main-content h2 { margin-top: 0; color: #333; }
        
        .cards-grid { display: flex; gap: 20px; margin-bottom: 30px; }
        .card { flex: 1; background: white; padding: 20px; border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); text-align: center; }
        .card h4 { margin: 0; color: #7f8c8d; font-size: 14px; text-transform: uppercase; }
        .card p { margin: 10px 0 0 0; font-size: 28px; font-weight: bold; color: #2c3e50; }
        
        .chart-section { background: white; padding: 20px; border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .chart-section h3 { margin-top: 0; font-size: 16px; color: #333; border-bottom: 1px solid #eee; padding-bottom: 10px; }
    </style>
</head>
<body>

<div class="sidebar">
    <h3>MENU WARGA</h3>
    <a href="warga_dashboard.php" class="active">Dashboard</a>
    <a href="riwayat.php">Riwayat</a>
    <a href="laporan.php">Pengaduan</a>
    <a href="logout.php" style="color: #e74c3c;">Logout</a>
</div>

<div class="main-content">
    <h2>DASHBOARD MONITORING LINGKUNGAN</h2>
    <p>Selamat Datang Warga, <b><?php echo $_SESSION['nama']; ?></b> (Hak Akses: Warga)</p>
    
    <div class="cards-grid">
        <div class="card">
            <h4>Ketinggian Air</h4>
            <p style="color: #2196F3;"><?= $level_sekarang; ?> cm</p>
            <small>Status: <b><?= $status_sekarang; ?></b></small>
        </div>
        <div class="card">
            <h4>Status Sistem</h4>
            <p style="color: <?= $warna_status; ?>;"><?= $status_sistem; ?></p>
            <small>Detak Jantung Alat</small>
        </div>
    </div>

    <div class="chart-section">
        <h3>GRAFIK MONITORING REAL-TIME</h3>
        <canvas id="wargaChart" style="max-height: 350px; height: 300px;"></canvas>
    </div>
</div>

<script>
    // ==========================================
    // 1. PLUGIN ZONA STATUS & BADGE BATAS (PASTEL MODERN)
    // ==========================================
    const zonaStatusModernPlugin = {
        id: 'zonaStatusModern',
        beforeDraw(chart) {
            const { ctx, chartArea: { top, bottom, left, right }, scales: { y } } = chart;
            ctx.save();

            // Fungsi Pembantu Gambar Zona Latar Pastel
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

            // A. GAMBAR ZONA BACKGROUND PASTEL
            drawZone(0, 30, 'rgba(34, 197, 94, 0.08)');   // Soft Emerald Green (0 - 30 cm)
            drawZone(30, 40, 'rgba(234, 179, 8, 0.12)');   // Soft Amber Yellow (30 - 40 cm)
            drawZone(40, y.max, 'rgba(239, 68, 68, 0.12)');// Soft Rose Red (> 40 cm)

            // B. GAMBAR GARIS BATAS & BADGE LABEL
            function drawThresholdLine(val, color, bgLabel, text) {
                const yPos = y.getPixelForValue(val);
                if (yPos >= top && yPos <= bottom) {
                    // Garis Putus-putus
                    ctx.beginPath();
                    ctx.lineWidth = 1.5;
                    ctx.strokeStyle = color;
                    ctx.setLineDash([6, 4]);
                    ctx.moveTo(left, yPos);
                    ctx.lineTo(right, yPos);
                    ctx.stroke();

                    // Kotak Badge Label
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

                    // Teks Label
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

    // ==========================================
    // 2. INISIALISASI CHART WARGA
    // ==========================================
    document.addEventListener("DOMContentLoaded", function() {
        const canvasEl = document.getElementById('wargaChart');
        if (!canvasEl) return;

        const ctx = canvasEl.getContext('2d');

        // Gradient under the line
        const gradientFill = ctx.createLinearGradient(0, 0, 0, 300);
        gradientFill.addColorStop(0, 'rgba(14, 165, 233, 0.4)'); // Ocean Blue Top
        gradientFill.addColorStop(1, 'rgba(14, 165, 233, 0.0)'); // Transparent Bottom

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($arr_waktu); ?>,
                datasets: [{
                    label: 'Ketinggian Air',
                    data: <?php echo json_encode($arr_level); ?>,
                    borderColor: '#0284c7',        // Ocean Blue
                    borderWidth: 3,
                    backgroundColor: gradientFill,
                    fill: true,
                    tension: 0.35,                 // Soft smooth curve
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
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: { display: false },    // Sembunyikan legend
                    tooltip: {
                        enabled: true,
                        backgroundColor: 'rgba(15, 23, 42, 0.9)', // Dark Slate Background
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
                                let status = "AMAN ";
                                if (val > 40) status = "BAHAYA ";
                                else if (val > 30) status = "WASPADA ";
                                
                                return ` Ketinggian: ${val} cm (${status})`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },  // Sembunyikan garis vertikal
                        ticks: {
                            color: '#64748b',
                            font: { size: 11, family: 'Arial' }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        suggestedMax: 45,          // Menjaga area zona merah (>40cm) selalu terlihat
                        grace: '10%',
                        grid: {
                            color: 'rgba(226, 232, 240, 0.7)', // Garis horizontal tipis
                            borderDash: [4, 4]
                        },
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
    });
</script>

</body>
</html>