<?php

namespace Modules\Users\Webservice;

use Bazalt\Auth\Model\Role;
use Bazalt\Auth\Model\User;
use Modules\Tags\Model\TagRefElement;
use Modules\Tags\Model\Tag;
use Bazalt\Data\Validator;
use Tonic\Response;

/**
 * UserResource
 *
 * @uri /auth/users/:id
 *
 * @apiDefineSuccessStructure UserStructure
 * @apiParam {Number} id Унікальний id
 * @apiParam {string} login Логін
 * @apiParam {string} firstname Ім’я
 * @apiParam {string} secondname Фамілія
 * @apiParam {string} patronymic По батькові
 * @apiParam {string} gender Стать
 * @apiParam {string} birth_date Дата народження
 * @apiParam {string} email Електронна пошта
 * @apiParam {string} created_at Дата створення
 * @apiParam {string} last_activity Остання дата активностів
 * @apiParam {string} need_edit Потрібно редагувати
 */
class UserResource extends \Bazalt\Rest\Resource
{
    /**
     * @method GET
     * @json
     */
    public function getUser($id)
    {
        $user = User::getById($id);
        if (!$user) {
            return new Response(400, ['id' => 'User not found']);
        }
        $res = $user->toArray();

        $res['photo_thumb'] = $res['photo'] = '';
        $photo = $user->setting('photo');
        if ($photo) {
            $config = \Bazalt\Config::container();
            $res['photo'] = $config['uploads.prefix'] . $user->setting('photo');
            try {
                $res['photo_thumb'] = $config['thumb.prefix'] . thumb(SITE_DIR . '/..' . $user->setting('photo'), '256x256', ['crop' => true, 'fit' => true]);
            } catch (\Exception $ex) {
                $res['photo_thumb'] = $config['uploads.prefix'] . $user->setting('photo');
            }
        }

        $roles = $user->Roles->get();
        $res['roles'] = [];
        if ($roles) {
            $arr = array();
            foreach ($roles as $role) {
                $arr[] = $role->id;
            }
            $res['roles'] = $arr;
        }
        $shortName = $this->getShortName($res['firstname'], $res['secondname'], $res['patronymic']);
        // echo $shortName;exit('O_o');
        if (!$shortName) {
            $res['shortName'] = $res['login'];
        } else {
            $res['shortName'] = $shortName;
        }
        if ($user->birth_date == '') {
            $res['birth_date'] = null;
        }

        $res['tags'] = [];
        $tags = TagRefElement::getElementTags($user->id, 'user');
        foreach ($tags as $itm) {
            $res['tags'] [] = $itm->body;
        }

        return new Response(Response::OK, $res);
    }

    /**
     * @method PUT
     * @action changePassword
     * @json
     */
    public function changePassword($id)
    {
        $user = User::getById($id);
        if (!$user || $user->is_deleted || !$user->is_active) {
            return new Response(400, ['id' => 'User not found']);
        }

        $current = \Bazalt\Auth::getUser();
        if (!$current->hasPermission('auth.can_edit_users')) {
            if ($user->id != $current->id) {
                return new Response(403, 'Permission denied');
            }
        }

        $data = Validator::create((array)$this->request->data);
        if ($current->id == $id || $current->id != $id && !$current->hasPermission('auth.can_delete_user')) {
            if (!isset($data['old_password']) || User::cryptPassword($data['old_password']) != $user->password) {
                return new Response(Response::BADREQUEST, [
                    'old_password' => ['invalid' => 'Invalid old password']
                ]);
            }
        }


        $data->field('new_password')->required()->equal($data['new_spassword']);
        if (!$data->validate()) {
            return new Response(400, $data->errors());
        }

        $user->password = User::cryptPassword($data['new_password']);
        $user->save();
        return new Response(Response::OK, $user->toArray());
    }

    /**
     * @method PUT
     * @action activate
     * @json
     */
    public function activateUser($id)
    {
        $user = User::getById($id);
        if (!$user || $user->is_deleted) {
            return new Response(400, ['id' => 'User not found']);
        }
        if (!isset($_GET['key']) || $user->getActivationKey() != trim($_GET['key'])) {
            return new Response(Response::BADREQUEST, [
                'key' => ['invalid' => 'Invalid activation key']
            ]);
        }
        if ($user->is_active) {
            return new Response(Response::BADREQUEST, [
                'key' => ['user_activated' => 'User already activated']
            ]);
        }
        $user->is_active = true;
        $user->save();
        \Bazalt\Auth::setUser($user);
        return new Response(Response::OK, $user->toArray());
    }

