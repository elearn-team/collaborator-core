<?php

namespace Modules\Tests\Webservice;

use Bazalt\Data\Validator;
use Modules\Tests\Model\ResultRefAnswer;
use Tonic\Response;
use Modules\Tests\Model\Test;
use Modules\Tests\Model\Question;
use Modules\Tests\Model\Answer;
use Modules\Tests\Model\AnswerResultFile;
use Modules\Tests\Model\Result;
use Modules\Tasks\Model\Task;
use Modules\Tasks\Model\TaskRefUser;
use Modules\Tasks\Model\TaskTestSetting;
use Modules\Courses\Model\CourseTestSetting;
use Bazalt\Auth\Model\User;

/**
 * ResultsResource
 *
 * @uri /tests/:taskId/results
 *
 * @apiDefineSuccessStructure ResultsStructure
 * @apiSuccess {Number} id Унікальний id
 * @apiSuccess {Number} test_id Унікальний id тесту
 * @apiSuccess {Number} task_id Унікальний id завдання
 * @apiSuccess {Enum} status Статус виконання завдадння
 * @apiSuccess {Number} user_id Унікальний id користувача
 * @apiSuccess {Date} created_at Дата створення
 * @apiSuccess {Date} updated_at Дата останнього редагування
 * @apiSuccess {Text} settings Налаштування для завдання
 * @apiSuccess {Float} mark Оцінка за завдання
 */
class ResultsResource extends \Bazalt\Rest\Resource
{
    protected $task = null;

    protected $test = null;

    protected $userTask = null;

    protected $curUser = null;

    protected $taskSett = null;

    protected $testing = null;

    protected $testId = null;

    protected function getTest($id)
    {

        $data = Validator::create((array)$this->request->data);
        if (isset($data['mode']) || isset($_GET['mode'])) {
            $this->testing = true;
            if (isset($data['testId'])) {
                $this->testId = (int)$data['testId'];
            }
            if (isset($_GET['testId'])) {
                $this->testId = (int)$_GET['testId'];
            }
        }

        $this->curUser = \Bazalt\Auth::getUser();
        if ($this->curUser->isGuest()) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }
        if ($this->testing && $this->testId) {
            $this->test = Test::getById($this->testId);
            if (!$this->test) {
                return new Response(400, ['not_found' => sprintf('Test "%s" not found', $this->task->element_id)]);
            }

            if (!$this->taskSett) {
                $this->taskSett = TaskTestSetting::getByTaskId($id);

            }
            if (!$this->taskSett) {
                return new Response(400, ['not_found' => sprintf('Task setting "%s" not found', $id)]);
            }


        } else if ($this->testing) {
            $this->task = Task::getById((int)$id);

            if (!$this->task) {
                return new Response(400, ['not_found' => sprintf('Task "%s" not found', $id)]);
            }

            $this->test = Test::getById((int)$this->task->element_id);
            if (!$this->test) {
                return new Response(400, ['not_found' => sprintf('Test "%s" not found', $this->task->element_id)]);
            }
        } else {
            $this->task = Task::getById((int)$id);

            if (!$this->task) {
                return new Response(400, ['not_found' => sprintf('Task "%s" not found', $id)]);
            }

            $this->test = Test::getById((int)$this->task->element_id);
            if (!$this->test) {
                return new Response(400, ['not_found' => sprintf('Test "%s" not found', $this->task->element_id)]);
            }

            if ($this->task->parent_id) {
                $parent = Task::getById((int)$this->task->parent_id);
                $this->taskSett = CourseTestSetting::getByCourseId($parent->element_id, $this->task->element_id);
            }
            if (!$this->taskSett) {
                $this->taskSett = TaskTestSetting::getByTaskId($id);

            }
            if (!$this->taskSett) {
                return new Response(400, ['not_found' => sprintf('Task setting "%s" not found', $id)]);
            }

            $this->userTask = TaskRefUser::getByUserAndTask($id, $this->curUser->id);
            if (!$this->userTask) {
                return new Response(400, ['not_assign' => sprintf('Task "%s" not assign for user "%s"', $id, $this->curUser->id)]);
            }
        }

