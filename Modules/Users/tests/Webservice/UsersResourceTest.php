<?php

namespace Modules\Users\Tests\Webservice;

use Bazalt\Auth\Model\Permission;
use Bazalt\Auth\Model\Role;
use Bazalt\Auth\Model\User;
use Bazalt\Rest;
use Bazalt\Session;
use Tonic;

class UsersResourceTest extends \Bazalt\Auth\Test\BaseCase
{
    protected $mailer = null;

    protected $users = [];

    protected function setUp()
    {
        parent::setUp();

        $this->initApp(getWebServices());

        $this->users [] = \Bazalt\Auth\Model\User::getById((int)$this->user->id);

        $this->mailer = $this->getMock('Modules\\Notification\\Transport\\Email', array('send'));
    }

    public function tearDown()
    {
        $this->mailer = null;

        parent::tearDown();
    }

    private function _addTestUser($i)
    {
        $user = \Bazalt\Auth\Model\User::create();
        $user->login = 'tuser' . $i . '@equalteam.net';
        $user->email = 'tuser' . $i . '@equalteam.net';
        $user->firstname = 'tuser#' . $i;
        $user->secondname = '#' . $i % 2;
        $user->save();
        $this->models [] = $user;
        $this->users [] = \Bazalt\Auth\Model\User::getById((int)$user->id);
    }

    private function _testGetUsersSorting($order, $sorting = array())
    {
        $res = [
            'data' => [],
            'pager' => [
                'current' => 1,
                'count' => 1,
                'total' => count($this->users),
                'countPerPage' => 10
            ]
        ];
        foreach ($order as $i) {
            $res['data'] [] = $this->userToArray($this->users[$i]);
        }
        $response = new \Bazalt\Rest\Response(200, $res);
        if (count($sorting) > 0) {
            \Bazalt\Rest\Resource::params(array(
                'sorting' => $sorting
            ));
        }
        $this->assertResponse('GET /auth/users', [
        ], $response);
    }

    public function userToArray($user)
    {
        $arr = $user->toArray();
        $rolesArr = [];
        $roles = $user->Roles->get();
        foreach ($roles as $role) {
            $rolesArr [] = $role->title;
        }

        $arr['created_at'] = strtotime($arr['created_at']) . '000';
        $arr['last_activity'] = strtotime($arr['last_activity']) . '000';
        $arr['roles'] = implode(', ', $rolesArr);
        $arr['photo_thumb'] = '';
        return $arr;
    }

