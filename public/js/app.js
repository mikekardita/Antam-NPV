/**
 * app.js — ANTAM NPV Kalkulator Investasi Emas
 *
 * Arsitektur: Module pattern — setiap bagian besar dibungkus
 * dalam IIFE atau object agar tidak mencemari global scope.
 *
 * Modul:
 *  1. ThreeJS   – Animasi 3D torus & sphere di hero
 *  2. Particles – Partikel emas mengambang
 *  3. Reveal    – Scroll reveal animation
 *  4. Prices    – Fetch & tampilkan harga realtime dari /api/prices
 *  5. Calculator– Hitung NPV via /api/npv/calculate
 *  6. Chart     – Render grafik Chart.js
 *  7. History   – CRUD riwayat analisis via /api/analysis
 *  8. Toast     – Notifikasi ringan
 */

'use strict';

/* ═══════════════════════════════════════════════════════════════════════════
   MODUL 1 — THREE.JS: 3D Gold Torus & Floating Spheres
   ═══════════════════════════════════════════════════════════════════════════ */
(function ThreeModule() {
    const canvas = document.getElementById('hero-canvas');
    if (!canvas || typeof THREE === 'undefined') return;

    const renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(45, 1, 0.1, 100);
    camera.position.set(0, 0, 5);

    function resize() {
        const { clientWidth: w, clientHeight: h } = canvas.parentElement;
        renderer.setSize(w, h);
        camera.aspect = w / h;
        camera.updateProjectionMatrix();
    }
    resize();
    window.addEventListener('resize', resize);

    // Materials
    const goldMat = new THREE.MeshStandardMaterial({ color: 0xD4A843, metalness: 1, roughness: 0.12, emissive: 0x553300, emissiveIntensity: 0.3 });
    const wireframeMat = new THREE.MeshBasicMaterial({ color: 0xD4A843, wireframe: true, transparent: true, opacity: 0.06 });

    // Torus (main gold ring)
    const torus = new THREE.Mesh(new THREE.TorusGeometry(1.8, 0.45, 64, 128), goldMat);
    torus.position.set(3, 0, -1);
    scene.add(torus);

    // Wireframe sphere
    const sphere = new THREE.Mesh(new THREE.SphereGeometry(2.5, 32, 32), wireframeMat);
    sphere.position.set(-2, 1, -3);
    scene.add(sphere);

    // Small floating gold balls
    const smallGroup = new THREE.Group();
    scene.add(smallGroup);
    [0, 1, 2, 3].forEach(i => {
        const mat = new THREE.MeshStandardMaterial({ color: 0xD4A843, metalness: 1, roughness: 0.1, emissive: 0x442200, emissiveIntensity: 0.5 });
        const m = new THREE.Mesh(new THREE.SphereGeometry(0.08 + i * 0.04, 16, 16), mat);
        const angle = (i / 4) * Math.PI * 2;
        m.position.set(Math.cos(angle) * 2.5, Math.sin(angle * 0.8) * 0.8, Math.sin(angle) * 2.5);
        smallGroup.add(m);
    });

    // Orbital ring
    const ring = new THREE.Mesh(
        new THREE.TorusGeometry(3.2, 0.04, 8, 80),
        new THREE.MeshBasicMaterial({ color: 0xD4A843, transparent: true, opacity: 0.2 })
    );
    ring.rotation.x = Math.PI * 0.3;
    ring.position.set(0, -0.5, -2);
    scene.add(ring);

    // Lights
    scene.add(new THREE.AmbientLight(0xffffff, 0.4));
    const p1 = new THREE.PointLight(0xFFD700, 3, 10); p1.position.set(3, 3, 3); scene.add(p1);
    const p2 = new THREE.PointLight(0x4466FF, 1, 8); p2.position.set(-3, -2, 2); scene.add(p2);
    const dl = new THREE.DirectionalLight(0xfff5cc, 1.5); dl.position.set(5, 5, 5); scene.add(dl);

    // Mouse parallax
    let mouse = { x: 0, y: 0 };
    window.addEventListener('mousemove', e => {
        mouse.x = (e.clientX / window.innerWidth - 0.5) * 2;
        mouse.y = -(e.clientY / window.innerHeight - 0.5) * 2;
    });

    let t = 0;
    (function animate() {
        requestAnimationFrame(animate);
        t += 0.008;

        torus.rotation.x = t * 0.4;
        torus.rotation.y = t * 0.6;
        torus.position.x = 3 + Math.sin(t * 0.3) * 0.3;
        torus.position.y = Math.cos(t * 0.2) * 0.4;

        sphere.rotation.y = t * 0.2;
        sphere.rotation.x = t * 0.1;

        smallGroup.rotation.y = t * 0.5;
        smallGroup.rotation.x = Math.sin(t * 0.3) * 0.2;

        ring.rotation.z = t * 0.1;

        camera.position.x += (mouse.x * 0.5 - camera.position.x) * 0.03;
        camera.position.y += (mouse.y * 0.3 - camera.position.y) * 0.03;
        camera.lookAt(0, 0, 0);

        renderer.render(scene, camera);
    })();
})();


