<?php

namespace ME\Utility\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BajarListGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'group_date',
        'color',
    ];

    public function items()
    {
        return $this->hasMany(BajarListItem::class, 'group_id');
    }
}
