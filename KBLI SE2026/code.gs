/**
 * =========================================================================
 * GOOGLE APPS SCRIPT: AUTO-LOCKING MULTI-USER CONTROLLER - SE2026 DEMAK
 * Menjamin tidak ada 2 orang yang mengerjakan perusahaan yang sama di Fasih
 * Dilengkapi Filter Kecamatan (14 Kecamatan Demak) & Monitoring Terintegrasi
 * OPTIMIZED: High performance single-row retrieval & lightning fast locking
 * =========================================================================
 */

var SPREADSHEET_ID = '17Bqh5O1VHiz1oOhAX69krC5MqegQ3lRrKSf-MdVQUHg';

// Master 15 Kecamatan / Wilayah di Kabupaten Demak (3321)
var KECAMATAN_DEMAK = {
  '010': 'Mranggen',
  '020': 'Karangawen',
  '030': 'Guntur',
  '040': 'Sayung',
  '050': 'Karangtengah',
  '060': 'Bonang',
  '070': 'Demak',
  '080': 'Wonosalam',
  '090': 'Dempet',
  '091': 'Kebonagung',
  '100': 'Gajah',
  '110': 'Karanganyar',
  '120': 'Mijen',
  '130': 'Wedung',
  '000': '3321000'
};

var KECAMATAN_NAMES_TO_CODE = {
  'mranggen': '010',
  'karangawen': '020',
  'guntur': '030',
  'sayung': '040',
  'karangtengah': '050',
  'bonang': '060',
  'demak': '070',
  'wonosalam': '080',
  'dempet': '090',
  'kebonagung': '091',
  'gajah': '100',
  'karanganyar': '110',
  'mijen': '120',
  'wedung': '130'
};

function getKecamatanCode(iddesa, namaUsaha) {
  var s = String(iddesa || '').trim();
  if (s.indexOf('3321000') === 0 || s === '000') {
    return '000';
  }
  var code = '';
  if (s.length >= 7 && s.indexOf('3321') === 0) {
    code = s.substr(4, 3);
  } else if (s.length === 3) {
    code = s;
  }
  
  if (!code && namaUsaha) {
    var nu = String(namaUsaha).toLowerCase();
    for (var kName in KECAMATAN_NAMES_TO_CODE) {
      if (nu.indexOf(kName) !== -1) {
        return KECAMATAN_NAMES_TO_CODE[kName];
      }
    }
  }
  return code || '000';
}

function getKecamatanName(iddesa, namaUsaha) {
  var code = getKecamatanCode(iddesa, namaUsaha);
  return KECAMATAN_DEMAK[code] || (code && code !== '000' ? ('Kec. ' + code) : '3321000');
}

function getSpreadsheet() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  if (!ss) {
    ss = SpreadsheetApp.openById(SPREADSHEET_ID);
  }
  return ss;
}

// Helper untuk angka uang Rupiah (selalu integer bulat utuh)
function parseMoney(val) {
  if (val === null || val === undefined) return 0;
  if (typeof val === 'number') {
    return isNaN(val) ? 0 : Math.round(val);
  }
  var str = String(val).replace(/[^0-9.-]/g, '');
  var num = parseFloat(str);
  return isNaN(num) ? 0 : Math.round(num);
}

// Helper untuk rasio desimal 0 s/d 1 (2 desimal)
function parseRatio(val) {
  if (val === null || val === undefined) return 0;
  var str = String(val).replace(/[^0-9.-]/g, '');
  var num = parseFloat(str);
  if (isNaN(num)) return 0;
  if (num > 1.0) num = num / 100.0;
  return parseFloat(num.toFixed(2));
}

// Helper untuk format URL Fasih agar selalu mengarah ke halaman edit (/edit)
function formatFasihUrl(url) {
  if (!url || typeof url !== 'string') return '';
  var cleanUrl = url.trim();
  if (cleanUrl.indexOf('http') !== 0) return '';
  if (cleanUrl.indexOf('fasih-sm.bps.go.id/app/assignment/') !== -1) {
    cleanUrl = cleanUrl.replace(/\/+$/, ''); // Hapus trailing slash jika ada
    if (!cleanUrl.endsWith('/edit')) {
      cleanUrl += '/edit';
    }
  }
  return cleanUrl;
}

function doGet(e) {
  return HtmlService.createHtmlOutputFromFile('Index')
    .setTitle('Fokus & Simulasi Data Cleaning SE2026 Demak')
    .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL)
    .addMetaTag('viewport', 'width=device-width, initial-scale=1');
}

