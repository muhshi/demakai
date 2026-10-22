/**
 * =========================================================================
 * PINTAR KBLI - SE2026 DEMAK: 5-ASSIGNMENT BATCH VERIFIER CONTROLLER
 * Google Apps Script backend logic for 5-row batch verification
 * Linked with Google Spreadsheet & Fasih-SM BPS
 * =========================================================================
 */

var SPREADSHEET_ID = '1gvylAdsDQ6-dafaj2Psh4YoRRlcWpphTvuxhp3eQLUU';

/**
 * Mendapatkan referensi Google Spreadsheet aktif atau berdasarkan SPREADSHEET_ID
 */
function getSpreadsheet() {
  var ss = null;
  try {
    ss = SpreadsheetApp.getActiveSpreadsheet();
  } catch (e) {
    ss = null;
  }
  if (!ss) {
    ss = SpreadsheetApp.openById(SPREADSHEET_ID);
  }
  return ss;
}

/**
 * Mendapatkan Sheet berdasarkan nama, atau fallback ke sheet pertama
 */
function getSheet(ss, sheetName) {
  if (!ss) ss = getSpreadsheet();
  if (sheetName && sheetName !== 'ALL') {
    var ws = ss.getSheetByName(sheetName);
    if (ws) return ws;
  }
  return ss.getSheets()[0];
}

/**
 * Mendapatkan daftar nama sheet yang tersedia di spreadsheet
 */
function getSheetsList() {
  var ss = getSpreadsheet();
  var sheets = ss.getSheets();
  var list = [];
  for (var i = 0; i < sheets.length; i++) {
    list.push(sheets[i].getName());
  }
  return list;
}

/**
 * Membersihkan nilai string dari cell
 */
function cleanString(val) {
  if (val === null || val === undefined) return '';
  var s = String(val).trim();
  if (s.toLowerCase() === 'nan' || s.toLowerCase() === 'null' || s.toLowerCase() === 'undefined') return '';
  return s;
}

/**
 * Mengekstrak 5-digit kode KBLI dari string (misal '47111 - Perdagangan...' -> '47111')
 */
function extractKbliCode(val) {
  if (!val) return '';
  var s = String(val).trim();
  var m5 = s.match(/\b\d{5}\b/);
  if (m5) return m5[0];
  var m4 = s.match(/\b\d{4}\b/);
  if (m4) return ('0' + m4[0]);
  return s;
}

/**
 * Memastikan URL Fasih-SM selalu berformat '/edit' yang valid
 */
function formatFasihUrl(url, assignmentId) {
  var cleanUrl = cleanString(url);
  if (!cleanUrl && assignmentId) {
    cleanUrl = 'https://fasih-sm.bps.go.id/app/assignment/fd68e454-ba45-4b85-8205-f3bf777ded24/' + assignmentId + '/edit';
  }
  if (cleanUrl.indexOf('http') === 0 && cleanUrl.indexOf('fasih-sm.bps.go.id') !== -1) {
    cleanUrl = cleanUrl.replace(/\/+$/, '');
    if (!cleanUrl.endsWith('/edit')) {
      cleanUrl += '/edit';
    }
  }
  return cleanUrl;
}

/**
 * Mengambil daftar unik kecamatan untuk filter dropdown
 */
function getKecamatanList(sheetName) {
  var ss = getSpreadsheet();
  var ws = getSheet(ss, sheetName);
  if (!ws) return [];

  var lastRow = ws.getLastRow();
  if (lastRow < 2) return [];

  var kecValues = ws.getRange(2, 9, lastRow - 1, 1).getValues();
  var dict = {};
  var list = [];

  for (var i = 0; i < kecValues.length; i++) {
    var val = cleanString(kecValues[i][0]);
    if (val && !dict[val]) {
      dict[val] = true;
      list.push(val);
    }
  }
  list.sort();
  return list;
}

/**
 * Mengambil batch 5 assignment yang belum berstatus 'Selesai'
 * Mendukung filter kecamatan dan startIndex pagination
 */
