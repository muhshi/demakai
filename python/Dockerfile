# Menggunakan image Python versi slim untuk memperkecil ukuran container
FROM python:3.11-slim

# Menyiapkan environment variable (opsional, mematikan bytecode .pyc)
ENV PYTHONDONTWRITEBYTECODE=1
ENV PYTHONUNBUFFERED=1

# Mengatur direktori kerja di dalam container
WORKDIR /app

# Menyalin file requirement untuk diinstall terlebih dahulu (memanfaatkan layer caching)
COPY requirements.txt /app/

# Menginstall dependensi python
RUN pip install --no-cache-dir --upgrade pip \
    && pip install --no-cache-dir -r requirements.txt

# Menyalin seluruh kode python dari folder lokal ke dalam folder /app di container
COPY . /app/

# Mengekspos port 8000 (port yang digunakan oleh server uvicorn API)
EXPOSE 8000

# Perintah inti untuk menjalankan server FastAPI menggunakan Uvicorn ketika container jalan
CMD ["uvicorn", "api:app", "--host", "0.0.0.0", "--port", "8000"]
