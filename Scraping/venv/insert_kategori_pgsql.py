import psycopg2

# Daftar kategori baru
kategori_baru = [
    "BERAS", "GULA", "MINYAK GORENG", "DAGING", "TELUR AYAM", "SUSU",
    "Jagung Pipilan Kering", "GARAM BERYODIUM", "TEPUNG TERIGU", "KACANG KEDELAI",
    "MIE INSTANT", "CABE", "BAWANG", "Ikan Asin Teri", "KACANG HIJAU", "KACANG TANAH",
    "KETELA POHON", "SAYUR MAYUR", "SEMEN", "IKAN SEGAR", "KAYU BALOK MERANTI (4 X 10)",
    "Papan Meranti (4m X 3cm X 20mm)", "TRIPLEK (6MM)", "BESI BETON (SNI MURNI)",
    "PAKU", "GAS ELPIGI 3 Kg", "PUPUK"
]

# Koneksi ke database PostgreSQL
connection = psycopg2.connect(
    user="postgres",
    password="root",
    host="127.0.0.1",
    port="5432",
    database="harga_komoditas"
)

cursor = connection.cursor()

# Query untuk mengecek apakah kategori sudah ada
cek_query = "SELECT COUNT(*) FROM kategori_komoditas WHERE kategori = %s"

# Query untuk memasukkan data baru
insert_query = "INSERT INTO kategori_komoditas (kategori) VALUES (%s)"

# Iterasi untuk memasukkan hanya data yang belum ada
for item in kategori_baru:
    cursor.execute(cek_query, (item,))
    count = cursor.fetchone()[0]  # Ambil hasil query (jumlah data yang cocok)
    
    if count == 0:  # Jika belum ada, lakukan INSERT
        cursor.execute(insert_query, (item,))
        print(f"Kategori '{item}' ditambahkan.")

# Commit transaksi
connection.commit()
print('Proses selesai.')

# Tutup cursor dan koneksi
cursor.close()
connection.close()
