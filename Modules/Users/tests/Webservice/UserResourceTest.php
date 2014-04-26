<?php

namespace Modules\Users\Tests\Webservice;

use Bazalt\Auth\Model\Permission;
use Bazalt\Auth\Model\Role;
use Bazalt\Auth\Model\User;
use Bazalt\Rest;
use Bazalt\Session;
use Tonic;

class UserResourceTest extends \Bazalt\Auth\Test\BaseCase
{
    protected $mailer = null;

    protected function setUp()
    {
        parent::setUp();

        $this->initApp(getWebServices());

        $this->mailer = $this->getMock('Modules\\Notification\\Transport\\Email', array('send'));
    }

    public function tearDown()
    {
        $this->mailer = null;

        parent::tearDown();
    }

    public function testDelete()
    {
        $user = User::create();
        $user->login = 'test';
        $user->is_active = true;
        $user->save();
        $this->models [] = $user;

        $response = new \Bazalt\Rest\Response(403, 'Permission denied');
        $this->assertResponse('DELETE /auth/users/' . $user->id, ['contentType' => 'application/json'], $response);

        $user = User::getById($user->id);
        $this->assertEquals(0, $user->is_deleted);


        $this->addPermission('auth.can_delete_user', $user);

        // login
        \Bazalt\Auth::setUser($user);

        $response = new \Bazalt\Rest\Response(400, ['id' => 'Can\'t delete yourself']);
        $this->assertResponse('DELETE /auth/users/' . $user->id, ['contentType' => 'application/json'], $response);

        $user = User::getById($user->id);
        $this->assertEquals(0, $user->is_deleted);

        $user2 = User::create();
        $user2->login = 'test2';
        $user2->is_active = true;
        $user2->save();
        $this->models [] = $user2;

        $this->addPermission('auth.can_delete_user', $user2);

        // login
        \Bazalt\Auth::setUser($user2);

        $response = new \Bazalt\Rest\Response(200, true);
        $this->assertResponse('DELETE /auth/users/' . $user->id, ['contentType' => 'application/json'], $response);

        $user = User::getById($user->id);
        $this->assertEquals(1, $user->is_deleted);
    }

    private function _testGetUserById($action = '')
    {
        $response = new \Bazalt\Rest\Response(400, [
            'id' => 'User not found'
        ]);
        if($action) {
            $_GET['action'] = $action;
        }
        $this->assertResponse('PUT /auth/users/9999', [
            'data' => json_encode(array(
                'id' => '9999'
            ))
        ], $response);

        $this->user->is_deleted = true;
        $this->user->save();
        $response = new \Bazalt\Rest\Response(400, [
            'id' => 'User not found'
        ]);
        if($action) {
            $_GET['action'] = $action;
        }
        $this->assertResponse('PUT /auth/users/' . $this->user->id, [
            'data' => json_encode(array(
                'id' => $this->user->id
            ))
        ], $response);
        $this->user->is_deleted = false;
        $this->user->save();
    }

    private function _test403($action = '')
    {
        $response = new \Bazalt\Rest\Response(403, 'Permission denied');
        if($action) {
            $_GET['action'] = $action;
        }
        $this->assertResponse('PUT /auth/users/' . $this->user->id, [
            'data' => json_encode(array(
                'id' => $this->user->id
            ))
        ], $response);
    }

    public function testDisableUser()
    {
        $this->_testGetUserById('disable');

        $this->_test403('disable');

        $this->addPermission('auth.can_edit_users');

        $response = new \Bazalt\Rest\Response(400, [
            'id' => 'Can\'t disable yourself'
        ]);
        $_GET['action'] = 'disable';
        $this->assertResponse('PUT /auth/users/' . $this->user->id, [
            'data' => json_encode(array(
                'id' => $this->user->id
            ))
        ], $response);


        $user2 = User::create();
        $user2->login = 'testuser2';
        $user2->is_active = false;
        $user2->save();
        $this->models []= $user2;

        $response = new \Bazalt\Rest\Response(400, [
            'user_disabled' => 'User already disabled'
        ]);
        $_GET['action'] = 'disable';
        $this->assertResponse('PUT /auth/users/' . $user2->id, [
            'data' => json_encode(array(
                'id' => $user2->id
            ))
        ], $response);

        $user2->is_active = true;
        $user2->save();

        $user2 = User::getById($user2->id);
        $user2->is_active = false;
        $response = new \Bazalt\Rest\Response(200, $user2->toArray());
        $_GET['action'] = 'disable';
        $this->assertResponse('PUT /auth/users/' . $user2->id, [
            'data' => json_encode(array(
                'id' => $user2->id,
                'block_message' => 'You are blocked now'
            ))
        ], $response);

        $user2 = User::getById($user2->id);
        $this->assertEquals('You are blocked now', $user2->setting('block_message'));
    }