function onOpen() {
  SpreadsheetApp.getUi()
    .createMenu('⚡ SE2026 Demak')
    .addItem('🖥️ Buka Lembar Kerja Fokus (Modal Card)', 'openFocusModal')
    .addSeparator()
    .addItem('🔓 Buka Kunci (Unlock) Semua Data "Sedang Dikerjakan"', 'unlockAllAbandonedRecords')
    .addToUi();
}

function openFocusModal() {
  var html = HtmlService.createHtmlOutputFromFile('Index')
    .setWidth(1180)
    .setHeight(750);
  SpreadsheetApp.getUi().showModalDialog(html, 'Lembar Kerja Fokus & Simulasi NTB SE2026 Demak');
}

/**
 * Mengambil rekap status pengerjaan saat ini (Super Cepat: hanya membaca Kolom B & C)
 */
function getQueueSummary(sheetName, userName, kecFilter) {
  var ss = getSpreadsheet();
  var ws = ss.getSheetByName(sheetName);
  if (!ws) return { total: 0, done: 0, remaining: 0, myDone: 0 };

  var lastRow = ws.getLastRow();
  if (lastRow < 2) return { total: 0, done: 0, remaining: 0, myDone: 0 };

  var numRows = lastRow - 1;
  var statusData = ws.getRange(2, 2, numRows, 2).getValues(); // Kolom B & C
  var iddesaData = (kecFilter && kecFilter !== 'ALL') ? ws.getRange(2, 25, numRows, 1).getValues() : null;
  var namaData = (kecFilter && kecFilter !== 'ALL') ? ws.getRange(2, 5, numRows, 1).getValues() : null;

  var total = 0;
  var done = 0;
  var myDone = 0;

  for (var i = 0; i < numRows; i++) {
    if (kecFilter && kecFilter !== 'ALL') {
      var iddesa = iddesaData ? iddesaData[i][0] : '';
      var namaUsaha = namaData ? namaData[i][0] : '';
      if (getKecamatanCode(iddesa, namaUsaha) !== kecFilter) continue;
    }

    total++;
    var st = String(statusData[i][0] || '').trim();
    var pet = String(statusData[i][1] || '').trim();
    if (st === 'Selesai Diperbaiki') {
      done++;
      if (userName && pet.toLowerCase() === userName.toLowerCase()) {
        myDone++;
      }
    }
  }

  return {
    total: total,
    done: done,
    remaining: total - done,
    myDone: myDone
  };
}

/**
 * Mengambil data komprehensif untuk Halaman Monitoring & Rekap Progress
 */
