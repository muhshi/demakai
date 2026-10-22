/**
 * =========================================================================
 * PINTAR KBLI - SE2026 DEMAK: 5-ASSIGNMENT BATCH VERIFIER CONTROLLER
 * Google Apps Script backend logic for 5-row batch verification
 * Features:
 *  1. Multi-user concurrency protection (row locking & 20-min timeout)
 *  2. Default KBLI yang Benar diisi dari KBLI Fasih (kbli_saat_ini)
 *  3. Tombol Skip / Lewati untuk melepas kunci dan mengambil penugasan lain
 *  4. Seamless dynamic replacement upon completion / skip
 *  5. Dedicated personal history & inline correction (getMyCompletedAssignments)
 *  6. Linked with Google Spreadsheet & Fasih-SM BPS
 * =========================================================================
 */

var SPREADSHEET_ID = '1gvylAdsDQ6-dafaj2Psh4YoRRlcWpphTvuxhp3eQLUU';
var LOCK_EXPIRATION_MS = 20 * 60 * 1000; // 20 Menit Lock Timeout

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

var FASIH_SURVEY_ID = 'fd68e454-ba45-4b85-8205-f3bf777ded24';

/**
 * Memastikan URL Fasih-SM selalu berformat resmi:
 * https://fasih-sm.bps.go.id/app/assignment/fd68e454-ba45-4b85-8205-f3bf777ded24/{assignment_id}/edit
 */
function formatFasihUrl(url, assignmentId) {
  var assignId = cleanString(assignmentId);
  if (!assignId && url) {
    var m = String(url).match(/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i);
    if (m) assignId = m[0];
  }

  if (assignId) {
    return 'https://fasih-sm.bps.go.id/app/assignment/' + FASIH_SURVEY_ID + '/' + assignId + '/edit';
  }

  var cleanUrl = cleanString(url);
  if (cleanUrl) {
    cleanUrl = cleanUrl.replace('assignment-detail', 'assignment/' + FASIH_SURVEY_ID);
    cleanUrl = cleanUrl.replace(/\/+$/, '');
    if (!cleanUrl.endsWith('/edit')) {
      cleanUrl += '/edit';
    }
    return cleanUrl;
  }
  return '#';
}

/**
 * Memeriksa apakah suatu baris sedang dikunci (locked) oleh petugas lain
 */
function isRecordLockedByOther(status, petugas, waktuStr, currentUser) {
  if (status !== 'Sedang Dikerjakan') return false;
  var cleanPet = cleanString(petugas).toLowerCase();
  var cleanCurrent = cleanString(currentUser).toLowerCase();
  if (!cleanPet) return false;
  if (cleanPet === cleanCurrent) return false; // Milik user sendiri, tidak terkunci

  if (!waktuStr) return false; // Waktu lock kosong, anggap expired
  var lockDate = new Date(waktuStr);
  if (isNaN(lockDate.getTime())) return false;

  var now = new Date().getTime();
  if (now - lockDate.getTime() > LOCK_EXPIRATION_MS) {
    return false; // Sudah expired > 20 menit, boleh diambil alih
  }
  return true; // Masih aktif dikerjakan pengguna lain
}

/**
 * Format objek assignment dari 1 baris sheet (26 kolom)
 */
