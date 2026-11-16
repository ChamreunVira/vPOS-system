<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Make stock_movements.order_item_id, .reference, and .note nullable.
     * Uses raw SQLite table rebuild because SQLite does not support ALTER COLUMN.
     */
    public function up(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');
        DB::statement('DROP TABLE IF EXISTS "__temp__stock_movements"');

        DB::statement('
            CREATE TABLE "__temp__stock_movements" (
                "id"                INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                "product_id"        INTEGER NOT NULL,
                "user_id"           INTEGER NOT NULL,
                "purchase_order_id" INTEGER NULL,
                "order_item_id"     INTEGER NULL,
                "type"              VARCHAR NOT NULL,
                "quantity"          NUMERIC NOT NULL,
                "balance"           NUMERIC NOT NULL,
                "reference"         VARCHAR NULL,
                "note"              TEXT NULL,
                "created_at"        DATETIME NULL,
                "updated_at"        DATETIME NULL,
                FOREIGN KEY ("product_id")        REFERENCES "products"("id")        ON DELETE CASCADE,
                FOREIGN KEY ("user_id")           REFERENCES "users"("id")           ON DELETE CASCADE,
                FOREIGN KEY ("purchase_order_id") REFERENCES "purchase_orders"("id") ON DELETE CASCADE,
                FOREIGN KEY ("order_item_id")     REFERENCES "order_items"("id")     ON DELETE CASCADE
            )
        ');

        DB::statement('INSERT INTO "__temp__stock_movements" SELECT * FROM "stock_movements"');
        DB::statement('DROP TABLE "stock_movements"');
        DB::statement('ALTER TABLE "__temp__stock_movements" RENAME TO "stock_movements"');

        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        // The original stock_movements schema had NOT NULL on order_item_id, reference, and note,
        // but existing data may already have NULLs from the up() migration. Rolling back to
        // NOT NULL would fail data integrity, so down() is intentionally a no-op.
    }
};
