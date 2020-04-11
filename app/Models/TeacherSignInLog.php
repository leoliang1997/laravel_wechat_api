<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\TeacherSignInLog
 *
 * @property int $kid
 * @property string $cid
 * @property string $command
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TeacherSignInLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TeacherSignInLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TeacherSignInLog query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TeacherSignInLog whereCid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TeacherSignInLog whereCommand($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TeacherSignInLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TeacherSignInLog whereKid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TeacherSignInLog whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class TeacherSignInLog extends Model
{
    protected $fillable = [
        'cid', 'command'
    ];
}
