<?php

namespace Modules\Tests\Webservice;

use Bazalt\Data\Validator;
use Modules\Courses\Model\Course;
use Modules\Tests\Model\Answer;
use Tonic\Response;
use Modules\Tests\Model\Test;
use Modules\Tags\Model\Tag;
use Modules\Tags\Model\TagRefElement;
use Modules\Tasks\Model\Task;
use Modules\Tests\Model\AnswerResultFile;

/**
 * TestsResource
 *
 * @uri /tests
 */
class TestsResource extends \Bazalt\Rest\Resource
{
    /**
     * @method GET
     * @json
     *
     * @api {get} /tests Отримати список тестів
     * @apiName getList
     * @apiGroup Tests-Test
     * @apiSuccess {Object[]} tests Список тестів
     * @apiSuccess {String} tests.title Назва тесту
     * @apiSuccess {String} tests.description Опис
     * @apiSuccess {Date} tests.created_at Дата створення
     * @apiSuccess {Date} tests.updated_at Дата останнього редагування
     * @apiSuccess {String[]} tests.tags Теги
     */
    public function getList()
    {
        $curUser = \Bazalt\Auth::getUser();
        if (!$curUser->hasPermission('tests.can_manage_tests')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }

        $collection = Test::getCollection();
        $table = new \Bazalt\Rest\Collection($collection);

        $table
            ->sortableBy('id')
            ->sortableBy('title')
            ->sortableBy('questions_count')

            ->filterBy('id', function ($collection, $columnName, $value) {
                $collection->andWhere('id = ?', (int)$value);
            })
            ->filterBy('title', function ($collection, $columnName, $value) {
                if ($value) {
                    $value = '%' . strtolower($value) . '%';
                    $collection->andWhere('LOWER(title) LIKE ?', $value);
                }
            })
            ->filterBy('questions_count', function ($collection, $columnName, $value) {
                if ($value) {
                    $collection->having('COUNT(q.id) = ' . (int)$value);
                }
            })
            ->filterBy('tags', function ($collection, $columnName, $value) {

                $tags = $params = $this->params();

                if (isset($tags['tags']) && count($tags['tags']) > 0) {
                    Tag::filterByTags($collection, $tags['tags'], 'test');
                } else {
                    $collection->innerJoin('Modules\\Tags\\Model\\TagRefElement te', ' ON te.element_id = f.id AND te.type = \'test\' ');
                    $collection->innerJoin('Modules\\Tags\\Model\\Tag t', ' ON t.id = te.tag_id ');
                    $collection->andWhere('LOWER(t.body) LIKE ?', '%' . mb_strtolower($value) . '%');
                }
            })
            ->filterBy('created_at', function ($collection, $columnName, $value) {
                $params = $this->params();
                $collection->andWhere('DATE(f.created_at) BETWEEN ? AND ?', $params['created_at']);
            });
        return new Response(Response::OK, $table->fetch($this->params(), function ($item) {
            $item['tags'] = implode(', ', $item['tags']);
            return $item;
        }));
    }

    /**
     * @method POST
     * @method PUT
     * @json
     *
     * @api {get} /tests Створення тесту
     * @apiName createItem
     * @apiGroup Tests-Test
     * @apiSuccessStructure TestStructure
     * @apiSuccess {String} questions_count  Кількість питань
     * @apiSuccess {String[]} tags Теги
     */
    public
    function createItem()
    {
        $data = Validator::create((array)$this->request->data);
        $data->field('title')->required();
        if (!$data->validate()) {
            return new Response(400, $data->errors());
        }

        $curUser = \Bazalt\Auth::getUser();
        if (!$curUser->hasPermission('tests.can_manage_tests')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }

        $test = Test::create();
        $test->title = $data['title'];
        $test->description = isset($data['description']) ? $data['description'] : '';
        $test->save();

        TagRefElement::clearTags($test->id, Task::TYPE_TEST);
        if (isset($data['tags'])) {
            foreach ($data['tags'] as $itm) {
                Tag::addTag($test->id, Task::TYPE_TEST, $itm);
            }
        }

        $res = $test->toArray();

        return new Response(Response::OK, $res);
    }

    /**
     * @method POST
     * @action deleteMulti
     * @provides application/json
     * @json
     * @return \Tonic\Response
     *
     * @api {get} /tests Масове видалення тестів
     * @apiName deleteMulti
     * @apiGroup Tests-Test
     * @apiSuccess {boolean} true Повертає true
     */
    public
    function deleteMulti()
    {
        $data = Validator::create((array)$this->request->data);

        $curUser = \Bazalt\Auth::getUser();
        if (!$curUser->hasPermission('tests.can_manage_tests')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }

        if (!is_array($data['ids'])) {
            return new Response(400, ['Must be array']);
        }

        if (count($data['ids']) > 0) {
            foreach ($data['ids'] as $item) {
                $item = Test::getById((int)$item);
                if ($item) {
                    $item->is_deleted = true;
                    $item->save();
                }
            }
        }

        return new Response(200, true);
    }


