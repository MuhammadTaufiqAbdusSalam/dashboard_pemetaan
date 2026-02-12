import psycopg2
import json
import requests

try:
    connection = psycopg2.connect(
    user="postgres",
    password="root",
    host="127.0.0.1",
    port="5432",
    database="harga_komoditas"
)
    cursor = connection.cursor()

    query_insert_pasar = """
    INSERT INTO pasar_copy 
    (id, psr_nama, kabkota_id, psr_status)
    VALUES (%s, %s, %s, %s)
    """

    query_insert_kabkota = """
    INSERT INTO kab_kota_1 
    (id, kab_nama, kab_keycode, kab_status)
    VALUES (%s, %s, %s, %s)
    """

    list_kabkota = ["bangkalankab", "banyuwangikab", "blitarkab", "bojonegorokab", "bondowosokab",
                    "gresikkab", "jemberkab", "jombangkab", "kedirikab", "lamongankab", "lumajangkab", "madiunkab",
                    "magetankab", "malangkab", "mojokertokab", "nganjukkab", "ngawikab", "pacitankab", "pamekasankab",
                    "pasuruankab", "ponorogokab", "probolinggokab", "sampangkab", "sidoarjokab", "situbondokab", "sumenepkab",
                    "trenggalekkab", "tubankab", "tulungagungkab", "batukota", "blitarkota", "kedirikota", "madiunkota",
                    "malangkota", "mojokertokota", "pasuruankota", "probolinggokota", "surabayakota"]

    for my_kabkota in list_kabkota:
        url = f"https://siskaperbapo.jatimprov.go.id/harga/pasar.json/{my_kabkota}"
        response = requests.get(url)

        if response.status_code == 200:
            try:
                data = response.json()
                if data:
                    kab_kota = data[0]
                    kab_kota_id = kab_kota.get("kab_id")
                    kab_nama = kab_kota.get("kab_nama")
                    kab_keycode = kab_kota.get("kab_keycode")
                    kab_status = True if kab_kota.get("kab_status") == 1 else False

                    # Cek apakah kabupaten/kota sudah ada
                    cursor.execute("SELECT 1 FROM kab_kota_1 WHERE id = %s", (kab_kota_id,))
                    if cursor.fetchone():
                        print(f"ID kab_kota {kab_kota_id} already exists. Skipping...")
                    else:
                        cursor.execute(query_insert_kabkota, (kab_kota_id, kab_nama, kab_keycode, kab_status))
                        print(f"Data kab_kota ID {kab_kota_id} inserted successfully.")

                    # Loop data pasar
                    for pasar in data:
                        psr_id = pasar.get("psr_id")
                        psr_nama = pasar.get("psr_nama")
                        psr_kabkota = pasar.get("psr_kabkota")
                        psr_status = True if pasar.get("psr_status") == 1 else False

                        # Cek apakah pasar sudah ada
                        cursor.execute("SELECT 1 FROM pasar_copy WHERE id = %s", (psr_id,))
                        if cursor.fetchone():
                            print(f"Pasar ID {psr_id} already exists. Skipping...")
                        else:
                            cursor.execute(query_insert_pasar, (psr_id, psr_nama, psr_kabkota, psr_status))
                            print(f"Data pasar ID {psr_id} inserted successfully.")

                    connection.commit()
                    print("Data berhasil dimasukkan untuk kab_kota dan pasar.")
                else:
                    print(f"Tidak ada data untuk {my_kabkota}.")
            except json.JSONDecodeError as e:
                print("Gagal memproses data JSON:", e)
            except psycopg2.Error as e:
                connection.rollback()
                print("Kesalahan database:", e)
        else:
            print(f"Request gagal dengan status code {response.status_code}")

    print("Seluruh data berhasil diproses.")

except (Exception, psycopg2.Error) as error:
    print("Failed to insert record into database:", error)

finally:
    if connection:
        cursor.close()
        connection.close()
        print("PostgreSQL connection is closed")
