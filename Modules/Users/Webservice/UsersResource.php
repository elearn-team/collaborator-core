<?php

namespace Modules\Users\Webservice;

use Bazalt\Auth\Model\Role;
use Bazalt\Auth\Model\User;
use Modules\Tags\Model\TagRefElement;
use Modules\Tags\Model\Tag;
use Bazalt\Data\Validator;
use Tonic\Response;

/**
 * UsersResource
 *
 * @uri /auth/users
 */
class UsersResource extends \Bazalt\Rest\Resource
{
    /**
     * @method GET
     * @json
     */
    public function getList()
    {
        $curUser = \Bazalt\Auth::getUser();
        if (!$curUser->hasPermission('auth.can_edit_users')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }

        $collection = \Bazalt\Auth\Model\User::getCollection();

        // table configuration
        $table = new \Bazalt\Rest\Collection($collection);
        $table
            ->sortableBy('id')
            ->sortableBy('fullname', function ($collection, $columnName, $direction) {
                $collection->orderBy('f.secondname ' . $direction . ', f.firstname ' . $direction . ', f.patronymic ' . $direction . ', f.login ' . $direction . '');
            })
            ->sortableBy('login')
            ->sortableBy('email')
            ->sortableBy('created_at')
            ->sortableBy('last_activity')

            ->filterBy('id', function ($collection, $columnName, $value) {
                $collection->andWhere('f.id = ?', (int)$value);
            })
            ->filterBy('fullname', function ($collection, $columnName, $value) {
                if ($value) {
                    $value = '%' . strtolower($value) . '%';
                    $collection->andWhere('(LOWER(f.firstname) LIKE ? OR LOWER(f.secondname) ' .
                        'LIKE ? OR LOWER(f.patronymic) LIKE ?)', array($value, $value, $value)
                    );
                }
            })
            ->filterBy('email', function ($collection, $columnName, $value) {
                if ($value) {
                    $value = '%' . strtolower($value) . '%';
                    $collection->andWhere('LOWER(f.email) LIKE ?', $value);
                }
            })
            ->filterBy('login', function ($collection, $columnName, $value) {
                if ($value) {
                    $value = '%' . strtolower($value) . '%';
                    $collection->andWhere('LOWER(f.login) LIKE ?', $value);
                }
            })

            ->filterBy('created_at', function ($collection, $columnName, $value) {
                $params = $this->params();
                $collection->andWhere('DATE(f.created_at) BETWEEN ? AND ?', array($params['created_at'][0], $params['created_at'][1]));
            })
            ->filterBy('last_activity', function ($collection, $columnName, $value) {
                $params = $this->params();
                $collection->andWhere('DATE(f.last_activity) BETWEEN ? AND ?', array($params['last_activity'][0], $params['last_activity'][1]));
            })
            ->filterBy('roles', function ($collection, $columnName, $value) {
                if ($value) {
                    $collection
                        ->select('f.*')
                        ->innerJoin('Bazalt\\Auth\\Model\\RoleRefUser ru', ['user_id', 'f.id'])
                        ->andWhere('ru.role_id = ?', (int)$value)
                        ->groupBy('f.id');
                }
            })
            ->filterBy('tags', function ($collection, $columnName, $value) {
                $collection->select('f.*');
                $tags = $params = $this->params();

                if (isset($tags['tags']) && count($tags['tags']) > 0) {
                    Tag::filterByTags($collection, $tags['tags'], 'user');
                } else {
                    $collection->innerJoin('Modules\\Tags\\Model\\TagRefElement te', ' ON te.element_id = f.id AND te.type = \'user\' ');
                    $collection->innerJoin('Modules\\Tags\\Model\\Tag t', ' ON t.id = te.tag_id ');
                    $collection->andWhere('LOWER(t.body) LIKE ?', '%' . mb_strtolower($value) . '%');
                }

            });

        return new Response(Response::OK, $table->fetch($this->params(), function ($item, $user) {
            $arr = [];
            $roles = $user->Roles->get();
            foreach ($roles as $role) {
                $arr[] = $role->title;
            }
            $item['roles'] = implode(', ', $arr);
            $item['created_at'] = strtotime($item['created_at']) . '000';
            $item['last_activity'] = strtotime($item['last_activity']) . '000';

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
            $tags = TagRefElement::getElementTags($item['id'], 'user');
            if(count($tags) > 0){
                foreach ($tags as $itm) {
                    $res[] = $itm->body;
                }
                $item['tags'] = implode(', ', $res);
            }

            return $item;
        }));
    }

    /**
     * @method POST
     * @json
     */
    public function registerUser()
    {
        $data = Validator::create((array)$this->request->data);

        $emailField = $data->field('email')->required()->email();

        $user = User::create();
        // check email
        $emailField->validator('uniqueEmail', function ($email) {
            return User::getUserByEmail($email, false) == null;
        }, 'User with this email already exists');


        if (!$data->validate()) {
            return new Response(400, $data->errors());
        }
        $user->login = $data['email'];
        $user->email = $data['email'];
        $user->save();

        $arr = array();
        $arr['user_id'] = $user->id;
        $arr['email'] = $user->email;
        $arr['domain'] = \Bazalt\Site::get()->domain;
        $arr['activation_key'] = $user->getActivationKey();

        \Modules\Notification\Broker::onNotification('Users.Registration.Activation', $arr);

        return new Response(200, $user->toArray());
    }

