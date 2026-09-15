<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProduksiPempekTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('produksi_header', function (Blueprint $table) {
            $table->string('no_faktur', 30)->primary();
            $table->unsignedBigInteger('user_id');
            $table->dateTime('tanggal');
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('CASCADE')
                ->onUpdate('CASCADE');
        });

        Schema::create('produksi_detail', function (Blueprint $table) {
            $table->bigIncrements('id_detail');
            $table->string('no_faktur', 30);
            $table->string('kode_pempek', 20);
            $table->integer('jumlah_produksi');
            $table->timestamps();

            $table->foreign('no_faktur')
                ->references('no_faktur')->on('produksi_header')
                ->onDelete('CASCADE')
                ->onUpdate('CASCADE');

            $table->foreign('kode_pempek')
                ->references('kode_pempek')->on('master_pempek')
                ->onDelete('RESTRICT')
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
        Schema::dropIfExists('produksi_detail');
        Schema::dropIfExists('produksi_header');
    }
}