function getMonitoringData(targetSheet, userName, kecFilter) {
  var ss = getSpreadsheet();
  var sheetsToProcess = [];
  
  if (!targetSheet || targetSheet === 'ALL') {
    sheetsToProcess = ['🎯 Prioritas 1 (1.734 Usaha)', '🎯 Prioritas 2 (8.121 Usaha)'];
  } else {
    sheetsToProcess = [targetSheet];
  }

  var summary = {
    targetSheet: targetSheet || 'ALL',
    kecFilter: kecFilter || 'ALL',
    total: 0,
    done: 0,
    inProgress: 0,
    pending: 0,
    myDone: 0,
    myInProgress: 0,
    byPetugas: {},
    byCategory: {},
    byKecamatan: {},
    bySheet: {},
    records: []
  };

  sheetsToProcess.forEach(function(sname) {
    var ws = ss.getSheetByName(sname);
    if (!ws) return;

    var lastRow = ws.getLastRow();
    if (lastRow < 2) return;

    var numRows = lastRow - 1;
    // Baca kolom penting untuk ringkasan monitoring (26 kolom)
    var rangeData = ws.getRange(2, 1, numRows, 26).getValues();

    var sTotal = 0;
    var sDone = 0;
    var sInProgress = 0;
    var sPending = 0;

    for (var i = 0; i < numRows; i++) {
      var r = rangeData[i];
      var rIndex = i + 2;
      var no = r[0];
      var status = String(r[1] || 'Belum Dikerjakan').trim();
      var petugas = String(r[2] || '').trim();
      var namaUsaha = String(r[4] || '').trim();
      var kat = String(r[5] || 'Lainnya').trim().toUpperCase();
      var kbli = String(r[6] || '').trim();
      var rasioAwal = parseRatio(r[8]).toFixed(2);
      var rasioBaru = parseRatio(r[16]).toFixed(2);
      var rincianUbah = String(r[9] || '').trim();
      var catatan = String(r[17] || '').trim();
      var assignId = String(r[23] || '').trim();
      var iddesa = String(r[24] || '').trim();
      var mapVal = String(r[25] || '').trim();
      var linkMap = (mapVal && mapVal !== '-' && mapVal.indexOf('http') === 0) ? mapVal : '';

      var kCode = getKecamatanCode(iddesa, namaUsaha);
      var kName = getKecamatanName(iddesa, namaUsaha);

      if (kecFilter && kecFilter !== 'ALL') {
        if (kCode !== kecFilter) continue;
      }

      summary.total++;
      sTotal++;

      var linkFasih = assignId ? formatFasihUrl('https://fasih-sm.bps.go.id/app/assignment/fd68e454-ba45-4b85-8205-f3bf777ded24/' + assignId) : '';

      if (status === 'Selesai Diperbaiki') {
        summary.done++;
        sDone++;
        if (petugas) {
          if (!summary.byPetugas[petugas]) summary.byPetugas[petugas] = { done: 0, inProgress: 0 };
          summary.byPetugas[petugas].done++;
        }
        if (userName && petugas.toLowerCase() === userName.toLowerCase()) {
          summary.myDone++;
        }
      } else if (status === 'Sedang Dikerjakan') {
        summary.inProgress++;
        sInProgress++;
        if (petugas) {
          if (!summary.byPetugas[petugas]) summary.byPetugas[petugas] = { done: 0, inProgress: 0 };
          summary.byPetugas[petugas].inProgress++;
        }
        if (userName && petugas.toLowerCase() === userName.toLowerCase()) {
          summary.myInProgress++;
        }
      } else {
        summary.pending++;
        sPending++;
      }

      // Kategori KBLI
      if (!summary.byCategory[kat]) summary.byCategory[kat] = { total: 0, done: 0, inProgress: 0 };
      summary.byCategory[kat].total++;
      if (status === 'Selesai Diperbaiki') summary.byCategory[kat].done++;
      else if (status === 'Sedang Dikerjakan') summary.byCategory[kat].inProgress++;

      // Kecamatan
      if (!summary.byKecamatan[kName]) {
        summary.byKecamatan[kName] = { code: kCode, name: kName, total: 0, done: 0, inProgress: 0 };
      }
      summary.byKecamatan[kName].total++;
      if (status === 'Selesai Diperbaiki') summary.byKecamatan[kName].done++;
      else if (status === 'Sedang Dikerjakan') summary.byKecamatan[kName].inProgress++;

      if (summary.records.length < 1500) {
        summary.records.push({
          sheetName: sname,
          rowIndex: rIndex,
          no: no,
          status: status,
          petugas: petugas,
          namaUsaha: namaUsaha,
          kategori: kat,
          kbli: kbli,
          rasioAwal: rasioAwal,
          rasioBaru: rasioBaru,
          rincianUbah: rincianUbah,
          catatan: catatan,
          linkFasih: linkFasih,
          linkMap: linkMap,
          iddesa: iddesa,
          kecCode: kCode,
          kecamatan: kName
        });
      }
    }

    summary.bySheet[sname] = {
      total: sTotal,
      done: sDone,
      inProgress: sInProgress,
      pending: sPending,
      pct: sTotal > 0 ? ((sDone / sTotal) * 100).toFixed(1) : '0.0'
    };
  });

  return summary;
}

/**
 * Mengambil & MENGUNCI (LOCK) data berikutnya khusus untuk userName
 * SUPER CEPAT: Hanya memindai Kolom B & C, lalu mengambil 1 baris target saja
 */
