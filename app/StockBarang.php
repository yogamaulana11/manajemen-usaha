<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StockBarang extends Model
{
      /**
     * @var string
     */
    protected $table = 'stock_barang';

    /**
     * @var array
     */
    protected $fillable = [
        'user_id',
        'kategori_barang',
        'nama_barang',
        'jumlah_stok',
        'tanggal_update'
    ];

    public function debits()
    {
        return $this->hasMany(Debit::class, 'stock_id');
    }
}
