<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\Factory;
use Database\Factories\UserFactory;

class User extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'User';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'user_id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    protected static function newFactory(): Factory
    {
        return UserFactory::new();
    }

    public function datafiles(): BelongsToMany
    {
        return $this->belongsToMany(Datafile::class, 'User_Datafile', 'user_id', 'datafile_id');
    }

    public function datapools(): BelongsToMany
    {
        return $this->belongsToMany(Datapool::class, 'User_Datapool', 'user_id', 'datapool_id');
    }

    public function pinnedDatapools(): BelongsToMany
    {
        return $this->belongsToMany(Datapool::class, 'Pinned_Datapool', 'user_id', 'datapool_id');
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class, 'user_id');
    }

    public function apis(): HasMany
    {
        return $this->hasMany(API::class, 'user_id');
    }
}