function claimNextRecord(sheetName, userName, kecFilter) {
  if (!userName || userName.trim() === '') {
    throw new Error('Nama pengguna harus diisi terlebih dahulu.');
  }
  userName = userName.trim();

  var ss = getSpreadsheet();
  var ws = ss.getSheetByName(sheetName);
  if (!ws) throw new Error('Sheet "' + sheetName + '" tidak ditemukan.');

  var lastRow = ws.getLastRow();
  if (lastRow < 2) return null;

  var lock = LockService.getScriptLock();
  try {
    lock.waitLock(10000);
  } catch (e) {
    try { lock.waitLock(3000); } catch(e2) {}
  }

  var targetRowIdx = -1;

  try {
    var numRows = lastRow - 1;
    // 1. Ambil kolom B (Status) & C (Petugas) saja (Sangat Cepat & Ringan!)
    var statusCol = ws.getRange(2, 2, numRows, 2).getValues();

    var iddesaCol = null;
    var namaUsahaCol = null;
    if (kecFilter && kecFilter !== 'ALL') {
      iddesaCol = ws.getRange(2, 25, numRows, 1).getValues(); // Kolom Y
      namaUsahaCol = ws.getRange(2, 5, numRows, 1).getValues(); // Kolom E
    }

    // A. Prioritaskan jika user ini sebelumnya sudah memegang record 'Sedang Dikerjakan'
    for (var i = 0; i < numRows; i++) {
      var st = String(statusCol[i][0] || '').trim();
      var pet = String(statusCol[i][1] || '').trim();

      if (st === 'Sedang Dikerjakan' && pet.toLowerCase() === userName.toLowerCase()) {
        targetRowIdx = i + 2;
        break;
      }
    }

    // B. Jika belum pegang, ambil record pertama yang masih BEBAS
    if (targetRowIdx === -1) {
      for (var j = 0; j < numRows; j++) {
        var stj = String(statusCol[j][0] || '').trim();

        if (stj !== 'Selesai Diperbaiki' && stj !== 'Sedang Dikerjakan') {
          if (kecFilter && kecFilter !== 'ALL') {
            var iddesaVal = iddesaCol ? iddesaCol[j][0] : '';
            var namaVal = namaUsahaCol ? namaUsahaCol[j][0] : '';
            var kCode = getKecamatanCode(iddesaVal, namaVal);
            if (kCode !== kecFilter) continue;
          }

          targetRowIdx = j + 2;
          ws.getRange(targetRowIdx, 2).setValue('Sedang Dikerjakan');
          ws.getRange(targetRowIdx, 3).setValue(userName);
          SpreadsheetApp.flush();
          break;
        }
      }
    }

    try { lock.releaseLock(); } catch(e) {}

    if (targetRowIdx === -1) {
      return null;
    }

    // 2. Ambil HANYA 1 BARIS target (26 kolom) + formula kolom D & Z
    var targetData = ws.getRange(targetRowIdx, 1, 1, 26).getValues()[0];
    var formula = ws.getRange(targetRowIdx, 4).getFormula() || '';
    var formulaZ = ws.getRange(targetRowIdx, 26).getFormula() || '';

    var linkFasih = '';
    if (formula.indexOf('HYPERLINK') !== -1) {
      var match = formula.match(/HYPERLINK\("([^"]+)"/i);
      if (match) linkFasih = match[1];
    } else {
      linkFasih = String(targetData[3] || '');
    }
    if ((!linkFasih || linkFasih.indexOf('http') !== 0) && targetData[23]) {
      linkFasih = 'https://fasih-sm.bps.go.id/app/assignment/fd68e454-ba45-4b85-8205-f3bf777ded24/' + String(targetData[23]).trim() + '/edit';
    }
    linkFasih = formatFasihUrl(linkFasih);

    var linkMap = '';
    if (formulaZ.indexOf('HYPERLINK') !== -1) {
      var matchZ = formulaZ.match(/HYPERLINK\("([^"]+)"/i);
      if (matchZ) linkMap = matchZ[1];
    } else {
      linkMap = String(targetData[25] || '').trim();
    }
    linkMap = (linkMap && linkMap !== '-' && linkMap.indexOf('http') === 0) ? linkMap : '';

    var omzBaru = parseMoney(targetData[15]);
    var iddesaTarget = String(targetData[24] || '');
    var namaTarget = String(targetData[4] || '');

    return {
      sheetName: sheetName,
      rowIndex: targetRowIdx,
      no: targetData[0],
      status: 'Sedang Dikerjakan',
      petugas: userName,
      linkFasih: linkFasih,
      linkMap: linkMap,
      namaUsaha: namaTarget,
      kategori: String(targetData[5] || ''),
      kbli: String(targetData[6] || ''),
      tk: parseMoney(targetData[7]),
      rasioAwal: parseRatio(targetData[8]).toFixed(2),
      rincianUbah: String(targetData[9] || ''),
      upahBaru: parseMoney(targetData[10]),
      prodBaru: parseMoney(targetData[11]),
      cogsBaru: parseMoney(targetData[12]),
      opsBaru: parseMoney(targetData[13]),
      totBaru: parseMoney(targetData[14]),
      omzBaru: omzBaru,
      rasioBaru: parseRatio(targetData[16]).toFixed(2),
      catatan: String(targetData[17] || ''),
      upahOld: parseMoney(targetData[18]),
      prodOld: parseMoney(targetData[19]),
      cogsOld: parseMoney(targetData[20]),
      opsOld: parseMoney(targetData[21]),
      nonOld: parseMoney(targetData[22]),
      totOld: parseMoney(targetData[14]),
      omzOld: omzBaru,
      iddesa: iddesaTarget,
      kecCode: getKecamatanCode(iddesaTarget, namaTarget),
      kecamatan: getKecamatanName(iddesaTarget, namaTarget)
    };

  } catch (err) {
    try { lock.releaseLock(); } catch(e) {}
    throw err;
  }
}

