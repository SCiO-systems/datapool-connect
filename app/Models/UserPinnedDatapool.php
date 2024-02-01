<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class UserPinnedDatapool extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'Pinned_Datapool';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'pin_id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'User_Datapool',  'datapool_id', 'user_id');
    }

}