function get5Assignments(sheetName, userName, kecFilter, startIndex) {
  var ss = getSpreadsheet();
  var ws = getSheet(ss, sheetName);
  if (!ws) {
    return {
      success: false,
      items: [],
      totalRemaining: 0,
      nextStartIndex: -1,
      hasMore: false,
      message: 'Sheet tidak ditemukan'
    };
  }

  var lastRow = ws.getLastRow();
  if (lastRow < 2) {
    return {
      success: true,
      items: [],
      totalRemaining: 0,
      nextStartIndex: -1,
      hasMore: false
    };
  }

  startIndex = parseInt(startIndex, 10);
  if (isNaN(startIndex) || startIndex < 2) {
    startIndex = 2;
  }

  var numRows = lastRow - 1;
  // Baca kolom 2 (B: status_pengerjaan) untuk penapisan super cepat
  var statusValues = ws.getRange(2, 2, numRows, 1).getValues();
  // Baca kolom 9 (I: kecamatan) jika ada filter kecamatan
  var kecValues = (kecFilter && kecFilter !== 'ALL') ? ws.getRange(2, 9, numRows, 1).getValues() : null;

  var matchingRowIndices = [];
  var totalRemaining = 0;
  var nextStartIndex = -1;

  for (var i = 0; i < numRows; i++) {
    var actualRowIndex = i + 2;
    var st = cleanString(statusValues[i][0]);
    if (st === 'Selesai') continue;

    if (kecFilter && kecFilter !== 'ALL') {
      var kVal = cleanString(kecValues[i][0]).toLowerCase();
      var fVal = String(kecFilter).trim().toLowerCase();
      if (kVal !== fVal && kVal.indexOf(fVal) === -1 && fVal.indexOf(kVal) === -1) {
        continue;
      }
    }

    totalRemaining++;

    if (actualRowIndex >= startIndex && matchingRowIndices.length < 5) {
      matchingRowIndices.push(actualRowIndex);
    }
  }

  // Jika startIndex berada di bagian akhir sheet tapi masih ada sisa baris di awal (wrap-around)
  if (matchingRowIndices.length === 0 && totalRemaining > 0 && startIndex > 2) {
    for (var j = 0; j < numRows; j++) {
      var wrapRowIndex = j + 2;
      var stWrap = cleanString(statusValues[j][0]);
      if (stWrap === 'Selesai') continue;

      if (kecFilter && kecFilter !== 'ALL') {
        var kValWrap = cleanString(kecValues[j][0]).toLowerCase();
        var fValWrap = String(kecFilter).trim().toLowerCase();
        if (kValWrap !== fValWrap && kValWrap.indexOf(fValWrap) === -1 && fValWrap.indexOf(kValWrap) === -1) {
          continue;
        }
      }

      if (matchingRowIndices.length < 5) {
        matchingRowIndices.push(wrapRowIndex);
      } else {
        break;
      }
    }
  }

  var items = [];
  for (var k = 0; k < matchingRowIndices.length; k++) {
    var rIdx = matchingRowIndices[k];
    var r = ws.getRange(rIdx, 1, 1, 26).getValues()[0];

    var rec1 = cleanString(r[16]);
    var rec2 = cleanString(r[18]);
    var rec3 = cleanString(r[20]);
    var assignId = cleanString(r[6]);
    var linkFasih = formatFasihUrl(r[25], assignId);

    items.push({
      rowIndex: rIdx,
      no: r[0],
      status_pengerjaan: cleanString(r[1]) || 'Belum Dikerjakan',
      status_kbli: cleanString(r[2]) || 'Belum Diverifikasi',
      kbli_final_benar: cleanString(r[3]),
      petugas_verifikasi: cleanString(r[4]),
      waktu_selesai: cleanString(r[5]),
      assignment_id: assignId,
      no_usaha: cleanString(r[7]),
      kecamatan: cleanString(r[8]),
      desa: cleanString(r[9]),
      kode_sls: cleanString(r[10]),
      petugas_lapangan: cleanString(r[11]),
      nama_usaha: cleanString(r[12]),
      uraian_kegiatan: cleanString(r[13]),
      kbli_saat_ini: cleanString(r[14]),
      judul_kbli_saat_ini: cleanString(r[15]),
      rekomendasi_1: rec1,
      rekomendasi_1_code: extractKbliCode(rec1),
      confidence_1: cleanString(r[17]),
      rekomendasi_2: rec2,
      rekomendasi_2_code: extractKbliCode(rec2),
      confidence_2: cleanString(r[19]),
      rekomendasi_3: rec3,
      rekomendasi_3_code: extractKbliCode(rec3),
      confidence_3: cleanString(r[21]),
      rule_terpicu: cleanString(r[22]),
      rekomendasi_tindakan: cleanString(r[23]),
      alasan_rekomendasi: cleanString(r[24]),
      link_fasih_sm: linkFasih
    });
  }

  if (matchingRowIndices.length > 0) {
    nextStartIndex = matchingRowIndices[matchingRowIndices.length - 1] + 1;
  }

  return {
    success: true,
    items: items,
    count: items.length,
    totalRemaining: totalRemaining,
    nextStartIndex: nextStartIndex,
    hasMore: totalRemaining > items.length
  };
}

/**
 * Menyimpan hasil verifikasi untuk 1 baris assignment
 * Dilengkapi ScriptLock untuk mencegah race condition multi-user
 */