/**
 * Tandai SELESAI, simpan nama petugas & angka bulat, lalu otomatis ambil record berikutnya
 */
function completeAndGetNext(sheetName, rowIndex, userName, customNote, customValues, kecFilter) {
  if (!sheetName) throw new Error('Nama sheet tidak boleh kosong.');
  if (!rowIndex || rowIndex < 2) throw new Error('Baris record (rowIndex) tidak valid.');
  if (!userName || userName.trim() === '') throw new Error('Nama pengguna harus diisi terlebih dahulu.');

  userName = userName.trim();
  var ss = getSpreadsheet();
  var ws = ss.getSheetByName(sheetName);
  if (!ws) throw new Error('Sheet "' + sheetName + '" tidak ditemukan.');

  var saveLock = LockService.getScriptLock();
  var hasLock = false;
  try {
    hasLock = saveLock.tryLock(10000);
  } catch (e) {
    hasLock = false;
  }

  try {
    // 1. Simpan Status Selesai & Nama Petugas
    ws.getRange(rowIndex, 2).setValue('Selesai Diperbaiki');
    ws.getRange(rowIndex, 3).setValue(userName);
    if (customNote !== undefined && customNote !== null) {
      ws.getRange(rowIndex, 18).setValue(customNote);
    }

    // 2. Simpan nilai kustom jika petugas mengubah angka di kalkulator simulasi
    if (customValues) {
      if (customValues.upahBaru !== undefined) ws.getRange(rowIndex, 11).setValue(Math.round(customValues.upahBaru));
      if (customValues.prodBaru !== undefined) ws.getRange(rowIndex, 12).setValue(Math.round(customValues.prodBaru));
      if (customValues.cogsBaru !== undefined) ws.getRange(rowIndex, 13).setValue(Math.round(customValues.cogsBaru));
      if (customValues.opsBaru !== undefined) ws.getRange(rowIndex, 14).setValue(Math.round(customValues.opsBaru));
      if (customValues.totBaru !== undefined) ws.getRange(rowIndex, 15).setValue(Math.round(customValues.totBaru));
      if (customValues.omzBaru !== undefined) ws.getRange(rowIndex, 16).setValue(Math.round(customValues.omzBaru));
      if (customValues.rasioBaru !== undefined) ws.getRange(rowIndex, 17).setValue(parseFloat(Number(customValues.rasioBaru).toFixed(2)));
      
      // Catat rincian mana yang diubah ke Kolom J (10)
      var changedFields = [];
      if (customValues.omzOld !== undefined && Math.abs(customValues.omzBaru - customValues.omzOld) >= 1) changedFields.push('Omzet (27.a)');
      if (customValues.cogsOld !== undefined && Math.abs(customValues.cogsBaru - customValues.cogsOld) >= 1) changedFields.push('Kulakan (26.c)');
      if (customValues.upahOld !== undefined && Math.abs(customValues.upahBaru - customValues.upahOld) >= 1) changedFields.push('Upah (26.a)');
      if (customValues.prodOld !== undefined && Math.abs(customValues.prodBaru - customValues.prodOld) >= 1) changedFields.push('Bahan Baku (26.b)');
      if (customValues.opsOld !== undefined && Math.abs(customValues.opsBaru - customValues.opsOld) >= 1) changedFields.push('Operasional (26.d)');
      
      if (changedFields.length > 0) {
        ws.getRange(rowIndex, 10).setValue(changedFields.join(', '));
      }
    }

    // PENTING: Segera flush perubahan agar status dan verifikasi langsung tersimpan permanen
    SpreadsheetApp.flush();
  } finally {
    if (hasLock) {
      try { saveLock.releaseLock(); } catch(e) {}
    }
  }

  // 3. Ambil record berikutnya di blok terpisah (isolated)
  // Mencegah status yang sudah disimpan ikut ter-rollback jika terjadi lock timeout / antrean habis
  var nextItem = null;
  try {
    nextItem = claimNextRecord(sheetName, userName, kecFilter);
  } catch (errClaim) {
    Logger.log('Pemberitahuan: Klaim berikutnya setelah simpan selesai mengalami kendala: ' + errClaim);
  }

  return {
    success: true,
    savedRow: rowIndex,
    nextItem: nextItem
  };
}

