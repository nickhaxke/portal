<?php

namespace Tests\Fixtures;

use PDO;

class GenerateCalibreFixture
{
    public static function create(string $targetDir): void
    {
        if (! is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $dbPath = $targetDir.DIRECTORY_SEPARATOR.'metadata.db';
        if (file_exists($dbPath)) {
            unlink($dbPath);
        }

        $pdo = new PDO("sqlite:{$dbPath}");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create standard Calibre tables
        $pdo->exec('
            CREATE TABLE books (
                id INTEGER PRIMARY KEY,
                title TEXT,
                sort TEXT,
                timestamp TEXT,
                pubdate TEXT,
                series_index REAL,
                path TEXT,
                has_cover BOOL DEFAULT 0
            );

            CREATE TABLE authors (
                id INTEGER PRIMARY KEY,
                name TEXT,
                sort TEXT,
                link TEXT
            );

            CREATE TABLE books_authors_link (
                id INTEGER PRIMARY KEY,
                book INTEGER,
                author INTEGER
            );

            CREATE TABLE data (
                id INTEGER PRIMARY KEY,
                book INTEGER,
                format TEXT,
                uncompressed_size INTEGER,
                name TEXT
            );

            CREATE TABLE comments (
                id INTEGER PRIMARY KEY,
                book INTEGER,
                text TEXT
            );

            CREATE TABLE identifiers (
                id INTEGER PRIMARY KEY,
                book INTEGER,
                type TEXT,
                val TEXT
            );

            CREATE TABLE publishers (
                id INTEGER PRIMARY KEY,
                name TEXT
            );

            CREATE TABLE books_publishers_link (
                id INTEGER PRIMARY KEY,
                book INTEGER,
                publisher INTEGER
            );

            CREATE TABLE tags (
                id INTEGER PRIMARY KEY,
                name TEXT
            );

            CREATE TABLE books_tags_link (
                id INTEGER PRIMARY KEY,
                book INTEGER,
                tag INTEGER
            );

            CREATE TABLE series (
                id INTEGER PRIMARY KEY,
                name TEXT,
                sort TEXT
            );

            CREATE TABLE books_series_link (
                id INTEGER PRIMARY KEY,
                book INTEGER,
                series INTEGER
            );

            CREATE TABLE languages (
                id INTEGER PRIMARY KEY,
                lang_code TEXT
            );

            CREATE TABLE books_languages_link (
                id INTEGER PRIMARY KEY,
                book INTEGER,
                lang_code INTEGER
            );
        ');

        // 1. Insert Authors
        $pdo->exec("INSERT INTO authors (id, name, sort) VALUES (1, 'Peter F. Drucker', 'Drucker, Peter F.')");
        $pdo->exec("INSERT INTO authors (id, name, sort) VALUES (2, 'Ray Dalio', 'Dalio, Ray')");
        $pdo->exec("INSERT INTO authors (id, name, sort) VALUES (3, 'James Clear', 'Clear, James')");

        // 2. Insert Publisher
        $pdo->exec("INSERT INTO publishers (id, name) VALUES (1, 'Harper Business')");

        // 3. Insert Tags, Series and Languages
        $pdo->exec("INSERT INTO tags (id, name) VALUES (1, 'Management')");
        $pdo->exec("INSERT INTO tags (id, name) VALUES (2, 'Executive Strategy')");
        $pdo->exec("INSERT INTO series (id, name, sort) VALUES (1, 'Executive Wisdom Series', 'Executive Wisdom Series')");
        $pdo->exec("INSERT INTO languages (id, lang_code) VALUES (1, 'en')");

        // 4. Book 1: The Effective Executive (PDF, EPUB, Cover, Tags, Series)
        $book1Dir = $targetDir.DIRECTORY_SEPARATOR.'Peter F. Drucker'.DIRECTORY_SEPARATOR.'The Effective Executive (1)';
        if (! is_dir($book1Dir)) {
            mkdir($book1Dir, 0755, true);
        }
        $pdo->exec("INSERT INTO books (id, title, sort, pubdate, series_index, path, has_cover) VALUES (1, 'The Effective Executive', 'Effective Executive, The', '2006-01-01', 1.0, 'Peter F. Drucker/The Effective Executive (1)', 1)");
        $pdo->exec('INSERT INTO books_authors_link (book, author) VALUES (1, 1)');
        $pdo->exec('INSERT INTO books_publishers_link (book, publisher) VALUES (1, 1)');
        $pdo->exec('INSERT INTO books_tags_link (book, tag) VALUES (1, 1)');
        $pdo->exec('INSERT INTO books_tags_link (book, tag) VALUES (1, 2)');
        $pdo->exec('INSERT INTO books_series_link (book, series) VALUES (1, 1)');
        $pdo->exec('INSERT INTO books_languages_link (book, lang_code) VALUES (1, 1)');
        $pdo->exec("INSERT INTO identifiers (book, type, val) VALUES (1, 'isbn', '978-0060833459')");
        $pdo->exec("INSERT INTO identifiers (book, type, val) VALUES (1, 'doi', '10.1000/drucker-exec')");
        $pdo->exec("INSERT INTO comments (book, text) VALUES (1, '<p>The definitive guide to getting the right things done in executive leadership.</p>')");
        $pdo->exec("INSERT INTO data (book, format, uncompressed_size, name) VALUES (1, 'PDF', 12345, 'The Effective Executive - Peter F. Drucker')");
        $pdo->exec("INSERT INTO data (book, format, uncompressed_size, name) VALUES (1, 'EPUB', 8900, 'The Effective Executive - Peter F. Drucker')");

        file_put_contents($book1Dir.DIRECTORY_SEPARATOR.'The Effective Executive - Peter F. Drucker.pdf', "%PDF-1.4\nSynthetic Calibre PDF Content for Drucker\n%%EOF");
        file_put_contents($book1Dir.DIRECTORY_SEPARATOR.'The Effective Executive - Peter F. Drucker.epub', 'Synthetic EPUB Content');
        file_put_contents($book1Dir.DIRECTORY_SEPARATOR.'cover.jpg', 'Fake JPG binary content');

        // 4. Book 2: Principles of Leadership (Multiple Authors: Dalio & Clear)
        $book2Dir = $targetDir.DIRECTORY_SEPARATOR.'Ray Dalio'.DIRECTORY_SEPARATOR.'Principles of Leadership (2)';
        if (! is_dir($book2Dir)) {
            mkdir($book2Dir, 0755, true);
        }
        $pdo->exec("INSERT INTO books (id, title, sort, pubdate, path, has_cover) VALUES (2, 'Principles of Leadership', 'Principles of Leadership', '2017-09-19', 'Ray Dalio/Principles of Leadership (2)', 0)");
        $pdo->exec('INSERT INTO books_authors_link (book, author) VALUES (2, 2)');
        $pdo->exec('INSERT INTO books_authors_link (book, author) VALUES (2, 3)');
        $pdo->exec("INSERT INTO data (book, format, uncompressed_size, name) VALUES (2, 'PDF', 54321, 'Principles of Leadership - Ray Dalio')");

        file_put_contents($book2Dir.DIRECTORY_SEPARATOR.'Principles of Leadership - Ray Dalio.pdf', "%PDF-1.4\nSynthetic Calibre PDF Content for Dalio\n%%EOF");

        // 5. Book 3: Monograph on AI Governance (Missing physical file on disk to test resilience)
        $pdo->exec("INSERT INTO books (id, title, sort, pubdate, path, has_cover) VALUES (3, 'Monograph on AI Governance', 'Monograph on AI Governance', '2024-05-10', 'Unknown/AI Governance (3)', 0)");
        $pdo->exec('INSERT INTO books_authors_link (book, author) VALUES (3, 1)');
        $pdo->exec("INSERT INTO data (book, format, uncompressed_size, name) VALUES (3, 'PDF', 1000, 'AI Governance - Peter F. Drucker')");
        // We purposefully do NOT create the file for Book 3 to test the fallback sample PDF generator!

        // Close PDO handle to release Windows file locks
        $pdo = null;
    }
}
