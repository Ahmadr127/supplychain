<?php
/**
 * Standalone Debug Tool for Technical Support 403 Forbidden
 * Save this file to public/debug-ts.php on your server.
 */

// Simple styling
echo '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug TS - 403 Forbidden Checker</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; max-width: 800px; margin: 40px auto; padding: 0 20px; color: #333; }
        h1, h2 { color: #2b6cb0; }
        .card { background: #f7fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
        .success { background: #c6f6d5; border-color: #38a169; color: #22543d; }
        .danger { background: #fed7d7; border-color: #e53e3e; color: #742a2a; }
        textarea { w-index: 100%; width: 100%; box-sizing: border-box; padding: 10px; border: 1px solid #cbd5e0; border-radius: 4px; font-family: monospace; font-size: 14px; }
        button { background: #3182ce; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold; }
        button:hover { background: #2b6cb0; }
        pre { background: #edf2f7; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 13px; }
    </style>
</head>
<body>
    <h1>Debug TS - 403 Forbidden Checker</h1>
    <p>Alat ini digunakan untuk mendeteksi apakah error 403 Forbidden disebabkan oleh Web Application Firewall (ModSecurity/WAF) di server Apache Anda, atau ada masalah di dalam Laravel.</p>';

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputText = $_POST['test_text'] ?? '';
    echo '<div class="card success">';
    echo '<h2>✅ Request POST Berhasil Diterima Server!</h2>';
    echo '<p>Server Apache berhasil menerima request POST ini tanpa hambatan. Hal ini menandakan bahwa server tidak memblokir metode POST dasar.</p>';
    echo '<p><strong>Teks yang dikirimkan:</strong></p>';
    echo '<pre>' . htmlspecialchars($inputText) . '</pre>';
    echo '</div>';
    
    echo '<p><a href="debug-ts.php">← Kembali ke Form</a></p>';
    exit;
}

// Display System Info
echo '<div class="card">';
echo '<h2>Info Request Saat Ini:</h2>';
echo '<ul>';
echo '<li><strong>HTTP Method:</strong> ' . $_SERVER['REQUEST_METHOD'] . '</li>';
echo '<li><strong>Server Software:</strong> ' . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . '</li>';
echo '<li><strong>Request URI:</strong> ' . $_SERVER['REQUEST_URI'] . '</li>';
echo '<li><strong>PHP Version:</strong> ' . phpversion() . '</li>';
echo '</ul>';
echo '</div>';

// Form to test text content
$defaultText = "Processor : Intel Core i3-12100, Motherboard : Chipset H610 / B760 (ASUS / Gigabyte / MSI) (Harus Ada Port RJ 45 dan 2 Port HDMI), RAM : 8GB, Storage : 256GB NVMe SSD (Samsung or Vgen) & HDD 500 GB, PSU : 400W - 450W (80 Plus Bronze), OS : Windows 10 Pro, Monitor : MSI 24 Inch";
?>

<div class="card">
    <h2>Uji Pengiriman Teks Spesifikasi</h2>
    <p>Tempelkan teks spesifikasi teknis yang menyebabkan error 403 di bawah ini, lalu klik tombol submit. Jika hasil submit memunculkan halaman 403 Forbidden, berarti <strong>WAF/ModSecurity server memblokir teks tersebut</strong>.</p>
    
    <form action="debug-ts.php" method="POST">
        <div style="margin-bottom: 15px;">
            <label for="test_text" style="display:block; font-weight:bold; margin-bottom:5px;">Teks Spesifikasi:</label>
            <textarea name="test_text" id="test_text" rows="10"><?php echo htmlspecialchars($defaultText); ?></textarea>
        </div>
        <button type="submit">Kirim Data POST</button>
    </form>
</div>

<div class="card">
    <h2>Cara Membaca Hasil:</h2>
    <ol>
        <li><strong>Jika pengiriman teks di atas menghasilkan 403 Forbidden dari Apache:</strong>
            <ul>
                <li>Berarti WAF/ModSecurity di hosting Anda mendeteksi teks spesifikasi tersebut sebagai ancaman (biasanya karena ada kombinasi kata hubung <code>or</code>, tanda kurung <code>()</code>, atau slash <code>/</code> yang menyerupai SQL injection).</li>
                <li><strong>Solusi:</strong> Anda perlu menghubungi administrator hosting/server untuk mematikan ModSecurity atau mengecualikan aturan (rule bypass) untuk domain/url bersangkutan, atau mengubah susunan teks spesifikasi agar tidak memicu deteksi WAF.</li>
            </ul>
        </li>
        <li><strong>Jika pengiriman berhasil dan memunculkan kotak hijau di atas:</strong>
            <ul>
                <li>Berarti server tidak memblokir konten ini. Masalahnya berada di level aplikasi Laravel (misal routing, CSRF token mismatch yang ter-override, middleware, dll).</li>
            </ul>
        </li>
    </ol>
</div>

</body>
</html>
<?php
// End of file
