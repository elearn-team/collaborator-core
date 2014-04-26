<?php

namespace Modules\Tasks\Webservice;

use Bazalt\Data\Validator;
use Tonic\Response;
use Modules\Tasks\Model\Task;
use Modules\Courses\Model\Course;
use Modules\Resources\Model\Page;
use Modules\Tests\Model\Test;
use Modules\Tags\Model\TagRefElement;
use Modules\Courses\Model\CourseTestSetting;


/**
 * TestsResource
 *
 * @uri /tasks
 */
class TasksResource extends \Bazalt\Rest\Resource
{
    /**
     * @method GET
     * @action my
     * @json
     */
    public function getMyList()
    {
        $curUser = \Bazalt\Auth::getUser();
        if ($curUser->isGuest()) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }

        $collection = Task::getUserCollection($curUser, isset($_GET['courses']) ? true : false);
        // table configuration
        $table = new \Bazalt\Rest\Collection($collection);
        $table
            ->sortableBy('id')
            ->sortableBy('title')
            ->sortableBy('type')
            ->sortableBy('created_at')
            ->sortableBy('status')
            ->sortableBy('mark')
            ->sortableBy('attempts_count')

            ->filterBy('title', function ($collection, $columnName, $value) {
                if ($value) {
                    $value = '%' . strtolower($value) . '%';
                    $collection->andWhere('LOWER(t.title) LIKE ?', $value);
                }
            })
            ->filterBy('status', function ($collection, $columnName, $value) {

                if ($value) {
                    if ($value === 'not_started') {
                        $collection->andWhere('status IS NULL');
                    } else {
                        $value = '%' . strtolower($value) . '%';
                        $collection->andWhere('LOWER(status) LIKE ?', $value);
                    }
                }
            })
            ->filterBy('mark', function ($collection, $columnName, $value) {
                if ($value) {
                    $collection->andWhere('mark = ?', (int)$value);
                }
            })
            ->filterBy('attempts_count', function ($collection, $columnName, $value) {
                if ($value) {
                    $collection->andWhere('attempts_count = ?', (int)$value);
                }
            })
            ->filterBy('type', function ($collection, $columnName, $value) {
                if ($value) {
                    $collection->andWhere('type = ?', $value);
                }
            })
            ->filterBy('created_at', function ($collection, $columnName, $value) {
                $params = $this->params();
                $collection->andWhere('DATE(t.created_at) BETWEEN ? AND ?', $params['created_at']);
            });

        $res = $table->fetch($this->params(), function ($item) {
            $element = null;
            switch ($item['type']) {
                case Task::TYPE_COURSE:
                    $element = Course::getById((int)$item['element_id']);
                    break;
                case Task::TYPE_TEST:
                    $element = Test::getById((int)$item['element_id']);
                    break;
                case Task::TYPE_RESOURCE:
                    $element = Page::getById((int)$item['element_id']);
                    break;
            }
            if ($element) {
                $item['element'] = $element->toArray();
            }
            return $item;
        });
        return new Response(Response::OK, $res);
    }


    /**
     * @method GET
     * @json
     */
    public function getList()
    {
        $curUser = \Bazalt\Auth::getUser();
        if (!$curUser->hasPermission('tasks.can_manage_tasks')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }

        $collection = Task::getCollection();
        // table configuration
        $table = new \Bazalt\Rest\Collection($collection);
        $table
            ->sortableBy('id')
            ->sortableBy('title')
            ->sortableBy('type')

            ->filterBy('id', function ($collection, $columnName, $value) {
                $collection->andWhere('id = ?', (int)$value);
            })
            ->filterBy('title', function ($collection, $columnName, $value) {
                if ($value) {
                    $value = '%' . strtolower($value) . '%';
                    $collection->andWhere('LOWER(title) LIKE ?', $value);
                }
            })
            ->filterBy('type', function ($collection, $columnName, $value) {
                if ($value) {
                    $value = '%' . strtolower($value) . '%';
                    $collection->andWhere('LOWER(type) LIKE ?', $value);
                }
            })
            ->filterBy('created_at', function ($collection, $columnName, $value) {
                if ($value) {
                    if (is_array($value)) {
                        $collection->andWhere('DATE(f.created_at) BETWEEN ? AND ?', array($value[0], $value[1]));
                    } else {
                        $collection->andWhere('DATE(f.created_at) = ?', $value);
                    }
                }
            });

        $res = $table->fetch($_GET, function ($item) {
            $element = null;
            switch ($item['type']) {
                case Task::TYPE_RESOURCE:
                    $element = Page::getById((int)$item['element_id']);
                    break;
                case Task::TYPE_COURSE:
                    $element = Course::getById((int)$item['element_id']);
                    break;
            }
            if ($element) {
                $item['element'] = $element->toArray();
            }
            return $item;
        });


        return new Response(Response::OK, $res);
    }

    /**
     * @method POST
     * @method PUT
     * @json
     */
    public function createItem()
    {
        $data = Validator::create((array)$this->request->data);
        $data->field('title')->required();
        $data->field('type')->required();
        $data->field('element_id')->required();
        if (!$data->validate()) {
            return new Response(400, $data->errors());
        }

        $curUser = \Bazalt\Auth::getUser();
        if (!$curUser->hasPermission('tasks.can_manage_tasks')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }

        $task = Task::create();
        $task->title = $data['title'];
        $task->description = $data['description'];
        $task->type = $data['type'];
        $task->element_id = (int)$data['element_id'];
        $task->threshold = isset($data['threshold']) ? (int)$data['threshold'] : '';

        if ($task->type == Task::TYPE_COURSE) {
            $task->saveForCourse();
        } else {
            $task->save();
        }

        $res = $task->toArray();

        return new Response(Response::OK, $res);
    }

    /**
     * @method POST
     * @action deleteMulti
     * @provides application/json
     * @json
     * @return \Tonic\Response
     */
    public function deleteMulti()
    {
        $data = Validator::create((array)$this->request->data);

        if (!$data->validate()) {
            return new Response(400, $data->errors());
        }

        $curUser = \Bazalt\Auth::getUser();
        if (!$curUser->hasPermission('courses.can_manage_courses')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }

        foreach ($data['ids'] as $item) {
            $item = Task::getById((int)$item);
            if ($item) {
                $item->is_deleted = true;
                $item->save();
            }
        }

        return new Response(200, true);
    }

    /**
     * @method GET
     * @action get-all-tests-report
     * @json
     */
    public function getAllTestsReport()
    {

        $collection = Task::getAllTestsReportCollection();

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
     * @action get-tests
     * @json
     */
    public function getTests()
    {
        $items = Task::getTests();
        $ret = [];
        foreach ($items as $item) {
            $ret [] = $item->toArray();
        }
        return new Response(Response::OK, ['data' => $ret]);
    }


}