function submitAssignment(sheetName, rowIndex, userName, finalKbli, originalRec1) {
  rowIndex = parseInt(rowIndex, 10);
  if (isNaN(rowIndex) || rowIndex < 2) {
    return { success: false, error: 'Indeks baris tidak valid.' };
  }

  var cleanUser = cleanString(userName) || 'Petugas';
  var cleanFinal = cleanString(finalKbli);
  var cleanOrig = cleanString(originalRec1);

  // Jika input KBLI final kosong, ambil kode dari original rekomendasi 1
  if (!cleanFinal && cleanOrig) {
    cleanFinal = extractKbliCode(cleanOrig);
  }

  // Bandingkan kode bersih KBLI
  var codeFinal = extractKbliCode(cleanFinal);
  var codeOrig = extractKbliCode(cleanOrig);

  var isEqual = false;
  if (codeFinal && codeOrig) {
    isEqual = (codeFinal === codeOrig);
  } else {
    isEqual = (cleanFinal.toLowerCase() === cleanOrig.toLowerCase());
  }

  var statusKbli = '';
  if (isEqual) {
    statusKbli = 'KBLI Sudah Benar Sesuai Rekomendasi 1';
  } else {
    statusKbli = 'KBLI Diubah ke: ' + cleanFinal;
  }

  var waktuSelesai = Utilities.formatDate(new Date(), 'Asia/Jakarta', 'yyyy-MM-dd HH:mm:ss');

  var lock = LockService.getScriptLock();
  var hasLock = false;
  try {
    hasLock = lock.tryLock(10000);
  } catch (e) {
    hasLock = false;
  }

  if (!hasLock) {
    return {
      success: false,
      error: 'Sistem sedang sibuk. Gagal mendapatkan kunci (lock). Silakan coba simpan kembali.'
    };
  }

  try {
    var ss = getSpreadsheet();
    var ws = getSheet(ss, sheetName);
    if (!ws) {
      throw new Error('Sheet "' + sheetName + '" tidak ditemukan.');
    }

    if (rowIndex > ws.getLastRow()) {
      throw new Error('Indeks baris melebihi jumlah data sheet.');
    }

    // Update Kolom B s/d F (5 Kolom):
    // Col B (2): status_pengerjaan
    // Col C (3): status_kbli
    // Col D (4): kbli_final_benar
    // Col E (5): petugas_verifikasi
    // Col F (6): waktu_selesai
    ws.getRange(rowIndex, 2, 1, 5).setValues([[
      'Selesai',
      statusKbli,
      cleanFinal,
      cleanUser,
      waktuSelesai
    ]]);

    SpreadsheetApp.flush();

    return {
      success: true,
      status_kbli: statusKbli,
      kbli_final_benar: cleanFinal,
      petugas_verifikasi: cleanUser,
      waktu_selesai: waktuSelesai,
      rowIndex: rowIndex
    };
  } catch (err) {
    return {
      success: false,
      error: err.message || String(err)
    };
  } finally {
    try {
      lock.releaseLock();
    } catch (e) {}
  }
}

/**
 * Ringkasan cepat jumlah baris total, selesai, dan sisa
 */
function getBatchSummary(sheetName, kecFilter) {
  var ss = getSpreadsheet();
  var ws = getSheet(ss, sheetName);
  if (!ws) return { total: 0, selesai: 0, sisa: 0 };

  var lastRow = ws.getLastRow();
  if (lastRow < 2) return { total: 0, selesai: 0, sisa: 0 };

  var numRows = lastRow - 1;
  var statusCol = ws.getRange(2, 2, numRows, 1).getValues();
  var kecCol = (kecFilter && kecFilter !== 'ALL') ? ws.getRange(2, 9, numRows, 1).getValues() : null;

  var total = 0;
  var selesai = 0;

  for (var i = 0; i < numRows; i++) {
    if (kecFilter && kecFilter !== 'ALL') {
      var kVal = cleanString(kecCol[i][0]).toLowerCase();
      var fVal = String(kecFilter).trim().toLowerCase();
      if (kVal !== fVal && kVal.indexOf(fVal) === -1 && fVal.indexOf(kVal) === -1) {
        continue;
      }
    }

    total++;
    var st = cleanString(statusCol[i][0]);
    if (st === 'Selesai') {
      selesai++;
    }
  }

  return {
    total: total,
    selesai: selesai,
    sisa: total - selesai
  };
}

/**
 * Mengambil ringkasan statistik komprehensif untuk halaman Monitoring & Leaderboard
 */
