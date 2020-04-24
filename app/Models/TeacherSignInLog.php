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
 * @property float $latitude
 * @property float $longitude
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TeacherSignInLog whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TeacherSignInLog whereLongitude($value)
 * @property int $start_time
 * @property int $end_time
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TeacherSignInLog whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TeacherSignInLog whereStartTime($value)
 */
class TeacherSignInLog extends Model
{
    protected $primaryKey = 'kid';

    protected $fillable = [
        'cid', 'command', 'latitude', 'longitude', 'start_time', 'end_time'
    ];
}
