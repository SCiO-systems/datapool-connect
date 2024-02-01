<?php

namespace App\Models;

use Database\Factories\DatapoolFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Datapool extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'Datapool';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'datapool_id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    protected static function newFactory(): Factory
    {
        return DatapoolFactory::new();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'User_Datapool',  'datapool_id', 'user_id');
    }

    public function datafiles(): BelongsToMany
    {
        return $this->belongsToMany(Datafile::class, 'Datapool_Datafile', 'datapool_id', 'datafile_id')->withPivot(['codebook']);
    }

    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class, 'datapool_id', 'datapool_id');
    }

    public function apis(): HasMany
    {
        return $this->hasMany(API::class, 'datapool_id');
    }
}
