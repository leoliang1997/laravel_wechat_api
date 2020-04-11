<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\LessonStudentRelation
 *
 * @property int $id
 * @property string $uid
 * @property string $cid
 * @property int $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\LessonStudentRelation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\LessonStudentRelation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\LessonStudentRelation query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\LessonStudentRelation whereCid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\LessonStudentRelation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\LessonStudentRelation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\LessonStudentRelation whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\LessonStudentRelation whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\LessonStudentRelation whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class LessonStudentRelation extends Model
{
    public $fillable = [
        'cid', 'uid', 'status'
    ];

    public const STATUS_NOT = 0; //未签到
    public const STATUS_DONE = 1; //已签到
}
