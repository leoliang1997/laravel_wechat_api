<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\StudentSignInLog
 *
 * @property int $id
 * @property string $uid
 * @property int $kid
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StudentSignInLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StudentSignInLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StudentSignInLog query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StudentSignInLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StudentSignInLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StudentSignInLog whereKid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StudentSignInLog whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StudentSignInLog whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property float $distance
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StudentSignInLog whereDistance($value)
 */
class StudentSignInLog extends Model
{
    protected $fillable = [
        'uid', 'kid', 'distance'
    ];
}
