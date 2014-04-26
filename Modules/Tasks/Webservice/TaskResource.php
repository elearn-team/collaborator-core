<?php

namespace Modules\Tasks\Webservice;

use Bazalt\Data\Validator;
use Modules\Tags\Model\TagRefElement;
use Tonic\Response;
use Modules\Tasks\Model\Task;
use Modules\Tasks\Model\TaskRefUser;
use Bazalt\Auth\Model\User;
use Modules\Tests\Model\Test;
use Modules\Courses\Model\Course;
use Modules\Resources\Model\Page;
use Modules\Courses\Model\CoursePlan;
use Modules\Courses\Model\CourseElement;

/**
 * TestResource
 *
 * @uri /tasks/:id
 */
class TaskResource extends \Bazalt\Rest\Resource
{
    /**
     * @method GET
     * @json
     */
    public function getItem($id)
    {
        $task = Task::getById((int)$id);
        if (!$task) {
            return new Response(400, ['id' => sprintf('Task "%s" not found', $id)]);
        }
        $curUser = \Bazalt\Auth::getUser();
        if ($curUser->isGuest()) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }
        $res = $task->toArray();

        if ($curUser->hasPermission('tasks.can_manage_tasks') && (isset($_GET['is_edit']) || isset($_GET['mode']))) {
            return new Response(Response::OK, $res);
        }

        $userTask = TaskRefUser::getByUserAndTask($id, $curUser->id);
        if (!$userTask) {
            return new Response(400, ['not_assign' => sprintf('Task "%s" not assign for user "%s"', $id, $curUser->id)]);
        }

        switch ($task->type) {
            case Task::TYPE_TEST:
                break;
            case Task::TYPE_RESOURCE:
                if (!$userTask->status) {
                    $userTask->status = TaskRefUser::STATUS_IN_PROGRESS;
                    $userTask->attempts_count = 1;
                } else {
                    $userTask->attempts_count++;
                }
                $userTask->save();
                break;
            case Task::TYPE_COURSE:
                if($res['status'] == TaskRefUser::STATUS_IN_VERIFICATION){
                    $userTask->status = TaskRefUser::STATUS_IN_VERIFICATION;
                    $userTask->save();
                }
                if (!$userTask->status) {
                    $userTask->status = TaskRefUser::STATUS_IN_PROGRESS;
                    $userTask->save();
                }
                break;
        }

        if ($task->parent_id && $task->type != Task::TYPE_COURSE) {
            $task->checkCourseState($curUser->id);
        }
        $res = $task->toArray();

