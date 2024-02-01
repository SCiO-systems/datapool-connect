<?php

namespace App\Models\Views;

use App\Models\Tag;
use App\Models\UserDataPool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserDatapoolRoleApiView extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'User_Datapool_Role_API_View';

    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class, 'datapool_id', 'datapool_id');
    }
}
