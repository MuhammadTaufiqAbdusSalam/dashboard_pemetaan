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
        
        .controls {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
        
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .map-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        #map {
            height: 600px;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
        }
        
        .legend {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .legend h3 {
            margin-bottom: 15px;
            color: #333;
            font-size: 18px;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            padding: 10px;
            border-radius: 6px;
            background: #f9f9f9;
        }
        
        .legend-color {
            width: 40px;
            height: 40px;
            border-radius: 6px;
            margin-right: 15px;
            border: 2px solid #ddd;
        }
        
        .legend-text {
            flex: 1;
        }
        
        .legend-text strong {
            display: block;
            color: #333;
            margin-bottom: 3px;
        }
        
        .legend-text small {
            color: #666;
            font-size: 13px;
        }
        
        .info-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .info-box h4 {
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .info-box p {
            color: #666;
            line-height: 1.6;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🗺️ Peta Harga Komoditas Jawa Timur</h1>
            <p>Visualisasi harga komoditas berdasarkan kategori per kabupaten/kota</p>
        </div>
        
        <div class="controls">
            <div class="form-group">
                <label for="kategori">Pilih Kategori Komoditas:</label>
                <select id="kategori" name="kategori_id">
                    @foreach($kategoriList as $kategori)
                        <option value="{{ $kategori->id }}" {{ $kategori->id == $selectedKategori ? 'selected' : '' }}>
                            {{ $kategori->kategori }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        
        <div class="info-box">
            <h4>ℹ️ Informasi</h4>
            <p id="infoText">Rata-rata harga keseluruhan: <strong id="avgPrice">-</strong></p>
        </div>
        
        <div class="map-container">
            <div id="loading" class="loading">Memuat data peta...</div>
            <div id="map" style="display: none;"></div>
        </div>
        
        <div class="legend">
            <h3>📊 Legenda Kategori Harga</h3>
            <div class="legend-item">
                <div class="legend-color" style="background-color: #FFEB3B;"></div>
                <div class="legend-text">
                    <strong>Harga Rendah</strong>
                    <small>Lebih rendah dari 10% rata-rata</small>
                </div>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background-color: #4CAF50;"></div>
                <div class="legend-text">
                    <strong>Harga Normal</strong>
                    <small>Dalam rentang ±10% dari rata-rata</small>
                </div>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background-color: #F44336;"></div>
                <div class="legend-text">
                    <strong>Harga Tinggi</strong>
                    <small>Lebih tinggi dari 10% rata-rata</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
        // Inisialisasi peta - Koordinat Jawa Timur
        const map = L.map('map').setView([-7.5, 112.5], 9); // Zoom lebih dekat dari 8 ke 9
        
        // Tambahkan tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 18,
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
        
        // Fungsi untuk memuat data
        function loadData() {
            const kategoriId = document.getElementById('kategori').value;
            const loading = document.getElementById('loading');
            const mapElement = document.getElementById('map');
            
            loading.style.display = 'block';
            mapElement.style.display = 'none';
            
            // Hapus marker lama
            markers.forEach(marker => map.removeLayer(marker));
            markers = [];
            
            // Fetch data dari API
            fetch(`/api/peta/data?kategori_id=${kategoriId}`, {
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
                
                // Update info rata-rata
                if (result.rata_rata_keseluruhan > 0) {
                    document.getElementById('avgPrice').textContent = 
                        'Rp ' + result.rata_rata_keseluruhan.toLocaleString('id-ID');
                } else {
                    document.getElementById('avgPrice').textContent = 'Tidak ada data';
                }
                
                if (!result.data || result.data.length === 0) {
                    alert('Tidak ada data untuk kategori yang dipilih');
                    return;
                }
                
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
                    const badgeClass = `badge-${item.kategori_harga.toLowerCase()}`;
                    const popupContent = `
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
                    
                    marker.bindPopup(popupContent);
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
        
        // Event listener untuk perubahan kategori
        document.getElementById('kategori').addEventListener('change', loadData);
        
        // Load data awal
        loadData();
    </script>
</body>
</html>