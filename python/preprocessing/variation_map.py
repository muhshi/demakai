"""
variation_map.py — Pemetaan Kueri Formal ke Variasi Lapangan (Natural Language)
-----------------------------------------------------------------------------
Digunakan untuk augmentasi kueri pada pipeline Query Expansion.
Membantu menjembatani celah leksikal antara bahasa formal BPS dan bahasa pengguna.
"""

VARIATION_MAP = {
    ("maton", "matun", "cabut rumput"): {
        "kbli": ["sawah padi", "lahan tani padi", "usaha tani"],
        "kbji": ["buruh matun sawah", "tukang cabut rumput", "pekerja sawah"]
    },
    ("etalase", "lemari kaca"): {
        "kbli": ["bengkel kaca etalase", "toko lemari kaca", "usaha rak kaca"],
        "kbji": ["tukang bikin etalase", "pengrajin lemari kaca", "sopir antar lemari"]
    },
    ("pertanian", "tani"): {
        "kbli": ["sektor pertanian", "usaha tani", "lahan kebun"],
        "kbji": ["petani", "pekerja ladang", "buruh tani"]
    },
    ("resoles", "risol", "risoles"): {
        "kbli": ["usaha jajanan pasar", "katering gorengan", "produksi risoles"],
        "kbji": ["tukang masak risoles", "pembuat gorengan", "penjual snack"]
    },
    ("pkn", "mts", "mengajar"): {
        "kbli": ["sekolah mts", "instansi pendidikan", "lembaga sekolah"],
        "kbji": ["guru pkn", "pengajar madrasah", "pendidik honorer"]
    },
    ("potong kayu", "pabrik kayu", "sawmill"): {
        "kbli": ["pabrik pengolahan kayu", "industri potong kayu", "sawmill kayu"],
        "kbji": ["tukang potong kayu", "operator mesin kayu", "buruh pabrik"]
    },
    ("padi", "sawah padi"): {
        "kbli": ["lahan sawah padi", "usaha tani padi", "pertanian pangan"],
        "kbji": ["petani padi", "buruh tanam padi", "pekerja pembibitan"]
    },
    ("bawang", "bawang merah"): {
        "kbli": ["kebun bawang merah", "usaha hortikultura", "lahan bawang"],
        "kbji": ["buruh petik bawang", "pekerja kebun bawang", "buruh harian bawang"]
    },
    ("konstruksi rumah", "kontruksi rumah", "renovasi rumah"): {
        "kbli": ["jasa bangun rumah", "usaha konstruksi gedung", "kontraktor kampung", "proyek bangun rumah"],
        "kbji": ["tukang bangunan rumah", "kuli bangunan proyek", "kepala tukang", "tukang renovasi rumah"]
    },
    ("barang bekas", "loak", "second"): {
        "kbli": ["toko barang bekas", "usaha jual beli second", "lapak loak"],
        "kbji": ["penjual barang bekas", "bakul loak", "pedagang barang second"]
    },
    ("pedagang motor", "showroom motor", "jual motor"): {
        "kbli": ["showroom motor rumahan", "jual beli motor bekas", "bisnis motor second"],
        "kbji": ["makelar motor bekas", "penjual sepeda motor", "pedagang motor rumah"]
    },
    ("transportasi", "angkutan", "logistik"): {
        "kbli": ["usaha angkutan barang", "jasa pengiriman logistik", "armada transportasi"],
        "kbji": ["sopir truk angkutan", "pengemudi antar barang", "driver logistik"]
    },
    ("bahan baku makanan", "sembako", "kebutuhan pokok"): {
        "kbli": ["toko bahan masakan", "agen bahan baku pangan", "supplier katering"],
        "kbji": ["pedagang bahan makanan", "pemasok bahan dapur", "penjual sembako"]
    },
    ("tukang batu",): {
        "kbli": ["jasa pengerjaan batu", "proyek bangunan fisik", "usaha renovasi"],
        "kbji": ["tukang batu konstruksi", "pekerja bangunan batu", "tukang plester"]
    },
    ("toko kelontong", "klontong", "pelayan toko"): {
        "kbli": ["warung kelontong sembako", "toko kebutuhan harian", "retail kelontong"],
        "kbji": ["penjaga warung kelontong", "pelayan toko sembako", "asisten penjualan"]
    },
    ("cuci motor", "cuci mobil", "steam"): {
        "kbli": ["bengkel cuci steam", "tempat cuci kendaraan", "usaha door wash"],
        "kbji": ["tukang cuci motor", "tenaga pencuci mobil", "pekerja cuci kendaraan"]
    },
    ("botok", "pecel", "pelas"): {
        "kbli": ["warung pecel botok", "kedai nasi pecel", "usaha kuliner tradisional"],
        "kbji": ["penjual nasi pecel", "pedagang botok keliling", "penjual pecel pelas"]
    },
    ("penyediaan makanan", "katering", "jasa boga"): {
        "kbli": ["industri jasa boga", "katering acara", "bisnis makanan siap saji"],
        "kbji": ["juru masak katering", "staf makanan katering", "koki pelayanan"]
    },
    ("semen", "ngaduk", "mengaduk semen", "plester"): {
        "kbli": ["proyek konstruksi gedung", "usaha renovasi rumah", "bengkel konstruksi"],
        "kbji": ["kuli aduk semen", "pembantu tukang bangunan", "pekerja kasar", "tukang pasang bata"]
    },
    ("es teh",): {
        "kbli": ["kedai minuman es teh", "stand es teh jumbo", "franchise es teh"],
        "kbji": ["penjual es teh jumbo", "penjaga stand minuman", "pedagang es teh"]
    },
    ("antar undangan", "kurir undangan"): {
        "kbli": ["jasa kurir undangan", "usaha jasa perorangan", "biro antar undangan"],
        "kbji": ["kurir sebar undangan", "tenaga antar undangan", "pengirim undangan"]
    },
    ("tukang bangunan", "kuli bangunan"): {
        "kbli": ["jasa renovasi harian", "layanan perbaikan rumah", "usaha tukang borongan"],
        "kbji": ["tukang bangunan lepas", "pekerja bangunan harian", "tenaga borongan"]
    },
    ("siomay",): {
        "kbli": ["usaha siomay keliling", "jualan makanan tidak tetap", "produksi siomay"],
        "kbji": ["penjual siomay sepeda", "pedagang siomay kayuh", "tukang siomay komplek"]
    },
    ("mesin produksi", "mekanik pabrik", "montir"): {
        "kbli": ["bengkel mesin pabrik", "industri perlengkapan", "usaha perbaikan alat"],
        "kbji": ["teknisi mesin pabrik", "montir mesin industri", "supervisor mekanik"]
    },
    ("air isi ulang", "isi ulang galon"): {
        "kbli": ["depot air galon", "usaha air mineral", "pabrik air minum"],
        "kbji": ["operator isi ulang galon", "penjaga depot air", "pengantar air galon"]
    }
}