    /**
     * @method PUT
     * @action disable
     * @json
     */
    public function disableUser($id)
    {
        $user = User::getById($id);
        if (!$user || $user->is_deleted) {
            return new Response(400, ['id' => 'User not found']);
        }
        $curUser = \Bazalt\Auth::getUser();
        if (!$curUser->hasPermission('auth.can_edit_users')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }
        if ($user->id == $curUser->id) {
            return new Response(Response::BADREQUEST, ['id' => 'Can\'t disable yourself']);
        }
        if (!$user->is_active) {
            return new Response(Response::BADREQUEST, [
                'user_disabled' => 'User already disabled'
            ]);
        }
        $user->is_active = false;
        $user->save();

        $data = Validator::create((array)$this->request->data);
        if (isset($data['block_message']) && $data['block_message']) {
            $user->setting('block_message', $data['block_message']);
        }

        return new Response(Response::OK, $user->toArray());
    }

    /**
     * @method PUT
     * @action enable
     * @json
     */
    public function enableUser($id)
    {
        $user = User::getById($id);
        if (!$user || $user->is_deleted) {
            return new Response(400, ['id' => 'User not found']);
        }
        $curUser = \Bazalt\Auth::getUser();
        if (!$curUser->hasPermission('auth.can_edit_users')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }
        if ($user->is_active) {
            return new Response(Response::BADREQUEST, [
                'user_disabled' => 'User already enabled'
            ]);
        }
        $user->is_active = true;
        $user->save();

        $user->setting('block_message', '');

        return new Response(Response::OK, $user->toArray());
    }

    /**
     * @method PUT
     * @action save-roles
     * @json
     */
    public function saveUserRoles($id)
    {
        $user = User::getById($id);
        if (!$user || $user->is_deleted) {
            return new Response(400, ['id' => 'User not found']);
        }
        $curUser = \Bazalt\Auth::getUser();
        if (!$curUser->hasPermission('auth.can_edit_roles')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }
        $data = Validator::create((array)$this->request->data);
        $userRoles = [];
        $data->field('roles')->validator('validRoles', function ($roles) use (&$userRoles) {
            if ($roles) {
                foreach ($roles as $role) {
                    $userRoles[$role] = \Bazalt\Auth\Model\Role::getById($role);
                    if (!$userRoles[$role]) {
                        return false;
                    }
                }
            }
            return true;
        }, 'Invalid roles');

        if (!$data->validate()) {
            return new Response(400, $data->errors());
        }

        $user->Roles->clearRelations(array_keys($userRoles));
        foreach ($userRoles as $role) {
            $user->Roles->add($role, ['site_id' => \Bazalt\Site::getId()]);
        }

        return new Response(Response::OK, $user->toArray());
    }

    /**
     * @method DELETE
     * @json
     */
    public function deleteUser($id)
    {
        $user = \Bazalt\Auth::getUser();
        $profile = User::getById($id);
        if (!$profile) {
            return new Response(400, ['id' => 'User not found']);
        }
        if (!$user->hasPermission('auth.can_delete_user')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }
        if (!$user->isGuest() && $user->id == $profile->id) {
            return new Response(Response::BADREQUEST, ['id' => 'Can\'t delete yourself']);
        }
        $profile->is_deleted = 1;
        $profile->save();
        return new Response(Response::OK, true);
    }

