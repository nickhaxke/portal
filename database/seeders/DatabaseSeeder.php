<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Book;
use App\Models\BookFile;
use App\Models\Category;
use App\Models\Insight;
use App\Models\User;
use App\Services\SimplePdfBuilder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Executive Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@executive.portal'],
            [
                'name' => 'Executive Director',
                'password' => Hash::make('Password123!'),
                'role' => 'admin',
                'position_title' => 'Chief Executive & Board Administrator',
            ]
        );

        // 2. Categories
        $categoriesData = [
            ['name' => 'Business & Economics', 'icon' => 'chart', 'order' => 1, 'desc' => 'Market dynamics, economic frameworks and sustainable enterprise models.'],
            ['name' => 'Leadership & Management', 'icon' => 'strategy', 'order' => 2, 'desc' => 'Executive governance, decision architecture and organizational culture.'],
            ['name' => 'Technology & Innovation', 'icon' => 'cog', 'order' => 3, 'desc' => 'Digital transformation, artificial intelligence and emerging paradigms.'],
            ['name' => 'Politics & Governance', 'icon' => 'scale', 'order' => 4, 'desc' => 'Policy evaluation, geopolitical strategy and public sector leadership.'],
            ['name' => 'Personal Development', 'icon' => 'sparkles', 'order' => 5, 'desc' => 'Habit mastery, cognitive resilience and continuous lifelong learning.'],
            ['name' => 'Global Affairs', 'icon' => 'globe', 'order' => 6, 'desc' => 'International systems, trade networks and multilateral developments.'],
        ];

        $categories = [];
        foreach ($categoriesData as $c) {
            $categories[$c['name']] = Category::firstOrCreate(
                ['slug' => Str::slug($c['name'])],
                [
                    'name' => $c['name'],
                    'icon' => $c['icon'],
                    'sort_order' => $c['order'],
                    'description' => $c['desc'],
                ]
            );
        }

        // 3. Books data from prototype
        $booksData = [
            [
                'title' => 'Atomic Habits',
                'author' => 'James Clear',
                'cat' => 'Personal Development',
                'bg' => '#e6d9c3',
                'fg' => '#9c6325',
                'desc' => 'An exploration of small daily improvements and the power of consistent habits.',
                'year' => '2018',
                'pages' => 320,
                'featured' => true,
            ],
            [
                'title' => 'Thinking, Fast and Slow',
                'author' => 'Daniel Kahneman',
                'cat' => 'Leadership & Management',
                'bg' => '#e7e3d9',
                'fg' => '#243449',
                'desc' => 'Explore two modes of thinking and the biases that shape decisions in leadership.',
                'year' => '2011',
                'pages' => 499,
                'featured' => true,
            ],
            [
                'title' => 'The 5 AM Club',
                'author' => 'Robin Sharma',
                'cat' => 'Personal Development',
                'bg' => '#e36c34',
                'fg' => '#ffffff',
                'desc' => 'A practical approach to routines, morning productivity and personal growth.',
                'year' => '2018',
                'pages' => 336,
                'featured' => true,
            ],
            [
                'title' => 'Dare to Lead',
                'author' => 'Brené Brown',
                'cat' => 'Leadership & Management',
                'bg' => '#e7eee8',
                'fg' => '#17565d',
                'desc' => 'A guide to courage, vulnerability and effective transformative leadership.',
                'year' => '2018',
                'pages' => 320,
                'featured' => true,
            ],
            [
                'title' => 'The Lean Startup',
                'author' => 'Eric Ries',
                'cat' => 'Business & Economics',
                'bg' => '#12669b',
                'fg' => '#ffffff',
                'desc' => 'How to build agile products, test assumptions and learn rapidly from stakeholders.',
                'year' => '2011',
                'pages' => 336,
                'featured' => true,
            ],
            [
                'title' => 'Principles',
                'author' => 'Ray Dalio',
                'cat' => 'Business & Economics',
                'bg' => '#1a1a1c',
                'fg' => '#ffffff',
                'desc' => 'Principles for work, life and radical transparency in thoughtful decision-making.',
                'year' => '2017',
                'pages' => 592,
                'featured' => false,
            ],
            [
                'title' => 'The Innovators',
                'author' => 'Walter Isaacson',
                'cat' => 'Technology & Innovation',
                'bg' => '#2d5671',
                'fg' => '#ffffff',
                'desc' => 'Stories about the people and collaborations behind the digital and computer revolution.',
                'year' => '2014',
                'pages' => 544,
                'featured' => false,
            ],
            [
                'title' => 'The Art of Strategy',
                'author' => 'Avinash Dixit & Barry Nalebuff',
                'cat' => 'Politics & Governance',
                'bg' => '#755d4b',
                'fg' => '#ffffff',
                'desc' => 'An introduction to game theory, strategic thinking and high-impact decision-making.',
                'year' => '2008',
                'pages' => 512,
                'featured' => false,
            ],
        ];

        // Ensure storage directory for private books exists
        Storage::disk('local')->makeDirectory('private/books');

        foreach ($booksData as $b) {
            $author = Author::firstOrCreate(
                ['slug' => Str::slug($b['author'])],
                ['name' => $b['author']]
            );

            $category = $categories[$b['cat']] ?? null;
            $slug = Str::slug($b['title']);

            $book = Book::firstOrCreate(
                ['slug' => $slug],
                [
                    'title' => $b['title'],
                    'category_id' => $category?->id,
                    'author_id' => $author->id,
                    'author_name' => $b['author'],
                    'description' => $b['desc'],
                    'cover_bg' => $b['bg'],
                    'cover_fg' => $b['fg'],
                    'is_featured' => $b['featured'],
                    'pages_count' => $b['pages'],
                    'published_year' => $b['year'],
                ]
            );

            // Generate sample legal PDF reading file
            $pdfContent = SimplePdfBuilder::createSamplePdf(
                $b['title'],
                $b['author'],
                $b['cat'],
                $b['desc']
            );

            $relativePath = 'private/books/'.$slug.'.pdf';
            Storage::disk('local')->put($relativePath, $pdfContent);

            BookFile::updateOrCreate(
                ['book_id' => $book->id, 'file_type' => 'pdf'],
                [
                    'file_path' => $relativePath,
                    'file_name' => $slug.'-executive-edition.pdf',
                    'file_size' => strlen($pdfContent),
                    'disk' => 'local',
                    'page_count' => 3,
                    'is_active' => true,
                ]
            );
        }

        // 4. Insights from prototype
        $insightsData = [
            [
                'title' => 'The Future of Leadership in a Changing World',
                'category_name' => 'Leadership',
                'icon' => 'strategy',
                'read_time' => '5 min read',
                'content' => "Leadership begins with curiosity. Good decisions require time to reflect, a willingness to listen and the discipline to learn from evidence.\n\nIn an era characterized by rapid technological advancement and geopolitical shifts, executive competence is defined by cognitive adaptability. Leaders who foster intellectual safety and deliberate reflection consistently outperform those relying on legacy command-and-control structures.",
            ],
            [
                'title' => 'Building a Growth Mindset',
                'category_name' => 'Personal Growth',
                'icon' => 'sparkles',
                'read_time' => '5 min read',
                'content' => "Progress begins with a willingness to learn. The most resilient executives treat operational setbacks not as personal verdicts, but as diagnostic indicators.\n\nBy viewing intellectual and professional challenges through the lens of systematic experimentation, teams unlock superior innovation and long-term institutional agility.",
            ],
            [
                'title' => 'Why Innovation Matters for Africa’s Development',
                'category_name' => 'Innovation',
                'icon' => 'insights',
                'read_time' => '5 min read',
                'content' => "Sustainable prosperity depends on leapfrogging legacy infrastructure with high-leverage digital solutions.\n\nFrom fintech and mobile payment ecosystems to localized logistics and green energy generation, African enterprise is actively pioneering models of frugal innovation that hold profound lessons for executives globally.",
            ],
        ];

        foreach ($insightsData as $in) {
            Insight::firstOrCreate(
                ['slug' => Str::slug($in['title'])],
                [
                    'title' => $in['title'],
                    'category_name' => $in['category_name'],
                    'icon' => $in['icon'],
                    'read_time' => $in['read_time'],
                    'content' => $in['content'],
                    'published_at' => now(),
                ]
            );
        }
    }
}
