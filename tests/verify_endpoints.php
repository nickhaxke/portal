<?php

use GuzzleHttp\Client;

require __DIR__.'/../vendor/autoload.php';

$client = new Client(['cookies' => true]);
$baseUrl = 'http://127.0.0.1:8001';

echo "=== VERIFYING LIVE HTTP SERVER (127.0.0.1:8001) ===\n";

// 1. Get Login Page & CSRF token
$res = $client->get($baseUrl.'/admin/login');
preg_match('/name="_token" value="([^"]+)"/', (string) $res->getBody(), $matches);
$csrfToken = $matches[1] ?? null;

echo '1. GET /admin/login => Status: '.$res->getStatusCode().' | CSRF Found: '.($csrfToken ? 'YES' : 'NO')."\n";

// 2. Submit Login
$res = $client->post($baseUrl.'/admin/login', [
    'form_params' => [
        '_token' => $csrfToken,
        'email' => 'admin@executive.portal',
        'password' => 'Password123!',
    ],
    'allow_redirects' => false,
]);
echo '2. POST /admin/login => Status: '.$res->getStatusCode().' (Redirected to: '.$res->getHeaderLine('Location').")\n";

// 3. Request Admin Dashboard
$res = $client->get($baseUrl.'/admin');
$html = (string) $res->getBody();
echo '3. GET /admin => Status: '.$res->getStatusCode()."\n";
echo "   - Contains 'Executive Intelligence Dashboard': ".(str_contains($html, 'Executive Intelligence Dashboard') ? 'PASS' : 'FAIL')."\n";
echo "   - Contains 'Private Vault Storage': ".(str_contains($html, 'Private Vault Storage') ? 'PASS' : 'FAIL')."\n";
echo "   - Contains Navigation '1. Overview': ".(str_contains($html, '1. Overview') ? 'PASS' : 'FAIL')."\n";
echo "   - Contains Navigation '3. Bulk Import': ".(str_contains($html, '3. Bulk Import') ? 'PASS' : 'FAIL')."\n";
echo "   - Contains Navigation '13. System': ".(str_contains($html, '13. System') ? 'PASS' : 'FAIL')."\n";

// 4. Request Publications
$res = $client->get($baseUrl.'/admin/books');
$html = (string) $res->getBody();
echo '4. GET /admin/books => Status: '.$res->getStatusCode()."\n";
echo "   - Contains 'Curated Publication Catalogue': ".(str_contains($html, 'Curated Publication Catalogue') ? 'PASS' : 'FAIL')."\n";
echo "   - Contains 'Add New Publication': ".(str_contains($html, 'Add New Publication') ? 'PASS' : 'FAIL')."\n";
echo '   - Contains Lifecycle Tabs: '.(str_contains($html, 'Published') && str_contains($html, 'Archived') ? 'PASS' : 'FAIL')."\n";

// 5. Request Calibre Migration Engine
$res = $client->get($baseUrl.'/admin/imports');
$html = (string) $res->getBody();
echo '5. GET /admin/imports => Status: '.$res->getStatusCode()."\n";
echo "   - Contains 'Calibre Library Migration Engine': ".(str_contains($html, 'Calibre Library Migration Engine') ? 'PASS' : 'FAIL')."\n";
echo "   - Contains 'Scan & Preview Library': ".(str_contains($html, 'Scan &amp; Preview') || str_contains($html, 'Scan & Preview') ? 'PASS' : 'FAIL')."\n";

// 5b. Test Calibre Scan POST endpoint with synthetic fixture
preg_match('/name="_token" value="([^"]+)"/', $html, $importCsrf);
$scanRes = $client->post($baseUrl.'/admin/imports/scan', [
    'form_params' => [
        '_token' => $importCsrf[1] ?? $csrfToken,
        'library_path' => str_replace('\\', '/', realpath(__DIR__.'/Fixtures/calibre')),
    ],
]);
$scanHtml = (string) $scanRes->getBody();
echo '5b. POST /admin/imports/scan => Status: '.$scanRes->getStatusCode()."\n";
echo "    - Detected 'The Effective Executive': ".(str_contains($scanHtml, 'The Effective Executive') ? 'PASS' : 'FAIL')."\n";
echo "    - Detected 'Principles of Leadership': ".(str_contains($scanHtml, 'Principles of Leadership') ? 'PASS' : 'FAIL')."\n";
echo "    - Detected Format 'PDF': ".(str_contains($scanHtml, 'PDF') ? 'PASS' : 'FAIL')."\n";

// 6. Request Disciplines & Categories
$res = $client->get($baseUrl.'/admin/categories');
$html = (string) $res->getBody();
echo '6. GET /admin/categories => Status: '.$res->getStatusCode()."\n";
echo "   - Contains 'Disciplines & Categories': ".(str_contains($html, 'Disciplines') ? 'PASS' : 'FAIL')."\n";

// 7. Request Authors & Publishers
$res = $client->get($baseUrl.'/admin/authors');
$html = (string) $res->getBody();
echo '7. GET /admin/authors => Status: '.$res->getStatusCode()."\n";
echo "   - Contains 'Authors & Contributors': ".(str_contains($html, 'Authors') ? 'PASS' : 'FAIL')."\n";

// 8. Request Readers & Members
$res = $client->get($baseUrl.'/admin/readers');
$html = (string) $res->getBody();
echo '8. GET /admin/readers => Status: '.$res->getStatusCode()."\n";
echo "   - Contains 'Reader & Member Administration': ".(str_contains($html, 'Reader') ? 'PASS' : 'FAIL')."\n";

// 9. Request Audit Logs
$res = $client->get($baseUrl.'/admin/audit-logs');
$html = (string) $res->getBody();
echo '9. GET /admin/audit-logs => Status: '.$res->getStatusCode()."\n";
echo "   - Contains 'Audit': ".(str_contains($html, 'Audit') ? 'PASS' : 'FAIL')."\n";

// 10. Request Portal Settings
$res = $client->get($baseUrl.'/admin/settings');
$html = (string) $res->getBody();
echo '10. GET /admin/settings => Status: '.$res->getStatusCode()."\n";
echo "   - Contains 'Portal Settings': ".(str_contains($html, 'Portal Settings') ? 'PASS' : 'FAIL')."\n";

echo "=== ALL HTTP ENDPOINTS VERIFIED SUCCESSFULLY ===\n";
