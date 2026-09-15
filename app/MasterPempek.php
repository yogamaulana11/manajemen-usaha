<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MasterPempek extends Model
{
    /**
     * @var string
     */
    protected $table = 'master_pempek';

    /**
     * @var string
     */
    protected $primaryKey = 'kode_pempek';

    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var string
     */
    protected $keyType = 'string';

    /**
     * @var array
     */
    protected $fillable = [
        'kode_pempek',
        'user_id',
        'nama_pempek',
        'jenis_ikan',
        'harga',
        'foto',
        'stok',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function produksiDetails()
    {
        return $this->hasMany(ProduksiDetail::class, 'kode_pempek', 'kode_pempek');
    }

    public function penjualanDetails()
    {
        return $this->hasMany(PenjualanDetail::class, 'kode_pempek', 'kode_pempek');
    }
}
