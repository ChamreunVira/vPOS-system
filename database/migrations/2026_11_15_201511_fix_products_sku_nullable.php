<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Make products.sku nullable.
     * Uses raw SQLite table rebuild because SQLite does not support ALTER COLUMN.
     */
    public function up(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');
        DB::statement('DROP TABLE IF EXISTS "__temp__products"');

        DB::statement('
            CREATE TABLE "__temp__products" (
                "id"          INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                "category_id" INTEGER NOT NULL,
                "unit_id"     INTEGER NOT NULL,
                "name"        VARCHAR(255) NOT NULL,
                "sku"         VARCHAR(255) NULL,
                "barcode"     VARCHAR(255) NULL,
                "cost"        NUMERIC NOT NULL,
                "price"       NUMERIC NOT NULL,
                "stock"       NUMERIC NOT NULL,
                "min_stock"   NUMERIC NOT NULL,
                "image"       VARCHAR(255) NULL,
                "status"      VARCHAR(255) NOT NULL DEFAULT \'active\',
                "created_at"  DATETIME NULL,
                "updated_at"  DATETIME NULL,
                FOREIGN KEY ("category_id") REFERENCES "categories"("id"),
                FOREIGN KEY ("unit_id")     REFERENCES "units"("id")
            )
        ');

        DB::statement('INSERT INTO "__temp__products" SELECT * FROM "products"');
        DB::statement('DROP TABLE "products"');
        DB::statement('ALTER TABLE "__temp__products" RENAME TO "products"');

        // Restore unique index on sku
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS "products_sku_unique" ON "products" ("sku")');

        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        // sku was always intended to be nullable (original migration says nullable())
        // Nothing to reverse.
    }
};
