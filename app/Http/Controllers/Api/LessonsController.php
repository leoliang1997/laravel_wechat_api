<?php
/**
 * Created by PhpStorm.
 * User: leoliang
 * Date: 2020/4/11
 * Time: 13:47
 */

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonStudentRelation;
use App\Models\User;
use Illuminate\Http\Request;

class LessonsController extends Controller
{
    public function create(Request $request)
    {
        $this->authorize('create');
        $this->validate($request, [
            'name' => 'required|string'
        ]);

        $lesson = Lesson::create([
            'cid' => Lesson::getCid(),
            'name' => $request->name,
            'uid' => $request->user()->uid,
            'status' => Lesson::STATUS_DONE,
        ]);

        return success(['cid' => $lesson->cid]);
    }

    public function lessonList(Request $request)
    {
        $this->validate($request, [
            'keyword' => 'required|string',
            'page' => 'required|int',
            'page_size' => 'required|int'
        ]);

        $totalCount = Lesson::count();
        $offset = $request->page * $request->page_size;

        /**
         * @var Lesson[] $lessons
         */
        $lessons = Lesson::limit($request->page_size)
            ->offset($offset)
            ->get();

        $list = [];

        foreach ($lessons as $lesson) {
            $cid = $lesson->cid;
            $list[$cid] = [
                'cid' => $cid,
                'name' => $lesson->name,
                'is_join' => 0
            ];
        }

        /**
         * @var LessonStudentRelation[] $studentLesson
         */
        $studentLesson = LessonStudentRelation::whereUid($request->user()->uid)
            ->get();

        foreach ($studentLesson as $lesson) {
            $cid = $lesson->cid;
            if (isset($list[$cid])) {
                $list[$cid]['is_join'] = 1;
            }
        }

        $data = [
            'page' => [
                'page' => $request->page,
                'gage_size' => $request->page_size,
                'page_count' => ceil($totalCount / $request->page_size),
                'total_count' => $totalCount
            ],
            'list' => $list
        ];

        return success($data);
    }

    public function join(Request $request)
    {
        $this->authorize('join');
        $this->validate($request, [
            'cid' => 'required|string'
        ]);
        $cid = $request->cid;
        $uid = $request->user()->uid;

        $lesson = Lesson::where('cid', '=', $cid)->first();
        if (empty($lesson)) {
            return error(-3, '课程号不存在!');
        }

        $lessonStudentRelation = LessonStudentRelation::where('cid', '=', $cid)
            ->where('uid', '=', $uid)
            ->first();
        if (!empty($lessonStudentRelation)) {
            return error(-1, '你已经加入该课程，无需重复加入!');
        }

        LessonStudentRelation::create([
            'uid' => $uid,
            'cid' => $cid,
            'status' => LessonStudentRelation::STATUS_NOT
        ]);

        return success();
    }

    public function myLesson(Request $request)
    {
        $this->validate($request, [
            'page' => 'required|int',
            'page_size' => 'required|int'
        ]);

        /**
         * @var User $user
         */
        $user = $request->user();
        $offset = $request->page * $request->page_size;
        if ($user->isStudent()) {
            $totalCount = LessonStudentRelation::where('uid', '=', $user->uid)->count();
            $lessonStudentRelations = LessonStudentRelation::where('uid', '=', $user->uid)
                ->limit($request->page_size)
                ->offset($offset)
                ->get();
            $lessonIds = [];
            foreach ($lessonStudentRelations as $lessonStudentRelation) {
                $lessonIds[] = $lessonStudentRelation->cid;
            }
            $lessons = Lesson::where('cid', 'in', $lessonIds)->get();
        } else {
            $totalCount = Lesson::where('uid', '=', $user->uid)->get();
            $lessons = Lesson::where('uid', '=', $user->uid)
                ->limit($request->page_size)
                ->offset($offset)
                ->get();
        }

        $list = [];

        /**
         * @var Lesson[] $lessons
         */
        foreach ($lessons as $lesson) {
            $list[] = [
                'cid' => $lesson->cid,
                'name' => $lesson->name
            ];
        }

        $data = [
            'page' => [
                'page' => $request->page,
                'gage_size' => $request->page_size,
                'page_count' => ceil($totalCount / $request->page_size),
                'total_count' => $totalCount
            ],
            'list' => $list
        ];

        return success($data);
    }

    public function studentList(Request $request)
    {
        $this->validate($request, [
            'cid' => 'required|string',
            'page' => 'required|int',
            'page_size' => 'required|int'
        ]);

        $totalCount = LessonStudentRelation::where('cid', '=', $request->cid)->count();
        $offset = $request->page * $request->page_size;
        /**
         * @var LessonStudentRelation[] $lessonStudentRelations
         */
        $lessonStudentRelations = LessonStudentRelation::where('cid', '=', $request->cid)
            ->limit($request->page_size)
            ->offset($offset)
            ->get();

        $studentIds = [];

        foreach ($lessonStudentRelations as $lessonStudentRelation) {
            $studentIds[] = $lessonStudentRelation->uid;
        }

        /**
         * @var User[] $students
         */
        $students = User::where('uid', 'in', $studentIds)->get();
        $list = [];
        foreach ($students as $student) {
            $list[] = [
                'uid' => $student->uid,
                'name' => $student->name
            ];
        }

        $data = [
            'page' => [
                'page' => $request->page,
                'gage_size' => $request->page_size,
                'page_count' => ceil($totalCount / $request->page_size),
                'total_count' => $totalCount
            ],
            'list' => $list
        ];

        return success($data);
    }
}