        return true;
    }

    protected function getTestQuestionsCount()
    {
        if (!$this->taskSett || $this->taskSett->all_questions) {
            return $this->test->getAllQuestionsCount();
        }
        return $this->taskSett->questions_count;
    }

    /**
     * @method GET
     * @action get-by-task
     * @json
     */
    public function getItemByTaskId($id)
    {
        $res = $this->getTest($id);

        if ($res !== true) { //return error
            return $res;
        }

        $result = Result::getLastActive($this->test->id);
        $testSession = $result ? $result->toArray() : [];

        $testSession['canFinish'] = false;
        if ($result && !$this->testing) {
            $questionsLimit = !$this->taskSett->all_questions ? $this->taskSett->questions_count : null;
            $question = $this->test->getRandomQuestion($result, $questionsLimit);
            $testSession['canFinish'] = $question ? false : true;
        }

        $res = [
            'test' => $this->test->toArray(),
            'testSession' => $testSession
        ];

        if (!$this->testing) {
            $res['settings'] = $this->taskSett->toArray();

        }
        $res['questionsCount'] = $this->getTestQuestionsCount();
        $res['test']['answers_received'] = 0;
        $res['test']['percent_complete'] = 0;
        $res['test']['mark'] = 0;
        if ($result) {
            $answersReceived = Result::getStepTest($result->id);
            $res['test']['answers_received'] = $answersReceived;
            $qCnt = $this->getTestQuestionsCount();
            $res['test']['percent_complete'] = $qCnt == 0 ? 0 : ceil(($answersReceived / $qCnt) * 100);
            $res['test']['mark'] = $this->test->getResult($result);
        }

        if (!$this->testing) {
            $lastFinished = Result::getLastFinished($this->test->id, $id);
            if ($lastFinished) {
                $res['lastFinished'] = $lastFinished->toArray();
            }
        }
        $res['current_time'] = date('Y-m-d H:i:s');

        return new Response(Response::OK, $res);
    }

    /**
     * @method GET
     * @action random-question
     * @json
     */
    public function getRandomQuestion($taskId)
    {
        if (!$this->test) {
            $res = $this->getTest($taskId);
            if ($res !== true) { //return error
                return $res;
            }
        }

        $result = Result::getLastActive($this->test->id);
        if (!$result) {
            return new Response(400, ['session' => 'No active test session found']);
        }

        $questionsLimit = !$this->taskSett->all_questions ? $this->taskSett->questions_count : null;
        $question = $this->test->getRandomQuestion($result, $questionsLimit);


        if (!$question) {
            return new Response(Response::OK, new \stdClass());
        }

        $answers = $question->Answers->getQuery()
            ->andWhere('ft.is_deleted != ?', 1)
            ->orderBy('RAND()')->fetchAll();
        $res = [];
        $res['id'] = $question->id;
        $res['type'] = $question->type;
        $res['body'] = $question->body;
        $res['allow_add_files'] = (bool)$question->allow_add_files;
        $res['answers'] = [];
        foreach ($answers as $answer) {
            $res['answers'] [] = [
                'id' => $answer->id,
                'body' => $answer->body
            ];
        }
        $step = Result::getStepTest($result->id);
        $res['step'] = $step + 1;
        $qCnt = $this->getTestQuestionsCount();
        $res['percent'] = $qCnt > 0 ? ceil((($step) / $qCnt) * 100) : 0;

        return new Response(Response::OK, $res);
    }

    /**
     * @method POST
     * @json
     */
    public function createItem($taskId)
    {
        $res = $this->getTest($taskId);


        if ($res !== true) { //return error
            return $res;
        }

        $result = Result::create($this->test->id);
        $result->updated_at = date('Y-m-d H:i:s');
        $result->save();

        if (!$this->testing) {
            if ($this->userTask->status != TaskRefUser::STATUS_STARTED) {
                $this->userTask->status = TaskRefUser::STATUS_STARTED;
            }
            $this->userTask->attempts_count = Result::getFinishedCount($this->test->id, $this->task) + 1;
            $this->userTask->save();
        }

        return new Response(Response::OK, $this->task ? $this->task->toArray() : new \stdClass());
    }

    /**
     * @method PUT
     * @json
     */
    public function saveResult($taskId)
    {
        $res = $this->getTest($taskId);


        if ($res !== true) { //return error
            return $res;
        }

        $result = Result::getLastActive($this->test->id);
        if (!$result) {
            return new Response(400, ['session' => 'No active test session found']);
        }

        $data = Validator::create((array)$this->request->data);
        $data->field('question_id')->required();
        if (!$data->validate()) {
            return new Response(400, $data->errors());
        }

        $question = Question::getById((int)$data['question_id']);
        if (!$question) {
            return new Response(400, ['id' => sprintf('Question "%s" not found', $data['question_id'])]);
        }
        if ($question->type == Question::TYPE_SINGLE_ANSWER) {
            $data->field('answer_id')->required();
            if (!$data->validate()) {
                return new Response(400, $data->errors());
            }
            $answer = Answer::getById((int)$data['answer_id']);
            if (!$answer) {
                throw new \Exception(sprintf('Answer "%s" not found', $data['answer_id']));
            }
            $result->saveAnswer($question, $answer);
        } else if ($question->type == Question::TYPE_MULTI_ANSWER) {
            if (isset($data['answer_ids']) && is_array($data['answer_ids']) && count($data['answer_ids']) > 0) {
                foreach ($data['answer_ids'] as $iId) {
                    $answer = Answer::getById((int)$iId);
                    if (!$answer) {
                        throw new \Exception(sprintf('Answer "%s" not found', $iId));
                    }
                    $result->saveAnswer($question, $answer);
                }
            } else {
                return new Response(400, ['answer_ids' => 'Field cannot be empty']);
            }
        } else if ($question->type == Question::TYPE_FREE_ANSWER) {
            //free question
            if (isset($data['answer']) || isset($data['answerFiles']) || isset($data['mode'])) {
                $resultId = $result->saveAnswer($question, ['text' => $data['answer']]);
                if (count($data['answerFiles']) > 0) {
                    foreach ($data['answerFiles'] as $file) {
                        if (!isset($file->error)) {
                            $newFile = AnswerResultFile::create();
                            $newFile->answer_result_id = $resultId;
                            $newFile->name = $file->name;
                            $newFile->extension = $file->extension;
                            $newFile->file = $file->file;
                            $newFile->save();
                        }
                    }
                }
            } else {
                return new Response(400, ['answer_ids' => 'Field cannot be empty']);
            }
        }

        if (!$this->testing) {
            $this->userTask->status = TaskRefUser::STATUS_IN_PROGRESS;
            $this->userTask->save();
        }
        if ($this->testing) {
            return $this->getRandomQuestion($this->testId);
        } else {
            return $this->getRandomQuestion($taskId);
        }

    }


    /**
     * @method PUT
     * @action finish
     * @json
     */
    public function finishResult($taskId, $resultId = null)
    {
        $res = $this->getTest($taskId);

        $task = Task::getById($taskId);

        if ($res !== true) { //return error
            return $res;
        }

        if ($resultId) {
            $result = Result::getById($resultId);
            $this->userTask = TaskRefUser::getByUserAndTask($taskId, $result->user_id);
        } else {
            $result = Result::getLastActive($this->test->id);
        }

        if (!$result) {
            return new Response(400, ['session' => 'No active test session found']);
        }


        $questionsLimit = null;
        if ($this->taskSett) {
            $result->settings = serialize($this->taskSett->toArray());
            $questionsLimit = !$this->taskSett->all_questions ? $this->taskSett->questions_count : null;
        }

        $canGetMark = Result::canGetMark($result->id);
        $mark = round($this->test->getResult($result, $questionsLimit));

        $result->status = TaskRefUser::STATUS_IN_VERIFICATION;
        if ($canGetMark) {
            $result->status = ($mark < $task->threshold) ? TaskRefUser::STATUS_FAIL : TaskRefUser::STATUS_FINISHED;
            $result->mark = ($canGetMark) ? $mark : null;
        }

//        $result->updated_at = date('Y-m-d H:i:s');
        $result->task_id = (int)$taskId;
        $result->save();

        $answersReceived = Result::getStepTest($result->id);

        $res = [
            'mark' => ($canGetMark) ? (int)$this->test->getResult(($resultId) ? $result : null, $questionsLimit) : false,
            'start' => $result->created_at,
            'end' => $result->updated_at,
            'answers_received' => $answersReceived,
            'nextTask' => null
        ];


        if (!$this->testing) {
            $this->userTask->status = $result->status;
            $this->userTask->mark = $result->mark;
            $this->userTask->save();

            if ($this->task->parent_id) {
                $this->task->checkCourseState($this->curUser->id, true);
                $nextTask = $this->task->getNextSubTask();
                if ($nextTask) {
                    $res['nextTask'] = $nextTask->toArray();
                }
            }
        }

        return new Response(Response::OK, $res);
    }

    /**
     * @method PUT
     * @action updateTime
     * @json
     */
    public function updateTime($taskId)
    {
        $res = $this->getTest($taskId);
        if ($res !== true) { //return error
            return $res;
        }

        $result = Result::getLastActive($this->test->id);

        if (!$result) {
            return new Response(400, ['session' => 'No active test session found']);
        }
        $result->updated_at = date('Y-m-d H:i:s');
        $result->save();

        return new Response(Response::OK, 'updated');

    }


    /**
     * @action answersUpload
     * @method POST
     * @accepts multipart/form-data
     * @json
     */
    public function answersUpload($taskId)
    {
        $uploader = new \Bazalt\Rest\Uploader(['jpg', 'png', 'jpeg', 'bmp', 'gif', 'xls', 'xlsx', 'doc', 'docx',
            'zip', 'rar', '7z', 'pdf', 'mp4', 'swf', 'flv', 'mp3'], 100000000000);
        $result = $uploader->handleUpload(UPLOAD_DIR, ['answers', (int)$taskId]);
        $file = explode(".", $result['file']);
        $extension = end($file);

        $result['file'] = '/uploads' . $result['file'];
        $result['extension'] = $extension;

        return new Response(Response::OK, $result);
    }


    /**
     * @method POST
     * @action giveMark
     * @json
     */
    public function giveMark($taskId)
    {

        $data = Validator::create((array)$this->request->data);
        $data->field('resultId')->required();
        $data->field('answerResultId')->required();
        if (!$data->validate()) {
            return new Response(400, $data->errors());
        }

        $task = Task::getById($taskId);

        $resultAnswer = ResultRefAnswer::getById((int)$data['answerResultId']);
        $result = Result::getById((int)$resultAnswer->result_id);
        if (!$resultAnswer) {
            return new Response(400, ['not_found' => sprintf('Result "%s" not found', $data['answerResultId'])]);
        }

        $resultAnswer->mark = (int)$data['mark'];
        $resultAnswer->save();

        $this->finishResult($taskId, (int)$data['resultId']);

        if ($task->parent_id) {
            $task->checkCourseState($result->user_id, true);
        }

        return new Response(Response::OK);
    }

    /**
     * @method GET
     * @action getAnswerForEvaluation
     * @json
     */
    public function getAnswerForEvaluation($id)
    {

        $data = Validator::create((array)$_GET);
        $data->field('answerResultId')->required();

        if (!$data->validate()) {
            return new Response(400, $data->errors());
        }
        $answerResult = ResultRefAnswer::getById((int)$data['answerResultId']);

        if (!$answerResult) {
            return new Response(400, ['id' => sprintf('Answer result "%s" not found', $data['answerResultId'])]);
        }

        $question = Question::getById($answerResult->question_id);

        if (!$question) {
            return new Response(400, ['id' => sprintf('Question "%s" not found', $answerResult->question_id)]);
        }

        $resultAnswerFiles = AnswerResultFile::getByAnswerResultId($answerResult->id);

        $files = [];

        if (count($resultAnswerFiles) > 0) {
            foreach ($resultAnswerFiles as $file) {
                $files[] = ['name' => $file->name, 'url' => $file->file, 'extension' => $file->extension];
            }
        }

        $result = Result::getById($answerResult->result_id);

        if (!$result) {
            return new Response(400, ['not_found' => sprintf('Result "%s" not found', $answerResult->result_id)]);
        }
        $test = Test::getById($result->test_id);
        if (!$test) {
            return new Response(400, ['not_found' => sprintf('Test "%s" not found', $result->test_id)]);
        }
        $task = Task::getById($result->task_id);
        if (!$task) {
            return new Response(400, ['not_found' => sprintf('Test "%s" not found', $result->task_id)]);
        }

        $user = User::getById((int)$result->user_id);

        $config = \Bazalt\Config::container();
        try {
            $photo = $config['thumb.prefix'] . thumb(SITE_DIR . '/..' . $user->setting('photo'), '128x128', ['crop' => true, 'fit' => true]);
        } catch (\Exception $ex) {
            $photo = $config['uploads.prefix'] . $user->setting('photo');
        }

        $res = [
            'question' => [
                'id' => $question->id,
                'weight' => $question->weight,
                'body' => $question->body,
                'mark' => $answerResult->mark
            ],
            'answer_result' => $answerResult->toArray(),
            'files' => $files,
            'user' => [
                'id' => $user->id,
                'firstname' => $user->firstname,
                'secondname' => $user->secondname,
                'patronymic' => $user->patronymic,
                'email' => $user->email,
                'photo' => $photo
            ],
            'params' => [
                'test' => [
                    'id' => $test->id,
                    'title' => $test->title,
                    'description' => $test->description
                ],
                'task' => [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description
                ],
                'result_id' => $answerResult->result_id
            ]
        ];

        return new Response(Response::OK, ['data' => $res]);
    }


}
