<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PenjualanHeader extends Model
{
    /**
     * @var string
     */
    protected $table = 'penjualan_header';

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
        'tanggal_jual',
        'total_bayar',
        'bayar',
        'kembalian',
        'catatan',
    ];

    /**
     * @var array
     */
    protected $dates = ['tanggal_jual'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function details()
    {
        return $this->hasMany(PenjualanDetail::class, 'no_faktur', 'no_faktur');
    }
}
