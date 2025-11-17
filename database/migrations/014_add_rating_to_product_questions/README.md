# Migration 014 - Add rating to product_questions

This migration adds an optional `rating` column to the `product_questions` table so a question can optionally include a rating (1..5).

Run this script from your project root (Windows PowerShell example):

```powershell
php database/migrations/014_add_rating_to_product_questions/run_migration.php
```

This simply executes the SQL in `001_add_rating_to_product_questions.sql` which runs:

ALTER TABLE product_questions
ADD COLUMN rating TINYINT UNSIGNED NULL DEFAULT NULL;
