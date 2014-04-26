<?php

namespace Modules\Users\Webservice;

use Bazalt\Auth\Model\User;
use Bazalt\Data\Validator;
use Bazalt\Rest\Response;


use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\Event;


/**
 * SessionResource
 *
 * @uri /auth/session
 */
class SessionResource extends \Bazalt\Rest\Resource
{
    /**
     * @method GET
     * @json
     */
    public function getUser()
    {
        $user = \Bazalt\Auth::getUser();

        if (!\Bazalt\Auth::getUser()->isGuest() && !$user->is_active) {
            \Bazalt\Auth::logout();
            return new Response(400, [
                'locked' => 'User is locked'
            ]);
        }

        $res = $user->toArray();
        $roles = $user->getRoles();
        $photo = $user->setting('photo');
        if ($photo) {
            $config = \Bazalt\Config::container();
            try {
                $res['photo_thumb'] = $config['thumb.prefix'] . thumb(SITE_DIR . '/..' .  $user->setting('photo'), '128x128', ['crop' => true, 'fit' => true]);
            } catch (\Exception $ex) {
                $res['photo_thumb'] = '';
            }
        }
        $res['roles'] = [];
        $res['role'] = null;
        foreach ($roles as $role) {
            $res['roles'] [] = $role->toArray();
        }
        if ($user->isGuest()) {
            $roles = \Bazalt\Auth\Model\Role::getGuestRoles();
            if (count($roles) == 0) {
                $role = \Bazalt\Auth\Model\Role::create();
                $role->title = 'Гость';
                $role->is_guest = true;
                $role->save();
                $roles = \Bazalt\Auth\Model\Role::getGuestRoles();
            }
            $res['role'] = $roles[0]->toArray();
        } else {
            $currentRole = \Bazalt\Auth::getCurrentRole();
            if (!$currentRole && !$user->need_edit) {
                $config = \Bazalt\Config::container();
                $currentRole = $config['auth.defaultRole'];
                \Bazalt\Auth::setCurrentRole($currentRole->id);
            }
            if ($currentRole) {
                $res['role'] = $currentRole->toArray();
            }
            $res['need_edit'] = (int)$res['need_edit'];
        }
        return new Response(Response::OK, $res);
    }

    /**
     * @method PUT
     * @json
     */
    public function renewSession()
    {
        return $this->getUser();
    }

    /**
     * @method PUT
     * @action changeRole
     * @json
     */
    public function changeRole()
    {
        $data = Validator::create($this->request->data);
        $data->field('role_id')->required();
        if (!$data->validate()) {
            return new Response(400, $data->errors());
        }
        $curUser = \Bazalt\Auth::getUser();
        if ($curUser->isGuest()) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }
        if (\Bazalt\Auth::setCurrentRole((int)$data['role_id']) !== true) {
            return new Response(400, sprintf('Unable to switch role to "%s"', $data['role_id']));
        }
        return $this->getUser();
    }

    /**
     * @method POST
     * @json
     */
    public function login()
    {
        $user = null;
        $data = Validator::create($this->request->data);
        $data->field('password')->required();
        $data->field('email')->required();
        if (!$data->validate()) {
            return new Response(400, $data->errors());
        }
        $user = User::getUserByLoginPassword($data['email'], $data['password'], true);
        if ($user) {
            $user->login($data['remember_me'] == 'true');
            return $this->getUser();
        } else {
            $user = User::getUserByEmail($data['email']);
            if(!$user){
                $user = User::getUserByLogin($data['email']);
            }
            if ($user && !$user->is_active) {
                return new Response(400, [
                    'locked' => 'User is locked'
                ]);
            }
        }
        return new Response(400, [
            'error' => 'User with this email does not exists'
        ]);

    }

    /**
     * @method DELETE
     * @json
     */
    public function logout()
    {
        if (!\Bazalt\Auth::getUser()->isGuest()) {
            \Bazalt\Auth::logout();
        }
        return $this->getUser();
    }
}