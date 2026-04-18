<?php

namespace ME\Utility\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BajarListItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'item_name',
        'brand',
        'source',
        'price',
        'description',
        'status',
    ];

    public function group()
    {
        return $this->belongsTo(BajarListGroup::class, 'group_id');
    }
}
