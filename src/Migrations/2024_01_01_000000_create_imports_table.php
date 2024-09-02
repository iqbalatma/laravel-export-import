<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("imports", function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->string("type");
            $table->string("name");
            $table->integer("success_row")->nullable();
            $table->integer("failed_row")->nullable();
            $table->integer("total_row")->nullable();
            $table->string("path")->nullable();
            $table->string("filename")->nullable();
            $table->string("original_filename")->nullable();
            $table->string("full_path")->nullable();
            $table->string("failed_path")->nullable();
            $table->string("failed_filename")->nullable();
            $table->string("failed_full_path")->nullable();
            $table->string("permission_name")->nullable();
            $table->foreignUuid("imported_by_id")
                ->nullable()
                ->references("id")
                ->on("users")
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->boolean("is_completed")->default(false);
            $table->timestamp("imported_at")->nullable();
            $table->timestamps(6);

            $table->index("type");
            $table->index(["is_completed", "type", "imported_at"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("imports");
    }
};
