<?php

namespace App\Models;

use App\CentralLogics\Helpers;
use Carbon\Carbon;
use App\Scopes\ZoneScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\ReportFilter;

class ProductItem extends Model
{
    use HasFactory;
    protected $table = 'product_item';
    protected $guarded = ['id'];

    protected $casts = [
      
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'qty' => 'integer',
        'store_id' => 'integer',
        'price' => 'float',
        'item_id' => 'integer',
    ];

    // protected $appends = ['module_type','order_attachment_full_url','order_proof_full_url'];



    public function store()
    {
        return $this->belongsTo(Store::class,'store_id');
    }
    
    public function item()
    {
        return $this->belongsTo(Item::class,'item_id');
    }
}
