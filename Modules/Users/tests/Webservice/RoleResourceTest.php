<?php

namespace Modules\Users\Tests\Webservice;

use Bazalt\Auth\Model\Permission;
use Bazalt\Auth\Model\Role;
use Bazalt\Auth\Model\User;
use Bazalt\Rest;
use Bazalt\Session;
use Tonic;


class RoleResourceTest extends \Bazalt\Auth\Test\BaseCase
{
    protected $role;

    protected function setUp()
    {
        parent::setUp();

        $this->initApp(getWebServices());

        $this->role = \Bazalt\Auth\Model\Role::create();
        $this->role->title = 'tsrole';
        $this->role->save();
        $this->models [] = $this->role;

        $perm = new \Bazalt\Auth\Model\Permission();
        $perm->id = 'tsrole.perm';
        $perm->save();
        $this->models []= $perm;
        $this->role->Permissions->add($perm);

        $perm2 = new \Bazalt\Auth\Model\Permission();
        $perm2->id = 'tsrole.perm2';
        $perm2->save();
        $this->models []= $perm2;
        $this->role->Permissions->add($perm2);
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    public function testGetRole()
    {
        $response = new \Bazalt\Rest\Response(400, [
            'id' => 'Role not found'
        ]);
        $this->assertResponse('GET /auth/roles/9999', [
            'data' => json_encode(array(
                'id' => '9999'
            ))
        ], $response);

        $res = $this->role->toArray();
        $res['permissions'] = [
            'tsrole.perm',
            'tsrole.perm2'
        ];
        $response = new \Bazalt\Rest\Response(200, $res);
        $this->assertResponse('GET /auth/roles/'.$this->role->id, [
            'data' => json_encode(array(
                'id' => $this->role->id
            ))
        ], $response);
    }

    public function testSaveRole()
    {
        $response = new \Bazalt\Rest\Response(400, [
            'id' => 'Role not found'
        ]);
        $this->assertResponse('GET /auth/roles/9999', [
            'data' => json_encode(array(
                'id' => '9999'
            ))
        ], $response);


        $response = new \Bazalt\Rest\Response(400, [
            'title' => [
                'required' => 'Field cannot be empty'
            ]
        ]);
        $this->assertResponse('POST /auth/roles/' . $this->role->id, [
            'data' => json_encode(array(
                'id' => $this->user->id
            ))
        ], $response);

        $response = new \Bazalt\Rest\Response(403, 'Permission denied');
        $this->assertResponse('POST /auth/roles/'.$this->role->id, [
            'data' => json_encode(array(
                'id' => $this->role->id,
                'title' => 'test2'
            ))
        ], $response);

        $this->addPermission('auth.can_manage_roles');

        $res = $this->role->toArray();
        $res['title'] = 'test2';
        $res['permissions'] = ['tsrole.perm', 'tsrole.perm2', 'tsrole.perm3'];

        $perm = new \Bazalt\Auth\Model\Permission();
        $perm->id = 'tsrole.perm3';
        $perm->save();
        $this->models []= $perm;

        $response = new \Bazalt\Rest\Response(200, $res);
        $this->assertResponse('POST /auth/roles/'.$this->role->id, [
            'data' => json_encode(array(
                'id' => $this->role->id,
                'title' => 'test2',
                'permissions' => ['tsrole.perm', 'tsrole.perm2', 'tsrole.perm3']
            ))
        ], $response);
    }
}