function formatAssignmentItem(r, rIdx) {
  var rec1 = cleanString(r[16]);
  var rec2 = cleanString(r[18]);
  var rec3 = cleanString(r[20]);
  var assignId = cleanString(r[6]);
  var linkFasih = formatFasihUrl(r[25], assignId);
  var fasihKbli = cleanString(r[14]);

  return {
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
    kbli_saat_ini: fasihKbli,
    kbli_saat_ini_code: extractKbliCode(fasihKbli),
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
  };
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
 * Mengambil batch 5 assignment yang belum selesai dan tidak terkunci oleh pengguna lain.
 * Secara otomatis mengunci (lock) 5 data tersebut atas nama userName aktif.
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

  var cleanUser = cleanString(userName) || 'Petugas';
  var numRows = lastRow - 1;

  var metaValues = ws.getRange(2, 2, numRows, 8).getValues();

  var matchingRowIndices = [];
  var totalRemaining = 0;
  var nextStartIndex = -1;

  for (var i = 0; i < numRows; i++) {
    var actualRowIndex = i + 2;
    var st = cleanString(metaValues[i][0]); // Col B
    var pet = cleanString(metaValues[i][3]); // Col E
    var waktu = cleanString(metaValues[i][4]); // Col F
    var kec = cleanString(metaValues[i][7]); // Col I

    if (st === 'Selesai') continue;

    if (kecFilter && kecFilter !== 'ALL') {
      var kVal = kec.toLowerCase();
      var fVal = String(kecFilter).trim().toLowerCase();
      if (kVal !== fVal && kVal.indexOf(fVal) === -1 && fVal.indexOf(kVal) === -1) {
        continue;
      }
    }

    totalRemaining++;

    if (isRecordLockedByOther(st, pet, waktu, cleanUser)) {
      continue;
    }

    if (actualRowIndex >= startIndex && matchingRowIndices.length < 5) {
      matchingRowIndices.push(actualRowIndex);
    }
  }

  if (matchingRowIndices.length < 5 && totalRemaining > 0 && startIndex > 2) {
    for (var j = 0; j < numRows; j++) {
      var wrapRowIndex = j + 2;
      if (matchingRowIndices.indexOf(wrapRowIndex) !== -1) continue;

      var stWrap = cleanString(metaValues[j][0]);
      var petWrap = cleanString(metaValues[j][3]);
      var waktuWrap = cleanString(metaValues[j][4]);
      var kecWrap = cleanString(metaValues[j][7]);

      if (stWrap === 'Selesai') continue;

      if (kecFilter && kecFilter !== 'ALL') {
        var kValW = kecWrap.toLowerCase();
        var fValW = String(kecFilter).trim().toLowerCase();
        if (kValW !== fValW && kValW.indexOf(fValW) === -1 && fValW.indexOf(kValW) === -1) {
          continue;
        }
      }

      if (isRecordLockedByOther(stWrap, petWrap, waktuWrap, cleanUser)) {
        continue;
      }

      matchingRowIndices.push(wrapRowIndex);
      if (matchingRowIndices.length >= 5) break;
    }
  }

  var currentTimeStr = Utilities.formatDate(new Date(), 'Asia/Jakarta', 'yyyy-MM-dd HH:mm:ss');
  var items = [];

  for (var k = 0; k < matchingRowIndices.length; k++) {
    var rIdx = matchingRowIndices[k];
    var r = ws.getRange(rIdx, 1, 1, 26).getValues()[0];

    var currentSt = cleanString(r[1]);
    if (currentSt !== 'Sedang Dikerjakan') {
      ws.getRange(rIdx, 2).setValue('Sedang Dikerjakan');
      ws.getRange(rIdx, 5).setValue(cleanUser);
      ws.getRange(rIdx, 6).setValue(currentTimeStr);
      r[1] = 'Sedang Dikerjakan';
      r[4] = cleanUser;
      r[5] = currentTimeStr;
    }

    items.push(formatAssignmentItem(r, rIdx));
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
 * Menyimpan verifikasi 1 baris dan LANGSUNG MENGAMBIL 1 BARIS BERIKUTNYA (Seamless Replacement).
 * Default status:
 * - Jika tidak diubah dari Fasih: 'KBLI Sudah Sesuai (Fasih)'
 * - Jika diubah: 'KBLI Diubah ke: [KODE]'
 */
function submitAndGetNext(sheetName, rowIndex, userName, finalKbli, originalFasihKbli, kecFilter, currentVisibleRowIndices, skippedRowIndices) {
  rowIndex = parseInt(rowIndex, 10);
  if (isNaN(rowIndex) || rowIndex < 2) {
    return { success: false, error: 'Indeks baris tidak valid.' };
  }

  var cleanUser = cleanString(userName) || 'Petugas';
  var cleanFinal = cleanString(finalKbli);
  var cleanOrigFasih = cleanString(originalFasihKbli);

  if (!cleanFinal && cleanOrigFasih) {
    cleanFinal = extractKbliCode(cleanOrigFasih);
  }

  var codeFinal = extractKbliCode(cleanFinal);
  var codeFasih = extractKbliCode(cleanOrigFasih);

  var isEqual = false;
  if (codeFinal && codeFasih) {
    isEqual = (codeFinal === codeFasih);
  } else {
    isEqual = (cleanFinal.toLowerCase() === cleanOrigFasih.toLowerCase());
  }

  var statusKbli = isEqual ? 'KBLI Sudah Sesuai (Fasih)' : ('KBLI Diubah ke: ' + cleanFinal);
  var waktuSelesai = Utilities.formatDate(new Date(), 'Asia/Jakarta', 'yyyy-MM-dd HH:mm:ss');

  var excludedMap = {};
  excludedMap[rowIndex] = true;
  if (currentVisibleRowIndices && Array.isArray(currentVisibleRowIndices)) {
    for (var x = 0; x < currentVisibleRowIndices.length; x++) {
      var exIdx = parseInt(currentVisibleRowIndices[x], 10);
      if (!isNaN(exIdx)) excludedMap[exIdx] = true;
    }
  }
  if (skippedRowIndices && Array.isArray(skippedRowIndices)) {
    for (var s = 0; s < skippedRowIndices.length; s++) {
      var skIdx = parseInt(skippedRowIndices[s], 10);
      if (!isNaN(skIdx)) excludedMap[skIdx] = true;
    }
  }

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
      error: 'Sistem sedang sibuk memproses lock. Silakan klik simpan kembali.'
    };
  }

  try {
    var ss = getSpreadsheet();
    var ws = getSheet(ss, sheetName);
    if (!ws) throw new Error('Sheet "' + sheetName + '" tidak ditemukan.');

    var lastRow = ws.getLastRow();
    if (rowIndex > lastRow) throw new Error('Indeks baris melebihi jumlah data sheet.');

    // 1. Simpan baris yang diselesaikan
    ws.getRange(rowIndex, 2, 1, 5).setValues([[
      'Selesai',
      statusKbli,
      cleanFinal,
      cleanUser,
      waktuSelesai
    ]]);

    // 2. Cari 1 baris berikutnya
    var numRows = lastRow - 1;
    var metaValues = ws.getRange(2, 2, numRows, 8).getValues();

    var nextRowIndex = -1;
    var totalRemaining = 0;

    for (var i = 0; i < numRows; i++) {
      var actIdx = i + 2;
      var st = cleanString(metaValues[i][0]);
      var pet = cleanString(metaValues[i][3]);
      var waktu = cleanString(metaValues[i][4]);
      var kec = cleanString(metaValues[i][7]);

      if (actIdx === rowIndex) {
        st = 'Selesai';
      }

      if (st === 'Selesai') continue;

      if (kecFilter && kecFilter !== 'ALL') {
        var kVal = kec.toLowerCase();
        var fVal = String(kecFilter).trim().toLowerCase();
        if (kVal !== fVal && kVal.indexOf(fVal) === -1 && fVal.indexOf(kVal) === -1) {
          continue;
        }
      }

      totalRemaining++;

      if (nextRowIndex === -1 && !excludedMap[actIdx]) {
        if (!isRecordLockedByOther(st, pet, waktu, cleanUser)) {
          nextRowIndex = actIdx;
        }
      }
    }

    var nextItem = null;
    if (nextRowIndex !== -1) {
      ws.getRange(nextRowIndex, 2).setValue('Sedang Dikerjakan');
      ws.getRange(nextRowIndex, 5).setValue(cleanUser);
      ws.getRange(nextRowIndex, 6).setValue(waktuSelesai);

      var nextRowData = ws.getRange(nextRowIndex, 1, 1, 26).getValues()[0];
      nextRowData[1] = 'Sedang Dikerjakan';
      nextRowData[4] = cleanUser;
      nextRowData[5] = waktuSelesai;
      nextItem = formatAssignmentItem(nextRowData, nextRowIndex);
    }

    SpreadsheetApp.flush();

    return {
      success: true,
      completedRowIndex: rowIndex,
      status_kbli: statusKbli,
      kbli_final_benar: cleanFinal,
      petugas_verifikasi: cleanUser,
      waktu_selesai: waktuSelesai,
      nextItem: nextItem,
      totalRemaining: totalRemaining
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
 * Melewati (Skip) 1 baris assignment yang sedang dikerjakan agar bisa dikerjakan orang lain:
 * 1. Mengembalikan status baris ke 'Belum Dikerjakan' dan menghapus lock petugas.
 * 2. Mengambil 1 baris baru dari antrean (yang tidak sedang ditampilkan & tidak termasuk daftar skipped).
 */
function skipAndGetNext(sheetName, rowIndex, userName, kecFilter, currentVisibleRowIndices, skippedRowIndices) {
  rowIndex = parseInt(rowIndex, 10);
  if (isNaN(rowIndex) || rowIndex < 2) {
    return { success: false, error: 'Indeks baris tidak valid.' };
  }

  var cleanUser = cleanString(userName) || 'Petugas';
  var waktuNow = Utilities.formatDate(new Date(), 'Asia/Jakarta', 'yyyy-MM-dd HH:mm:ss');

  var excludedMap = {};
  excludedMap[rowIndex] = true;
  if (currentVisibleRowIndices && Array.isArray(currentVisibleRowIndices)) {
    for (var x = 0; x < currentVisibleRowIndices.length; x++) {
      var exIdx = parseInt(currentVisibleRowIndices[x], 10);
      if (!isNaN(exIdx)) excludedMap[exIdx] = true;
    }
  }
  if (skippedRowIndices && Array.isArray(skippedRowIndices)) {
    for (var s = 0; s < skippedRowIndices.length; s++) {
      var skIdx = parseInt(skippedRowIndices[s], 10);
      if (!isNaN(skIdx)) excludedMap[skIdx] = true;
    }
  }

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
      error: 'Sistem sedang sibuk memproses lock. Silakan klik lewati kembali.'
    };
  }

  try {
    var ss = getSpreadsheet();
    var ws = getSheet(ss, sheetName);
    if (!ws) throw new Error('Sheet "' + sheetName + '" tidak ditemukan.');

    var lastRow = ws.getLastRow();
    if (rowIndex > lastRow) throw new Error('Indeks baris melebihi jumlah data sheet.');

    // 1. Lepas kunci record yang di-skip: kembalikan ke 'Belum Dikerjakan', bersihkan Col E & Col F
    ws.getRange(rowIndex, 2).setValue('Belum Dikerjakan');
    ws.getRange(rowIndex, 5).setValue('');
    ws.getRange(rowIndex, 6).setValue('');

    // 2. Cari 1 baris pengganti
    var numRows = lastRow - 1;
    var metaValues = ws.getRange(2, 2, numRows, 8).getValues();

    var nextRowIndex = -1;
    var totalRemaining = 0;

    for (var i = 0; i < numRows; i++) {
      var actIdx = i + 2;
      var st = cleanString(metaValues[i][0]);
      var pet = cleanString(metaValues[i][3]);
      var waktu = cleanString(metaValues[i][4]);
      var kec = cleanString(metaValues[i][7]);

      if (actIdx === rowIndex) {
        st = 'Belum Dikerjakan';
      }

      if (st === 'Selesai') continue;

      if (kecFilter && kecFilter !== 'ALL') {
        var kVal = kec.toLowerCase();
        var fVal = String(kecFilter).trim().toLowerCase();
        if (kVal !== fVal && kVal.indexOf(fVal) === -1 && fVal.indexOf(kVal) === -1) {
          continue;
        }
      }

      totalRemaining++;

      if (nextRowIndex === -1 && !excludedMap[actIdx]) {
        if (!isRecordLockedByOther(st, pet, waktu, cleanUser)) {
          nextRowIndex = actIdx;
        }
      }
    }

    var nextItem = null;
    if (nextRowIndex !== -1) {
      ws.getRange(nextRowIndex, 2).setValue('Sedang Dikerjakan');
      ws.getRange(nextRowIndex, 5).setValue(cleanUser);
      ws.getRange(nextRowIndex, 6).setValue(waktuNow);

      var nextRowData = ws.getRange(nextRowIndex, 1, 1, 26).getValues()[0];
      nextRowData[1] = 'Sedang Dikerjakan';
      nextRowData[4] = cleanUser;
      nextRowData[5] = waktuNow;
      nextItem = formatAssignmentItem(nextRowData, nextRowIndex);
    }

    SpreadsheetApp.flush();

    return {
      success: true,
      skippedRowIndex: rowIndex,
      nextItem: nextItem,
      totalRemaining: totalRemaining
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
 * Menyimpan hasil verifikasi untuk 1 baris assignment (kompatibilitas backward)
 */
function submitAssignment(sheetName, rowIndex, userName, finalKbli, originalFasihKbli) {
  return submitAndGetNext(sheetName, rowIndex, userName, finalKbli, originalFasihKbli, 'ALL', [], []);
}

/**
 * Mengambil riwayat data yang sudah dikerjakan (Selesai) oleh userName saat ini.
 */
function getMyCompletedAssignments(sheetName, userName, limit) {
  var ss = getSpreadsheet();
  var ws = getSheet(ss, sheetName);
  if (!ws) {
    return {
      success: false,
      items: [],
      message: 'Sheet tidak ditemukan.'
    };
  }

  var lastRow = ws.getLastRow();
  if (lastRow < 2) {
    return {
      success: true,
      items: [],
      totalCount: 0
    };
  }

  var cleanUserLower = cleanString(userName).toLowerCase();
  var numRows = lastRow - 1;
  var statusCol = ws.getRange(2, 2, numRows, 4).getValues();

  var matchingIndices = [];
  var totalFound = 0;
  var maxLimit = limit ? parseInt(limit, 10) : 100;

  for (var i = numRows - 1; i >= 0; i--) {
    var st = cleanString(statusCol[i][0]);
    var pet = cleanString(statusCol[i][3]).toLowerCase();

    if (st === 'Selesai' && pet === cleanUserLower) {
      totalFound++;
      if (matchingIndices.length < maxLimit) {
        matchingIndices.push(i + 2);
      }
    }
  }

  var items = [];
  for (var k = 0; k < matchingIndices.length; k++) {
    var rIdx = matchingIndices[k];
    var r = ws.getRange(rIdx, 1, 1, 26).getValues()[0];
    items.push(formatAssignmentItem(r, rIdx));
  }

  return {
    success: true,
    items: items,
    totalCount: totalFound
  };
}

/**
 * Mengoreksi kembali data assignment yang sudah pernah diselesaikan oleh pengguna
 */
function updateCompletedAssignment(sheetName, rowIndex, userName, newKbli, originalFasihKbli) {
  rowIndex = parseInt(rowIndex, 10);
  if (isNaN(rowIndex) || rowIndex < 2) {
    return { success: false, error: 'Indeks baris tidak valid.' };
  }

  var cleanUser = cleanString(userName) || 'Petugas';
  var cleanFinal = cleanString(newKbli);
  var cleanOrigFasih = cleanString(originalFasihKbli);

  if (!cleanFinal && cleanOrigFasih) {
    cleanFinal = extractKbliCode(cleanOrigFasih);
  }

  var codeFinal = extractKbliCode(cleanFinal);
  var codeFasih = extractKbliCode(cleanOrigFasih);

  var isEqual = false;
  if (codeFinal && codeFasih) {
    isEqual = (codeFinal === codeFasih);
  } else {
    isEqual = (cleanFinal.toLowerCase() === cleanOrigFasih.toLowerCase());
  }

  var statusKbli = isEqual ? 'KBLI Sudah Sesuai (Fasih)' : ('KBLI Diubah ke: ' + cleanFinal);
  var waktuUpdate = Utilities.formatDate(new Date(), 'Asia/Jakarta', 'yyyy-MM-dd HH:mm:ss');

  var lock = LockService.getScriptLock();
  try {
    lock.tryLock(10000);
  } catch (e) {}

  try {
    var ss = getSpreadsheet();
    var ws = getSheet(ss, sheetName);
    if (!ws) throw new Error('Sheet tidak ditemukan.');

    ws.getRange(rowIndex, 2, 1, 5).setValues([[
      'Selesai',
      statusKbli,
      cleanFinal,
      cleanUser,
      waktuUpdate
    ]]);

    SpreadsheetApp.flush();

    return {
      success: true,
      rowIndex: rowIndex,
      status_kbli: statusKbli,
      kbli_final_benar: cleanFinal,
      petugas_verifikasi: cleanUser,
      waktu_selesai: waktuUpdate
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
 * Membuka kunci (unlock) semua record berstatus 'Sedang Dikerjakan' yang sudah lewat batas waktu (abandoned)
 */
function unlockAllAbandonedRecords() {
  var ss = getSpreadsheet();
  var sheets = ss.getSheets();
  var totalUnlocked = 0;
  var now = new Date().getTime();

  for (var s = 0; s < sheets.length; s++) {
    var ws = sheets[s];
    var lastRow = ws.getLastRow();
    if (lastRow < 2) continue;

    var numRows = lastRow - 1;
    var range = ws.getRange(2, 2, numRows, 5); // B..F
    var vals = range.getValues();
    var modified = false;

    for (var i = 0; i < numRows; i++) {
      var st = cleanString(vals[i][0]);
      var waktu = cleanString(vals[i][4]);

      if (st === 'Sedang Dikerjakan') {
        var isExpired = true;
        if (waktu) {
          var dt = new Date(waktu);
          if (!isNaN(dt.getTime()) && (now - dt.getTime() < LOCK_EXPIRATION_MS)) {
            isExpired = false;
          }
        }
        if (isExpired) {
          vals[i][0] = 'Belum Dikerjakan';
          vals[i][3] = ''; // clear petugas
          totalUnlocked++;
          modified = true;
        }
      }
    }

    if (modified) {
      range.setValues(vals);
    }
  }

  SpreadsheetApp.flush();
  try {
    SpreadsheetApp.getActiveSpreadsheet().toast('Berhasil membuka ' + totalUnlocked + ' data tergantung (unlocked).', 'PINTAR KBLI');
  } catch (e) {}

  return { success: true, totalUnlocked: totalUnlocked };
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
  var rangeValues = ws.getRange(2, 2, numRows, 8).getValues();

  var total = 0;
  var done = 0;
  var myDone = 0;
  var inProgress = 0;
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

      var petKey = pet || 'Tanpa Nama';
      byPetugas[petKey] = (byPetugas[petKey] || 0) + 1;

      var statusKey = stKbli;
      if (statusKey.indexOf('KBLI Diubah') === 0) {
        statusKey = 'KBLI Diubah';
      }
      if (!statusKey) statusKey = 'Belum Diverifikasi';
      byStatusKbli[statusKey] = (byStatusKbli[statusKey] || 0) + 1;
    } else if (stPengerjaan === 'Sedang Dikerjakan') {
      inProgress++;
    }

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

  for (var kName in byKecamatan) {
    byKecamatan[kName].remaining = byKecamatan[kName].total - byKecamatan[kName].done;
  }

  return {
    total: total,
    done: done,
    inProgress: inProgress,
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
    .setWidth(1320)
    .setHeight(840);
  SpreadsheetApp.getUi().showModalDialog(html, '⚡ PINTAR KBLI - Verifikator 5 Baris KBLI SE2026');
}

/**
 * Memperbarui seluruh isi Kolom Z (link_fasih_sm) agar menggunakan format resmi Fasih:
 * https://fasih-sm.bps.go.id/app/assignment/fd68e454-ba45-4b85-8205-f3bf777ded24/{assignment_id}/edit
 */
function updateAllFasihUrlsInSheet() {
  var ss = getSpreadsheet();
  var sheets = ss.getSheets();
  var totalUpdated = 0;

  for (var s = 0; s < sheets.length; s++) {
    var ws = sheets[s];
    var lastRow = ws.getLastRow();
    if (lastRow < 2) continue;

    var numRows = lastRow - 1;
    // Baca assignment_id (Kolom G = 7)
    var assignIds = ws.getRange(2, 7, numRows, 1).getValues();
    var newLinks = [];

    for (var i = 0; i < numRows; i++) {
      var aId = cleanString(assignIds[i][0]);
      var validUrl = formatFasihUrl('', aId);
      newLinks.push([validUrl]);
    }

    ws.getRange(2, 26, numRows, 1).setValues(newLinks);
    totalUpdated += numRows;
  }

  SpreadsheetApp.flush();
  try {
    SpreadsheetApp.getActiveSpreadsheet().toast('Berhasil memperbarui ' + totalUpdated + ' link Fasih di Kolom Z.', 'PINTAR KBLI');
  } catch (e) {}

  return { success: true, count: totalUpdated };
}

/**
 * Menambahkan Menu Kustom di UI Spreadsheet
 */
function onOpen() {
  SpreadsheetApp.getUi()
    .createMenu('⚡ PINTAR KBLI')
    .addItem('🚀 Buka Verifikator 5 Baris', 'open5BarisModal')
    .addSeparator()
    .addItem('📜 Riwayat & Koreksi Saya', 'open5BarisModal')
    .addItem('📊 Monitoring & Leaderboard', 'open5BarisModal')
    .addSeparator()
    .addItem('🔗 Perbarui Seluruh Link Fasih (Kolom Z)', 'updateAllFasihUrlsInSheet')
    .addItem('🔓 Buka Kunci (Unlock) Semua Data Tergantung', 'unlockAllAbandonedRecords')
    .addToUi();
}