        return new Response(Response::OK, $res);
    }

    /**
     * @method PUT
     * @action mark-as-complete
     * @json
     */
    public function markAsComplete($id)
    {
        $data = Validator::create((array)$this->request->data);
        $data->field('id')->required();
        if (!$data->validate()) {
            return new Response(400, $data->errors());
        }

        $task = Task::getById($id);
        if (!$task) {
            return new Response(400, ['id' => sprintf('Task "%s" not found', $id)]);
        }

        $curUser = \Bazalt\Auth::getUser();
        if ($curUser->hasPermission('tasks.can_manage_tasks') && (isset($_GET['mode']) || isset($data['mode']))) {

        } else {
            $userTask = TaskRefUser::getByUserAndTask($id, $curUser->id);
            if (!$userTask) {
                return new Response(400, ['not_assign' => sprintf('Task "%s" not assign for user "%s"', $id, $curUser->id)]);
            }

            switch ($task->type) {
                case Task::TYPE_TEST:
                    break;
                case Task::TYPE_RESOURCE:
                    $userTask->status = TaskRefUser::STATUS_FINISHED;
                    $userTask->mark = 100;
                    $userTask->save();
                    break;
                case Task::TYPE_COURSE:
                    break;
            }

            if ($task->parent_id && $task->type != Task::TYPE_COURSE) {
                $task->checkCourseState($curUser->id);
            }
        }

        if ($task->parent_id) {
            $nextTask = $task->getNextSubTask();
            if ($nextTask) {
                return new Response(Response::OK, $nextTask->toArray());
            }
        }

        return new Response(Response::OK, null);
    }

    /**
     * @method POST
     * @method PUT
     * @json
     */
    public function saveItem($id)
    {
        $data = Validator::create((array)$this->request->data);
        $data->field('id')->required();
        $data->field('title')->required();
        if (!$data->validate()) {
            return new Response(400, $data->errors());
        }

        $task = Task::getById($id);
        if (!$task) {
            return new Response(400, ['id' => sprintf('Task "%s" not found', $id)]);
        }

        $curUser = \Bazalt\Auth::getUser();
        if (!$curUser->hasPermission('tasks.can_manage_tasks')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }

        $task->title = $data['title'];
        $task->description = $data['description'];
        $task->type = isset($data['type']) ? $data['type'] : '';
        $task->element_id = isset($data['element_id']) ? $data['element_id'] : '';
        $task->threshold = isset($data['threshold']) ? (int)$data['threshold'] : '';
        if ($task->type == Task::TYPE_COURSE) {
            $task->parent_id = $task->id;
        }
        $task->save();

        $res = $task->toArray();

        return new Response(Response::OK, $res);
    }

    /**
     * @method DELETE
     * @provides application/json
     * @json
     * @return \Tonic\Response
     */
    public function deleteItem($id)
    {
        $curUser = \Bazalt\Auth::getUser();
        if (!$curUser->hasPermission('tasks.can_manage_tasks')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }

        $item = Task::getById((int)$id);

        if (!$item) {
            return new Response(400, ['id' => sprintf('Task "%s" not found', $id)]);
        }
        $item->is_deleted = true;
        $item->save();
        return new Response(200, true);
    }


    /**
     * @method GET
     * @action getUsers
     * @json
     */
    public function getUsers($id)
    {
        $curUser = \Bazalt\Auth::getUser();
        if (!$curUser->hasPermission('tasks.can_manage_tasks')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }

        $collection = TaskRefUser::getUsers($id);

        // table configuration
        $table = new \Bazalt\Rest\Collection($collection);

        $table
            ->sortableBy('id')
            ->sortableBy('fullname', function ($collection, $columnName, $direction) {
                $c = 'CASE WHEN (t.user_id IS NULL) THEN 0 ELSE 1 END';
                $collection->orderBy($c.' DESC, u.secondname ' . $direction . ', u.firstname ' . $direction . ', u.patronymic ' . $direction . ', u.login ' . $direction . '');
            })
            ->sortableBy('login', function ($collection, $columnName, $direction) {
                $c = 'CASE WHEN (t.user_id IS NULL) THEN 0 ELSE 1 END';
                $collection->orderBy($c.' DESC, u.'.$columnName.' ' . $direction . '');
            })
            ->sortableBy('email', function ($collection, $columnName, $direction) {
                $c = 'CASE WHEN (t.user_id IS NULL) THEN 0 ELSE 1 END';
                $collection->orderBy($c.' DESC, u.'.$columnName.' ' . $direction . '');
            })
            ->sortableBy('created_at', function ($collection, $columnName, $direction) {
                $c = 'CASE WHEN (t.user_id IS NULL) THEN 0 ELSE 1 END';
                $collection->orderBy($c.' DESC, u.'.$columnName.' ' . $direction . '');
            })
            ->sortableBy('last_activity', function ($collection, $columnName, $direction) {
                $c = 'CASE WHEN (t.user_id IS NULL) THEN 0 ELSE 1 END';
                $collection->orderBy($c.' DESC, u.'.$columnName.' ' . $direction . '');
            })
            ->filterBy('fullname', function ($collection, $columnName, $value) {
                if ($value) {
                    $value = '%' . strtolower($value) . '%';
                    $collection->andWhere('(LOWER(u.firstname) LIKE ? OR LOWER(u.secondname) ' .
                        'LIKE ? OR LOWER(u.patronymic) LIKE ?)', array($value, $value, $value)
                    );
                }
            })
            ->filterBy('email', function ($collection, $columnName, $value) {
                if ($value) {
                    $value = '%' . strtolower($value) . '%';
                    $collection->andWhere('(LOWER(u.firstname) LIKE ? OR LOWER(u.secondname) ' .
                        'LIKE ? OR LOWER(u.patronymic) LIKE ? OR LOWER(u.email) LIKE ?)', array($value, $value, $value, $value)
                    );
                }
            })
            ->filterBy('created_at', function ($collection, $columnName, $value) {
                $params = $this->params();
                $collection->andWhere('DATE(u.created_at) BETWEEN ? AND ?', array($params['created_at'][0], $params['created_at'][1]));
            })
            ->filterBy('last_activity', function ($collection, $columnName, $value) {
                $params = $this->params();
                $collection->andWhere('DATE(u.last_activity) BETWEEN ? AND ?', array($params['last_activity'][0], $params['last_activity'][1]));
            })
            ->filterBy('roles', function ($collection, $columnName, $value) {
                if ($value) {
                    $collection
                        ->innerJoin('Bazalt\\Auth\\Model\\RoleRefUser ru', ['user_id', 'u.id'])
                        ->andWhere('ru.role_id = ?', (int)$value)
                        ->groupBy('u.id');
                }
            })
            ->filterBy('tags', function ($collection, $columnName, $value) {
                if (isset($value) && $value != '') {
                    $collection->innerJoin('Modules\\Tags\\Model\\TagRefElement te', ' ON te.element_id = u.id AND te.type = \'user\'');
                    $collection->innerJoin('Modules\\Tags\\Model\\Tag ta', ' ON ta.id = te.tag_id ');
                    $collection->andWhere('LOWER(ta.body) LIKE ?', '%' . mb_strtolower($value) . '%');
                }
            });

        return new Response(Response::OK, $table->fetch($_GET, function ($item, $user) {
            $res = [];
            $tags = TagRefElement::getElementTags($user->id, 'user');
            foreach ($tags as $itm) {
                $res[] = $itm->body;
            }
            $item['tags'] = implode(', ', $res);

            $item['checked'] = (bool)$item['checked'];
            $item['photo_thumb'] = '';
            $photo = $user->setting('photo');
            if ($photo) {
                $config = \Bazalt\Config::container();
                try {
                    $item['photo_thumb'] = $config['thumb.prefix'] . thumb(SITE_DIR . '/..' . $user->setting('photo'), '128x128', ['crop' => true, 'fit' => true]);
                } catch (\Exception $ex) {
                    $res['photo_thumb'] = '';
                }
            }
            $item['created_at'] = strtotime($item['created_at']) . '000';
            $item['last_activity'] = strtotime($item['last_activity']) . '000';
            $arr = [];
            $roles = $user->Roles->get();
            foreach ($roles as $role) {
                $arr[] = $role->title;
            }
            $item['roles'] = implode(', ', $arr);

            return $item;
        }));
    }

    /**
     * @method POST
     * @action assign
     * @json
     */
    public function assignUser($id)
    {
        $data = (array)$this->request->data;

        $curUser = \Bazalt\Auth::getUser();

        if (!$curUser->hasPermission('tasks.can_manage_tasks')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }

        if ($data && $data['checked'] == $data['id']) {
            $err = TaskRefUser::assignUser((int)$id, (int)$data['id']);


            $user = User::getById((int)$data['id']);
            $task = Task::getById((int)$id);
            $link = null;

            switch ($task->type) {
                case 'course' :
                    $link = 'http://' . \Bazalt\Site::get()->domain . '/courses/execute/' . $task->id . '?restore=true';
                    break;
                case 'test' :
                    $link = 'http://' . \Bazalt\Site::get()->domain . '/test/execute/' . $task->id;
                    break;
                case 'resource' :
                    $link = 'http://' . \Bazalt\Site::get()->domain . '/pages/execute/' . $task->id;
                    break;
            }


            $arr = ['email' => $user->email,
                'fullname' => $user->getName(),
                'task' => $task->title,
                'link' => $link
            ];

            \Modules\Notification\Broker::onNotification('Tasks.Assign.User', $arr);


            return new Response(Response::OK, ['data' => $err]);
        }

        if ($data && $data['checked'] == false) {
            TaskRefUser::unassignUser((int)$id, (int)$data['id']);
            return new Response(Response::OK, ['data' => []]);
        }

        return new Response(Response::FORBIDDEN, 'Error occurred');
    }


    /**
     * @method POST
     * @action assignMulti
     * @json
     */
    public function assignUserMulti($id)
    {
        $data = (array)$this->request->data;
        $curUser = \Bazalt\Auth::getUser();

        if (!$curUser->hasPermission('tasks.can_manage_tasks')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }
        $err = [];
        if (count($data['ids']) > 0) {
            if ($data['type'] == 'assign') {
                foreach ($data['ids'] as $item) {
                    $err = array_merge($err, TaskRefUser::assignUser((int)$id, (int)$item));

                    $user = User::getById((int)$item);
                    $task = Task::getById((int)$id);
                    $link = null;

                    switch ($task->type) {
                        case 'course' :
                            $link = 'http://' . \Bazalt\Site::get()->domain . '/courses/execute/' . $task->id . '?restore=true';
                            break;
                        case 'test' :
                            $link = 'http://' . \Bazalt\Site::get()->domain . '/test/execute/' . $task->id;
                            break;
                        case 'resource' :
                            $link = 'http://' . \Bazalt\Site::get()->domain . '/pages/execute/' . $task->id;
                            break;
                    }

                    $arr = ['email' => $user->email,
                        'fullname' => $user->getName(),
                        'task' => $task->title,
                        'link' => $link
                    ];

                    \Modules\Notification\Broker::onNotification('Tasks.Assign.User', $arr);

                }
            } elseif ($data['type'] == 'unassign') {
                foreach ($data['ids'] as $item) {
                    TaskRefUser::unassignUser((int)$id, (int)$item);
                }
            }

        }

        return new Response(Response::OK, ['over_limit_count' => count($err)]);
    }

    /**
     * @method GET
     * @action get-test-report
     * @json
     */
    public function getTestReport($id)
    {

        $task = Task::getById($id);
        if (!$task) {
            return new Response(400, ['id' => sprintf('Task "%s" not found', $id)]);
        }

        $collection = $task->getTestReportCollection((int)$id);
//        echo $collection->toSql();exit;

        $table = new \Bazalt\Rest\Collection($collection);
        $table
            ->sortableBy('user_full_name', function ($collection, $columnName, $direction) {
                $collection->orderBy('u.firstname ' . $direction . ', u.secondname ' . $direction . ', u.patronymic ' . $direction . ', u.login ' . $direction);
            })
            ->filterBy('user_full_name', function ($collection, $columnName, $value) {
                if ($value) {
                    $value = '%' . strtolower($value) . '%';
                    $collection->andWhere('(LOWER(u.firstname) LIKE ? OR LOWER(u.secondname) ' .
                        'LIKE ? OR LOWER(u.patronymic) LIKE ? OR LOWER(u.login) LIKE ?)', array($value, $value, $value, $value)
                    );
                }
            })
            ->filterBy('user_id', function ($collection, $columnName, $value) {
                if ($value) {
                    $collection->andWhere('u.id = ?', (int)$value);
                }
            })

            ->sortableBy('task_status', function ($collection, $columnName, $direction) {
                $collection->orderBy('r.status ' . $direction);
            })
            ->filterBy('task_status', function ($collection, $columnName, $value) {
                if ($value) {
                    $collection->andWhere('r.status = ?', $value);
                }
            })


            ->sortableBy('task_start_date', function ($collection, $columnName, $direction) {
//                exit('O_o');
                $collection->orderBy('r.created_at ' . $direction);
            })
            ->filterBy('task_start_date', function ($collection, $columnName, $value) {
                if ($value) {
                    $params = $this->params();
                    $collection->andWhere('r.created_at BETWEEN ? AND ?', $params['task_start_date']);
                }
            })

            ->sortableBy('task_finish_date', function ($collection, $columnName, $direction) {
                $collection->orderBy('r.updated_at ' . $direction);
            })
            ->filterBy('task_finish_date', function ($collection, $columnName, $value) {
                if ($value) {
                    $params = $this->params();
                    $collection->andWhere('r.updated_at BETWEEN ? AND ?', $params['task_finish_date']);
                }
            })


            ->sortableBy('task_mark', function ($collection, $columnName, $direction) {
                $collection->orderBy('r.mark ' . $direction);
            })


            ->filterBy('tags', function ($collection, $columnName, $value) {
                if (isset($value) && $value != '') {
                    $collection->innerJoin('Modules\\Tags\\Model\\TagRefElement te', ' ON te.element_id = r.user_id AND te.type = \'user\' ');
                    $collection->innerJoin('Modules\\Tags\\Model\\Tag t', ' ON t.id = te.tag_id ');
                    $collection->andWhere('LOWER(t.body) LIKE ?', '%' . mb_strtolower($value) . '%');
                }
            });


        $table->exec($this->params());

        $return = array();
        try {
            $result = $collection->fetchPage('\\Bazalt\\Auth\\Model\\User');
        } catch(\Bazalt\ORM\Exception\Collection $ex) {//Invalid page
            $collection->page(1);
            $result = $collection->fetchPage('\\Bazalt\\Auth\\Model\\User');
        }


        $usersAttempts = array();
        $resRef = array();
        foreach ($result as $k => $item) {
            if(!isset($usersAttempts[$item->user_id])) {
                $usersAttempts[$item->user_id] = array();
            }
            $usersAttempts[$item->user_id] []= $item->result_id;
            $res = [
                'user_id' => $item->user_id,
                'user_full_name' => $item->getName(),
                'task_start_date' => $item->start_date,
                'task_finish_date' => $item->finish_date,
                'task_status' => $item->status,
                'task_mark' => $item->mark,
                'task_attempt_number' => 0,
                'task_id' => $item->task_id,
                'parent_id' => $item->parent_id,
                'task_result_id' => $item->result_id
            ];
            $tagsArr = [];
            $tags = TagRefElement::getElementTags($item->user_id, 'user');
            foreach ($tags as $itm) {
                $tagsArr [] = $itm->body;
            }
            $res['tags'] = implode(',', $tagsArr);
            $resRef[$item->result_id] = count($return);
            $return[] = $res;
        }
        foreach($usersAttempts as $uid => $usersAttempts) {
            sort($usersAttempts);
            $i = 1;
            foreach($usersAttempts as $resId) {
                $k = $resRef[$resId];
                $return[$k]['task_attempt_number'] = $i++;
            }
        }
        $data = array(
            'data' => $return,
            'pager' => array(
                'current' => $collection->page(),
                'count' => $collection->getPagesCount(),
                'total' => $collection->count(),
                'countPerPage' => $collection->countPerPage()
            )
        );
        return new Response(Response::OK, $data);
    }

    /**
     * @method GET
     * @action get-test-attempts
     * @json
     */
    public function getTestAttempts($id)
    {
        $curUser = \Bazalt\Auth::getUser();
        if (!($curUser->hasPermission('tasks.can_manage_tasks') || $curUser->hasPermission('tests.can_manage_attempts'))) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }

        $task = Task::getById($id);
        if (!$task) {
            return new Response(400, ['id' => sprintf('Task "%s" not found', $id)]);
        }

        $collection = $task->getTestAttemptsCollection((int)$id);

        $table = new \Bazalt\Rest\Collection($collection);
        $table
            ->sortableBy('user_full_name', function ($collection, $columnName, $direction) {
                $collection->orderBy('u.firstname ' . $direction . ', u.secondname ' . $direction . ', u.patronymic ' . $direction . ', u.login ' . $direction);
            })
            ->filterBy('user_full_name', function ($collection, $columnName, $value) {
                if ($value) {
                    $value = '%' . strtolower($value) . '%';
                    $collection->andWhere('(LOWER(u.firstname) LIKE ? OR LOWER(u.secondname) ' .
                        'LIKE ? OR LOWER(u.patronymic) LIKE ? OR LOWER(u.login) LIKE ?)', array($value, $value, $value, $value)
                    );
                }
            })

            ->sortableBy('task_status', function ($collection, $columnName, $direction) {
                $collection->orderBy('tu.status ' . $direction);
            })
            ->filterBy('task_status', function ($collection, $columnName, $value) {
                if ($value) {
                    $collection->andWhere('tu.status = ?', $value);
                }
            })

            ->sortableBy('task_start_date', function ($collection, $columnName, $direction) {
                $collection->orderBy('tu.created_at ' . $direction);
            })
            ->filterBy('task_start_date', function ($collection, $columnName, $value) {
                if ($value) {
                    $params = $this->params();
                    $collection->andWhere('tu.created_at BETWEEN ? AND ?', $params['task_start_date']);
                }
            });


        $table->exec($this->params());

        $return = array();
        try {
            $result = $collection->fetchPage('\\Bazalt\\Auth\\Model\\User');
        } catch(\Bazalt\ORM\Exception\Collection $ex) {//Invalid page
            $collection->page(1);
            $result = $collection->fetchPage('\\Bazalt\\Auth\\Model\\User');
        }
