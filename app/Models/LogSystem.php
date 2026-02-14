<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LogSystem extends Model
{
    use SoftDeletes;
    protected $table = 'log_system';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'action',
        'url_request',
        'entity_name',
        'entity_id',
        'description',
        'pre_data',
        'post_data',
        'host_address',
        'host_name',
        'created_by',
        'created_by_name',
    ];

}
