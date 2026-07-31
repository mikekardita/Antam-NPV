{{-- components/history.blade.php --}}
<section id="riwayat">
    <div class="sec-eye reveal">Manajemen Data</div>
    <h2 class="sec-title reveal">Riwayat Analisis</h2>

    {{-- Toolbar: Search, Filter, Aksi --}}
    <div class="crud-toolbar reveal">
        <div class="crud-search-wrap">
            <input id="crud-search" type="text" placeholder="🔍 Cari analisis...">
            <select id="crud-filter">
                <option value="all">Semua</option>
                <option value="layak">Layak</option>
                <option value="tidak">Tidak Layak</option>
            </select>
        </div>
        <div style="display:flex;gap:8px">
            <button class="btn-sm" id="exportBtn">⬇ Export CSV</button>
            <button class="btn-sm" id="clearAllBtn"
                    style="color:var(--red);border-color:rgba(248,113,113,.3)">
                🗑 Hapus Semua
            </button>
        </div>
    </div>

    {{-- Tabel Riwayat --}}
    <div class="table-panel reveal">
        <div class="scroll-wrap">
            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox" id="check-all" style="accent-color:var(--gold)"></th>
                        <th>NAMA</th>
                        <th>TANGGAL</th>
                        <th>MODAL</th>
                        <th>GRAM</th>
                        <th>NPV</th>
                        <th>STATUS</th>
                        <th>AKSI</th>
                    </tr>
                </thead>
                <tbody id="crud-tbody">
                    <tr>
                        <td colspan="8"
                            style="text-align:center;padding:32px;color:var(--text-muted)">
                            Belum ada analisis tersimpan
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

{{-- Modal Simpan Analisis --}}
<div id="modal-save" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-title">Simpan Analisis</div>
        <input id="save-name" class="modal-input" type="text" placeholder="Nama analisis...">
        <textarea id="save-note" class="modal-input" placeholder="Catatan (opsional)" rows="3"></textarea>
        <div class="modal-preview" id="save-preview"></div>
        <div class="modal-footer">
            <button class="btn-cancel" id="modal-cancel">Batal</button>
            <button class="btn-confirm" id="modal-confirm">Simpan</button>
        </div>
    </div>
</div>
