<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$categoryUpdates = [
    'business-economics' => 'chart',
    'leadership-management' => 'strategy',
    'technology-innovation' => 'cog',
    'politics-governance' => 'scale',
    'personal-development' => 'sparkles',
    'global-affairs' => 'globe',
    'general-executive' => 'strategy',
];

foreach ($categoryUpdates as $slug => $icon) {
    DB::table('categories')->where('slug', $slug)->update(['icon' => $icon]);
}

// Any other categories with unicode icons
$allCats = DB::table('categories')->get();
$map = [
    '♟' => 'strategy',
    '⚖' => 'scale',
    '✧' => 'sparkles',
    '◎' => 'globe',
    '▥' => 'chart',
    '⌘' => 'cog',
    '🏛' => 'collections',
    '🌐' => 'globe',
    '📊' => 'chart',
    '💡' => 'insights',
    '🛡' => 'shield',
];

foreach ($allCats as $c) {
    if (isset($map[$c->icon])) {
        DB::table('categories')->where('id', $c->id)->update(['icon' => $map[$c->icon]]);
    }
}

// Insights
$allInsights = DB::table('insights')->get();
foreach ($allInsights as $in) {
    if (isset($map[$in->icon])) {
        DB::table('insights')->where('id', $in->id)->update(['icon' => $map[$in->icon]]);
    } elseif ($in->icon === '🌱') {
        DB::table('insights')->where('id', $in->id)->update(['icon' => 'sparkles']);
    } elseif ($in->icon === '✦') {
        DB::table('insights')->where('id', $in->id)->update(['icon' => 'insights']);
    }
}

echo "Database icons normalized successfully.\n";
