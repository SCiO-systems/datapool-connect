<?php

namespace App\Models;

use Database\Factories\DatafileFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Datafile extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'Datafile';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'datafile_id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    protected static function newFactory(): Factory
    {
        return DatafileFactory::new();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'User_Datafile', 'datafile_id', 'user_id');
    }

    public function datapools(): BelongsToMany
    {
        return $this->belongsToMany(Datapool::class, 'Datapool_Datafile', 'datafile_id', 'datapool_id');
    }
}
