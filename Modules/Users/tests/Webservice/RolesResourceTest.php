<?php

namespace Modules\Users\Tests\Webservice;

use Bazalt\Auth\Model\Permission;
use Bazalt\Auth\Model\Role;
use Bazalt\Auth\Model\User;
use Bazalt\Rest;
use Bazalt\Session;
use Tonic;

class RolesResourceTest extends \Bazalt\Auth\Test\BaseCase
{
    protected $roles = [];

    protected $permissions = [];

    protected function setUp()
    {
        parent::setUp();

        $this->initApp(getWebServices());
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    private function _addTestRole($i)
    {
        $role = \Bazalt\Auth\Model\Role::create();
        $role->title = 'trole' . $i;
        $role->save();
        $this->models [] = $role;
        $this->roles [] = $role;
    }

    private function _addTestPerm($i)
    {
        $perm = new \Bazalt\Auth\Model\Permission();
        $perm->id = 'perm.' . $i;
        $perm->description = 'Perm ' . $i;
        $perm->save();
        $this->models [] = $perm;
        $this->permissions [] = $perm;
    }

    public function testGetRoles()
    {
        for ($i = 0; $i < 3; $i++) {
            $this->_addTestRole($i);
        }

        $res = [
            'data' => []
        ];

        $role = \Bazalt\Auth\Model\Role::getByName('Администратор');
        $res['data'] []= [
            'id' => $role->id,
            'title' => $role->title
        ];

        $role = \Bazalt\Auth\Model\Role::getByName('Тьютор');
        $res['data'] []= [
            'id' => $role->id,
            'title' => $role->title
        ];

        $role = \Bazalt\Auth\Model\Role::getByName('Пользователь');
        $res['data'] []= [
            'id' => $role->id,
            'title' => $role->title
        ];

        $roles = \Bazalt\Auth\Model\Role::getGuestRoles();
        $res['data'] []= [
            'id' => $roles[0]->id,
            'title' => $roles[0]->title
        ];

        foreach($this->roles as $role) {
            $res['data'] []= [
                'id' => $role->id,
                'title' => $role->title
            ];
        }

        $response = new \Bazalt\Rest\Response(200, $res);
        $this->assertResponse('GET /auth/roles', [
        ], $response);
    }

    public function testGetPermissions()
    {
        for ($i = 0; $i < 3; $i++) {
            $this->_addTestPerm($i);
        }

        $this->addPermission('auth.can_manage_roles');

        $res = [
            'data' => []
        ];
        $res['data'] []= [
            'id' => 'auth.can_delete_user',
            'title' => 'Пользователь может удалять других пользователей, кроме себя'
        ];
        $res['data'] []= [
            'id' => 'auth.can_edit_roles',
            'title' => 'Пользователь может изменить роли других пользователей, кроме себя'
        ];
        $res['data'] []= [
            'id' => 'auth.can_edit_users',
            'title' => 'Пользователь может редактировать других пользователей'
        ];
        $res['data'] []= [
            'id' => 'auth.can_manage_roles',
            'title' => 'Пользователь может управлять ролями'
        ];
        $res['data'] []= [
            'id' => 'courses.can_manage_courses',
            'title' => 'Пользователь может управлять курсами'
        ];
        $res['data'] []= [
            'id' => 'pages.can_manage_pages',
            'title' => 'Пользователь может управлять инфо ресурсами'
        ];

        foreach($this->permissions as $permission) {
            $res['data'] []= [
                'id' => $permission->id,
                'title' => $permission->description
            ];
        }

        $res['data'] []= [
            'id' => 'tasks.can_manage_tasks',
            'title' => 'Пользователь может управлять заданиями'
        ];
        $res['data'] []= [
            'id' => 'tests.can_manage_attempts',
            'title' => 'Пользователь может управлять попытками тестирования'
        ];
        $res['data'] []= [
            'id' => 'tests.can_manage_tests',
            'title' => 'Пользователь может управлять тестами'
        ];


        $_GET['action'] = 'permissions';
        $response = new \Bazalt\Rest\Response(200, $res);
        $this->assertResponse('GET /auth/roles', [
        ], $response);
    }

    public function testSaveRole()
    {
        $response = new \Bazalt\Rest\Response(400, [
            'title' => [
                'required' => 'Field cannot be empty'
            ]
        ]);
        $this->assertResponse('POST /auth/roles/', [
            'data' => json_encode([])
        ], $response);

        $response = new \Bazalt\Rest\Response(403, 'Permission denied');
        $this->assertResponse('POST /auth/roles/', [
            'data' => json_encode([
                'title' => 'test2'
            ])
        ], $response);

        $this->addPermission('auth.can_manage_roles');

        $perm = new \Bazalt\Auth\Model\Permission();
        $perm->id = 'roles.test_create';
        $perm->save();
        $this->models []= $perm;

        list($code, $retResponse) = $this->send('POST /auth/roles/', [
            'data' => json_encode([
                'title' => 'test_create',
                'description' => 'description',
                'permissions' => ['roles.test_create']
            ])
        ]);

        $role = \Bazalt\Auth\Model\Role::getById($retResponse['id']);
        $this->models []= $role;
        $res = $role->toArray();
        $res['permissions'] = ['roles.test_create'];
        $response = new \Bazalt\Rest\Response(200, $res);
        $this->assertEquals($response->code, $code, json_encode($retResponse));
        $this->assertEquals($response->body, $retResponse);
    }
}