    /**
     * @method POST
     * @action create
     * @json
     */
    public function saveUser()
    {
        $data = Validator::create((array)$this->request->data);

        $user = User::create();
        $curUser = \Bazalt\Auth::getUser();
        if (!$curUser->hasPermission('auth.can_edit_users')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }

        $emailField = $data->field('email')->required()->email();
        $data->field('password')->required()->equal($data['spassword']);

        $emailField->validator('uniqueEmail', function ($email) use ($user) {
            $usr = User::getUserByEmail($email, false);
            return $usr == null || $usr->id == $user->id;
        }, 'User with this email already exists');

        $loginField = $data->field('login')->required();
        $loginField->validator('uniqueLogin', function ($login) use ($user) {
            $usr = User::getUserByLogin($login, false);
            return $usr == null || $usr->id == $user->id;
        }, 'User with this login already exists');

        if (!$data->validate()) {
            return new Response(400, $data->errors());
        }

        $user->login = $data['login'];
        $user->email = $data['email'];
        $user->firstname = $data['firstname'];
        $user->secondname = $data['secondname'];
        $user->patronymic = $data['patronymic'];
        if ($data['birth_date']) {
            $user->birth_date = date('Y-m-d', strToTime($data['birth_date']));
        }
        $user->password = User::cryptPassword($data['password']);
        $user->gender = isset($data['gender']) && $data['gender'] ? $data['gender'] : 'unknown';
        $user->is_active = false;
        $user->is_deleted = false;
        $user->need_edit = false;
        $user->save();

        TagRefElement::clearTags($user->id, 'user');
        if (isset($data['tags'])) {
            foreach ($data['tags'] as $itm) {
                Tag::addTag($user->id, 'user', $itm);
            }
        }

        return new Response(200, $user->toArray());
    }

    /**
     * @action upload
     * @method POST
     * @accepts multipart/form-data
     * @json
     */
    public function uploadImage()
    {
        $curUser = \Bazalt\Auth::getUser();
        if ($curUser->isGuest()) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }

        $uploader = new \Bazalt\Rest\Uploader(['jpg', 'png', 'jpeg', 'bmp', 'gif'], 1000000);
        $result = $uploader->handleUpload(UPLOAD_DIR, ['users', $curUser->id]);
//        $result['file'] = '/uploads' . $result['file'];
        return new Response(Response::OK, '/uploads' . $result['file']);
    }


    /**
     * @method PUT
     * @action recovery
     * @json
     */
    public function recovery()
    {
        $data = Validator::create((array)$this->request->data);

        $emailField = $data->field('email')->required()->email();

        // check email
        $emailField->validator('uniqueEmail', function ($email) {
            return User::getUserByEmail($email, false) != null;
        }, 'User with this e-mail does not exist');

        $user = User::getUserByEmail($data['email'], false);

        if (!$data->validate()) {
            return new Response(400, $data->errors());
        }

        $arr['user_id'] = $user->id;
        $arr['email'] = $user->email;
        $arr['domain'] = \Bazalt\Site::get()->domain;
        $arr['remind_key'] = $user->getRemindKey();

        \Modules\Notification\Broker::onNotification('Users.Recovery.Password', $arr);
    }

    /**
     * @method PUT
     * @action changePassword
     * @json
     */
    public function changePassword()
    {
        $curUser = \Bazalt\Auth::getUser();
        if ($curUser->isGuest()) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }

        $data = Validator::create((array)$this->request->data);
        if (!isset($data['old_password']) || User::cryptPassword($data['old_password']) != $curUser->password) {
            return new Response(Response::BADREQUEST, [
                'old_password' => ['invalid' => 'Invalid old password']
            ]);
        }
        $data->field('new_password')->required()->equal($data['new_spassword']);
        if (!$data->validate()) {
            return new Response(400, $data->errors());
        }

        $curUser->password = User::cryptPassword($data['new_password']);
        $curUser->save();
        return new Response(Response::OK, $curUser->toArray());
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
            $item = User::getById((int)$item);
            if ($item) {
                $item->is_deleted = true;
                $item->save();
            }
        }

        return new Response(200, true);
    }


    /**
     * @method POST
     * @action disableMulti
     * @provides application/json
     * @json
     * @return \Tonic\Response
     */
    public function disableMulti()
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
            $item = User::getById((int)$item);
            if ($item) {
                $item->is_active = false;
                $item->save();
            }
        }

        return new Response(200, true);
    }

    /**
     * @method POST
     * @action enableMulti
     * @provides application/json
     * @json
     * @return \Tonic\Response
     */
    public function enableMulti()
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
            $item = User::getById((int)$item);
            if ($item) {
                $item->is_active = true;
                $item->save();
            }
        }

        return new Response(200, true);
    }
}