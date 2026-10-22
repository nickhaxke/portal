<?php

namespace Database\Seeders;

use App\Models\HeroSlide;
use App\Models\Insight;
use Illuminate\Database\Seeder;

class HeroSlideSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Hero Slides
        $slides = [
            [
                'title' => 'Strategic Foresight & Executive Governance',
                'subtitle' => 'Curated intelligence and peer-reviewed monographs for institutional decision makers.',
                'image_path' => 'hero/slide-1.jpg',
                'cta_text' => 'Explore Catalogue',
                'cta_url' => '/explore',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'Multilateral Leadership & Global Policy',
                'subtitle' => 'Frameworks navigating geopolitical volatility and economic transitions.',
                'image_path' => 'hero/slide-2.jpg',
                'cta_text' => 'Executive Insights',
                'cta_url' => '/insights',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'title' => 'Technological Horizons & Ethical Stewardship',
                'subtitle' => 'Empirical analyses bridging artificial intelligence, governance and long-term societal resilience.',
                'image_path' => 'hero/slide-3.jpg',
                'cta_text' => 'Explore Disciplines',
                'cta_url' => '/categories',
                'sort_order' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($slides as $data) {
            HeroSlide::firstOrCreate(
                ['title' => $data['title']],
                $data
            );
        }

        // 2. Enhance existing Insights with real editorial imagery & metadata
        $editorialData = [
            1 => [
                'featured_image' => 'insights/insight-1.jpg',
                'image_alt' => 'High-level multilateral executive boardroom discussion',
                'excerpt' => 'In volatile multilateral boardrooms, cognitive agility and systemic frameworks determine institutional survival beyond conventional quarterly metrics.',
                'author_name' => 'Dr. Evelyn Vance',
                'is_featured' => true,
                'sort_order' => 1,
            ],
            2 => [
                'featured_image' => 'insights/insight-2.jpg',
                'image_alt' => 'Executive intelligence research library and knowledge archives',
                'excerpt' => 'How visionary leaders build organizational repositories that withstand generational shifts and technological disruption.',
                'author_name' => 'Marcus Sterling, Ph.D.',
                'is_featured' => true,
                'sort_order' => 2,
            ],
            3 => [
                'featured_image' => 'insights/insight-3.jpg',
                'image_alt' => 'Strategic research atrium with interactive global intelligence data',
                'excerpt' => 'Structuring executive oversight to reconcile rapid technological deployment with fiduciary governance and ethical imperatives.',
                'author_name' => 'Executive Research Council',
                'is_featured' => true,
                'sort_order' => 3,
            ],
        ];

        $insights = Insight::orderBy('id')->get();
        $i = 1;
        foreach ($insights as $insight) {
            if (isset($editorialData[$i])) {
                $insight->update($editorialData[$i]);
            } else {
                $insight->update([
                    'author_name' => 'Executive Editorial Board',
                    'sort_order' => $i,
                    'is_featured' => ($i <= 3),
                ]);
            }
            $i++;
        }
    }
}
