{{-- resources/views/peta/index.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Peta Harga Komoditas Jawa Timur</title>
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
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
        }
        
        .header h1 {
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 0;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group select,
        .form-group .date-input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group select:focus,
        .form-group .date-input:focus {
            outline: none;
            border-color: #667eea;
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

        /* Sidebar Legend */
        .sidebar-legend {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
        }

        .sidebar-legend h3 {
            font-size: 14px;
            margin-bottom: 12px;
            color: #333;
            border-bottom: none;
            padding-bottom: 0;
        }

        .legend-item-small {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            font-size: 13px;
        }

        .legend-color-small {
            width: 20px;
            height: 20px;
            border-radius: 3px;
            flex-shrink: 0;
        }

        .legend-text-small {
            flex: 1;
        }

        .legend-text-small strong {
            display: block;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .legend-text-small small {
            display: block;
            color: #666;
            font-size: 12px;
        }

        /* Main Content Area */
        .main-content {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Map Container */
        .map-container {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            height: 600px;
        }
        
        #map {
            height: 600px;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
        }
        
        .loading {
            text-align: center;
            padding: 20px;
            color: #667eea;
            font-weight: 600;
        }
        
        /* Leaflet Popup Custom Style */
        .leaflet-popup-content-wrapper {
            border-radius: 8px;
            box-shadow: 0 3px 14px rgba(0,0,0,0.3);
        }
        
        .popup-content {
            padding: 10px;
        }
        
        .popup-content h3 {
            margin-bottom: 12px;
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 8px;
        }
        
        .popup-info {
            margin-bottom: 8px;
        }
        
        .popup-info strong {
            color: #555;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            color: white;
            margin-left: 8px;
        }
        
        .badge-rendah {
            background-color: #FFEB3B;
            color: #333;
        }
        
        .badge-normal {
            background-color: #4CAF50;
        }
        
        .badge-tinggi {
            background-color: #F44336;
        }
        
        .badge-tidakadadata {
            background-color: #9E9E9E;
        }
        
        /* AI Narasi Section */
        .ai-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .ai-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .ai-section-header h3 {
            color: #333;
            font-size: 18px;
            margin: 0;
        }

        .ai-section-header .ai-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .ai-narasi-box {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 25px;
            border-radius: 10px;
            border-left: 5px solid #667eea;
            margin-bottom: 20px;
            line-height: 1.8;
            color: #333;
            font-size: 15px;
            white-space: pre-line;
        }

        .ai-narasi-box.loading-narasi {
            text-align: center;
            color: #667eea;
            font-style: italic;
        }

        .btn-refresh-narasi {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 8px 18px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: opacity 0.3s;
        }

        .btn-refresh-narasi:hover {
            opacity: 0.85;
        }

        .btn-refresh-narasi:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Statistik Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        .stat-card {
            padding: 18px;
            border-radius: 10px;
            text-align: center;
            color: white;
            box-shadow: 0 2px 6px rgba(0,0,0,0.12);
        }

        .stat-card.sangat-stabil { background: linear-gradient(135deg, #43A047, #66BB6A); }
        .stat-card.stabil { background: linear-gradient(135deg, #1E88E5, #42A5F5); }
        .stat-card.cukup-stabil { background: linear-gradient(135deg, #FB8C00, #FFA726); }
        .stat-card.tidak-stabil { background: linear-gradient(135deg, #E53935, #EF5350); }

        .stat-card .stat-number {
            font-size: 32px;
            font-weight: 700;
            display: block;
        }

        .stat-card .stat-label {
            font-size: 13px;
            opacity: 0.9;
            margin-top: 4px;
        }

        /* Top table */
        .top-tables-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        @media (max-width: 900px) {
            .top-tables-grid {
                grid-template-columns: 1fr;
            }
        }

        .top-table-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .top-table-card h4 {
            margin-bottom: 15px;
            color: #333;
            font-size: 16px;
        }

        .top-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .top-table thead {
            background: #f0f0f0;
        }

        .top-table th {
            padding: 10px;
            text-align: left;
            font-weight: 600;
            color: #555;
            border-bottom: 2px solid #ddd;
        }

        .top-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        .top-table tbody tr:hover {
            background-color: #f5f5f5;
        }

        .stability-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
            color: white;
        }

        .stability-badge.sangat_stabil { background-color: #43A047; }
        .stability-badge.stabil { background-color: #1E88E5; }
        .stability-badge.cukup_stabil { background-color: #FB8C00; }
        .stability-badge.tidak_stabil { background-color: #E53935; }
        
        /* Table Harga Mean */
        .table-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            max-height: 642px;
        }
        
        .table-container h3 {
            margin-bottom: 15px;
            color: #333;
            font-size: 18px;
            flex-shrink: 0;
        }
        
        .table-wrapper {
            overflow-y: auto;
            flex: 1;
        }
        
        .price-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .price-table thead {
            position: sticky;
            top: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            z-index: 10;
        }
        
        .price-table th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #ddd;
        }
        
        .price-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        
        .price-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .price-table tbody tr:hover {
            background-color: #e8e8e8;
            cursor: pointer;
        }
        
        .price-table tbody tr.no-data {
            color: #999;
            font-style: italic;
        }
        
        .price-badge {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        @media (max-width: 768px) {
            .price-table {
                font-size: 14px;
            }
            
            .price-table th,
            .price-table td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🗺️ Peta Harga Komoditas Jawa Timur</h1>
            <p>Visualisasi harga komoditas per kabupaten/kota</p>
        </div>
        
        
        <div class="main-layout">
            <!-- Sidebar -->
            <div class="sidebar">
                <h3>⚙️ Filter Data</h3>
                
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
                    <label for="tanggal_awal">Tanggal Awal:</label>
                    <input type="date" id="tanggal_awal" name="tanggal_awal" class="date-input">
                </div>

                <div class="sidebar-group">
                    <label for="tanggal_akhir">Tanggal Akhir:</label>
                    <input type="date" id="tanggal_akhir" name="tanggal_akhir" class="date-input">
                </div>

                <div class="sidebar-group" style="background: #f0f7ff; padding: 12px; border-radius: 6px; margin-top: 15px;">
                    <strong id="avgPrice" style="font-size: 14px; display: block; margin-bottom: 5px;">Rata-rata: -</strong>
                    <small id="periodInfo" style="color: #666; display: block;">-</small>
                </div>

                <!-- Sidebar Legend -->
                <div class="sidebar-legend">
                    <h3>📊 Legenda</h3>
                    <div class="legend-item-small">
                        <div class="legend-color-small" style="background-color: #FFEB3B;"></div>
                        <div class="legend-text-small">
                            <strong>Rendah</strong>
                            <small>&lt; -10%</small>
                        </div>
                    </div>
                    <div class="legend-item-small">
                        <div class="legend-color-small" style="background-color: #4CAF50;"></div>
                        <div class="legend-text-small">
                            <strong>Normal</strong>
                            <small>±10%</small>
                        </div>
                    </div>
                    <div class="legend-item-small">
                        <div class="legend-color-small" style="background-color: #F44336;"></div>
                        <div class="legend-text-small">
                            <strong>Tinggi</strong>
                            <small>&gt; +10%</small>
                        </div>
                    </div>
                    <div class="legend-item-small">
                        <div class="legend-color-small" style="background-color: #9E9E9E;"></div>
                        <div class="legend-text-small">
                            <strong>No Data</strong>
                            <small>Tidak ada</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="main-content">
                <!-- Map -->
                <div class="map-container">
                    <div id="loading" class="loading">Memuat data peta...</div>
                    <div id="map" style="display: none;"></div>
                </div>

                <!-- Table Mean Price -->
                <div class="table-container">
                    <h3>📈 Harga Mean per Wilayah</h3>
                    <div class="table-wrapper">
                        <table class="price-table">
                            <thead>
                                <tr>
                                    <th>Kabupaten/Kota</th>
                                    <th>Harga Mean</th>
                                    <th>Kategori</th>
                                </tr>
                            </thead>
                            <tbody id="priceTableBody">
                                <tr>
                                    <td colspan="3" style="text-align: center; color: #999;">Pilih komoditas dan tanggal</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- AI Analisis Stabilitas Harga Section -->
        <div class="ai-section" id="aiSection">
            <div class="ai-section-header">
                <h3>🤖 Analisis AI - Stabilitas Harga (<span id="periodeAnalisisText">-</span>)</h3>
                <div>
                    <span class="ai-badge">Powered by Gemini AI</span>
                    <button class="btn-refresh-narasi" id="btnRefreshNarasi" onclick="refreshNarasi()" title="Perbarui narasi AI">
                        🔄 Refresh
                    </button>
                </div>
            </div>

            <!-- Statistik Cards -->
            <div class="stats-grid" id="statsGrid">
                <div class="stat-card sangat-stabil">
                    <span class="stat-number" id="statSangatStabil">-</span>
                    <span class="stat-label">Sangat Stabil</span>
                </div>
                <div class="stat-card stabil">
                    <span class="stat-number" id="statStabil">-</span>
                    <span class="stat-label">Stabil</span>
                </div>
                <div class="stat-card cukup-stabil">
                    <span class="stat-number" id="statCukupStabil">-</span>
                    <span class="stat-label">Cukup Stabil</span>
                </div>
                <div class="stat-card tidak-stabil">
                    <span class="stat-number" id="statTidakStabil">-</span>
                    <span class="stat-label">Tidak Stabil</span>
                </div>
            </div>

            <!-- Narasi AI -->
            <div class="ai-narasi-box loading-narasi" id="narasiBox">
                Memuat analisis AI...
            </div>

            <!-- Top Tables -->
            <div class="top-tables-grid">
                <div class="top-table-card">
                    <h4>🏆 Top 5 Paling Stabil</h4>
                    <table class="top-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Komoditas</th>
                                <th>Wilayah</th>
                                <th>CV</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="topStabilBody">
                            <tr><td colspan="5" style="text-align:center;color:#999;">Memuat...</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="top-table-card">
                    <h4>⚠️ Top 5 Paling Fluktuatif</h4>
                    <table class="top-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Komoditas</th>
                                <th>Wilayah</th>
                                <th>CV</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="topTidakStabilBody">
                            <tr><td colspan="5" style="text-align:center;color:#999;">Memuat...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
        // Batas wilayah Jawa Timur (Southwest dan Northeast)
        const jatimBounds = [
            [-8.8, 111.0],  // Southwest (bawah kiri)
            [-6.8, 114.5]   // Northeast (atas kanan)
        ];
        
        // Inisialisasi peta - Koordinat Jawa Timur dengan pembatasan
        const map = L.map('map', {
            center: [-7.5, 112.5],
            zoom: 9,
            minZoom: 8,        // Zoom minimal
            maxZoom: 11,       // Zoom maksimal
            maxBounds: jatimBounds,     // Batasi area
            maxBoundsViscosity: 1.0,    // Tidak bisa keluar dari bounds
            dragging: true,             // ✅ Bisa digeser dalam batas
            scrollWheelZoom: true,      // ✅ Zoom dengan scroll
            doubleClickZoom: true,      // ✅ Double click zoom
            boxZoom: false,             // ❌ Nonaktifkan box zoom
            keyboard: true,             // ✅ Keyboard navigation
            touchZoom: true,            // ✅ Touch zoom
            zoomControl: true           // ✅ Tampilkan tombol zoom +/-
        });
        
        // Tambahkan tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 11,
            bounds: jatimBounds
        }).addTo(map);
        
        let markers = [];
        
        // Koordinat kabupaten/kota di Jawa Timur berdasarkan tabel Anda
        const koordinatWilayah = {
            'Kota Surabaya': [-7.2575, 112.7521],
            'Kota Malang': [-7.9666, 112.6326],
            'Kota Kediri': [-7.8147, 112.0125],
            'Kabupaten Jember': [-8.1706, 113.6998],
            'Kabupaten Bangkalan': [-7.0454, 112.7349],
            'Kabupaten Banyuwangi': [-8.2190, 114.3691],
            'Kabupaten Blitar': [-8.0954, 112.1609],
            'Kabupaten Bojonegoro': [-7.1502, 111.8817],
            'Kabupaten Bondowoso': [-7.9139, 113.8214],
            'Kabupaten Gresik': [-7.1554, 112.6544],
            'Kabupaten Jombang': [-7.5459, 112.2344],
            'Kabupaten Kediri': [-7.8486, 112.1809],
            'Kabupaten Lamongan': [-7.1172, 112.4177],
            'Kabupaten Lumajang': [-8.1332, 113.2248],
            'Kabupaten Madiun': [-7.6298, 111.5239],
            'Kabupaten Magetan': [-7.6477, 111.3401],
            'Kabupaten Malang': [-8.1668, 112.7139],
            'Kabupaten Mojokerto': [-7.4664, 112.4338],
            'Kabupaten Nganjuk': [-7.6054, 111.9039],
            'Kabupaten Ngawi': [-7.4039, 111.4464],
            'Kabupaten Pacitan': [-8.2069, 111.0919],
            'Kabupaten Pamekasan': [-7.1568, 113.4747],
            'Kabupaten Pasuruan': [-7.7297, 112.9007],
            'Kabupaten Ponorogo': [-7.8659, 111.4616],
            'Kabupaten Probolinggo': [-7.8754, 113.2159],
            'Kabupaten Sampang': [-7.1847, 113.2391],
            'Kabupaten Sidoarjo': [-7.4467, 112.7185],
            'Kabupaten Situbondo': [-7.7063, 114.0095],
            'Kabupaten Sumenep': [-7.0170, 113.8554],
            'Kabupaten Trenggalek': [-8.0500, 111.7089],
            'Kabupaten Tuban': [-6.8977, 111.9560],
            'Kabupaten Tulungagung': [-8.0657, 111.9027],
            'Kota Batu': [-7.8747, 112.5287],
            'Kota Blitar': [-8.0983, 112.1681],
            'Kota Madiun': [-7.6298, 111.5239],
            'Kota Mojokerto': [-7.4724, 112.4338],
            'Kota Pasuruan': [-7.6453, 112.9072],
            'Kota Probolinggo': [-7.7543, 113.2159]
        };
        
        // Set tanggal default ke hari ini (akhir) dan 6 hari yang lalu (awal)
        const today = new Date();
        const sixDaysAgo = new Date();
        sixDaysAgo.setDate(today.getDate() - 6);

        document.getElementById('tanggal_akhir').valueAsDate = today;
        document.getElementById('tanggal_awal').valueAsDate = sixDaysAgo;
        
        // Fungsi untuk update tabel harga mean
        function updatePriceTable(data) {
            const tbody = document.getElementById('priceTableBody');
            tbody.innerHTML = '';
            
            // Filter dan sort data: yang punya harga dulu, lalu yang tidak ada data
            const dataWithPrice = data.filter(item => item.avg_harga > 0)
                .sort((a, b) => b.avg_harga - a.avg_harga);
            const dataWithoutPrice = data.filter(item => item.avg_harga === 0)
                .sort((a, b) => a.nama.localeCompare(b.nama));
            
            const sortedData = [...dataWithPrice, ...dataWithoutPrice];
            
            sortedData.forEach(item => {
                const row = document.createElement('tr');
                if (item.avg_harga === 0) {
                    row.classList.add('no-data');
                }
                
                const namaCell = document.createElement('td');
                namaCell.innerHTML = `
                    <span class="price-badge" style="background-color: ${item.color};"></span>
                    ${item.nama}
                `;
                
                const hargaCell = document.createElement('td');
                if (item.avg_harga > 0) {
                    hargaCell.textContent = 'Rp' + item.avg_harga.toLocaleString('id-ID');
                } else {
                    hargaCell.textContent = 'Tidak ada data';
                }
                
                const kategoriCell = document.createElement('td');
                if (item.avg_harga > 0) {
                    const badge = document.createElement('span');
                    badge.style.display = 'inline-block';
                    badge.style.padding = '6px 12px';
                    badge.style.borderRadius = '20px';
                    badge.style.fontSize = '12px';
                    badge.style.fontWeight = '600';
                    badge.style.whiteSpace = 'nowrap';
                    badge.textContent = item.kategori_harga;
                    
                    if (item.kategori_harga === 'Rendah') {
                        badge.style.backgroundColor = '#FFEB3B';
                        badge.style.color = '#333';
                    } else if (item.kategori_harga === 'Normal') {
                        badge.style.backgroundColor = '#4CAF50';
                        badge.style.color = '#fff';
                    } else if (item.kategori_harga === 'Tinggi') {
                        badge.style.backgroundColor = '#F44336';
                        badge.style.color = '#fff';
                    } else {
                        badge.style.backgroundColor = '#9E9E9E';
                        badge.style.color = '#fff';
                    }
                    
                    kategoriCell.appendChild(badge);
                } else {
                    kategoriCell.textContent = '-';
                    kategoriCell.style.color = '#999';
                }
                
                row.appendChild(namaCell);
                row.appendChild(hargaCell);
                row.appendChild(kategoriCell);
                tbody.appendChild(row);
            });
        }
        
        // Fungsi untuk memuat data
        function loadData() {
            const komoditasNama = document.getElementById('komoditas').value;
            const tanggalAwal = document.getElementById('tanggal_awal').value;
            const tanggalAkhir = document.getElementById('tanggal_akhir').value;
            const loading = document.getElementById('loading');
            const mapElement = document.getElementById('map');
            
            if (!tanggalAwal || !tanggalAkhir) {
                alert('Silakan pilih tanggal awal dan tanggal akhir');
                loading.style.display = 'none';
                return;
            }
            
            loading.style.display = 'block';
            mapElement.style.display = 'none';
            
            // Hapus marker lama
            markers.forEach(marker => map.removeLayer(marker));
            markers = [];
            
            // Fetch data dari API
            fetch(`/api/peta/data?komoditas_nama=${encodeURIComponent(komoditasNama)}&tanggal_awal=${tanggalAwal}&tanggal_akhir=${tanggalAkhir}`, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(result => {
                console.log('Data received:', result); // Debug
                
                loading.style.display = 'none';
                mapElement.style.display = 'block';
                
                // Fix: Refresh map size setelah container terlihat
                setTimeout(() => {
                    map.invalidateSize();
                }, 100);
                
                // Update info rata-rata
                if (result.rata_rata_keseluruhan > 0) {
                    document.getElementById('avgPrice').textContent = 
                        'Rp ' + result.rata_rata_keseluruhan.toLocaleString('id-ID');
                } else {
                    document.getElementById('avgPrice').textContent = 'Tidak ada data';
                }
                
                // Update info tanggal
                const dateAwalObj = new Date(tanggalAwal);
                const dateAkhirObj = new Date(tanggalAkhir);
                const options = { year: 'numeric', month: 'long', day: 'numeric' };
                const formattedAwal = dateAwalObj.toLocaleDateString('id-ID', options);
                const formattedAkhir = dateAkhirObj.toLocaleDateString('id-ID', options);
                document.getElementById('periodInfo').textContent = `${formattedAwal} - ${formattedAkhir}`;
                
                // Update tabel harga mean
                updatePriceTable(result.data);
                
                // Tambahkan marker untuk setiap kabupaten
                result.data.forEach(item => {
                    console.log('Processing:', item.nama); // Debug
                    const namaWilayah = item.nama;
                    let koordinat = koordinatWilayah[namaWilayah];
                    
                    // Jika koordinat tidak ditemukan, skip
                    if (!koordinat) {
                        console.warn(`⚠️ Koordinat untuk "${namaWilayah}" tidak ditemukan`);
                        console.log('Available coordinates:', Object.keys(koordinatWilayah));
                        return;
                    }
                    
                    console.log(`✅ Marker created for ${namaWilayah}:`, koordinat, item.color);
                    
                    // Buat circle marker
                    const marker = L.circleMarker(koordinat, {
                        radius: 20,           // Perbesar dari 15 ke 20
                        fillColor: item.color,
                        color: '#333',        // Outline lebih gelap
                        weight: 3,            // Outline lebih tebal
                        opacity: 1,
                        fillOpacity: 0.9      // Lebih solid
                    }).addTo(map);
                    
                    // Buat popup content
                    const badgeClass = `badge-${item.kategori_harga.toLowerCase().replace(/\s+/g, '')}`;
                    let popupContent;
                    
                    if (item.avg_harga === 0) {
                        // Popup untuk wilayah tanpa data
                        popupContent = `
                            <div class="popup-content">
                                <h3>${item.nama}</h3>
                                <div class="popup-info">
                                    <strong>Status:</strong> 
                                    <span class="badge ${badgeClass}">${item.kategori_harga}</span>
                                </div>
                                <div class="popup-info">
                                    <small>Tidak ada data harga untuk komoditas ini pada tanggal yang dipilih</small>
                                </div>
                            </div>
                        `;
                    } else {
                        // Popup normal untuk wilayah dengan data
                        popupContent = `
                            <div class="popup-content">
                                <h3>${item.nama}</h3>
                                <div class="popup-info">
                                    <strong>Rata-rata Harga:</strong> Rp ${item.avg_harga.toLocaleString('id-ID')}
                                </div>
                                <div class="popup-info">
                                    <strong>Kategori:</strong> 
                                    <span class="badge ${badgeClass}">${item.kategori_harga}</span>
                                </div>
                                <div class="popup-info">
                                    <strong>Selisih:</strong> ${item.selisih_persen > 0 ? '+' : ''}${item.selisih_persen}%
                                </div>
                                <div class="popup-info">
                                    <strong>Jumlah Data:</strong> ${item.jumlah_data} pasar
                                </div>
                            </div>
                        `;
                    }
                    
                    // Bind popup with better positioning
                    marker.bindPopup(popupContent, {
                        maxWidth: 300,
                        autoPan: true,              // Auto pan map when popup opens
                        autoPanPaddingTopLeft: [50, 150],   // Padding lebih besar dari top-left
                        autoPanPaddingBottomRight: [50, 50], // Padding dari bottom-right
                        closeButton: true,
                        offset: [0, -20]           // Offset popup lebih tinggi dari marker
                    });
                    
                    markers.push(marker);
                });
                
                console.log(`Total markers created: ${markers.length} dari ${result.data.length} data`);
            })
            .catch(error => {
                console.error('❌ Error:', error);
                loading.textContent = 'Gagal memuat data: ' + error.message;
                loading.style.color = 'red';
            });
        }
        
        // Event listener untuk perubahan komoditas dan tanggal
        document.getElementById('komoditas').addEventListener('change', loadData);
        document.getElementById('tanggal_awal').addEventListener('change', () => {
            loadData();
            loadAnalisisAI();
        });
        document.getElementById('tanggal_akhir').addEventListener('change', () => {
            loadData();
            loadAnalisisAI();
        });
        
        // Load data awal
        loadData();

        // ========================================
        // AI Analisis Stabilitas Harga
        // ========================================
        
        function loadAnalisisAI() {
            const tanggalAwal = document.getElementById('tanggal_awal').value;
            const tanggalAkhir = document.getElementById('tanggal_akhir').value;
            if (!tanggalAwal || !tanggalAkhir) return;

            const narasiBox = document.getElementById('narasiBox');
            narasiBox.className = 'ai-narasi-box loading-narasi';
            narasiBox.textContent = '⏳ Memuat analisis AI stabilitas harga...';

            fetch(`/api/analisis/stabilitas?tanggal_awal=${tanggalAwal}&tanggal_akhir=${tanggalAkhir}`, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(result => {
                if (!result.success) {
                    narasiBox.className = 'ai-narasi-box';
                    narasiBox.textContent = result.message || 'Belum ada data untuk analisis.';
                    resetStatsAndTables();
                    document.getElementById('periodeAnalisisText').textContent = '-';
                    return;
                }

                // Update statistik cards
                const stats = result.statistik_umum;
                document.getElementById('statSangatStabil').textContent = stats.jumlah_sangat_stabil || 0;
                document.getElementById('statStabil').textContent = stats.jumlah_stabil || 0;
                document.getElementById('statCukupStabil').textContent = stats.jumlah_cukup_stabil || 0;
                document.getElementById('statTidakStabil').textContent = stats.jumlah_tidak_stabil || 0;

                // Update periode analisis text
                const dateAwalObj = new Date(result.periode.dari);
                const dateAkhirObj = new Date(result.periode.sampai);
                const options = { year: 'numeric', month: 'long', day: 'numeric' };
                document.getElementById('periodeAnalisisText').textContent = 
                    `${dateAwalObj.toLocaleDateString('id-ID', options)} s/d ${dateAkhirObj.toLocaleDateString('id-ID', options)}`;

                // Update narasi
                narasiBox.className = 'ai-narasi-box';
                narasiBox.textContent = result.narasi || 'Narasi tidak tersedia.';

                // Update top stabil table
                updateTopTable('topStabilBody', result.top_stabil);

                // Update top tidak stabil table
                updateTopTable('topTidakStabilBody', result.top_tidak_stabil);
            })
            .catch(error => {
                console.error('Error loading AI analysis:', error);
                narasiBox.className = 'ai-narasi-box';
                narasiBox.textContent = '❌ Gagal memuat analisis AI: ' + error.message;
            });
        }

        function updateTopTable(tbodyId, data) {
            const tbody = document.getElementById(tbodyId);
            tbody.innerHTML = '';

            if (!data || data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#999;">Tidak ada data</td></tr>';
                return;
            }

            data.forEach((item, index) => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${index + 1}</td>
                    <td>${item.komoditas_nama}</td>
                    <td>${item.kabupaten_nama}</td>
                    <td>${item.cv_persen}%</td>
                    <td><span class="stability-badge ${item.status_stabilitas}">${item.label_stabilitas}</span></td>
                `;
                tbody.appendChild(row);
            });
        }

        function resetStatsAndTables() {
            document.getElementById('statSangatStabil').textContent = '-';
            document.getElementById('statStabil').textContent = '-';
            document.getElementById('statCukupStabil').textContent = '-';
            document.getElementById('statTidakStabil').textContent = '-';
            document.getElementById('topStabilBody').innerHTML = '<tr><td colspan="5" style="text-align:center;color:#999;">Tidak ada data</td></tr>';
            document.getElementById('topTidakStabilBody').innerHTML = '<tr><td colspan="5" style="text-align:center;color:#999;">Tidak ada data</td></tr>';
        }

        function refreshNarasi() {
            const tanggalAwal = document.getElementById('tanggal_awal').value;
            const tanggalAkhir = document.getElementById('tanggal_akhir').value;
            if (!tanggalAwal || !tanggalAkhir) return;

            const btn = document.getElementById('btnRefreshNarasi');
            const narasiBox = document.getElementById('narasiBox');

            btn.disabled = true;
            btn.textContent = '⏳ Memproses...';
            narasiBox.className = 'ai-narasi-box loading-narasi';
            narasiBox.textContent = '🔄 Menggenerate narasi baru dari Gemini AI...';

            fetch(`/api/analisis/refresh-narasi`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ 
                    tanggal_awal: tanggalAwal,
                    tanggal_akhir: tanggalAkhir
                })
            })
            .then(response => response.json())
            .then(result => {
                narasiBox.className = 'ai-narasi-box';
                narasiBox.textContent = result.narasi || 'Narasi tidak tersedia.';
            })
            .catch(error => {
                narasiBox.className = 'ai-narasi-box';
                narasiBox.textContent = '❌ Gagal refresh narasi: ' + error.message;
            })
            .finally(() => {
                btn.disabled = false;
                btn.textContent = '🔄 Refresh';
            });
        }

        // Load AI analysis after a short delay to not block the map loading
        setTimeout(loadAnalisisAI, 500);
    </script>
</body>
</html>