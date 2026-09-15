<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PenjualanDetail extends Model
{
    /**
     * @var string
     */
    protected $table = 'penjualan_detail';

    /**
     * @var string
     */
    protected $primaryKey = 'id_detail';

    /**
     * @var array
     */
    protected $fillable = [
        'no_faktur',
        'kode_pempek',
        'harga',
        'jumlah_jual',
        'subtotal',
    ];

    public function header()
    {
        return $this->belongsTo(PenjualanHeader::class, 'no_faktur', 'no_faktur');
    }

    public function pempek()
    {
        return $this->belongsTo(MasterPempek::class, 'kode_pempek', 'kode_pempek');
    }
}