/**
 * Lepas Kunci (Un-lock) jika pegawai ingin melewati
 */
function releaseLockAndGetNext(sheetName, rowIndex, userName, kecFilter) {
  var ss = getSpreadsheet();
  var ws = ss.getSheetByName(sheetName);
  if (ws && rowIndex >= 2) {
    ws.getRange(rowIndex, 2).setValue('Belum Dikerjakan');
    ws.getRange(rowIndex, 3).setValue('');
    SpreadsheetApp.flush();
  }

  var nextItem = null;
  try {
    nextItem = claimNextRecord(sheetName, userName, kecFilter);
  } catch (e) {
    Logger.log('Pemberitahuan: Klaim berikutnya setelah lewati record mengalami kendala: ' + e);
  }

  return {
    success: true,
    nextItem: nextItem
  };
}

/**
 * Lepas Kunci (Un-lock) record spesifik tanpa mengambil data baru
 */
function releaseSpecificRecord(sheetName, rowIndex, userName) {
  if (!sheetName || !rowIndex || rowIndex < 2) return false;
  var ss = getSpreadsheet();
  var ws = ss.getSheetByName(sheetName);
  if (!ws) return false;

  var lock = LockService.getScriptLock();
  try { lock.waitLock(10000); } catch(e) {}
  try {
    var lastRow = ws.getLastRow();
    if (rowIndex <= lastRow) {
      var status = String(ws.getRange(rowIndex, 2).getValue() || '').trim();
      var petugas = String(ws.getRange(rowIndex, 3).getValue() || '').trim();
      if (status === 'Sedang Dikerjakan' && (!userName || petugas.toLowerCase() === userName.trim().toLowerCase())) {
        ws.getRange(rowIndex, 2).setValue('Belum Dikerjakan');
        ws.getRange(rowIndex, 3).setValue('');
        SpreadsheetApp.flush();
      }
    }
    try { lock.releaseLock(); } catch(e) {}
    return true;
  } catch (err) {
    try { lock.releaseLock(); } catch(e) {}
    return false;
  }
}

/**
 * Fitur darurat admin: Melepas semua kunci 'Sedang Dikerjakan'
 */
function unlockAllAbandonedRecords() {
  return unlockAllFromMonitoring('ALL');
}

/**
 * Membuka kunci (Unlock) record spesifik dari Halaman Monitoring
 */
function unlockRecordFromMonitoring(sheetName, rowIndex) {
  if (!sheetName || !rowIndex || rowIndex < 2) {
    throw new Error('Parameter baris atau sheet tidak valid.');
  }
  var ss = getSpreadsheet();
  var ws = ss.getSheetByName(sheetName);
  if (!ws) throw new Error('Sheet ' + sheetName + ' tidak ditemukan.');

  var lock = LockService.getScriptLock();
  try { lock.waitLock(10000); } catch(e) {}
  try {
    var lastRow = ws.getLastRow();
    if (rowIndex > lastRow) {
      try { lock.releaseLock(); } catch(e) {}
      throw new Error('Baris ' + rowIndex + ' di luar batas sheet.');
    }

    var status = String(ws.getRange(rowIndex, 2).getValue() || '').trim();
    var petugas = String(ws.getRange(rowIndex, 3).getValue() || '').trim();

    if (status === 'Selesai Diperbaiki') {
      try { lock.releaseLock(); } catch(e) {}
      throw new Error('Data ini sudah berstatus Selesai Diperbaiki.');
    }

    ws.getRange(rowIndex, 2).setValue('Belum Dikerjakan');
    ws.getRange(rowIndex, 3).setValue('');

    try { lock.releaseLock(); } catch(e) {}
    return {
      success: true,
      rowIndex: rowIndex,
      sheetName: sheetName,
      message: 'Kunci berhasil dibuka (sebelumnya dipegang: ' + (petugas || 'Tanpa Nama') + ').'
    };
  } catch (err) {
    try { lock.releaseLock(); } catch(e) {}
    throw err;
  }
}

/**
 * Membuka kunci SEMUA record 'Sedang Dikerjakan' dari Halaman Monitoring
 */
