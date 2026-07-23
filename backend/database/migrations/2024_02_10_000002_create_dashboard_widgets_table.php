<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('dashboard_layout_id')->constrained('dashboard_layouts')->cascadeOnDelete();

            // One of the catalogued widget types (see WidgetCatalogue) — e.g. "kpi_visitors",
            // "kpi_conversions", "top_landing_pages". Kept as a plain string (not an enum) so
            // new widget types don't require a migration.
            $table->string('widget_type');
            $table->json('config')->nullable(); // widget-specific options: metric, date range, chart type, etc.

            // Grid position/size — matches react-grid-layout's { x, y, w, h } on the frontend.
            $table->unsignedSmallInteger('pos_x')->default(0);
            $table->unsignedSmallInteger('pos_y')->default(0);
            $table->unsignedSmallInteger('width')->default(4);
            $table->unsignedSmallInteger('height')->default(2);

            $table->boolean('is_hidden')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('dashboard_layout_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_widgets');
    }
};
