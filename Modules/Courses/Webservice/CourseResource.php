<?php

namespace Modules\Courses\Webservice;

use Bazalt\Data\Validator;
use Tonic\Response;
use Modules\Courses\Model\Course;
use Modules\Courses\Model\CoursePlan;
use Modules\Courses\Model\CourseElement;
use Modules\Courses\Model\File;
use Modules\Tasks\Model\Task;
use Modules\Tasks\Model\TaskRefUser;
use Modules\Tags\Model\Tag;
use Modules\Tags\Model\TagRefElement;
use Modules\Tags\Model\TagRefUser;

/**
 * CourseResource
 *
 * @uri /courses/:id
 */
class CourseResource extends \Bazalt\Rest\Resource
{
    /**
     * @method GET
     * @json
     */
    public function getItem($id)
    {
        $course = Course::getById($id);
        if (!$course) {
            return new Response(400, ['id' => sprintf('Course "%s" not found', $id)]);
        }
        $res = $course->toArray();
        if (isset($_GET['include_materials'])) {
//            $curUser = \Bazalt\Auth::getUser();
//            if (!$curUser->hasPermission('courses.can_manage_courses')) {
            $materials = CourseElement::getList($id, null, true);
//            } else {
//                $materials = CourseElement::getList($id);
//            }

            $res['materials'] = [];
            foreach ($materials as $material) {
                $res['materials'] [] = [
                    'id' => $material->id,
                    'element_id' => $material->element_id,
                    'type' => $material->type,
                    'sub_type' => $material->sub_type,
                    'title' => $material->title
                ];
            }
        }
        if (isset($_GET['include_plan'])) {
            $res['plan'] = [];
            $planItems = CoursePlan::getList($id);
            foreach ($planItems as $planItem) {
                $res['plan'] [] = [
                    'id' => $planItem->id,
                    'element_id' => $planItem->element_id,
                    'type' => $planItem->type,
                    'sub_type' => $planItem->sub_type,
                    'title' => $planItem->title
                ];
            }
        }

        return new Response(Response::OK, $res);
    }

    /**
     * @method GET
     * @action export
     * @json
     */
    public function export($id)
    {
        $course = Course::getById($id);
        if (!$course) {
            return new Response(400, ['id' => sprintf('Course "%s" not found', $id)]);
        }
        $course->export();
    }

