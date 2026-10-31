<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorComparisonVendorPrice extends Model
{
    protected $fillable = [
        'vendor_comparison_vendor_id',
        'vendor_comparison_item_id',
        'harga',
    ];

    protected $casts = [
        'harga' => 'decimal:2',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(VendorComparisonVendor::class, 'vendor_comparison_vendor_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(VendorComparisonItem::class, 'vendor_comparison_item_id');
    }
}
