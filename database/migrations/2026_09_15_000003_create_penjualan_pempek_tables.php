<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePenjualanPempekTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('penjualan_header', function (Blueprint $table) {
            $table->string('no_faktur', 30)->primary();
            $table->unsignedBigInteger('user_id');
            $table->dateTime('tanggal_jual');
            $table->decimal('total_bayar', 12, 2);
            $table->decimal('bayar', 12, 2)->default(0);
            $table->decimal('kembalian', 12, 2)->default(0);
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('CASCADE')
                ->onUpdate('CASCADE');
        });

        Schema::create('penjualan_detail', function (Blueprint $table) {
            $table->bigIncrements('id_detail');
            $table->string('no_faktur', 30);
            $table->string('kode_pempek', 20);
            $table->decimal('harga', 12, 2);
            $table->integer('jumlah_jual');
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();

            $table->foreign('no_faktur')
                ->references('no_faktur')->on('penjualan_header')
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
        Schema::dropIfExists('penjualan_detail');
        Schema::dropIfExists('penjualan_header');
    }
}
