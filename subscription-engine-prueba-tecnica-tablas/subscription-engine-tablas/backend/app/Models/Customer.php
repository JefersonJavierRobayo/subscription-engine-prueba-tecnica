<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $table = 'customers';

    protected $fillable = ['name', 'email', 'document', 'phone'];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}