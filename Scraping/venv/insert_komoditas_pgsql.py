import psycopg2
import csv
import requests
import pathlib
from datetime import datetime

csv_path = pathlib.Path.cwd() / "komoditas2013-15.csv"

dict_list = list()
with csv_path.open(mode="r") as csv_reader:
    csv_reader = csv.reader(csv_reader)
    for rows in csv_reader:
        dict_list.append({'tanggal':rows[0], 
        'id_kabkota':rows[1], 'nm_kabkota':rows[2], 'id_pasar':rows[3], 'nm_pasar':rows[4],
        'kategori':rows[5], 'nm_komoditas':rows[6], 'satuan':rows[7], 'harga_before':rows[8],
        'harga_current':rows[9], 'perubahan':rows[10]})
        
def is_valid_date(date_str):
    try:
        # Coba parsing tanggal
        datetime.strptime(date_str, "%Y-%m-%d")
        return True
    except ValueError:
        # Jika parsing gagal, tanggal tidak valid
        return False
    
    
connection = psycopg2.connect(
    user="postgres",
    password="root",
    host="127.0.0.1",
    port="5432",
    database="harga_komoditas"
)

cursor = connection.cursor()

# Query SQL untuk memasukkan data
query = """
INSERT INTO komoditas
(tanggal, pasar_id,kategori_id,komoditas_nama,satuan,harga_before,harga_current)
VALUES (%s, %s, %s, %s, %s, %s, %s)
"""

for item in dict_list:
    if not is_valid_date(item['tanggal']):
        continue
    # Lewati jika harga_before dan harga_current adalah "0"
    if item['harga_before'] == "0" and item['harga_current'] == "0":
        continue
    
    # Query untuk memfilter kategori
    queryFilter = "SELECT id FROM kategori_komoditas WHERE kategori LIKE %s"
    
    # Tambahkan wildcard (%) di sekitar item['kategori']
    search_pattern = f"%{item['kategori']}%"
    
    # Eksekusi queryFilter
    cursor.execute(queryFilter, (search_pattern,))
    kategori_result = cursor.fetchone()  # Ambil hasil query
    
    if kategori_result:  # Jika data ditemukan
        kategori_id = kategori_result[0]  # Ambil ID dari hasil query
    
        if item['perubahan'] == "-":
            item['perubahan'] = 0
        val = (
            item['tanggal'], 
            item['id_pasar'], 
            kategori_id,  # Gunakan kategori_id dari query
            item['nm_komoditas'], 
            item['satuan'], 
            item['harga_before'], 
            item['harga_current'], 
            item['perubahan']
        )
        
        # Eksekusi queryInsert
        cursor.execute(query, val)
        print({item['tanggal'], item['id_kabkota'], item['nm_kabkota'], item['id_pasar'],item['nm_pasar'], item['kategori'], item['nm_komoditas'], item['satuan'], item['harga_before'], item['harga_current'], item['perubahan']})
        

connection.commit()
print('success')

cursor.close()
connection.close()