    /**
     * @method GET
     * @action get-report
     * @json
     */
    public function getReport($id)
    {
        $course = Course::getById($id);
        if (!$course) {
            return new Response(400, ['id' => sprintf('Course "%s" not found', $id)]);
        }

        if (isset($_GET['taskId'])) {
            $collection = $course->getReportCollection((int)$_GET['taskId']);
        } else {
            $collection = $course->getReportCollection();
        }

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
            })

            ->sortableBy('task_finish_date', function ($collection, $columnName, $direction) {
                $collection->orderBy('tu.updated_at ' . $direction);
            })
            ->filterBy('task_finish_date', function ($collection, $columnName, $value) {
                if ($value) {
                    $params = $this->params();
                    $collection->andWhere('tu.updated_at BETWEEN ? AND ?', $params['task_start_date']);
                }
            })
            ->filterBy('tags', function ($collection, $columnName, $value) {
                if (isset($value) && $value != '') {
                    $collection->innerJoin('Modules\\Tags\\Model\\TagRefElement te', ' ON te.element_id = u.id AND te.type = \'user\'');
                    $collection->innerJoin('Modules\\Tags\\Model\\Tag ta', ' ON ta.id = te.tag_id ');
                    $collection->andWhere('LOWER(ta.body) LIKE ?', '%' . mb_strtolower($value) . '%');
                }
            });
        $plan = [];
        $planItems = CoursePlan::getList($id);
        foreach ($planItems as $planItem) {
            $plan [] = [
                'id' => $planItem->type . '_' . $planItem->element_id,
                'title' => $planItem->title
            ];
        }


        $table->exec($this->params());
        $return = array();

        try {
            $result = $collection->fetchPage('\\Bazalt\\Auth\\Model\\User');
        } catch(\Bazalt\ORM\Exception\Collection $ex) {//Invalid page
            $collection->page(1);
            $result = $collection->fetchPage('\\Bazalt\\Auth\\Model\\User');
        }

        foreach ($result as $k => $item) {
            $courseTask = new \stdClass();
            $courseTask->element_id = $item->course_id;
            $courseTask->id = $item->task_id;
            $mark = $item->status == TaskRefUser::STATUS_FINISHED ? $item->mark : Task::calcCoursePercent($courseTask, $item->user_id);
            $res = [
                'user_id' => $item->user_id,
                'user_full_name' => $item->getName(),
                'task_start_date' => $item->start_date,
                'task_finish_date' => $item->finish_date,
                'task_status' => $item->status,
                'task_mark' => $mark
            ];
            foreach ($planItems as $planItem) {
                $res[$planItem->type . '_' . $planItem->element_id] =
                    Task::getSubTaskMark($item->task_id, $planItem->type, $planItem->element_id, $item->user_id);
            }
            $arr = [];
            $tags = TagRefElement::getElementTags($item->user_id, 'user');
            foreach ($tags as $itm) {
                $arr[] = $itm->body;
            }
            $res['tags'] = implode(', ', $arr);
            $return[$k] = $res;
        }

        $data = array(
            'data' => $return,
            'plan' => $plan,
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
    public function getTests($id)
    {
        $course = Course::getById($id);
        if (!$course) {
            return new Response(400, ['id' => sprintf('Course "%s" not found', $id)]);
        }

        $items = $course->getTestTasks();
        $ret = [];
        foreach ($items as $item) {
            $ret [] = $item->toArray();
        }
        return new Response(Response::OK, ['data' => $ret]);
    }

    /**
     * @method POST
     * @method PUT
     * @json
     */
    public function saveItem($id = null)
    {
        $data = Validator::create((array)$this->request->data);
        if ($id) {
            $data->field('id')->required();
        }
        $data->field('title')->required();
        if (!$data->validate()) {
            return new Response(400, $data->errors());
        }

        if ($id) {
            $course = Course::getById($id);
        } else {
            $course = Course::create();
        }
        if (!$course) {
            return new Response(400, ['id' => sprintf('Course "%s" not found', $id)]);
        }

        $curUser = \Bazalt\Auth::getUser();
        if (!$curUser->hasPermission('courses.can_manage_courses')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }

        $course->title = $data['title'];
        $course->description = $data['description'];
        $course->score_employment = $data['score_employment'];
        $course->course_length = $data['course_length'];

        $course->is_published = (bool)$data['is_published'];
        $course->category_id = $data['category_id'];

        $course->code = $data['code'];
        $course->annotation = $data['annotation'];
        $course->icon = isset($data['icon']) ? $data['icon'] : '';
        $course->finish_type = isset($data['finish_type']) ? $data['finish_type'] : '';
        $course->start_type = isset($data['start_type']) ? $data['start_type'] : '';
        $course->registration_for_course = $data['registration_for_course'];
        $course->save();

        TagRefElement::clearTags($course->id, Task::TYPE_COURSE);
        if (isset($data['tags'])) {
            foreach ($data['tags'] as $itm) {
                Tag::addTag($course->id, Task::TYPE_COURSE, $itm);
            }
        }

        $ids = [];
        if ($data['files']) {
            foreach ($data['files'] as $fileData) {
                $fileArr = (array)$fileData;
                if (isset($fileArr['error'])) {
                    continue;
                }
                $file = isset($fileArr['id']) ? File::getById((int)$fileArr['id']) : File::create();

                $file->course_id = $course->id;
                $file->name = $fileArr['name'];
                $file->url = $fileArr['url'];
                $file->save();

                $course->Files->add($file);

                $ids [] = $file->id;
            }
        }
        $course->Files->clearRelations($ids);

        CoursePlan::clearIsFinalTest($course->id);
        if ($course->finish_type == Course::FINISH_TYPE_BY_TEST && isset($data['final_test_id'])) {
            $planItm = CoursePlan::getPlanItem((int)$data['final_test_id'], 'test', $course->id);
            if ($planItm) {
                $planItm->is_determ_final_mark = true;
                $planItm->save();
            }
        }

        $res = $course->toArray();

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
        $item = Course::getById((int)$id);

        if (!$item) {
            return new Response(400, ['id' => sprintf('Course "%s" not found', $id)]);
        }
        $item->is_deleted = true;
        $item->save();
        return new Response(200, true);
    }

    /**
     * @method GET
     * @action getAssignUsers
     * @json
     * @return \Tonic\Response
     */
    public function getAssignUsers($id)
    {
        $curUser = \Bazalt\Auth::getUser();
        if (!$curUser->hasPermission('courses.can_manage_courses')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }

        $course = Course::getById($id);
        if (!$course) {
            return new Response(400, ['id' => sprintf('Course "%s" not found', $id)]);
        }

        $collection = $course->getAssignUsers();
        $table = new \Bazalt\Rest\Collection($collection);
        $table
            ->sortableBy('email')
            ->sortableBy('fullname', function ($collection, $columnName, $direction) {
                $collection->orderBy('u.firstname, u.secondname, u.patronymic');
            })

            ->filterBy('fullname', function ($collection, $columnName, $value) {
                if ($value) {
                    $value = '%' . strtolower($value) . '%';
                    $collection->andWhere('(LOWER(u.firstname) LIKE ? OR LOWER(u.secondname) ' .
                        'LIKE ? OR LOWER(u.patronymic) LIKE ?)', array($value, $value, $value)
                    );
                }
            })
            ->filterBy('last_activity', function ($collection, $columnName, $value) {
                $params = $this->params();
                $collection->andWhere('DATE(u.last_activity) BETWEEN ? AND ?', $params['last_activity']);
            })
            ->filterBy('email', function ($collection, $columnName, $value) {
                if ($value) {
                    $value = '%' . strtolower($value) . '%';
                    $collection->andWhere('LOWER(u.email) LIKE ?', $value);
                }
            })
            ->filterBy('tags', function ($collection, $columnName, $value) {
                if (isset($value) && $value != '') {
                    $collection->innerJoin('Modules\\Tags\\Model\\TagRefElement te', ' ON te.element_id  = u.id');
                    $collection->innerJoin('Modules\\Tags\\Model\\Tag ta', ' ON ta.id = te.tag_id ');
                    $collection->andWhere('LOWER(ta.body) LIKE ?', '%' . mb_strtolower($value) . '%');
                }
            });


        $res = $table->fetch($this->params(), function ($item, $user) {
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
            $res = [];
            $tags = TagRefElement::getElementTags($user->id, 'user');

            foreach ($tags as $itm) {
                $res[] = $itm->body;
            };
            $item['tags'] = implode(', ', $res);

            return $item;
        });

        return new Response(Response::OK, $res);
    }
}
