<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Lesson
 *
 * @property int $id
 * @property string $cid
 * @property string $name
 * @property string $uid
 * @property int $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Lesson newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Lesson newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Lesson query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Lesson whereCid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Lesson whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Lesson whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Lesson whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Lesson whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Lesson whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Lesson whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Lesson extends Model
{
    public const STATUS_DONE = 0; //考勤结束
    public const STATUS_NOW = 1; //正在考勤

    public const PREFIX = 'C';

    protected $fillable = [
        'cid', 'name', 'uid', 'status'
    ];

    protected $hidden = [];

    public static function getCid()
    {
        $model = self::orderBy('created_at', 'DESC')->first('cid');

        if ($model === null) {
            return self::PREFIX.'0000001';
        }

        $cid = (string)(substr($model->cid, 1) + 1);

        return self::PREFIX.str_pad($cid, 8, '0', STR_PAD_LEFT);
    }
}
