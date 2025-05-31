<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdComments extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'ad_id',
        'user_id',
        'titel',
        'start_date',
        'updated_at',
        'created_at',
    ];
    protected $casts = [
        'ad_id' => 'integer',
        'user_id' => 'integer',
        'titel' => 'string',
         'created_at'=>'string',
         'updated_at'=>'string'

    ];

    // public function ads()
    // {
    //     return $this->belongsTo(Advertisement::class,'ad_id');
    // }

    // public function ads()
    // {
    //     return $this->belongsTo(Store::class, 'store_id');
    // }
    
}