    public function testEnableUser()
    {
        $this->_testGetUserById('enable');

        $this->_test403('enable');

        $this->addPermission('auth.can_edit_users');

        $user2 = User::create();
        $user2->login = 'testuser3';
        $user2->is_active = true;
        $user2->save();
        $this->models []= $user2;

        $response = new \Bazalt\Rest\Response(400, [
            'user_disabled' => 'User already enabled'
        ]);
        $_GET['action'] = 'enable';
        $this->assertResponse('PUT /auth/users/' . $user2->id, [
            'data' => json_encode(array(
                'id' => $user2->id
            ))
        ], $response);

        $user2->is_active = false;
        $user2->save();

        $user2 = User::getById($user2->id);
        $user2->is_active = true;
        $response = new \Bazalt\Rest\Response(200, $user2->toArray());
        $_GET['action'] = 'enable';
        $this->assertResponse('PUT /auth/users/' . $user2->id, [
            'data' => json_encode(array(
                'id' => $user2->id
            ))
        ], $response);

        $user2 = User::getById($user2->id);
        $this->assertEquals('', $user2->setting('block_message'));
    }

    public function testSaveUserRoles()
    {
        $this->_testGetUserById('save-roles');

        $this->_test403('save-roles');

        $this->addPermission('auth.can_edit_roles');

        $role = \Bazalt\Auth\Model\Role::create();
        $role->title = 'TEST111';
        $role->save();
        $this->models []= $role;

        $response = new \Bazalt\Rest\Response(400, [
            'roles' => [
                'validRoles' => 'Invalid roles'
            ]
        ]);
        $_GET['action'] = 'save-roles';
        $this->assertResponse('PUT /auth/users/' . $this->user->id, [
            'data' => json_encode(array(
                'id' => $this->user->id,
                'roles' => [
                    9999
                ]
            ))
        ], $response);

        $_GET['action'] = 'save-roles';
        $this->assertResponseCode('PUT /auth/users/' . $this->user->id, [
            'data' => json_encode(array(
                'id' => $this->user->id,
                'roles' => [
                    $role->id
                ]
            ))
        ], 200);

        $this->assertTrue($this->user->hasRole($role->id, $this->site));
    }

