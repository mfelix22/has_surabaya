<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorComparisonItem extends Model
{
    protected $fillable = [
        'vendor_comparison_id',
        'pr_detail_id',
        'item_name',
        'qty',
        'uom',
        'sort_order',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
    ];

    public function comparison(): BelongsTo
    {
        return $this->belongsTo(VendorComparison::class, 'vendor_comparison_id');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(VendorComparisonVendorPrice::class);
    }
}
