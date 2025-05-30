<?php

namespace App\Models\Voyager;

use Illuminate\Database\Eloquent\Model;

class PermissionRole extends Model
{
    //
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'permission_role';

    /**
     * primaryKey
     *
     * @var integer
     * @access protected
     */
    protected $primaryKey = null;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Indicates if the use timestamps.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Indicates fillable fields.
     *
     * @var array
     */
    protected $fillable = ['permission_id', 'role_id'];
}
