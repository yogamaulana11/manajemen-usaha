<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Debit extends Model
{
    /**
     * @var string
     */
    protected $table = 'debit';

    /**
     * @var array
     */
    protected $fillable = [
        'user_id',
        'category_id',
        'stock_id',
        'qty',
        'nominal',
        'description',
        'debit_date'
    ];

    public function stock()
    {
        return $this->belongsTo(StockBarang::class, 'stock_id');
    }

    public function category()
    {
        return $this->belongsTo(CategoriesDebit::class, 'category_id');
    }
}