    public function testGetUsers()
    {
        for ($i = 0; $i < 3; $i++) {
            $this->_addTestUser($i);
        }

        $user = \Bazalt\Auth\Model\User::getUserByLogin('admin');
        $this->users [] = $user;
        $this->addPermission('auth.can_edit_users');

        $this->_testGetUsersSorting(array(1, 2, 3, 4, 0));

        $this->_testGetUsersSorting(array(4, 0, 1, 2, 3), array('id' => 'asc'));

        $this->_testGetUsersSorting(array(3, 2, 1, 0, 4), array('id' => 'desc'));

        $this->_testGetUsersSorting(array(0, 4, 1, 3, 2), array('fullname' => 'asc'));

        $this->_testGetUsersSorting(array(4, 1, 2, 3, 0), array('login' => 'asc'));

        $this->_testGetUsersSorting(array(4, 0, 1, 2, 3), array('email' => 'asc'));

        $this->_testGetUsersSorting(array(4, 0, 1, 2, 3), array('created_at' => 'asc'));

        $this->_testGetUsersSorting(array(4, 1, 2, 3, 0), array('last_activity' => 'asc'));
        $res = [
            'data' => [],
            'pager' => [
                'current' => 1,
                'count' => 1,
                'total' => 1,
                'countPerPage' => 10
            ]
        ];
        $res['data'] [] = $this->userToArray($this->users[3]);
        $response = new \Bazalt\Rest\Response(200, $res);
        \Bazalt\Rest\Resource::params(array(
            'filter' => array('id' => $this->users[3]->id)
        ));
        $this->assertResponse('GET /auth/users', [
        ], $response);

        $res = [
            'data' => [],
            'pager' => [
                'current' => 1,
                'count' => 1,
                'total' => 1,
                'countPerPage' => 10
            ]
        ];
        $res['data'] [] = $this->userToArray($this->users[3]);
        $response = new \Bazalt\Rest\Response(200, $res);
        \Bazalt\Rest\Resource::params(array(
            'filter' => array('fullname' => '#2')
        ));
        $this->assertResponse('GET /auth/users', [
        ], $response);


        $res = [
            'data' => [],
            'pager' => [
                'current' => 1,
                'count' => 1,
                'total' => 1,
                'countPerPage' => 10
            ]
        ];
        $res['data'] [] = $this->userToArray($this->users[3]);
        $response = new \Bazalt\Rest\Response(200, $res);
        \Bazalt\Rest\Resource::params(array(
            'filter' => array('login' => 'tuser2')
        ));
        $this->assertResponse('GET /auth/users', [
        ], $response);

        $res = [
            'data' => [],
            'pager' => [
                'current' => 1,
                'count' => 1,
                'total' => 4,
                'countPerPage' => 10
            ]
        ];
        $res['data'] [] = $this->userToArray($this->users[1]);
        $res['data'] [] = $this->userToArray($this->users[2]);
        $res['data'] [] = $this->userToArray($this->users[3]);
        $res['data'] [] = $this->userToArray($this->users[0]);
        $response = new \Bazalt\Rest\Response(200, $res);
        \Bazalt\Rest\Resource::params(array(
            'filter' => array(
                'created_at' => 1
            ),
            'created_at' => array(date('Y-m-d', strtotime($this->users[3]->created_at)), date('Y-m-d', strtotime($this->users[3]->created_at)))
        ));
        $this->assertResponse('GET /auth/users', [
        ], $response);

        $res = [
            'data' => [],
            'pager' => [
                'current' => 1,
                'count' => 1,
                'total' => 4,
                'countPerPage' => 10
            ]
        ];

        $res['data'] [] = $this->userToArray($this->users[1]);
        $res['data'] [] = $this->userToArray($this->users[2]);
        $res['data'] [] = $this->userToArray($this->users[3]);
        $res['data'] [] = $this->userToArray($this->users[0]);
        $response = new \Bazalt\Rest\Response(200, $res);
        \Bazalt\Rest\Resource::params(array(
            'filter' => array(
                'created_at' => 1
            ),
            'created_at' => array(date('Y-m-d', strtotime($this->users[0]->created_at)), date('Y-m-d', strtotime($this->users[3]->created_at)))
        ));
        $this->assertResponse('GET /auth/users', [
        ], $response);

        $res = [
            'data' => [],
            'pager' => [
                'current' => 1,
                'count' => 1,
                'total' => 1,
                'countPerPage' => 10
            ]
        ];
        $res['data'] [] = $this->userToArray($this->users[0]);
        $response = new \Bazalt\Rest\Response(200, $res);
        \Bazalt\Rest\Resource::params(array(
            'filter' => array(
                'last_activity' => 1
            ),
            'last_activity' => array(date('Y-m-d', strtotime($this->users[0]->created_at)), date('Y-m-d', strtotime($this->users[0]->created_at)))
        ));
        $this->assertResponse('GET /auth/users', [
        ], $response);


        $role = \Bazalt\Auth\Model\Role::create();
        $role->title = 'TEST111';
        $role->save();
        $this->models [] = $role;
        $this->users[0]->Roles->add($role);
        $res = [
            'data' => [],
            'pager' => [
                'current' => 1,
                'count' => 1,
                'total' => 1,
                'countPerPage' => 10
            ]
        ];
        $res['data'] [] = $this->userToArray($this->users[0]);
        $response = new \Bazalt\Rest\Response(200, $res);
        \Bazalt\Rest\Resource::params(array(
            'filter' => array('roles' => $role->id)
        ));
        $this->assertResponse('GET /auth/users', [
        ], $response);
    }

    public function testSaveUser()
    {
        $response = new \Bazalt\Rest\Response(400, [
            'email' => [
                'required' => 'Field cannot be empty',
                'email' => 'Invalid email'
            ]
        ]);
        $this->assertResponse('POST /auth/users', [
        ], $response);


        $response = new \Bazalt\Rest\Response(400, [
            'email' => [
                'uniqueEmail' => 'User with this email already exists'
            ]
        ]);
        $this->assertResponse('POST /auth/users', [
            'data' => json_encode(array(
                'email' => $this->user->email
            ))
        ], $response);


        $data = array();
        $data['email'] = 'test2@equalteam.net';;

        $templateCont = 'Для активации перейдите по ссылке';

        \Modules\Notification\Broker::setDefaultTransport($this->mailer);
        $this->mailer->expects($this->once())
            ->method('send')
            ->with(
                $this->equalTo(array('no-reply@domain.com' => 'Collaborator')),
                $this->equalTo($data['email']),
                $this->equalTo('Els'),
                $this->stringContains($templateCont)
            );

        $this->send('POST /auth/users', [
            'data' => json_encode($data)
        ]);

        $user = \Bazalt\Auth\Model\User::getUserByLogin($data['email']);
        $this->assertEquals($data['email'], $user->email);
        $this->assertFalse((bool)$user->is_active);
        $this->models [] = $user;
    }
}