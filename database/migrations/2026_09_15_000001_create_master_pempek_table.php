<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMasterPempekTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('master_pempek', function (Blueprint $table) {
            $table->string('kode_pempek', 20)->primary();
            $table->unsignedBigInteger('user_id');
            $table->string('nama_pempek', 100);
            $table->string('jenis_ikan', 50);
            $table->decimal('harga', 12, 2);
            $table->string('foto', 255)->nullable();
            $table->integer('stok')->default(0);
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('CASCADE')
                ->onUpdate('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('master_pempek');
    }
}
