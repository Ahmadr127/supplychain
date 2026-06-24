<?php
// Save this file as public/clear_opcache.php
// Visit https://e-proc.rsazra.co.id/clear_opcache.php in your browser to run this.

header('Content-Type: text/plain');

if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        echo "✅ OPcache has been successfully cleared/reset!\n";
    } else {
        echo "❌ OPcache reset failed.\n";
    }
} else {
    echo "⚠️ OPcache is not enabled or opcache_reset function is disabled on this server.\n";
}