/* ═══════════════════════════════════════════════════════════════════════════
   MODUL 2 — PARTICLES: Gold Dust Floating
   ═══════════════════════════════════════════════════════════════════════════ */
(function ParticlesModule() {
    const canvas = document.getElementById('particles-canvas');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    let W, H;

    function resize() {
        W = canvas.width = canvas.parentElement.clientWidth;
        H = canvas.height = canvas.parentElement.clientHeight;
    }
    resize();
    window.addEventListener('resize', resize);

    const N = 70;
    const particles = Array.from({ length: N }, () => ({
        x: Math.random() * 100,
        y: Math.random() * 100,
        vx: (Math.random() - 0.5) * 0.015,
        vy: -Math.random() * 0.025 - 0.005,
        r: Math.random() * 1.8 + 0.4,
        alpha: Math.random() * 0.5 + 0.1,
        life: Math.random(),
    }));

    (function draw() {
        ctx.clearRect(0, 0, W, H);
        particles.forEach(p => {
            p.x += p.vx;
            p.y += p.vy;
            p.life += 0.003;

            if (p.y < -2 || p.life > 1) {
                p.x = Math.random() * 100;
                p.y = 102;
                p.life = 0;
            }

            const a = p.alpha * Math.sin(p.life * Math.PI);
            ctx.beginPath();
            ctx.arc(p.x / 100 * W, p.y / 100 * H, p.r, 0, Math.PI * 2);
            ctx.fillStyle = `rgba(212,168,67,${a})`;
            ctx.fill();
        });
        requestAnimationFrame(draw);
    })();
})();


/* ═══════════════════════════════════════════════════════════════════════════
   MODUL 3 — REVEAL: Scroll Reveal Intersection Observer
   ═══════════════════════════════════════════════════════════════════════════ */
(function RevealModule() {
    const observer = new IntersectionObserver(entries => {
        entries.forEach(e => {
            if (e.isIntersecting) e.target.classList.add('visible');
        });
    }, { threshold: 0.15 });

    document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
})();


/* ═══════════════════════════════════════════════════════════════════════════
   MODUL 4 — PRICES: Realtime Price Fetching (dari /api/prices server-side)
   ═══════════════════════════════════════════════════════════════════════════ */
const PriceModule = (function () {

    let liveANTAM = 0, prevANTAM = 0;
    let liveXAU = 0;
    let liveIDR = 0;
    let liveBuyback = 0;

    // ── Animated counter ──────────────────────────────────────────────────
    function animateCount(el, toVal, decimals) {
        if (!el) return;
        const from = parseFloat(el.dataset.raw) || 0;
        el.dataset.raw = toVal;

        if (from === toVal) {
            el.textContent = decimals > 0
                ? toVal.toFixed(decimals)
                : Math.round(toVal).toLocaleString('id-ID');
            return;
        }

        const dur = 900, start = performance.now();
        (function step(now) {
            const p = Math.min((now - start) / dur, 1);
            const ease = 1 - Math.pow(1 - p, 4);
            const cur = from + (toVal - from) * ease;
            el.textContent = decimals > 0
                ? cur.toFixed(decimals)
                : Math.round(cur).toLocaleString('id-ID');
            if (p < 1) requestAnimationFrame(step);
        })(start);
    }

    // ── Fetch dari endpoint server kita (menghindari CORS) ────────────────
    async function fetchPrices() {
        try {
            const res = await fetch(`${window.APP.apiBase}/prices`);
            const json = await res.json();

            if (!json.success) throw new Error('API error');

            const d = json.data;
            prevANTAM = liveANTAM || d.antam;
            liveANTAM = d.antam;
            liveXAU = d.xau_usd;
            liveIDR = d.usd_idr;
            liveBuyback = d.buyback;

            updateUI();
            buildTicker();
        } catch (e) {
            console.warn('[PriceModule] Gagal fetch harga:', e);
        }
    }

    // ── Update DOM ────────────────────────────────────────────────────────
    function updateUI() {
        animateCount(document.getElementById('hero-price'), liveANTAM, 0);
        animateCount(document.getElementById('hero-xau'), liveXAU, 2);
        animateCount(document.getElementById('hero-idr'), liveIDR, 0);

        // Sync harga beli input jika belum diubah manual
        const hargaInput = document.getElementById('inp-harga');
        if (hargaInput && !hargaInput.dataset.manual && liveANTAM) {
            hargaInput.value = liveANTAM;
            CalculatorModule.updateGram();
        }
    }

    // ── Build ticker marquee ──────────────────────────────────────────────
    function buildTicker() {
        const change = (prevANTAM && prevANTAM !== liveANTAM)
            ? ((liveANTAM - prevANTAM) / prevANTAM * 100).toFixed(2)
            : '0.00';
        const arrow = liveANTAM > prevANTAM ? '▲' : liveANTAM < prevANTAM ? '▼' : '—';
        const cls = liveANTAM > prevANTAM ? 'up' : liveANTAM < prevANTAM ? 'dn' : '';
        const pph = Math.round(liveANTAM * 1.0025);

        const items = [
            `◆ <span class="nm">ANTAM 1gr</span> Rp ${Math.round(liveANTAM).toLocaleString('id-ID')} <span class="${cls}">${arrow} ${change}%</span>`,
            `◆ <span class="nm">+PPh 0.25%</span> Rp ${pph.toLocaleString('id-ID')}`,
            `◆ <span class="nm">Buyback</span> Rp ${Math.round(liveBuyback).toLocaleString('id-ID')}`,
            `◆ <span class="nm">XAU/USD</span> $${liveXAU.toFixed(2)}`,
            `◆ <span class="nm">USD/IDR</span> Rp ${Math.round(liveIDR).toLocaleString('id-ID')}`,
            `◆ <span class="nm">Update</span> ${new Date().toLocaleTimeString('id-ID')}`,
        ];

        const tickerEl = document.getElementById('ticker-inner');
        if (tickerEl) {
            tickerEl.innerHTML = [...items, ...items]
                .map(i => `<span class="ticker-item">${i}</span>`)
                .join('');
        }
    }

    // ── Init & interval (auto-refresh setiap 30 detik, tanpa indikator) ──
    fetchPrices();
    setInterval(fetchPrices, 30_000);

    // ── Public ────────────────────────────────────────────────────────────
    return {
        getLiveANTAM: () => liveANTAM,
    };
})();


