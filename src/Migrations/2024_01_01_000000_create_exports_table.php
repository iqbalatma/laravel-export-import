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
        Schema::create("exports", function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->string("type");
            $table->string("name");
            $table->string("path")->nullable();
            $table->string("filename")->nullable();
            $table->string("full_path")->nullable();
            $table->string("permission_name")->nullable();
            $table->foreignUuid("exported_by_id")
                ->nullable()
                ->references("id")
                ->on("users")
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->boolean("is_completed")->default(false);
            $table->timestamp("available_until")->nullable();
            $table->timestamp("exported_at")->nullable();
            $table->timestamps(6);

            $table->index("type");
            $table->index(["is_completed", "type", "exported_at"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("exports");
    }
};