    /**
     * @method GET
     * @action getAnswersListForEvaluation
     * @json
     *
     * @api {get} /tests Отримати список відповідей для перевірки
     * @apiName getAnswersListForEvaluation
     * @apiGroup Tests-Test
     * @apiSuccess (200) {String} text_answer Текст відповіді
     * @apiSuccess (200) {Number} mark Оцінка за віповідь
     * @apiSuccess (200) {String} question Текст питання
     * @apiSuccess (200) {Number} question_id Унікальний id питання
     * @apiSuccess (200) {Number} weight Вага питання
     * @apiSuccess (200) {String} type Тип завдання (тест як окреме завдання або тест у курсі)
     * @apiSuccess (200) {String} title Назва завдання
     * @apiSuccess (200) {Number} threshold Поріг проходження завдання
     * @apiSuccess (200) {String} test_title Назва тесту
     * @apiSuccess (200) {String} firstname Ім’я користувача
     * @apiSuccess (200) {String} secondname Фамілія користувача
     * @apiSuccess (200) {String} patronymic По батькові користувача
     * @apiSuccess (200) {Number} id Унікальний id результату
     * @apiSuccess (200) {Number} task_id Унікальний id завдання
     * @apiSuccess (200) {String} email Електронна пошта користуача
     * @apiSuccess (200) {Number} mark Оцінка результату
     */
    public
    function getAnswersListForEvaluation()
    {
        $curUser = \Bazalt\Auth::getUser();
        if (!$curUser->hasPermission('tests.can_manage_tests')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }

        $collection = Answer::getAnswersListForEvaluation();
        // table configuration
        $table = new \Bazalt\Rest\Collection($collection);
        $table
            ->sortableBy('fullname', function ($collection, $columnName, $direction) {
                $collection->orderBy('u.secondname ' . $direction . ', u.firstname ' . $direction . ', u.patronymic ' . $direction . '');
            })
            ->sortableBy('task', function ($collection, $columnName, $direction) {
                $collection->orderBy('t.title ' . $direction);
            })
            ->sortableBy('question', function ($collection, $columnName, $direction) {
                $collection->orderBy('q.body ' . $direction);
            })
            ->sortableBy('answer', function ($collection, $columnName, $direction) {
                $collection->orderBy('ra.text_answer ' . $direction);
            })
            ->sortableBy('email', function ($collection, $columnName, $direction) {
                $collection->orderBy('u.email ' . $direction);
            })
            ->sortableBy('updated_at')
            ->sortableBy('mark')

            ->filterBy('fullname', function ($collection, $columnName, $value) {
                if ($value) {
                    $value = '%' . strtolower($value) . '%';
                    $collection->andWhere('(LOWER(u.firstname) LIKE ? OR LOWER(u.secondname) ' .
                        'LIKE ? OR LOWER(u.patronymic) LIKE ?)', array($value, $value, $value)
                    );
                }
            })
            ->filterBy('course', function ($collection, $columnName, $value) {
                if ($value) {
                    $collection->andWhere('t.parent_id = ?', (int)$value);
                }
            })
            ->filterBy('mark', function ($collection, $columnName, $value) {
                if ($value) {
                    $collection->andWhere('ra.mark = ?', (int)$value);
                }
            })
            ->filterBy('task', function ($collection, $columnName, $value) {
                if ($value) {
                    $collection->andWhere('t.id = ?', (int)$value);
                }
            })
            ->filterBy('question', function ($collection, $columnName, $value) {
                if ($value) {
                    $collection->andWhere('LOWER(q.body) LIKE ?', '%' . $value . '%');
                }
            })
            ->filterBy('email', function ($collection, $columnName, $value) {
                if ($value) {
                    $collection->andWhere('LOWER(u.email) LIKE ?', '%' . $value . '%');
                }
            })
            ->filterBy('answer', function ($collection, $columnName, $value) {
                if ($value) {
                    $collection->andWhere('LOWER(ra.text_answer) LIKE ?', '%' . $value . '%');
                }
            })
            ->filterBy('updated_at', function ($collection, $columnName, $value) {
                $params = $this->params();
                $collection->andWhere('DATE(ra.updated_at) BETWEEN ? AND ?', array($params['updated_at'][0], $params['updated_at'][1]));
            })
            ->filterBy('dataFilter', function ($collection, $columnName, $value) {
                if ($value == 'verified') {
                    $collection->andWhere('ra.mark IS NOT NULL');
                } elseif ($value == 'unverified') {
                    $collection->andWhere('ra.mark IS NULL');
                }
            });

        $res = $table->fetch($this->params(), function ($item, $object) {
            $item['text_answer'] = strip_tags($object->text_answer);
            $item['mark'] = $object->mark;
            $item['question'] = strip_tags($object->question);
            $item['question_id'] = $object->question_id;
            $item['weight'] = $object->weight;
            $item['type'] = ($object->parent_id) ? 'course' : 'test';
            $item['title'] = $object->title;
            $item['threshold'] = $object->threshold;
            $item['test_title'] = $object->test_title;
            $item['firstname'] = $object->firstname;
            $item['secondname'] = $object->secondname;
            $item['patronymic'] = $object->patronymic;
            $item['id'] = $object->id;
            $item['task_id'] = (int)$object->task_id;
            $item['task_title'] = $object->task_title;
            $item['email'] = $object->email;
            $item['mark'] = $object->mark;

            $resultAnswerFiles = AnswerResultFile::getByAnswerResultId($object->id);

            $files = [];

            if (count($resultAnswerFiles) > 0) {
                foreach ($resultAnswerFiles as $file) {
                    $files[] = ['name' => $file->name, 'url' => $file->file, 'extension' => $file->extension];
                }
            }

            $item['files'] = $files;

            if ($object->parent_id) {
                $task = Task::getById($object->parent_id);
                $item['parent'] = [
                    'title' => $task->title,
                    'element_id' => $task->element_id,
                    'id' => $task->id
                ];
            }

            return $item;
        });
        return new Response(Response::OK, $res);

    }