    /**
     * @method PUT
     * @method POST
     * @json
     */
    public function saveUser()
    {
        $data = Validator::create((array)$this->request->data);
        $data->field('id')->required();
        if (!$data->validate()) {
            return new Response(400, $data->errors());
        }
        $user = User::getById($data['id']);
        if (!$user) {
            return new Response(400, ['id' => 'User not found']);
        }

        $curUser = \Bazalt\Auth::getUser();
        if ($user->id != $curUser->id && !$curUser->hasPermission('auth.can_edit_users')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }

        $emailField = $data->field('email')->required()->email();

        if ($user->need_edit) {
            $data->field('password')->required()->equal($data['spassword']);
        }

        $emailField->validator('uniqueEmail', function ($email) use ($user) {
            $usr = User::getUserByEmail($email, false);
            return $usr == null || $usr->id == $user->id;
        }, 'User with this email already exists');

        $loginField = $data->field('login')->required();
        $loginField->validator('uniqueLogin', function ($login) use ($user) {
            $usr = User::getUserByLogin($login, false);
            return $usr == null || $usr->id == $user->id;
        }, 'User with this login already exists');

        $data->field('gender')->required();

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
        $user->gender = $data['gender'];
        $user->is_active = $data['is_active'];
        $user->is_deleted = $data['is_deleted'];

        if ($user->need_edit) {
            $user->password = User::cryptPassword($data['password']);

            $arr = [];
            $arr['domain'] = \Bazalt\Site::get()->domain;
            $arr['email'] = $user->email;
            $arr['login'] = $user->login;
            $arr['fullname'] = $user->getName();
            $arr['password'] = $data['password'];

            \Modules\Notification\Broker::onNotification('Users.Registration.Complete', $arr);
        }
        $user->need_edit = 0;
        $user->save();

        TagRefElement::clearTags($user->id, 'user');
        if (isset($data['tags'])) {
            foreach ($data['tags'] as $itm) {
                Tag::addTag($user->id, 'user', $itm);
            }
        }

        if (isset($data['photo'])) {
            $user->setting('photo', $data['photo']);
        }

        return $this->getUser($user->id);
    }

    /**
     * @action upload
     * @method POST
     * @accepts multipart/form-data
     * @json
     */
    public function uploadImage($id)
    {
        $user = User::getById($id);
        if (!$user) {
            return new Response(400, ['id' => 'User not found']);
        }

        $uploader = new \Bazalt\Rest\Uploader(['jpg', 'png', 'jpeg', 'bmp', 'gif'], 1000000);
        $result = $uploader->handleUpload(UPLOAD_DIR, ['users', $user->id]);
//        $result['file'] = '/uploads' . $result['file'];
        return new Response(Response::OK, '/uploads' . $result['file']);
    }


    /**
     * @method PUT
     * @action recoveryPassword
     * @json
     */
    public function recoveryPassword($id)
    {

        $user = User::getById($id);
        if (!$user || $user->is_deleted) {
            return new Response(400, ['id' => 'User not found']);
        }

        if (!isset($_GET['key']) || $user->getRemindKey() != trim($_GET['key'])) {
            return new Response(Response::BADREQUEST, [
                'key' => ['invalid' => 'Invalid activation key']
            ]);
        }

        $data = Validator::create((array)$this->request->data);

        $data->field('password')->required()->equal($data['spassword']);
        if (!$data->validate()) {
            return new Response(400, $data->errors());
        }

        $user->password = User::cryptPassword($data['password']);
        $user->save();
        \Bazalt\Auth::setUser($user, true);
        return new Response(Response::OK, $user->toArray());
    }

    public function getShortName($firstName, $lastName, $patronymic)
    {

        if (!empty($firstName) && !empty($lastName) && !empty($patronymic)) {
            return $lastName . ' ' . mb_strcut($firstName, 0, 2, 'UTF-8') . '. ' . mb_strcut($patronymic, 0, 2, 'UTF-8') . '.';
        } elseif (!empty($firstName) && !empty($lastName)) {
            return $lastName . ' ' . mb_strcut($firstName, 0, 2, 'UTF-8');
        } elseif (!empty($firstName)) {
            return $firstName;
        }
        return false;
    }


    /**
     * @method POST
     * @action changePhoto
     * @json
     */
    public function changePhoto($id)
    {
        $user = User::getById($id);
        if (!$user) {
            return new Response(400, ['id' => 'User not found']);
        }

        $data = Validator::create((array)$this->request->data);
        if (isset($data['photo']) && $data['photo'] != '') {
            $user->setting('photo', $data['photo']);
        } else {
            $user->setting('photo', '');
        }

        return new Response(Response::OK, true);
    }
}