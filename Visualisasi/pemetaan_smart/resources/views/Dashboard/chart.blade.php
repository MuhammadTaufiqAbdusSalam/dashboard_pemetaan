{{-- resources/views/Dashboard/chart.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Grafik Tren Harga Komoditas Jawa Timur</title>
    
    <!-- ApexCharts CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts@3.41.0/dist/apexcharts.min.css" />
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .header-title {
            flex: 1;
            min-width: 280px;
        }
        
        .header h1 {
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 14px;
        }

        .nav-buttons {
            display: flex;
            gap: 12px;
        }

        .nav-btn {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.25);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .nav-btn.active {
            background: white;
            color: #764ba2;
            border-color: white;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }
        
        /* Main Layout dengan Sidebar */
        .main-layout {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        @media (max-width: 1024px) {
            .main-layout {
                grid-template-columns: 250px 1fr;
                gap: 15px;
            }
        }

        @media (max-width: 768px) {
            .main-layout {
                grid-template-columns: 1fr;
            }
        }

        /* Sidebar */
        .sidebar {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        .sidebar h3 {
            font-size: 16px;
            margin-bottom: 15px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 8px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }

        .sidebar-group {
            margin-bottom: 20px;
        }

        .sidebar-group:last-child {
            margin-bottom: 0;
        }

        .sidebar-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .sidebar-group select,
        .sidebar-group input {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .sidebar-group select:focus,
        .sidebar-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        /* Main Content Area */
        .main-content {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Chart Card */
        .chart-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .chart-card h3 {
            font-size: 18px;
            color: #333;
            margin-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .chart-container {
            width: 100%;
            min-height: 350px;
            position: relative;
        }
        
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.85);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10;
            border-radius: 8px;
            font-weight: 600;
            color: #667eea;
            font-size: 16px;
        }

        .loading-spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin-right: 12px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Quick Stats Grid */
        .quick-stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 10px;
        }

        @media (max-width: 576px) {
            .quick-stats-grid {
                grid-template-columns: 1fr;
            }
        }

        .quick-stat-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }

        .quick-stat-label {
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
            display: block;
        }

        .quick-stat-value {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-title">
                <h1>📊 Grafik Tren Harga Komoditas</h1>
                <p>Analisis tren pergerakan harga komoditas dan perbandingan antar wilayah Jawa Timur</p>
            </div>
            <div class="nav-buttons">
                <a href="{{ route('Dashboard.index') }}" class="nav-btn">
                    🗺️ Peta Wilayah
                </a>
                <a href="{{ route('Dashboard.chart') }}" class="nav-btn active">
                    📊 Grafik Tren
                </a>
            </div>
        </div>
        
        <div class="main-layout">
            <!-- Sidebar Filters -->
            <div class="sidebar">
                <h3>⚙️ Filter Grafik</h3>
                
                <div class="sidebar-group">
                    <label for="komoditas">Pilih Komoditas:</label>
                    <select id="komoditas" name="komoditas_nama">
                        @foreach($komoditasList as $komoditas)
                            <option value="{{ $komoditas->komoditas_nama }}" {{ $komoditas->komoditas_nama == $selectedKomoditas ? 'selected' : '' }}>
                                {{ $komoditas->komoditas_nama }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="sidebar-group">
                    <label for="kabupaten">Bandingkan dengan Kabupaten/Kota:</label>
                    <select id="kabupaten" name="kabupaten_id">
                        <option value="">-- Tanpa Perbandingan (Jatim Saja) --</option>
                        @foreach($kabupatenList as $kabupaten)
                            <option value="{{ $kabupaten->id }}" {{ $kabupaten->id == $selectedKabupaten ? 'selected' : '' }}>
                                {{ $kabupaten->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="sidebar-group">
                    <label for="tanggal_awal">Tanggal Awal:</label>
                    <input type="date" id="tanggal_awal" name="tanggal_awal">
                </div>

                <div class="sidebar-group">
                    <label for="tanggal_akhir">Tanggal Akhir:</label>
                    <input type="date" id="tanggal_akhir" name="tanggal_akhir">
                </div>
            </div>

            <!-- Main Content (Charts) -->
            <div class="main-content">
                
                <!-- Quick Stats for Selected Commodity -->
                <div class="quick-stats-grid">
                    <div class="quick-stat-box">
                        <span class="quick-stat-label">Rata-rata Jawa Timur</span>
                        <span class="quick-stat-value" id="statAvgJatim">Rp0</span>
                    </div>
                    <div class="quick-stat-box">
                        <span class="quick-stat-label">Harga Tertinggi</span>
                        <span class="quick-stat-value" id="statMaxPrice">Rp0</span>
                    </div>
                    <div class="quick-stat-box">
                        <span class="quick-stat-label">Harga Terendah</span>
                        <span class="quick-stat-value" id="statMinPrice">Rp0</span>
                    </div>
                </div>

                <!-- Line Chart Card -->
                <div class="chart-card">
                    <h3>📈 Tren Perkembangan Harga Harian</h3>
                    <div class="chart-container">
                        <div id="chartLoadingLine" class="loading-overlay">
                            <div class="loading-spinner"></div>
                            Memuat data grafik tren...
                        </div>
                        <div id="trendLineChart"></div>
                    </div>
                </div>

                <!-- Bar Chart Card -->
                <div class="chart-card">
                    <h3>📊 Perbandingan Harga Rata-rata Antar Wilayah</h3>
                    <div class="chart-container">
                        <div id="chartLoadingBar" class="loading-overlay">
                            <div class="loading-spinner"></div>
                            Memuat data grafik perbandingan...
                        </div>
                        <div id="comparisonBarChart"></div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- ApexCharts JS -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.41.0/dist/apexcharts.min.js"></script>
    
    <script>
        // Set default date range to last 30 days for better trend views, but allow custom
        const today = new Date();
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(today.getDate() - 30);

        document.getElementById('tanggal_akhir').valueAsDate = today;
        document.getElementById('tanggal_awal').valueAsDate = thirtyDaysAgo;

        // Inisialisasi Chart Objects agar bisa diupdate
        let lineChart = null;
        let barChart = null;

        // Fungsi Load Chart Data
        function loadCharts() {
            const komoditasNama = document.getElementById('komoditas').value;
            const kabupatenId = document.getElementById('kabupaten').value;
            const kabupatenText = document.getElementById('kabupaten').options[document.getElementById('kabupaten').selectedIndex].text;
            const tanggalAwal = document.getElementById('tanggal_awal').value;
            const tanggalAkhir = document.getElementById('tanggal_akhir').value;

            if (!tanggalAwal || !tanggalAkhir) {
                alert('Pilih tanggal awal dan akhir terlebih dahulu.');
                return;
            }

            // Tampilkan loading overlay
            document.getElementById('chartLoadingLine').style.display = 'flex';
            document.getElementById('chartLoadingBar').style.display = 'flex';

            // Fetch data dari API rute baru
            fetch(`/api/peta/chart-data?komoditas_nama=${encodeURIComponent(komoditasNama)}&kabupaten_id=${kabupatenId}&tanggal_awal=${tanggalAwal}&tanggal_akhir=${tanggalAkhir}`)
                .then(response => {
                    if (!response.ok) throw new Error('Gagal mengambil data dari server');
                    return response.json();
                })
                .then(data => {
                    // Sembunyikan loading overlay
                    document.getElementById('chartLoadingLine').style.display = 'none';
                    document.getElementById('chartLoadingBar').style.display = 'none';

                    // 1. Proses Data Line Chart
                    const labels = [];
                    const seriesJatim = [];
                    const seriesKab = [];

                    // Buat set tanggal yang unik dari kedua dataset untuk dicocokkan
                    const tanggalSet = new Set();
                    data.jatim.forEach(item => tanggalSet.add(item.tanggal));
                    if (data.kabupaten && data.kabupaten.length > 0) {
                        data.kabupaten.forEach(item => tanggalSet.add(item.tanggal));
                    }
                    
                    const sortedTanggal = Array.from(tanggalSet).sort();

                    // Format tanggal untuk label
                    const formatTanggalId = (strTanggal) => {
                        const date = new Date(strTanggal);
                        return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
                    };

                    sortedTanggal.forEach(tgl => {
                        labels.push(formatTanggalId(tgl));
                        
                        const jatimItem = data.jatim.find(item => item.tanggal === tgl);
                        seriesJatim.push(jatimItem ? parseInt(jatimItem.avg_harga) : null);

                        if (data.kabupaten && data.kabupaten.length > 0) {
                            const kabItem = data.kabupaten.find(item => item.tanggal === tgl);
                            seriesKab.push(kabItem ? parseInt(kabItem.avg_harga) : null);
                        }
                    });

                    // Update Quick Stats
                    let allPrices = [...seriesJatim];
                    if (data.kabupaten && data.kabupaten.length > 0) {
                        allPrices = [...allPrices, ...seriesKab];
                    }
                    // Filter null
                    allPrices = allPrices.filter(p => p !== null && p > 0);

                    const avgJatim = data.rata_rata_keseluruhan || 0;
                    const maxPrice = allPrices.length > 0 ? Math.max(...allPrices) : 0;
                    const minPrice = allPrices.length > 0 ? Math.min(...allPrices) : 0;

                    document.getElementById('statAvgJatim').textContent = avgJatim > 0 ? 'Rp ' + avgJatim.toLocaleString('id-ID') : 'Tidak ada data';
                    document.getElementById('statMaxPrice').textContent = maxPrice > 0 ? 'Rp ' + maxPrice.toLocaleString('id-ID') : 'Tidak ada data';
                    document.getElementById('statMinPrice').textContent = minPrice > 0 ? 'Rp ' + minPrice.toLocaleString('id-ID') : 'Tidak ada data';

                    // Series data untuk Line Chart
                    const lineSeries = [{
                        name: 'Rata-rata Jawa Timur',
                        data: seriesJatim
                    }];

                    if (kabupatenId && data.kabupaten && data.kabupaten.length > 0) {
                        lineSeries.push({
                            name: kabupatenText,
                            data: seriesKab
                        });
                    }

                    // Render atau Update Line Chart
                    const lineOptions = {
                        series: lineSeries,
                        chart: {
                            type: 'line',
                            height: 350,
                            toolbar: { show: true },
                            fontFamily: 'inherit',
                            animations: { enabled: true, easing: 'easeinout', speed: 800 }
                        },
                        colors: ['#667eea', '#f43f5e'],
                        stroke: { width: 3, curve: 'smooth' },
                        grid: { borderColor: '#f1f5f9' },
                        xaxis: {
                            categories: labels,
                            labels: { rotate: -45, style: { colors: '#64748b', fontSize: '12px' } }
                        },
                        yaxis: {
                            labels: {
                                formatter: function (val) {
                                    return 'Rp ' + Math.round(val).toLocaleString('id-ID');
                                },
                                style: { colors: '#64748b' }
                            }
                        },
                        tooltip: {
                            y: {
                                formatter: function (val) {
                                    return 'Rp ' + Math.round(val).toLocaleString('id-ID');
                                }
                            }
                        },
                        markers: { size: 4 }
                    };

                    if (lineChart) {
                        lineChart.updateOptions(lineOptions);
                    } else {
                        lineChart = new ApexCharts(document.querySelector("#trendLineChart"), lineOptions);
                        lineChart.render();
                    }


                    // 2. Proses Data Bar Chart (Perbandingan Wilayah)
                    const barLabels = [];
                    const barSeriesData = [];
                    const colors = [];

                    // Batasi maksimal 15 wilayah agar grafik tidak terlalu padat
                    const comparisonLimit = data.perbandingan.slice(0, 15);

                    comparisonLimit.forEach(item => {
                        barLabels.push(item.kabupaten_nama);
                        barSeriesData.push(parseInt(item.avg_harga));
                        
                        // Berikan warna berbeda jika ini wilayah perbandingan yang dipilih
                        if (kabupatenId && item.kabupaten_nama === kabupatenText) {
                            colors.push('#f43f5e'); // Merah muda terang untuk yang terpilih
                        } else {
                            colors.push('#764ba2'); // Ungu default
                        }
                    });

                    const barOptions = {
                        series: [{
                            name: 'Harga Rata-rata',
                            data: barSeriesData
                        }],
                        chart: {
                            type: 'bar',
                            height: 380,
                            toolbar: { show: true },
                            fontFamily: 'inherit'
                        },
                        plotOptions: {
                            bar: {
                                barHeight: '70%',
                                distributed: true, // mengizinkan warna dinamis per bar
                                horizontal: true,
                                dataLabels: { position: 'bottom' }
                            }
                        },
                        colors: colors,
                        grid: { borderColor: '#f1f5f9' },
                        xaxis: {
                            categories: barLabels,
                            labels: {
                                formatter: function (val) {
                                    return 'Rp ' + Math.round(val).toLocaleString('id-ID');
                                },
                                style: { colors: '#64748b' }
                            }
                        },
                        yaxis: {
                            labels: { style: { colors: '#64748b', fontSize: '11px', fontWeight: 600 } }
                        },
                        tooltip: {
                            y: {
                                formatter: function (val) {
                                    return 'Rp ' + Math.round(val).toLocaleString('id-ID');
                                }
                            }
                        },
                        dataLabels: {
                            enabled: true,
                            textAnchor: 'start',
                            style: { colors: ['#fff'], fontSize: '12px' },
                            formatter: function (val) {
                                return 'Rp ' + Math.round(val).toLocaleString('id-ID');
                            },
                            offsetX: 10
                        },
                        legend: { show: false } // Sembunyikan legenda terdistribusi
                    };

                    if (barChart) {
                        barChart.updateOptions(barOptions);
                    } else {
                        barChart = new ApexCharts(document.querySelector("#comparisonBarChart"), barOptions);
                        barChart.render();
                    }
                })
                .catch(error => {
                    console.error('Error loading chart data:', error);
                    document.getElementById('chartLoadingLine').textContent = '⚠️ Gagal memuat data: ' + error.message;
                    document.getElementById('chartLoadingBar').textContent = '⚠️ Gagal memuat data: ' + error.message;
                });
        }

        // Event listener untuk perubahan filters
        document.getElementById('komoditas').addEventListener('change', loadCharts);
        document.getElementById('kabupaten').addEventListener('change', loadCharts);
        document.getElementById('tanggal_awal').addEventListener('change', loadCharts);
        document.getElementById('tanggal_akhir').addEventListener('change', loadCharts);

        // Load awal saat halaman siap
        loadCharts();
    </script>
</body>
</html>