    /**
     * @method GET
     * @action getAnswersListForEvaluationDashboard
     * @json
     *
     * @api {get} /tests Отримати список відповідей для перевірки (для віджета на головній сторінці)
     * @apiName getAnswersListForEvaluationDashboard
     * @apiGroup Tests-Test
     * @apiSuccess {Object[]} data Питання на перевірку (для віджета на головній сторінці)
     * @apiSuccess {String} data.status Статус завдання
     * @apiSuccess {Number} data.mark Оцінка завдання
     * @apiSuccess {Number} data.attempts_count Кількість спроб тестування
     * @apiSuccess {Number} data.attempts_limit Обмеження кількості спроб тестування
     * @apiSuccess {Number} data.attempts_limit Обмеження кількості спроб тестування
     * @apiSuccess {Number} data.plan_percent_complete Відсоток пройденого плану
     * @apiSuccess {Number} data.plan_sum Сумма балів за пройдені завдання в в плані
     * @apiSuccess {Number} data.plan_percent Відсоток проходження
     * @apiSuccess {Number} data.is_success Чи виконано завдання (якщо оцінка більша за поріг проходження то 1 інакше 0)
     * @apiSuccess {Object[]} data.element Массив з елементом (Курс, Сторінка, Тест)
     * @apiSuccess {Number} data.can_execute Можливість виконання завдання
     * @apiSuccess {Object[]} data.start_element стартовий елемент плану
     * @apiSuccess {Object[]} data.plan План
     * @apiSuccess {Number} data.plan.course_id Унікальний id курсу
     * @apiSuccess {Number} data.plan.element Унікальний id елементу
     * @apiSuccess {Number} data.plan.start_element Стартовий елемент плану
     * @apiSuccess {Number} data.plan.is_determ_final_mark Визначати результат проходження курсу по тестуванню
     * @apiSuccess {String} data.plan.type Тип завдання
     * @apiSuccess {Date} data.plan.created_at Дата створення
     * @apiSuccess {Date} data.plan.updated_at Дата редагування
     * @apiSuccess {Date} data.plan.order Cортування елементів плану
     */
    public
    function getAnswersListForEvaluationDashboard()
    {
        $curUser = \Bazalt\Auth::getUser();
        if (!$curUser->hasPermission('tests.can_manage_tests')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }

        $res = [];
        $items = Answer::getAnswersListForEvaluationDashboard();

        $taskIds = [];
        foreach ($items as $item) {
            $taskIds[$item->task_id] = $item->task_id;
        }
        if (count($taskIds) > 0) {
            $tasks = Task::getByIds($taskIds);
            foreach ($tasks as $task) {
                $resItm = $task->toArray();

                foreach ($items as $item) {
                    if ($item->task_id == $task->id) {
                        $item->all_answers_count = (int)$item->all_answers_count;
                        $item->ver_answers_count = Answer::getAnswersNotRated($task->id);
                        $resItm['answers_count'] = (array)$item;
                    }
                }

                $res [] = $resItm;
            }
        }
        return new Response(Response::OK, ['data' => $res]);
    }
}