//        print_r($result);exit;

        foreach ($result as $k => $item) {
            $res = [
                'user_id' => $item->user_id,
                'user_full_name' => $item->getName(),
                'task_created_at' => $item->created_at,
                'task_id' => (int)$item->task_id,
                'task_parent_id' => (int)$item->task_parent_id,
                'task_mark' => $item->mark,
                'task_status' => $item->status,
                'task_attempts_limit' => $item->attempts_limit,
                'task_attempts_count' => $item->attempts_count
            ];
            $tags = TagRefElement::getElementTags($item->user_id, 'user');
            $tagsArr = [];
            foreach ($tags as $itm) {
                $tagsArr [] = $itm->body;
            }
            $res['tags'] = implode(',', $tagsArr);

            $return[$k] = $res;
        }
        $data = array(
            'data' => $return,
            'pager' => array(
                'current' => $collection->page(),
                'count' => $collection->getPagesCount(),
                'total' => $collection->count(),
                'countPerPage' => $collection->countPerPage()
            )
        );
        return new Response(Response::OK, $data);
    }

    /**
     * @method PUT
     * @action add-attempt
     * @json
     */
    public function addAttempt($id)
    {
        $data = Validator::create((array)$this->request->data);
        $data->field('id')->required();
        $data->field('user_id')->required();
        if (!$data->validate()) {
            return new Response(400, $data->errors());
        }

        $curUser = \Bazalt\Auth::getUser();
        if (!($curUser->hasPermission('tasks.can_manage_tasks') && $curUser->hasPermission('tests.can_manage_attempts'))) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }

        $task = Task::getById($id);
        if (!$task) {
            return new Response(400, ['id' => sprintf('Task "%s" not found', $id)]);
        }
        $userTask = TaskRefUser::getByUserAndTask($id, $data['user_id']);
        $userTask->attempts_limit++;
        $userTask->save();

        return new Response(Response::OK, null);
    }


    /**
     * @method PUT
     * @action add-attempt-multi
     * @json
     */
    public function addAttemptMulti($id)
    {
        $data = Validator::create((array)$this->request->data);
        $data->field('id')->required();
        if (!$data->validate()) {
            return new Response(400, $data->errors());
        }

        $curUser = \Bazalt\Auth::getUser();
        if (!($curUser->hasPermission('tasks.can_manage_tasks') && $curUser->hasPermission('tests.can_manage_attempts'))) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }

        $task = Task::getById($id);
        if (!$task) {
            return new Response(400, ['id' => sprintf('Task "%s" not found', $id)]);
        }
        if(count($data['user_ids']) > 0){

            foreach($data['user_ids'] as $userId){
                $userTask = TaskRefUser::getByUserAndTask($id, $userId);
                $userTask->attempts_limit++;
                $userTask->save();
            }

        }

        return new Response(Response::OK, null);
    }

    /**
     * @method GET
     * @action get-resource-report
     * @json
     */
    public function getResourceReport($id)
    {

        $task = Task::getById($id);
        if (!$task) {
            return new Response(400, ['id' => sprintf('Task "%s" not found', $id)]);
        }

        $collection = $task->getResourceReportCollection((int)$id);

        $table = new \Bazalt\Rest\Collection($collection);
        $table
            ->sortableBy('user_full_name', function ($collection, $columnName, $direction) {
                $collection->orderBy('u.firstname ' . $direction . ', u.secondname ' . $direction . ', u.patronymic ' . $direction . ', u.login ' . $direction);
            })
            ->filterBy('user_full_name', function ($collection, $columnName, $value) {
                if ($value) {
                    $value = '%' . strtolower($value) . '%';
                    $collection->andWhere('(LOWER(u.firstname) LIKE ? OR LOWER(u.secondname) ' .
                        'LIKE ? OR LOWER(u.patronymic) LIKE ? OR LOWER(u.login) LIKE ?)', array($value, $value, $value, $value)
                    );
                }
            })
            ->filterBy('user_id', function ($collection, $columnName, $value) {
                if ($value) {
                    $collection->andWhere('u.id = ?', (int)$value);
                }
            })

            ->sortableBy('task_status', function ($collection, $columnName, $direction) {
                $collection->orderBy('r.status ' . $direction);
            })
            ->filterBy('task_status', function ($collection, $columnName, $value) {
                if ($value) {
                    $collection->andWhere('r.status = ?', $value);
                }
            })


            ->sortableBy('task_finish_date', function ($collection, $columnName, $direction) {
                $collection->orderBy('r.updated_at ' . $direction);
            })
            ->filterBy('task_finish_date', function ($collection, $columnName, $value) {
                if ($value) {
                    $params = $this->params();
                    $collection->andWhere('r.updated_at BETWEEN ? AND ?', $params['task_finish_date']);
                }
            })


            ->sortableBy('task_mark', function ($collection, $columnName, $direction) {
                $collection->orderBy('r.mark ' . $direction);
            })


            ->filterBy('tags', function ($collection, $columnName, $value) {
                if (isset($value) && $value != '') {
                    $collection->innerJoin('Modules\\Tags\\Model\\TagRefElement te', ' ON te.element_id = r.user_id AND te.type = \'user\' ');
                    $collection->innerJoin('Modules\\Tags\\Model\\Tag ta', ' ON ta.id = te.tag_id ');
                    $collection->andWhere('LOWER(ta.body) LIKE ?', '%' . mb_strtolower($value) . '%');
                }
            });


        $table->exec($this->params());

        $return = array();
        try {
            $result = $collection->fetchPage('\\Bazalt\\Auth\\Model\\User');
        } catch(\Bazalt\ORM\Exception\Collection $ex) {//Invalid page
            $collection->page(1);
            $result = $collection->fetchPage('\\Bazalt\\Auth\\Model\\User');
        }

        $usersAttempts = array();
        $resRef = array();
        foreach ($result as $k => $item) {
            if(!isset($usersAttempts[$item->user_id])) {
                $usersAttempts[$item->user_id] = array();
            }
            $usersAttempts[$item->user_id] []= $item->result_id;
            $res = [
                'user_id' => $item->user_id,
                'user_full_name' => $item->getName(),
                'task_finish_date' => $item->updated_at,
                'task_status' => $item->status,
                'task_mark' => $item->mark,
                'task_attempt_number' => 0,
                'task_id' => $item->task_id,
                'parent_id' => $item->parent_id,
                'task_result_id' => $item->result_id
            ];
            $tagsArr = [];
            $tags = TagRefElement::getElementTags($item->user_id, 'user');
            foreach ($tags as $itm) {
                $tagsArr [] = $itm->body;
            }
            $res['tags'] = implode(',', $tagsArr);
            $resRef[$item->result_id] = count($return);
            $return[] = $res;
        }
        foreach($usersAttempts as $uid => $usersAttempts) {
            sort($usersAttempts);
            $i = 1;
            foreach($usersAttempts as $resId) {
                $k = $resRef[$resId];
                $return[$k]['task_attempt_number'] = $i++;
            }
        }
        $data = array(
            'data' => $return,
            'pager' => array(
                'current' => $collection->page(),
                'count' => $collection->getPagesCount(),
                'total' => $collection->count(),
                'countPerPage' => $collection->countPerPage()
            )
        );
        return new Response(Response::OK, $data);
    }
}