function unlockAllFromMonitoring(targetSheet) {
  var ss = getSpreadsheet();
  var sheets = [];
  if (!targetSheet || targetSheet === 'ALL') {
    sheets = ['🎯 Prioritas 1 (1.734 Usaha)', '🎯 Prioritas 2 (8.121 Usaha)'];
  } else {
    sheets = [targetSheet];
  }

  var lock = LockService.getScriptLock();
  try { lock.waitLock(10000); } catch(e) {}
  var totalUnlocked = 0;

  try {
    sheets.forEach(function(sname) {
      var ws = ss.getSheetByName(sname);
      if (ws) {
        var lastRow = ws.getLastRow();
        if (lastRow >= 2) {
          var range = ws.getRange(2, 2, lastRow - 1, 2);
          var values = range.getValues();
          var modified = false;

          for (var i = 0; i < values.length; i++) {
            if (String(values[i][0] || '').trim() === 'Sedang Dikerjakan') {
              values[i][0] = 'Belum Dikerjakan';
              values[i][1] = '';
              totalUnlocked++;
              modified = true;
            }
          }

          if (modified) {
            range.setValues(values);
          }
        }
      }
    });

    try { lock.releaseLock(); } catch(e) {}
    
    try {
      SpreadsheetApp.getActiveSpreadsheet().toast('Berhasil membuka kunci ' + totalUnlocked + ' data yang menggantung.', 'Unlock Sukses');
    } catch(e) {}

    return {
      success: true,
      totalUnlocked: totalUnlocked,
      message: 'Berhasil membuka ' + totalUnlocked + ' data yang terkunci.'
    };
  } catch (err) {
    try { lock.releaseLock(); } catch(e) {}
    throw err;
  }
}

/**
 * Mengambil daftar ringkas nama usaha untuk Searchable Dropdown (maksimal 1000 item pertama)
 */
function getEnterpriseList(sheetName, kecFilter) {
  var ss = getSpreadsheet();
  var ws = ss.getSheetByName(sheetName);
  if (!ws) return [];

  var lastRow = ws.getLastRow();
  if (lastRow < 2) return [];

  var numRows = lastRow - 1;
  var noCol = ws.getRange(2, 1, numRows, 1).getValues();
  var statusCol = ws.getRange(2, 2, numRows, 2).getValues();
  var namaCol = ws.getRange(2, 5, numRows, 1).getValues();
  var iddesaCol = ws.getRange(2, 25, numRows, 1).getValues();

  var list = [];
  for (var i = 0; i < numRows; i++) {
    var st = String(statusCol[i][0] || 'Belum Dikerjakan').trim();
    if (st === 'Selesai Diperbaiki') continue; // Jangan tampilkan yang sudah selesai di dropdown pencarian

    var namaUsaha = String(namaCol[i][0] || '').trim();
    var iddesa = iddesaCol[i][0];
    var kCode = getKecamatanCode(iddesa, namaUsaha);

    if (kecFilter && kecFilter !== 'ALL') {
      if (kCode !== kecFilter) continue;
    }

    list.push({
      rowIndex: i + 2,
      no: noCol[i][0],
      status: st,
      petugas: String(statusCol[i][1] || '').trim(),
      namaUsaha: namaUsaha,
      kecCode: kCode,
      kecamatan: getKecamatanName(iddesa, namaUsaha)
    });
  }
  return list;
}

/**
 * Klaim dan buka perusahaan spesifik yang dipilih dari Searchable Dropdown
 */