function getMonitoringData(targetSheet, userName, kecFilter) {
  var ss = getSpreadsheet();
  var ws = getSheet(ss, targetSheet);
  if (!ws) {
    return {
      total: 0,
      done: 0,
      remaining: 0,
      myDone: 0,
      byPetugas: {},
      byStatusKbli: {},
      byKecamatan: {}
    };
  }

  var lastRow = ws.getLastRow();
  if (lastRow < 2) {
    return {
      total: 0,
      done: 0,
      remaining: 0,
      myDone: 0,
      byPetugas: {},
      byStatusKbli: {},
      byKecamatan: {}
    };
  }

  var numRows = lastRow - 1;
  // Baca kolom B (2) sampai I (9) = 8 kolom:
  // idx 0 = Col B (status_pengerjaan)
  // idx 1 = Col C (status_kbli)
  // idx 2 = Col D (kbli_final_benar)
  // idx 3 = Col E (petugas_verifikasi)
  // idx 4 = Col F (waktu_selesai)
  // idx 5 = Col G (assignment_id)
  // idx 6 = Col H (no_usaha)
  // idx 7 = Col I (kecamatan)
  var rangeValues = ws.getRange(2, 2, numRows, 8).getValues();

  var total = 0;
  var done = 0;
  var myDone = 0;
  var byPetugas = {};
  var byStatusKbli = {};
  var byKecamatan = {};

  var cleanUserLower = cleanString(userName).toLowerCase();

  for (var i = 0; i < numRows; i++) {
    var r = rangeValues[i];
    var stPengerjaan = cleanString(r[0]);
    var stKbli = cleanString(r[1]);
    var pet = cleanString(r[3]);
    var kec = cleanString(r[7]);

    if (kecFilter && kecFilter !== 'ALL') {
      var kLower = kec.toLowerCase();
      var fLower = String(kecFilter).trim().toLowerCase();
      if (kLower !== fLower && kLower.indexOf(fLower) === -1 && fLower.indexOf(kLower) === -1) {
        continue;
      }
    }

    total++;

    if (stPengerjaan === 'Selesai') {
      done++;

      if (cleanUserLower && pet.toLowerCase() === cleanUserLower) {
        myDone++;
      }

      // Agregasi Petugas
      var petKey = pet || 'Tanpa Nama';
      byPetugas[petKey] = (byPetugas[petKey] || 0) + 1;

      // Agregasi Status KBLI
      var statusKey = stKbli;
      if (statusKey.indexOf('KBLI Diubah') === 0) {
        statusKey = 'KBLI Diubah';
      }
      if (!statusKey) statusKey = 'Belum Diverifikasi';
      byStatusKbli[statusKey] = (byStatusKbli[statusKey] || 0) + 1;
    }

    // Agregasi Kecamatan
    if (kec) {
      if (!byKecamatan[kec]) {
        byKecamatan[kec] = { total: 0, done: 0, remaining: 0 };
      }
      byKecamatan[kec].total++;
      if (stPengerjaan === 'Selesai') {
        byKecamatan[kec].done++;
      }
    }
  }

  // Hitung remaining tiap kecamatan
  for (var kName in byKecamatan) {
    byKecamatan[kName].remaining = byKecamatan[kName].total - byKecamatan[kName].done;
  }

  return {
    total: total,
    done: done,
    remaining: total - done,
    myDone: myDone,
    byPetugas: byPetugas,
    byStatusKbli: byStatusKbli,
    byKecamatan: byKecamatan,
    lastUpdated: Utilities.formatDate(new Date(), 'Asia/Jakarta', 'yyyy-MM-dd HH:mm:ss')
  };
}

/**
 * Entrypoint Web App doGet
 */
function doGet(e) {
  return HtmlService.createHtmlOutputFromFile('Index_5Baris')
    .setTitle('Verifikator 5 Baris KBLI SE2026 - PINTAR KBLI')
    .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL)
    .addMetaTag('viewport', 'width=device-width, initial-scale=1');
}

/**
 * Membuka Modal Verifikator 5 Baris langsung di dalam Google Spreadsheet
 */
function open5BarisModal() {
  var html = HtmlService.createHtmlOutputFromFile('Index_5Baris')
    .setWidth(1280)
    .setHeight(820);
  SpreadsheetApp.getUi().showModalDialog(html, '⚡ PINTAR KBLI - Verifikator 5 Baris KBLI SE2026');
}

/**
 * Menambahkan Menu Kustom di UI Spreadsheet
 */
function onOpen() {
  SpreadsheetApp.getUi()
    .createMenu('⚡ PINTAR KBLI')
    .addItem('🚀 Buka Verifikator 5 Baris', 'open5BarisModal')
    .addSeparator()
    .addItem('📊 Monitoring & Leaderboard', 'open5BarisModal')
    .addToUi();
}