/* ═══════════════════════════════════════════════════════════════════════════
   MODUL 5 — CALCULATOR: Hitung NPV via /api/npv/calculate
   ═══════════════════════════════════════════════════════════════════════════ */
const CalculatorModule = (function () {

    let pendingResult = null;

    // ── Hitung gram estimasi (preview sebelum hitung) ─────────────────────
    function updateGram() {
        const modal = parseFloat(document.getElementById('inp-modal')?.value) || 0;
        const harga = parseFloat(document.getElementById('inp-harga')?.value) || 0;
        const el = document.getElementById('inp-gram');
        if (el) {
            if (modal && harga) {
                const estGram = modal / harga;
                el.value = '~' + estGram.toFixed(2) + 'g (estimasi)';
            } else {
                el.value = '';
            }
        }
    }

    // ── Format angka Rupiah ───────────────────────────────────────────────
    function fmt(v) {
        return Math.round(v).toLocaleString('id-ID');
    }

    // ── Tampilkan hasil di DOM ────────────────────────────────────────────
    function renderResults(data) {

        // ── Kartu Denominasi ──────────────────────────────────────────────
        const denomCard = document.getElementById('denom-card');
        denomCard.style.display = 'block';

        document.getElementById('res-denom').textContent = data.denom_label;
        document.getElementById('res-batang').textContent = data.jumlah_batang + ' batang';
        document.getElementById('res-total-gram').textContent = data.total_gram + ' gram';
        document.getElementById('res-harga-gram').textContent = 'Rp ' + fmt(data.harga_per_gram);
        document.getElementById('res-premi').textContent = '+' + data.premi_cetak_pct + '%';
        document.getElementById('res-pph').textContent = 'Rp ' + fmt(data.biaya_pph);
        document.getElementById('res-c0-total').textContent = 'Rp ' + fmt(data.c0);
        document.getElementById('res-sisa-kas').textContent = 'Rp ' + fmt(data.sisa_kas);

        // Update gram display
        const gramEl = document.getElementById('inp-gram');
        if (gramEl) {
            gramEl.value = data.total_gram + 'g (' + data.denom_label + ' × ' + data.jumlah_batang + ')';
        }

        // ── Kartu Break-Even ─────────────────────────────────────────────
        const beCard = document.getElementById('break-even-card');
        beCard.style.display = 'block';

        const beIcon = document.getElementById('break-even-icon');
        const beTitle = document.getElementById('break-even-title');
        const beDesc = document.getElementById('break-even-desc');

        beCard.classList.remove('layak', 'tidak-layak');

        if (data.break_even_month !== null) {
            beCard.classList.add('layak');
            beIcon.textContent = '✅';
            beTitle.textContent = 'Break-Even Point';

            if (data.break_even_month <= 1) {
                beDesc.innerHTML = 'Investasi <strong>langsung menguntungkan</strong> sejak bulan pertama!';
            } else {
                const tahun = Math.floor(data.break_even_month / 12);
                const bulan = data.break_even_month % 12;
                let durasi = '';
                if (tahun > 0) durasi += tahun + ' tahun ';
                if (bulan > 0) durasi += bulan + ' bulan';

                beDesc.innerHTML = 'Investasi mulai <strong>LAYAK di bulan ke-' +
                    data.break_even_month + '</strong> (' + durasi.trim() + ').<br>' +
                    'Simpan emas Anda minimal selama itu untuk untung.';
            }
        } else {
            beCard.classList.add('tidak-layak');
            beIcon.textContent = '⚠️';
            beTitle.textContent = 'Belum Break-Even';
            beDesc.innerHTML = 'Dalam <strong>' + data.rows.length + ' bulan</strong> proyeksi, ' +
                'investasi ini <strong>belum mencapai titik impas</strong>.<br>' +
                'Coba perpanjang horizon atau gunakan skenario tren yang lebih tinggi.';
        }

        // ── Kartu Hasil NPV ──────────────────────────────────────────────

        // NPV
        const npvEl = document.getElementById('res-npv');
        npvEl.innerHTML = (data.npv >= 0 ? '+' : '') + fmt(data.npv);
        npvEl.className = 'rc-val ' + (data.npv >= 0 ? 'pos' : 'neg');

        // ROI
        const roiEl = document.getElementById('res-roi');
        roiEl.innerHTML = (data.roi >= 0 ? '+' : '') + data.roi.toFixed(2) + '%';
        roiEl.className = 'rc-val ' + (data.roi >= 0 ? 'pos' : 'neg');

        // Status
        const statusEl = document.getElementById('res-status');
        const statusText = data.npv > 0 ? '✓ LAYAK' : (data.npv < 0 ? '✗ TIDAK LAYAK' : '◎ IMPAS');
        statusEl.innerHTML = statusText;
        statusEl.className = 'rc-val ' + (data.npv > 0 ? 'pos' : data.npv < 0 ? 'neg' : 'gld');

        // Harga & Nilai Akhir
        document.getElementById('res-harga-akhir').innerHTML = fmt(data.final_price);
        document.getElementById('res-nilai-akhir').innerHTML = fmt(data.final_value);

        // Keuntungan
        const profit = data.final_value - data.c0;
        const profitEl = document.getElementById('res-profit');
        profitEl.innerHTML = (profit >= 0 ? '+' : '') + fmt(profit);
        profitEl.className = 'rc-val ' + (profit >= 0 ? 'pos' : 'neg');

        // ── AI Insight ───────────────────────────────────────────────────
        const aiEl = document.getElementById('ai-content');

        // Konteks & saran strategi berdasarkan hasil
        let modalAdvice = '';
        if (data.modal < 3_000_000) {
            modalAdvice = '💡 <strong>Saran Modal:</strong> Karena modal di bawah Rp 3 Juta, pecahan emas yang terbeli (0.5g / 1g) memiliki premi cetak relatif lebih tinggi (+4.5% s/d +6.5%). Disarankan menyimpan lebih lama atau menambah modal agar lebih efisien.';
        } else if (data.modal >= 50_000_000) {
            modalAdvice = '💎 <strong>Keunggulan Modal:</strong> Modal di atas Rp 50 Juta mendapatkan pecahan besar (50g / 100g) dengan biaya premi sangat efisien (+0.5% s/d +0.8%), membuat titik impas tercapai lebih cepat.';
        }

        // Actionable recommendation
        let actionAdvice = '';
        if (data.npv > 0) {
            actionAdvice = `🎯 <strong>Rekomendasi:</strong> Investasi ini <strong>LAYAK dieksekusi</strong>. Dalam horizon ${data.rows.length} bulan, keuntungan emas berhasil mengalahkan inflasi & biaya transaksi dengan potensi ROI <strong>+${data.roi.toFixed(2)}%</strong>.`;
        } else if (data.break_even_month !== null) {
            actionAdvice = `⏱ <strong>Rekomendasi:</strong> Untuk mencapai keuntungan positif, perpanjang horizon simpan dari ${data.rows.length} bulan menjadi minimal <strong>${data.break_even_month} bulan</strong>.`;
        } else {
            actionAdvice = `📊 <strong>Rekomendasi:</strong> Horizon ${data.rows.length} bulan terlalu singkat untuk skenario tren ini. Disarankan memperpanjang jangka waktu simpan atau memilih skenario tren yang lebih optimistik.`;
        }

        const verdict = data.npv > 0
            ? `✅ <strong style="color:var(--green)">STATUS: LAYAK (Rekomendasi Beli)</strong><br>Investasi menghasilkan keuntungan bersih di atas biaya modal & inflasi.`
            : (data.npv < 0
                ? `⚠️ <strong style="color:var(--red)">STATUS: TIDAK LAYAK (Simpan Lebih Lama)</strong><br>Nilai emas belum cukup mengkompensasi biaya premi cetak (${data.premi_cetak_pct}%), PPh 22, dan potongan buyback ANTAM (2.5%).`
                : `◎ <strong style="color:var(--gold)">STATUS: IMPAS (Break-Even)</strong>`);

        const breakEvenInfo = data.break_even_month !== null
            ? `📅 <strong>Titik Impas (BEP):</strong> Bulan ke-<strong>${data.break_even_month}</strong>.`
            : `📅 <strong>Titik Impas (BEP):</strong> Belum tercapai dalam horizon ${data.rows.length} bulan.`;

        aiEl.innerHTML =
            `<div style="margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid rgba(255,255,255,.06)">` +
            `📍 <strong>Alokasi Emas:</strong> Modal Rp <strong>${fmt(data.modal)}</strong> → <strong>${data.denom_label}</strong> × ${data.jumlah_batang} batang (Total: <strong>${data.total_gram}g</strong>)` +
            ` | Sisa Uang Tunai: <strong>Rp ${fmt(data.sisa_kas)}</strong>` +
            `</div>` +
            `<div style="margin-bottom:12px">${verdict}</div>` +
            `<div style="margin-bottom:10px">${breakEvenInfo}</div>` +
            `<div style="margin-bottom:12px;font-family:'JetBrains Mono',monospace;color:var(--gold)">` +
            `NPV = ${(data.npv >= 0 ? '+' : '') + fmt(data.npv)} | ROI = ${(data.roi >= 0 ? '+' : '') + data.roi.toFixed(2)}%` +
            `</div>` +
            `<div style="margin-bottom:10px;color:var(--text);line-height:1.6">${actionAdvice}</div>` +
            (modalAdvice ? `<div style="font-size:12px;color:var(--text-dim);line-height:1.5">${modalAdvice}</div>` : '');

        // Simpan data pending untuk modal save
        pendingResult = data;
        document.getElementById('btn-save').style.display = 'block';

        // Render chart & table
        ChartModule.render(data.rows);
        renderTable(data.rows);
    }

    // ── Tabel proyeksi ───────────────────────────────────────────────────
    function renderTable(rows) {
        const tbody = document.getElementById('proj-tbody');
        tbody.innerHTML = rows.map(r => {
            const cls = r.change_pct > 0 ? 'badge-g' : 'badge-r';
            const chStr = (r.change_pct > 0 ? '+' : '') + r.change_pct.toFixed(2) + '%';
            const npvStr = (r.npv_cumulative >= 0 ? '+' : '') + Math.round(r.npv_cumulative).toLocaleString('id-ID');
            const npvColor = r.npv_cumulative >= 0 ? 'var(--green)' : 'var(--red)';

            return `<tr>
                <td><strong style="color:var(--text)">${r.period}</strong></td>
                <td>${Math.round(r.price).toLocaleString('id-ID')}</td>
                <td>${Math.round(r.sale_value).toLocaleString('id-ID')}</td>
                <td>${Math.round(r.present_value).toLocaleString('id-ID')}</td>
                <td><span class="badge ${cls}">${chStr}</span></td>
                <td style="color:${npvColor}">${npvStr}</td>
            </tr>`;
        }).join('');
    }

    // ── Kirim request ke API ──────────────────────────────────────────────
    async function runAnalysis() {
        const modal = parseFloat(document.getElementById('inp-modal')?.value);
        const harga = parseFloat(document.getElementById('inp-harga')?.value);
        const horizon = parseInt(document.getElementById('inp-horizon')?.value);
        const disc = parseFloat(document.getElementById('inp-diskonto')?.value);
        const trend = document.getElementById('inp-trend')?.value;

        // ── Validasi client-side sesuai skenario blackbox ─────────────────

        // HR01: modal kosong / tidak valid
        if (!modal || isNaN(modal) || modal <= 0) {
            ToastModule.show('Modal harus diisi');
            return;
        }
        // HR02: harga beli kosong / tidak valid
        if (!harga || isNaN(harga) || harga <= 0) {
            ToastModule.show('Harga beli harus diisi');
            return;
        }
        // PI08: horizon = 0 atau kosong
        if (!horizon || horizon < 1) {
            ToastModule.show('Minimal 1 bulan');
            return;
        }
        // PI11: diskonto negatif
        if (isNaN(disc) || disc < 0) {
            ToastModule.show('Diskonto tidak boleh negatif');
            return;
        }

        const btn = document.getElementById('btn-analyze');
        btn.disabled = true;
        // AI02: loading indicator
        btn.innerHTML = '⧗ &nbsp;Menghitung...';

        try {
            const res = await fetch(`${window.APP.apiBase}/npv/calculate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.APP.csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    modal,
                    harga_beli: harga,
                    horizon_months: horizon,
                    discount_rate: disc,
                    trend,
                }),
            });

            const json = await res.json();

            if (!json.success) {
                // AI03: error handling
                const errs = json.errors
                    ? Object.values(json.errors).flat().join(', ')
                    : json.message || 'Terjadi kesalahan';
                ToastModule.show(errs);
                return;
            }

            renderResults(json.data);
        } catch (e) {
            // AI03: network error tidak crash
            ToastModule.show('Gagal menghubungi server');
            console.error('[CalculatorModule]', e);
        } finally {
            btn.disabled = false;
            btn.innerHTML = '◆ &nbsp;Hitung NPV &amp; ROI';
        }
    }

    // ── Event listeners ───────────────────────────────────────────────────
    // GR02: gram update saat modal berubah
    document.getElementById('inp-modal')?.addEventListener('input', updateGram);
    // GR03: gram update saat harga beli berubah
    document.getElementById('inp-harga')?.addEventListener('input', function () {
        this.dataset.manual = '1';
        updateGram();
    });

    // PI06: Reset harga ke realtime (BB22)
    document.getElementById('resetHargaBtn')?.addEventListener('click', () => {
        const live = PriceModule.getLiveANTAM();
        if (live) {
            const el = document.getElementById('inp-harga');
            el.value = live;
            delete el.dataset.manual;
            updateGram();
            ToastModule.show('Harga direset ke realtime');
        } else {
            ToastModule.show('Menunggu data realtime...');
        }
    });

    document.getElementById('btn-analyze')?.addEventListener('click', runAnalysis);

    // Contoh Layak — parameter realistis yang menghasilkan NPV positif
    document.getElementById('exampleBtn')?.addEventListener('click', () => {
        document.getElementById('inp-modal').value = 50_000_000;
        document.getElementById('inp-diskonto').value = 5;
        document.getElementById('inp-trend').value = 'moderate';
        document.getElementById('inp-horizon').value = 36;
        delete document.getElementById('inp-harga').dataset.manual;
        updateGram();
        runAnalysis();
    });

    document.getElementById('btn-save')?.addEventListener('click', () => HistoryModule.openSaveModal());

    // ── Public ────────────────────────────────────────────────────────────
    return {
        updateGram,
        getPendingResult: () => pendingResult,
    };
})();


/* ═══════════════════════════════════════════════════════════════════════════
   MODUL 6 — CHART: Chart.js Line Chart (Dual Axis dengan Panduan Klien)
   ═══════════════════════════════════════════════════════════════════════════ */
const ChartModule = (function () {
    let chart = null;

    function render(rows) {
        const ctx = document.getElementById('priceChart')?.getContext('2d');
        if (!ctx) return;

        if (chart) chart.destroy();

        const lastNpv = rows.length > 0 ? rows[rows.length - 1].npv_cumulative : 0;
        const npvColor = lastNpv >= 0 ? '#4ADE80' : '#F87171';

        chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: rows.map(r => r.period + 'bln'),
                datasets: [
                    {
                        label: 'Harga (Rp/gram)',
                        data: rows.map(r => Math.round(r.price)),
                        borderColor: '#D4A843',
                        backgroundColor: 'rgba(212,168,67,.08)',
                        tension: 0.4,
                        fill: true,
                        yAxisID: 'y1',
                        pointRadius: 3,
                        borderWidth: 2,
                    },
                    {
                        label: 'NPV Kumulatif (Rp)',
                        data: rows.map(r => Math.round(r.npv_cumulative)),
                        borderColor: npvColor,
                        borderDash: [5, 4],
                        fill: false,
                        yAxisID: 'y2',
                        tension: 0.3,
                        pointRadius: 2,
                        borderWidth: 2,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15,15,15,.95)',
                        borderColor: 'rgba(212,168,67,.3)',
                        borderWidth: 1,
                        titleColor: '#D4A843',
                        bodyColor: '#aaa',
                        padding: 12,
                        callbacks: {
                            label: c => `${c.dataset.label}: Rp ${Math.round(c.parsed.y).toLocaleString('id-ID')}`,
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { color: 'rgba(255,255,255,.04)' },
                        ticks: { color: '#888', font: { size: 11 } },
                    },
                    y1: {
                        position: 'left',
                        title: { display: true, text: 'Harga Emas (Rp/g)', color: '#D4A843', font: { size: 10 } },
                        grid: { color: 'rgba(255,255,255,.04)' },
                        ticks: { color: '#D4A843', font: { size: 11 }, callback: v => 'Rp ' + (v / 1_000_000).toFixed(2) + 'jt' },
                    },
                    y2: {
                        position: 'right',
                        title: { display: true, text: 'NPV (Rp)', color: npvColor, font: { size: 10 } },
                        grid: { display: false },
                        ticks: { color: npvColor, font: { size: 11 }, callback: v => (v >= 0 ? '+' : '') + Math.round(v / 1000) + 'rb' },
                    },
                },
            },
        });

        const sub = document.getElementById('chart-sub');
        if (sub) {
            sub.textContent = `${rows.length} bulan proyeksi — Perhatikan sumbu kiri (Harga Emas) dan sumbu kanan (Untung/Rugi)`;
        }

        // Show the chart reading guide
        const guide = document.getElementById('chart-guide');
        if (guide) guide.style.display = 'block';

        // Update NPV legend dot color dynamically
        const legendDot = document.getElementById('npv-legend-dot');
        if (legendDot) legendDot.style.background = npvColor;
        const guideDot = document.getElementById('npv-guide-dot');
        if (guideDot) guideDot.style.color = npvColor;
    }

    return { render };
})();


/* ═══════════════════════════════════════════════════════════════════════════
   MODUL 7 — HISTORY: CRUD Riwayat Analisis via API
   ═══════════════════════════════════════════════════════════════════════════ */
const HistoryModule = (function () {

    // ── Render tabel riwayat ──────────────────────────────────────────────
    async function loadAndRender() {
        const search = document.getElementById('crud-search')?.value || '';
        const filter = document.getElementById('crud-filter')?.value || 'all';

        try {
            const params = new URLSearchParams({ search, filter });
            const res = await fetch(`${window.APP.apiBase}/analysis?${params}`, {
                headers: { 'Accept': 'application/json' },
            });
            const json = await res.json();
            renderTable(json.data || []);
        } catch (e) {
            console.error('[HistoryModule] Gagal load:', e);
        }
    }

    function renderTable(records) {
        const tbody = document.getElementById('crud-tbody');
        if (!tbody) return;

        if (!records.length) {
            tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:32px;color:var(--text-muted)">Tidak ada data</td></tr>`;
            return;
        }

        tbody.innerHTML = records.map(r => `
            <tr>
                <td><input type="checkbox" class="row-chk" data-id="${r.id}" style="accent-color:var(--gold)"></td>
                <td style="color:var(--text)">${escHtml(r.name)}</td>
                <td>${r.created_at}</td>
                <td>${Math.round(r.modal).toLocaleString('id-ID')}</td>
                <td>${Number(r.gram).toFixed(2)}g</td>
                <td style="color:${r.npv >= 0 ? 'var(--green)' : 'var(--red)'}">
                    ${r.npv >= 0 ? '+' : ''}${Math.round(r.npv).toLocaleString('id-ID')}
                </td>
                <td><span class="badge ${r.is_layak ? 'badge-g' : 'badge-r'}">${r.status_label}</span></td>
                <td>
                    <button class="btn-sm" style="color:var(--gold);border-color:rgba(212,168,67,.3)"
                            onclick="HistoryModule.loadRecord(${r.id})">Muat</button>
                    <button class="btn-sm" style="color:var(--red);border-color:rgba(248,113,113,.3)"
                            onclick="HistoryModule.deleteRecord(${r.id})">Hapus</button>
                </td>
            </tr>
        `).join('');
    }

    // ── Simpan analisis ───────────────────────────────────────────────────
    async function doSave() {
        const name = document.getElementById('save-name')?.value.trim();
        const note = document.getElementById('save-note')?.value.trim();
        const data = CalculatorModule.getPendingResult();

        if (!name) { ToastModule.show('Nama harus diisi'); return; }
        if (!data) { ToastModule.show('Hitung dulu!'); return; }

        try {
            const res = await fetch(`${window.APP.apiBase}/analysis`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.APP.csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    name,
                    note,
                    modal: data.modal,
                    gram: data.gram,
                    harga_beli: data.harga_beli,
                    horizon_months: data.horizon_months || parseInt(document.getElementById('inp-horizon').value),
                    discount_rate: data.discount_rate || parseFloat(document.getElementById('inp-diskonto').value),
                    trend: data.trend,
                    npv: data.npv,
                    roi: data.roi,
                    final_value: data.final_value,
                }),
            });
            const json = await res.json();

            if (json.success) {
                closeModal();
                loadAndRender();
                ToastModule.show('Analisis disimpan ✓');
            } else {
                ToastModule.show(json.message || 'Gagal menyimpan');
            }
        } catch (e) {
            ToastModule.show('Gagal menghubungi server');
        }
    }

    // ── Hapus satu record ─────────────────────────────────────────────────
    async function deleteRecord(id) {
        try {
            const res = await fetch(`${window.APP.apiBase}/analysis/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': window.APP.csrfToken,
                    'Accept': 'application/json',
                },
            });
            const json = await res.json();
            if (json.success) {
                loadAndRender();
                ToastModule.show('Dihapus');
            }
        } catch (e) {
            ToastModule.show('Gagal menghapus');
        }
    }

    // ── Hapus semua ───────────────────────────────────────────────────────
    async function deleteAll() {
        if (!confirm('Hapus semua data riwayat?')) return;

        try {
            const res = await fetch(`${window.APP.apiBase}/analysis/all`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': window.APP.csrfToken,
                    'Accept': 'application/json',
                },
            });
            const json = await res.json();
            if (json.success) {
                loadAndRender();
                ToastModule.show('Semua data dihapus');
            }
        } catch (e) {
            ToastModule.show('Gagal menghapus semua');
        }
    }

    // ── Muat record ke form ───────────────────────────────────────────────
    async function loadRecord(id) {
        try {
            const res = await fetch(`${window.APP.apiBase}/analysis?search=`, {
                headers: { 'Accept': 'application/json' },
            });
            const json = await res.json();
            const rec = (json.data || []).find(r => r.id === id);
            if (!rec) return;

            document.getElementById('inp-modal').value = rec.modal;
            document.getElementById('inp-harga').value = rec.harga_beli;
            document.getElementById('inp-horizon').value = rec.horizon_months;
            document.getElementById('inp-diskonto').value = rec.discount_rate;
            document.getElementById('inp-trend').value = rec.trend;
            delete document.getElementById('inp-harga').dataset.manual;

            CalculatorModule.updateGram();
            document.getElementById('btn-analyze').click();
            document.getElementById('analisis').scrollIntoView({ behavior: 'smooth' });
        } catch (e) {
            ToastModule.show('Gagal memuat record');
        }
    }

    // ── Modal open/close ──────────────────────────────────────────────────
    function openSaveModal() {
        const data = CalculatorModule.getPendingResult();
        if (!data) { ToastModule.show('Hitung dulu!'); return; }

        document.getElementById('save-name').value = 'Investasi ' + new Date().toLocaleDateString('id-ID');
        document.getElementById('save-note').value = '';
        document.getElementById('save-preview').textContent =
            `Modal: Rp ${Math.round(data.modal).toLocaleString('id-ID')}\n` +
            `Gram: ${Number(data.gram).toFixed(4)}g\n` +
            `NPV: ${data.npv >= 0 ? '+' : ''}${Math.round(data.npv).toLocaleString('id-ID')}`;

        document.getElementById('modal-save').style.display = 'flex';
    }

    function closeModal() {
        const m = document.getElementById('modal-save');
        if (m) m.style.display = 'none';
    }

    // ── Utility ───────────────────────────────────────────────────────────
    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // ── Event listeners ───────────────────────────────────────────────────
    document.getElementById('crud-search')?.addEventListener('input', loadAndRender);
    document.getElementById('crud-filter')?.addEventListener('change', loadAndRender);

    document.getElementById('exportBtn')?.addEventListener('click', () => {
        window.location.href = `${window.APP.apiBase}/analysis/export`;
    });

    document.getElementById('clearAllBtn')?.addEventListener('click', deleteAll);
    document.getElementById('modal-cancel')?.addEventListener('click', closeModal);
    document.getElementById('modal-confirm')?.addEventListener('click', doSave);

    // Tutup modal jika klik di luar
    document.getElementById('modal-save')?.addEventListener('click', function (e) {
        if (e.target === this) closeModal();
    });

    // ── Init ──────────────────────────────────────────────────────────────
    loadAndRender();

    // ── Public (dipanggil dari inline onclick di tabel) ───────────────────
    return {
        openSaveModal,
        loadRecord,
        deleteRecord,
    };
})();

// Expose ke window agar onclick di tabel bisa memanggil
window.HistoryModule = HistoryModule;


/* ═══════════════════════════════════════════════════════════════════════════
   MODUL 8 — TOAST: Notifikasi Ringan
   ═══════════════════════════════════════════════════════════════════════════ */
const ToastModule = (function () {
    let timer = null;

    function show(msg, duration = 2500) {
        const el = document.getElementById('toast');
        if (!el) return;

        el.textContent = msg;
        el.classList.add('show');

        if (timer) clearTimeout(timer);
        timer = setTimeout(() => el.classList.remove('show'), duration);
    }

    return { show };
})();
