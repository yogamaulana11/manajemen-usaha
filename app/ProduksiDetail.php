<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProduksiDetail extends Model
{
    /**
     * @var string
     */
    protected $table = 'produksi_detail';

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
        'jumlah_produksi',
    ];

    public function header()
    {
        return $this->belongsTo(ProduksiHeader::class, 'no_faktur', 'no_faktur');
    }

    public function pempek()
    {
        return $this->belongsTo(MasterPempek::class, 'kode_pempek', 'kode_pempek');
    }
}