    public function testSaveUser()
    {
        $this->_testGetUserById();

        $this->_test403();

        $this->addPermission('auth.can_edit_users');

        $response = new \Bazalt\Rest\Response(400, [
            'email' => [
                'required' => 'Field cannot be empty',
                'email' => 'Invalid email'
            ],
            'password' => [
                'required' => 'Field cannot be empty'
            ],
            'login' => [
                'required' => 'Field cannot be empty'
            ],
            'gender' => [
                'required' => 'Field cannot be empty'
            ]
        ]);
        $this->assertResponse('POST /auth/users/' . $this->user->id, [
            'data' => json_encode(array(
                'id' => $this->user->id
            ))
        ], $response);

        /*$response = new \Bazalt\Rest\Response(400, [
            'email' => [
                'required' => 'Field cannot be empty',
                'email' => 'Invalid email'
            ],
            'password' => [
                'equal' => 'Fields not equals'
            ],
            'login' => [
                'required' => 'Field cannot be empty'
            ],
            'gender' => [
                'required' => 'Field cannot be empty'
            ]
        ]);
        $this->assertResponse('POST /auth/users/' . $this->user->id, [
            'data' => json_encode(array(
                'id' => $this->user->id,
                'password' => '1',
                'spassword' => '2'
            ))
        ], $response);
*/
        $response = new \Bazalt\Rest\Response(400, [
            'email' => [
                'uniqueEmail' => 'User with this email already exists'
            ],
            'password' => [
                'required' => 'Field cannot be empty'
            ],
            'login' => [
                'required' => 'Field cannot be empty'
            ],
            'gender' => [
                'required' => 'Field cannot be empty'
            ]
        ]);
        $user2 = \Bazalt\Auth\Model\User::create();
        $user2->login = 'test221';
        $user2->save();
        $this->models [] = $user2;
        $this->assertResponse('POST /auth/users/' . $user2->id, [
            'data' => json_encode(array(
                'id' => $user2->id,
                'email' => $this->user->email
            ))
        ], $response);


        $arr = array();
        $arr['id'] =  $this->user->id;
        $arr['email'] = 'ccc' . $this->user->email;
        $arr['login'] = 'ccc' . $this->user->login;
        $arr['firstname'] = 'ccc' . $this->user->firstname;
        $arr['secondname'] = 'ccc' . $this->user->secondname;
        $arr['patronymic'] = 'ccc' . $this->user->patronymic;
        $arr['gender'] = 'male';
        $arr['birth_date'] = '1988-07-26';
        $arr['password'] = '1';
        $arr['spassword'] = '1';
        $arr['is_active'] = 1;
        $arr['is_deleted'] = 0;

        \Modules\Notification\Broker::setDefaultTransport($this->mailer);
        $this->mailer->expects($this->once())
            ->method('send')
            ->with(
                $this->equalTo(array('no-reply@domain.com' => 'Collaborator')),
                $this->equalTo($arr['email']),
                $this->equalTo('Els')
            );

        $this->assertResponseCode('POST /auth/users/' . $this->user->id, [
            'data' => json_encode($arr)
        ], 200);

        $user = \Bazalt\Auth\Model\User::getById($this->user->id);
        $this->assertEquals($arr['email'], $user->email);
        $this->assertEquals($arr['login'], $user->login);
        $this->assertEquals($arr['firstname'], $user->firstname);
        $this->assertEquals($arr['secondname'], $user->secondname);
        $this->assertEquals($arr['patronymic'], $user->patronymic);
        $this->assertEquals($arr['birth_date'], $user->birth_date);
        $this->assertEquals('male', $user->gender);
        $this->assertEquals(1, $user->is_active);
        $this->assertEquals(0, $user->is_deleted);
    }

    public function testActivateUser()
    {
        $response = new \Bazalt\Rest\Response(400, [
            'id' => 'User not found'
        ]);
        $_GET['action'] = 'activate';
        $this->assertResponse('PUT /auth/users/9999', [
            'data' => json_encode(array(
                'id' => '9999'
            ))
        ], $response);

        $this->user->is_deleted = true;
        $this->user->save();
        $response = new \Bazalt\Rest\Response(400, [
            'id' => 'User not found'
        ]);
        $_GET['action'] = 'activate';
        $this->assertResponse('PUT /auth/users/' . $this->user->id, [
            'data' => json_encode(array(
                'id' => $this->user->id
            ))
        ], $response);
        $this->user->is_deleted = false;
        $this->user->save();


        $response = new \Bazalt\Rest\Response(400, [
            'key' => [
                'invalid' => 'Invalid activation key'
            ]
        ]);
        $_GET['action'] = 'activate';
        $this->assertResponse('PUT /auth/users/' . $this->user->id, [
            'data' => json_encode(array(
                'id' => $this->user->id
            ))
        ], $response);

        $response = new \Bazalt\Rest\Response(400, [
            'key' => [
                'invalid' => 'Invalid activation key'
            ]
        ]);
        $_GET['action'] = 'activate';
        $_GET['key'] = '000';
        $this->assertResponse('PUT /auth/users/' . $this->user->id, [
            'data' => json_encode(array(
                'id' => $this->user->id
            ))
        ], $response);

        $this->user->is_active = false;
        $this->user->save();
        $_GET['action'] = 'activate';
        $_GET['key'] = $this->user->getActivationKey();
        $this->send('PUT /auth/users/' . $this->user->id, [
            'data' => json_encode(array(
                'id' => $this->user->id
            ))
        ]);
        $this->user = \Bazalt\Auth\Model\User::getById($this->user->id);
//        print_r($this->user);
        $this->assertTrue((bool)$this->user->is_active);
        $this->assertTrue((bool)$this->user->need_edit);


        $response = new \Bazalt\Rest\Response(400, [
            'key' => [
                'user_activated' => 'User already activated'
            ]
        ]);
        $_GET['action'] = 'activate';
        $_GET['key'] = $this->user->getActivationKey();
        $this->assertResponse('PUT /auth/users/' . $this->user->id, [
            'data' => json_encode(array(
                'id' => $this->user->id
            ))
        ], $response);
    }
}