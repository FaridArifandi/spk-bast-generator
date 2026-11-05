/**
 * Penerima ekspor SPK-BAST Generator -> Google Sheets (searah).
 *
 * Cara pasang:
 * 1. Buka spreadsheet -> Extensions -> Apps Script, tempel isi berkas ini (gabung dengan doGet lama bila ada).
 * 2. Ganti TOKEN di bawah dengan teks acak panjang; isi nilai yang sama di SHEETS_TOKEN pada .env aplikasi.
 * 3. Deploy -> New deployment -> Web app: Execute as "Me", Who has access "Anyone". Salin URL /exec ke SHEETS_URL.
 * 4. Setelah mengubah skrip, buat deployment versi baru supaya perubahan berlaku.
 * Catatan: tiap ekspor menulis ulang seluruh isi tab; jangan mengedit tab ini langsung.
 */
var TOKEN = 'GANTI_DENGAN_TOKEN_RAHASIA';

function doPost(e) {
  try {
    var body = JSON.parse(e.postData.contents);
    if (!body.token || body.token !== TOKEN) return keluar({ status: 'error', message: 'token salah' });

    var ss = SpreadsheetApp.getActiveSpreadsheet();
    var jumlah = {};
    Object.keys(body.sheets).forEach(function (nama) {
      var data = body.sheets[nama];
      var ws = ss.getSheetByName(nama) || ss.insertSheet(nama);
      var baris = [data.headers].concat(data.rows);
      var lebar = data.headers.length;

      ws.clearContents();
      var rng = ws.getRange(1, 1, baris.length, lebar);
      rng.setNumberFormat('@'); // simpan sebagai teks agar nomor/tanggal tidak diubah Sheets
      rng.setValues(baris.map(function (r) {
        return r.map(function (v) { return v === null || v === undefined ? '' : v; });
      }));
      ws.getRange(1, 1, 1, lebar).setFontWeight('bold');
      ws.setFrozenRows(1);
      jumlah[nama] = data.rows.length;
    });

    return keluar({ status: 'ok', rows: jumlah });
  } catch (err) {
    return keluar({ status: 'error', message: String(err) });
  }
}

function keluar(obj) {
  return ContentService.createTextOutput(JSON.stringify(obj)).setMimeType(ContentService.MimeType.JSON);
}
