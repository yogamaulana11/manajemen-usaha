<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProduksiHeader extends Model
{
    /**
     * @var string
     */
    protected $table = 'produksi_header';

    /**
     * @var string
     */
    protected $primaryKey = 'no_faktur';

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
        'no_faktur',
        'user_id',
        'tanggal',
        'keterangan',
    ];

    /**
     * @var array
     */
    protected $dates = ['tanggal'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function details()
    {
        return $this->hasMany(ProduksiDetail::class, 'no_faktur', 'no_faktur');
    }
}
