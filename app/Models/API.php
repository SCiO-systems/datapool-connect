<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class API extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'API';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'api_id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

//    public function userDatapools(): BelongsToMany
//    {
//        return $this->belongsToMany(UserDataPool::class);
//    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id' );
    }
    public function datapool(): BelongsTo
    {
        return $this->belongsTo(Datapool::class, 'datapool_id' );
    }
}
