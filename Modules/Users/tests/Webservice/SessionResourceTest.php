<?php

namespace Modules\Users\Tests\Webservice;

use Bazalt\Auth\Model\Permission;
use Bazalt\Rest;
use Bazalt\Session;
use Tonic;

class SessionResourceTest extends \Bazalt\Auth\Test\BaseCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->initApp(getWebServices());

        \Bazalt\Site\Option::set(\Bazalt\Auth::SPLIT_ROLES_OPTION, false, $this->site->id);
    }

    public function testGet()
    {
        \Bazalt\Auth::logout();
        $res = [
            'guest_id' => Session::getSessionId(),
            'is_guest' => 1,
            'roles' => [],
            'acl' => []
        ];
        list($code, $retResponse) = $this->send('GET /auth/session', ['contentType' => 'application/json']);
        $roles = \Bazalt\Auth\Model\Role::getGuestRoles();
        $res['roles'] []= $roles[0]->toArray();
        $res['role'] = $roles[0]->toArray();
        $response = new \Bazalt\Rest\Response(200, $res);
        $this->assertEquals($response->code, $code, json_encode($retResponse));
        $this->assertEquals($response->body, $retResponse);
    }


    public function testPost()
    {
        $this->user->need_edit = 0;
        $this->user->save();

        $response = new \Bazalt\Rest\Response(400, [
            'password' => [
                'required' => 'Field cannot be empty'
            ],
            'email' => [
                'required' => 'Field cannot be empty'
            ]
        ]);

        $this->assertResponse('POST /auth/session', [
            'data' => json_encode(array(
                'hello' => 'computer'
            ))
        ], $response);

        $user = \Bazalt\Auth\Model\User::getById($this->user->id);
        $role = \Bazalt\Auth\Model\Role::getByName('Пользователь');
        $res = $user->toArray();
        $res['roles'] = [];
        $res['role'] = $role->toArray();
        $response = new \Bazalt\Rest\Response(200, $res);

        $this->assertResponse('POST /auth/session', [
            'data' => json_encode(array(
                'email' => $this->user->email,
                'password' => '1'
            ))
        ], $response);

        // get logined user
        $response = new \Bazalt\Rest\Response(200, $res);
        $this->assertResponse('GET /auth/session', ['contentType' => 'application/json'], $response);

       /* [roles] => Array
    (
    )

    [role] => Array
    (
        [id] => 150
            [site_id] =>
            [title] => Пользователь
            [description] =>
            [is_guest] => 0
            [system_acl] => 0
            [is_hidden] => 0
        )*/

        // logout
        $response = new \Bazalt\Rest\Response(200, '/is_guest/');
        $this->assertRegExpResponse('DELETE /auth/session', [], $response);

        // guest logout
        $response = new \Bazalt\Rest\Response(200, '/is_guest/');
        $this->assertRegExpResponse('DELETE /auth/session', [], $response);
    }

    public function testChangeRole()
    {
        $response = new \Bazalt\Rest\Response(400, [
            'role_id' => [
                'required' => 'Field cannot be empty'
            ]
        ]);
        $_GET['action'] = 'changeRole';
        $this->assertResponse('PUT /auth/session', [
        ], $response);


        $response = new \Bazalt\Rest\Response(400, 'Unable to switch role to "999"');
        $_GET['action'] = 'changeRole';
        $this->assertResponse('PUT /auth/session', [
            'data' => json_encode(array(
                'role_id' => '999'
            ))
        ], $response);


        $pr = new Permission();
        $pr->id = 'perm1';
        $pr->save();
        $this->models []= $pr;

        $role = \Bazalt\Auth\Model\Role::create();
        $role->title = 'Test1';
        $role->save();
        $this->models []= $role;
        $role->Permissions->add($pr);


        $pr2 = new Permission();
        $pr2->id = 'perm2';
        $pr2->save();
        $this->models []= $pr2;

        $role2 = \Bazalt\Auth\Model\Role::create();
        $role2->title = 'Test2';
        $role2->save();
        $this->models []= $role2;
        $role2->Permissions->add($pr2);

        $this->user->Roles->add($role, ['site_id' => $this->site->id]);
        $this->user->Roles->add($role2, ['site_id' => $this->site->id]);

        $_GET['action'] = 'changeRole';
        $this->assertResponseCode('PUT /auth/session', [
            'data' => json_encode(array(
                'role_id' => $role->id
            ))
        ], 200);

        $arr = $this->user->toArray();
        $this->assertTrue(in_array('perm1', $arr['permissions']));


        $_GET['action'] = 'changeRole';
        $this->assertResponseCode('PUT /auth/session', [
            'data' => json_encode(array(
                'role_id' => $role2->id
            ))
        ], 200);

        $arr = $this->user->toArray();
        $this->assertTrue(in_array('perm2', $arr['permissions']));


        $this->user->is_god = true;
        $this->user->save();
        $arr = $this->user->toArray();
        $this->assertTrue(in_array('perm1', $arr['permissions']));
        $this->assertTrue(in_array('perm2', $arr['permissions']));
    }
}