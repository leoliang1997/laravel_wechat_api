<?php
/**
 * Created by PhpStorm.
 * User: leoliang
 * Date: 2020/4/11
 * Time: 13:47
 */

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Lesson;
use App\Models\LessonStudentRelation;
use App\Models\Question;
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
            'qa_status' => Lesson::QA_STATUS_DONE
        ]);

        return success(['cid' => $lesson->cid]);
    }

    public function delete(Request $request)
    {
        $this->validate($request, [
            'cid' => 'required|string'
        ]);
        $cid = $request->cid;
        $lesson = Lesson::whereCid($cid)->first();
        if (empty($lesson) || !$lesson->isMyLesson($request->user()->uid)) {
            return error(-1, '课程不存在');
        }
        $kids = $this->getKid($cid);
        try {
            \DB::beginTransaction();
            Lesson::whereCid($cid)->delete();
            LessonStudentRelation::whereCid( $cid)->delete();
            TeacherSignInLog::whereCid($cid)->delete();
            StudentSignInLog::whereIn('kid', $kids)->delete();
            \DB::commit();
            return success();
        } catch (\Exception $e) {
            \DB::rollBack();
            return error(-1, '删除课程失败:'.$e->getMessage());
        }
    }

    public function deleteStudent(Request $request)
    {
        $this->validate($request, [
            'cid' => 'required|string',
            'uid' => 'required|string'
        ]);
        $cid = $request->cid;
        $studentId = $request->uid;
        $lesson = Lesson::where('cid', '=', $cid)->first();
        if (empty($lesson) || !$lesson->isMyLesson($request->user()->uid)) {
            return error(-1, '课程不存在');
        }
        $kids = $this->getKid($cid);
        try {
            \DB::beginTransaction();
            LessonStudentRelation::where('uid', '=', $studentId)
                ->where('cid', '=', $cid)
                ->delete();
            StudentSignInLog::whereIn('kid', $kids)
                ->whereIn('uid', '=', $studentId)
                ->delete();
            \DB::commit();
            return success();
        } catch (\Exception $e) {
            \DB::rollBack();
            return error(-1, '删除学生失败:'.$e->getMessage());
        }
    }

    private function getKid($cid)
    {
        $logs = TeacherSignInLog::whereCid($cid)->get();
        $result = [];
        foreach ($logs as $log) {
            $result[] = $log->kid;
        }

        return $result;
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
        $uidList = [];
        foreach ($lessons as $lesson) {
            $uidList[] = $lesson->uid;
            $list[$lesson->cid] = [
                'cid' => $lesson->cid,
                'lesson_name' => $lesson->name,
                'uid' => $lesson->uid,
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

        $this->getTeacherInfo($list, $uidList);

        $data = packageData($request->page, $request->page_size, $totalCount, $list);

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
        $uidList = [];
        /**
         * @var Lesson[] $lessons
         */
        foreach ($lessons as $lesson) {
            $uidList[] = $lesson->uid;
            $list[$lesson->cid] = [
                'cid' => $lesson->cid,
                'uid' => $lesson->uid,
                'lesson_name' => $lesson->name,
                'status' => $lesson->status
            ];
        }

        $this->getTeacherInfo($list, $uidList);

        $data = packageData($request->page, $request->page_size, $totalCount, $list);

        return success($data);
    }

    private function getTeacherInfo(&$list, $uidList)
    {
        /**
         * @var User[] $teachers
         */
        $teachers = User::whereIn('uid', $uidList)->get();
        foreach ($list as $key => $value) {
            foreach ($teachers as $teacher) {
                if ($value['uid'] === $teacher->uid) {
                    $list[$key]['teacher_name'] = $teacher->name;
                    break;
                }
            }
        }

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

        $data = packageData($request->page, $request->page_size, $totalCount, $list);

        return success($data);
    }

    public function startSignIn(Request $request)
    {
        $this->validate($request, [
            'cid' => 'required|string',
            'command' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric'
        ]);
        $cid = $request->cid;
        $command = $request->command;
        $latitude = $request->latitude;
        $longitude = $request->longitude;

        /**
         * @var User $user
         */
        $user = $request->user();
        $lesson = Lesson::where('cid', '=', $cid)->first();
        if ($user->isTeacher()) {
            if (empty($lesson) || !$lesson->isMyLesson($user->uid)) {
                return error(-1, '课程不存在!');
            }

            if ($lesson->isSignInNow()) {
                return error(-1, '课程正在签到，不能重复发起!');
            }

            $studentCount = LessonStudentRelation::whereCid($cid)->count();
            if (!$studentCount) {
                return error(-1, '该课程没有学生，无法签到!');
            }

            $lesson->status = Lesson::STATUS_NOW;
            $lesson->save();

            LessonStudentRelation::where('cid', '=', $cid)
                ->update(['status' => LessonStudentRelation::STATUS_NOT]);

            TeacherSignInLog::create([
                'cid' => $cid,
                'command' => $command,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'start_time' => time()
            ]);
        } else {
            if (empty($lesson)) {
                return error(-1, '课程不存在!');
            }

            if (!$lesson->isSignInNow()) {
                return error(-1, '该课程未在签到!');
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

            $distance = getDistance($latitude, $longitude,
                $teacherSignInLog->latitude, $teacherSignInLog->longitude);

            if ($distance > 200) {
                return error(-1, '不在签到范围!');
            }

            $lessonStudentRelation->status = LessonStudentRelation::STATUS_DONE;
            $lessonStudentRelation->save();

            StudentSignInLog::create([
                'uid' => $user->uid,
                'kid' => $teacherSignInLog->kid,
                'distance' => $distance
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
        $lesson->qa_status = Lesson::QA_STATUS_DONE;
        $lesson->status = Lesson::STATUS_DONE;
        $lesson->save();

        $teacherSignInLog = TeacherSignInLog::whereCid($cid)
            ->orderBy('created_at', 'DESC')
            ->first();

        if (empty($teacherSignInLog)) {
            return error(-1, '签到记录不存在!');
        }

        $teacherSignInLog->end_time = time();
        $teacherSignInLog->save();

        return success(['kid' => $teacherSignInLog->kid]);
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
        /**
         * @var TeacherSignInLog[] $teacherLogs
         */
        $teacherLogs = TeacherSignInLog::where('cid', '=', $cid)
            ->limit($request->page_size)
            ->offset($offset)
            ->get();

        $cids = [];
        $list = [];
        foreach ($teacherLogs as $log) {
            if (empty($log->end_time)) {
                continue;
            }
            $cids[] = $log->cid;
            $list[] = [
                'kid' => $log->kid,
                'cid' => $log->cid,
                'start_time' => date('Y-m-d H:i:s', $log->start_time),
                'end_time' => date('Y-m-d H:i:s', $log->end_time)
            ];
        }
        $lessons = Lesson::whereIn('cid', $cids)->get();
        $cnameList = [];

        foreach ($lessons as $lesson) {
            $cnameList[$lesson->cid] = $lesson->name;
        }

        foreach ($list as $key => $value) {
            if (isset($cnameList[$value['cid']])) {
                $list[$key]['name'] = $cnameList[$value['cid']];
            }
        }

        $data = packageData($request->page, $request->page_size, $totalCount, $list);

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
        $studentLogs = StudentSignInLog::whereKid($kid)->get();
        $list = [];
        foreach ($users as $user) {
            $list[$user->uid] = [
                'uid' => $user->uid,
                'name' => $user->name,
                'distance' => 0,
                'status' => LessonStudentRelation::STATUS_NOT
            ];
        }
        foreach ($studentLogs as $studentLog) {
            if (isset($list[$studentLog->uid])) {
                $list[$studentLog->uid]['status'] = LessonStudentRelation::STATUS_DONE;
                $list[$studentLog->uid]['distance'] = $studentLog->distance;
            }
        }

        $data = packageData($request->page, $request->page_size, $totalCount, $list);

        return success($data);
    }

    public function signInStatus(Request $request)
    {
        $this->validate($request, [
            'cid' => 'required|string'
        ]);

        $model = Lesson::whereCid($request->cid)->first();

        if (empty($model)) {
            return error(-1, '课程id无效!');
        }

        return success(['status' => $model->status]);
    }

    public function fileList(Request $request)
    {
        $this->validate($request, [
            'cid' => 'required|string',
            'page' => 'required|int',
            'page_size' => 'required|int'
        ]);

        $totalCount = File::count();
        $offset = $request->page * $request->page_size;
        $files = File::where('status', '=', File::STATUS_NORMAL)
            ->where('cid', '=', $request->cid)
            ->limit($request->page_size)
            ->offset($offset)
            ->get();

        $uidList = [];

        $list = [];

        foreach ($files as $file) {
            $list[] = [
                'file_name' => $file->file_name,
                'created_at' => $file->created_at->format('Y-m-d H:i:s'),
                'uploaded_by' => '',
                'uid' => $file->uid,
                'download_url' => $file->url
            ];
            $uidList[] = $file->uid;
        }

        $users = User::whereIn('uid', $uidList)->get();
        $userNameList = [];
        foreach ($users as $user) {
            $userNameList[$user->cid] = $user->name;
        }

        foreach ($list as $key => $value) {
            if (isset($userNameList[$value['uid']])) {
                $list[$key]['uploaded_by'] = $userNameList[$value['uid']];
            }
        }

        $data = packageData($request->page, $request->page_size, $totalCount, $list);

        return success($data);
    }

    public function upload(Request $request)
    {
        $this->authorize('teacher', Lesson::class);
        $this->validate($request, [
            'cid' => 'required',
            'file' => 'required',
            'file_name' => 'required|string'
        ]);

        $cid = $request->cid;
        $fileName = $request->file_name;
        $file = $request->file('file');
        $uploadPath = '/uploads/' . time();
        $downloadUrl = env('APP_URL') . $uploadPath . '/' . $fileName;
        $file->move(public_path() . $uploadPath, $fileName);

        File::create([
            'file_name' => $fileName,
            'cid' => $cid,
            'url' => $downloadUrl,
            'uid' => $request->user()->uid,
            'status' => File::STATUS_NORMAL
        ]);

        return success();
    }

    public function rollCall(Request $request)
    {
        $this->validate($request, [
            'cid' => 'required|string',
            'num' => 'required|int'
        ]);

        $cid = $request->cid;
        $num = $request->num;

        $lesson = Lesson::whereCid($cid)
            ->whereUid($request->user()->uid)
            ->first();
        if (empty($lesson)) {
            return error(-1, '课程号无效');
        }

        $allCount = LessonStudentRelation::whereCid($cid)->count();
        if ($allCount < $num) {
            return error(-1, 'num大于当前课程人数');
        }

        $students = LessonStudentRelation::whereCid($cid)
            ->inRandomOrder()
            ->limit($num)
            ->get();

        $uids = [];

        foreach ($students as $student) {
            $uids[] = $students->uid;
        }

        $studentInfos = User::whereIn('uid', $uids)->get();

        $list = [];

        foreach ($studentInfos as $studentInfo) {
            $list[] = [
                'uid' => $studentInfo->uid,
                'name' => $studentInfo->name
            ];
        }

        return success(['list' => $list]);
    }

    public function qaStatus(Request $request)
    {
        $this->validate($request, [
            'cid' => 'required|string'
        ]);

        $lesson = Lesson::whereCid($request->cid)->first();
        if (empty($lesson)) {
            return error(-1, '课程号无效');
        }

        $status = $lesson->qa_status;

        return success(['status' => $status]);
    }

    public function startQa(Request $request)
    {
        $this->validate($request, [
            'cid' => 'required|string'
        ]);

        $lesson = Lesson::whereCid($request->cid)
            ->whereUid($request->user()->uid)
            ->whereQaStatus(Lesson::QA_STATUS_DONE)
            ->first();
        if (empty($lesson)) {
            return error(-1, '课程号无效');
        }

        $lesson->qa_status = Lesson::QA_STATUS_NOW;
        $lesson->save();

        return success();
    }

    public function endQa(Request $request)
    {
        $this->validate($request, [
            'cid' => 'required|string'
        ]);

        $lesson = Lesson::whereCid($request->cid)
            ->whereUid($request->user()->uid)
            ->whereQaStatus(Lesson::QA_STATUS_NOW)
            ->first();
        if (empty($lesson)) {
            return error(-1, '课程号无效');
        }

        $lesson->qa_status = Lesson::QA_STATUS_DONE;
        $lesson->save();

        Question::whereCid($request->cid)->update(['status' => Question::STATUS_DONE]);

        return success();
    }

    public function questionList(Request $request)
    {
        $this->validate($request, [
            'cid' => 'required|string',
            'page' => 'required|int',
            'page_size' => 'required|int'
        ]);

        $totalCount = Question::whereCid($request->cid)
            ->whereStatus(Question::STATUS_NOW)
            ->count();

        $offset = $request->page * $request->page_size;

        $questions = Question::whereCid($request->cid)
            ->whereStatus(Question::STATUS_NOW)
            ->limit($request->page)
            ->offset($offset)
            ->get();

        $list = [];
        $uids = [];
        foreach ($questions as $question) {
            $uids[] = $question->uid;
            $list[] = [
                'uid' => $question->uid,
                'name' => '',
                'question' => $question->content
            ];
        }

        $studentInfos = User::whereIn('uid', $uids)->get();

        foreach ($list as $key => $value) {
            foreach ($studentInfos as $studentInfo) {
                if ($value['uid'] === $studentInfo->uid) {
                    $list[$key]['name'] = $studentInfo->name;
                    break;
                }
            }
        }

        return success(packageData($request->page, $request->page_size, $totalCount, $list));
    }

    public function askQuestion(Request $request)
    {
        $this->validate($request, [
            'cid' => 'required|string',
            'question' => 'required|string'
        ]);

        $lesson = Lesson::whereCid($request->cid)
            ->whereQaStatus(Lesson::QA_STATUS_NOW)
            ->first();
        if (empty($lesson)) {
            return error(-1, '未在提问时间');
        }

        Question::insert([
            'cid' => $request->cid,
            'uid' => $request->user()->uid,
            'content' => $request->question,
            'status' => Question::STATUS_NOW
        ]);

        return success();
    }

}
