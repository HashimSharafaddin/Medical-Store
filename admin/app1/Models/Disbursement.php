<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Disbursement extends Model
{
    use HasFactory;
    protected $casts = [
        'disbursement_id' => 'integer',
        'delivery_man_id' => 'integer',
        'disbursement_amount' => 'float',
        'debit' => 'float',
        'admin_bonus'=>'float',
        'updated_at'=>'string',
        'payment_method'=>'string',
        'created_at'=>'string'
    ];
    public function details()
    {
        return $this->hasMany(DisbursementDetails::class);
    }
}
