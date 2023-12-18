<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceLineItem extends Model
{
    use HasFactory;

    protected $fillable = [
      'invoice_id',
      'description',
      'quantity',
      'amount',
    ];

    public function invoice(){
        return $this->belongsTo(Invoice::class);
    }
}
