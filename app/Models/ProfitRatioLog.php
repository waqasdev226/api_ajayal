<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfitRatioLog extends Model
{
    use HasFactory;
    protected $table = 'profit_ratio_log';
    protected $fillable = [
        'user_id',
        'cash',
        'ratio',
        'ratio_per_day',
        'days_to_calculate',
        'total',
        'status',
    ];

    public function userDetail(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
