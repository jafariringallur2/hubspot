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
        Schema::create('hubspot_contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hubspot_account_id'); // Link contact to a specific HubSpot account
            $table->string('hubspot_contact_id')->unique(); // Unique contact ID in HubSpot
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->json('properties')->nullable(); // Store additional contact properties
            $table->timestamps();
            
            // Foreign key to HubSpot accounts
            $table->foreign('hubspot_account_id')->references('id')->on('hubspot_accounts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hubspot_contacts');
    }
};