function claimSpecificRecord(sheetName, rowIndex, userName, previousRowIndex) {
  if (!userName || userName.trim() === '') {
    throw new Error('Nama pengguna harus diisi terlebih dahulu.');
  }
  userName = userName.trim();

  var ss = getSpreadsheet();
  var ws = ss.getSheetByName(sheetName);
  if (!ws) throw new Error('Sheet ' + sheetName + ' tidak ditemukan.');

  var lock = LockService.getScriptLock();
  try { lock.waitLock(10000); } catch(e) {}

  try {
    var lastRow = ws.getLastRow();
    if (rowIndex < 2 || rowIndex > lastRow) {
      try { lock.releaseLock(); } catch(e) {}
      throw new Error('Baris ' + rowIndex + ' tidak valid.');
    }

    var rowData = ws.getRange(rowIndex, 1, 1, 26).getValues()[0];
    var formula = ws.getRange(rowIndex, 4).getFormula() || '';
    var formulaZ = ws.getRange(rowIndex, 26).getFormula() || '';

    var currentStatus = String(rowData[1] || '').trim();
    var currentPetugas = String(rowData[2] || '').trim();

    if (currentStatus === 'Sedang Dikerjakan' && currentPetugas.toLowerCase() !== userName.toLowerCase()) {
      try { lock.releaseLock(); } catch(e) {}
      throw new Error('Perusahaan ini sedang dikerjakan oleh ' + currentPetugas + '. Silakan pilih yang lain.');
    }

    if (previousRowIndex && previousRowIndex >= 2 && previousRowIndex !== rowIndex && previousRowIndex <= lastRow) {
      var prevStatus = String(ws.getRange(previousRowIndex, 2).getValue() || '').trim();
      var prevPetugas = String(ws.getRange(previousRowIndex, 3).getValue() || '').trim();
      if (prevStatus === 'Sedang Dikerjakan' && prevPetugas.toLowerCase() === userName.toLowerCase()) {
        ws.getRange(previousRowIndex, 2).setValue('Belum Dikerjakan');
        ws.getRange(previousRowIndex, 3).setValue('');
      }
    }

    if (currentStatus !== 'Selesai Diperbaiki') {
      ws.getRange(rowIndex, 2).setValue('Sedang Dikerjakan');
      ws.getRange(rowIndex, 3).setValue(userName);
      SpreadsheetApp.flush();
    }

    try { lock.releaseLock(); } catch(e) {}

    var linkFasih = '';
    if (formula.indexOf('HYPERLINK') !== -1) {
      var match = formula.match(/HYPERLINK\("([^"]+)"/i);
      if (match) linkFasih = match[1];
    } else {
      linkFasih = String(rowData[3] || '');
    }
    if ((!linkFasih || linkFasih.indexOf('http') !== 0) && rowData[23]) {
      linkFasih = 'https://fasih-sm.bps.go.id/app/assignment/fd68e454-ba45-4b85-8205-f3bf777ded24/' + String(rowData[23]).trim() + '/edit';
    }
    linkFasih = formatFasihUrl(linkFasih);

    var linkMap = '';
    if (formulaZ.indexOf('HYPERLINK') !== -1) {
      var matchZ = formulaZ.match(/HYPERLINK\("([^"]+)"/i);
      if (matchZ) linkMap = matchZ[1];
    } else {
      linkMap = String(rowData[25] || '').trim();
    }
    linkMap = (linkMap && linkMap !== '-' && linkMap.indexOf('http') === 0) ? linkMap : '';

    var omzBaru = parseMoney(rowData[15]);
    var iddesaVal = String(rowData[24] || '');
    var namaUsahaVal = String(rowData[4] || '');

    return {
      sheetName: sheetName,
      rowIndex: rowIndex,
      no: rowData[0],
      status: (currentStatus === 'Selesai Diperbaiki') ? 'Selesai Diperbaiki' : 'Sedang Dikerjakan',
      petugas: (currentStatus === 'Selesai Diperbaiki') ? currentPetugas : userName,
      linkFasih: linkFasih,
      linkMap: linkMap,
      namaUsaha: namaUsahaVal,
      kategori: String(rowData[5] || ''),
      kbli: String(rowData[6] || ''),
      tk: parseMoney(rowData[7]),
      rasioAwal: parseRatio(rowData[8]).toFixed(2),
      rincianUbah: String(rowData[9] || ''),
      upahBaru: parseMoney(rowData[10]),
      prodBaru: parseMoney(rowData[11]),
      cogsBaru: parseMoney(rowData[12]),
      opsBaru: parseMoney(rowData[13]),
      totBaru: parseMoney(rowData[14]),
      omzBaru: omzBaru,
      rasioBaru: parseRatio(rowData[16]).toFixed(2),
      catatan: String(rowData[17] || ''),
      upahOld: parseMoney(rowData[18]),
      prodOld: parseMoney(rowData[19]),
      cogsOld: parseMoney(rowData[20]),
      opsOld: parseMoney(rowData[21]),
      nonOld: parseMoney(rowData[22]),
      totOld: parseMoney(rowData[14]),
      omzOld: omzBaru,
      iddesa: iddesaVal,
      kecCode: getKecamatanCode(iddesaVal, namaUsahaVal),
      kecamatan: getKecamatanName(iddesaVal, namaUsahaVal)
    };

  } catch (err) {
    try { lock.releaseLock(); } catch(e) {}
    throw err;
  }
}
