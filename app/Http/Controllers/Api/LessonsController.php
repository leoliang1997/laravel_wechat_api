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
use App\Models\StudentSignInLog;
use App\Models\TeacherSignInLog;
use App\Models\User;
use Illuminate\Http\Request;

class LessonsController extends Controller
{
    public function create(Request $request)
    {
        $this->authorize('teacher', Lesson::class);
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
            'keyword' => 'sometimes',
            'page' => 'required|int',
            'page_size' => 'required|int'
        ]);

        $totalCount = Lesson::count();
        $offset = $request->page * $request->page_size;

        /**
         * @var Lesson[] $lessons
         */
        $lessons = Lesson::where('name', 'like', '%'.$request->keyword.'%')
            ->limit($request->page_size)
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
                'page' => (int)$request->page,
                'page_size' => (int)$request->page_size,
                'page_count' => ceil($totalCount / $request->page_size),
                'total_count' => $totalCount
            ],
            'list' => array_values($list)
        ];

        return success($data);
    }

    public function join(Request $request)
    {
        $this->authorize('student', Lesson::class);
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
            $lessons = Lesson::whereIn('cid', $lessonIds)->get();
        } else {
            $totalCount = Lesson::where('uid', '=', $user->uid)->count();
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
                'name' => $lesson->name,
                'status' => $lesson->status
            ];
        }

        $data = [
            'page' => [
                'page' => (int)$request->page,
                'page_size' => (int)$request->page_size,
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
        $students = User::whereIn('uid', $studentIds)->get();
        $list = [];
        foreach ($students as $student) {
            $list[] = [
                'uid' => $student->uid,
                'name' => $student->name
            ];
        }

        $data = [
            'page' => [
                'page' => (int)$request->page,
                'page_size' => (int)$request->page_size,
                'page_count' => ceil($totalCount / $request->page_size),
                'total_count' => $totalCount
            ],
            'list' => $list
        ];

        return success($data);
    }

    public function startSignIn(Request $request)
    {
        $this->validate($request, [
            'cid' => 'required|string',
            'command' => 'required|string',
        ]);
        $cid = $request->cid;
        $command = $request->command;
        /**
         * @var User $user
         */
        $user = $request->user();
        $lesson = Lesson::where('cid', '=', $cid)
            ->where('uid', '=', $user->uid)
            ->first();
        if ($user->isTeacher()) {
            if (empty($lesson)) {
                return error(-1, '课程不存在!');
            }
            if ($lesson->isSignInNow()) {
                return error(-1, '课程正在签到，不能重复发起！');
            }
            $lesson->status = Lesson::STATUS_NOW;
            $lesson->save();

            LessonStudentRelation::where('cid', '=', $cid)
                ->update(['status' => LessonStudentRelation::STATUS_NOT]);

            TeacherSignInLog::create([
                'cid' => $cid,
                'command' => $command
            ]);
        } else {
            if (!$lesson->isSignInNow()) {
                return error(-1, '该课程未在签到！');
            }

            $lessonStudentRelation = LessonStudentRelation::where('uid', '=', $user->uid)
                ->where('cid', '=', $cid)
                ->where('status', '=', LessonStudentRelation::STATUS_NOT)
                ->first();

            if (empty($lessonStudentRelation)) {
                return error(-1, '签到失败!');
            }

            $teacherSignInLog = TeacherSignInLog::where('command', '=', $command)
                ->where('cid', '=', $cid)
                ->orderBy('created_at', 'DESC')
                ->first();

            if (empty($teacherSignInLog)) {
                return error(-1, '口令错误!');
            }

            $lessonStudentRelation->status = LessonStudentRelation::STATUS_DONE;
            $lessonStudentRelation->save();

            StudentSignInLog::create([
               'uid' => $user->uid,
               'kid' => $teacherSignInLog->kid
            ]);
        }

        return success();
    }

    public function endSignIn(Request $request)
    {
        $this->authorize('teacher', Lesson::class);
        $this->validate($request, [
            'cid' => 'required|string'
        ]);
        $cid = $request->cid;
        $user = $request->user();
        $lesson = Lesson::where('cid', '=', $cid)
            ->where('uid', '=', $user->uid)
            ->first();

        if (empty($lesson)) {
            return error(-1, '无效操作!');
        }

        $lesson->status = Lesson::STATUS_DONE;
        $lesson->save();

        return success();
    }

    public function signInHistory(Request $request)
    {
        $this->validate($request, [
            'cid' => 'required|string',
            'page' => 'required|int',
            'page_size' => 'required|int'
        ]);
        $cid = $request->cid;
        $offset = $request->page * $request->page_size;
        $totalCount = TeacherSignInLog::where('cid', '=', $cid)->count();
        $teacherLogs = TeacherSignInLog::where('cid', '=', $cid)
            ->limit($request->page_size)
            ->offset($offset)
            ->get();

        $cids = [];
        $list = [];
        foreach ($teacherLogs as $log) {
            $cids[] = $log->cid;
            $list[$log->cid] = [
                'kid' => $log->kid,
                'start_time' => $log->created_at,
                'end_time' => $log->updated_at
            ];
        }
        $lessons = Lesson::whereIn('cid', $cids)->get();

        foreach ($lessons as $lesson) {
            if (isset($list[$lesson->cid])) {
                $list[$lesson->cid]['name'] = $lesson->name;
            }
        }

        $data = [
            'page' => [
                'page' => (int)$request->page,
                'page_size' => (int)$request->page_size,
                'page_count' => ceil($totalCount / $request->page_size),
                'total_count' => $totalCount
            ],
            'list' => array_values($list)
        ];

        return success($data);
    }

    public function signInDetail(Request $request)
    {
        $this->validate($request, [
            'kid' => 'required|int',
            'page' => 'required|int',
            'page_size' => 'required|int'
        ]);
        $kid = $request->kid;
        $offset = $request->page * $request->page_size;
        $teacherLog = TeacherSignInLog::where('kid', '=', $kid)->first();
        if (empty($teacherLog)) {
            return error(-1, '记录不存在!');
        }
        $totalCount = LessonStudentRelation::where('cid', '=', $teacherLog->cid)->count();
        $lessonStudentRelations = LessonStudentRelation::where('cid', '=', $teacherLog->cid)
            ->limit($request->page_size)
            ->offset($offset)
            ->get('uid');

        $studentIds = [];
        foreach ($lessonStudentRelations as $lessonStudentRelation) {
            $studentIds[] = $lessonStudentRelation->uid;
        }

        $users = User::whereIn('uid', $studentIds)->get();
        $studentLogs = StudentSignInLog::whereIn('uid', $studentIds)->get();
        $list = [];
        foreach ($users as $user) {
            $list[$user->uid] = [
                'uid' => $user->uid,
                'name' => $user->name,
                'status' => LessonStudentRelation::STATUS_NOT
            ];
        }
        foreach ($studentLogs as $studentLog) {
            if (isset($list[$studentLog->uid])) {
                $list[$studentLog->uid]['status'] = LessonStudentRelation::STATUS_DONE;
            }
        }

        $data = [
            'page' => [
                'page' => (int)$request->page,
                'page_size' => (int)$request->page_size,
                'page_count' => ceil($totalCount / $request->page_size),
                'total_count' => $totalCount
            ],
            'list' => array_values($list)
        ];

        return success($data);
    }
}
