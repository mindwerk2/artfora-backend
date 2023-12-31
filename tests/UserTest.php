<?php

namespace App\Tests;

use App\Facades\TokenGenerator;
use App\Models\User;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;

class UserTest extends TestCase
{
    protected $admin;
    protected $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->admin = User::find(1);
        $this->user = User::find(2);
    }

    public function testCreate()
    {
        TokenGenerator::shouldReceive('getRandom')->andReturn('123123123');

        $data = $this->getJsonFixture('create_user.json');

        $response = $this->actingAs($this->admin)->json('post', '/users', $data);

        $response->assertStatus(Response::HTTP_OK);

        $expect = Arr::except($data, ['id', 'password', 'updated_at', 'created_at']);
        $actual = Arr::except($response->json(), ['id', 'updated_at', 'created_at']);

        $this->assertEqualsFixture('create_user_response.json', $actual);

        $this->assertDatabaseHas('users', $expect);
    }

    public function testCreateNoAuth()
    {
        $data = $this->getJsonFixture('create_user.json');

        $response = $this->json('post', '/users', $data);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);

        $this->assertDatabaseMissing('users', Arr::except($data, ['password']));
    }

    public function testCreateNoPermission()
    {
        $data = $this->getJsonFixture('create_user.json');

        $response = $this->actingAs($this->user)->json('post', '/users', $data);

        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $this->assertDatabaseMissing('users', Arr::except($data, ['password']));
    }

    public function testCreateUserExists()
    {
        $response = $this->actingAs($this->admin)->json('post', '/users', $this->user->toArray());

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testUpdate()
    {
        $data = $this->getJsonFixture('update_user.json');

        $response = $this->actingAs($this->admin)->json('put', '/users/2', $data);

        $response->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertDatabaseHas('users', $data);
    }

    public function testUpdateNoPermission()
    {
        $data = $this->getJsonFixture('update_user.json');

        $response = $this->actingAs($this->user)->json('put', '/users/1', $data);

        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $this->assertDatabaseMissing('users', [
            'id' => 1,
            'username' => $data['username'],
            'email' => $data['email']
        ]);
    }

    public function testUpdateWithEmailOfAnotherUser()
    {
        $response = $this->actingAs($this->admin)->json('put', '/users/2', [
            'email' => 'admin@example.com'
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertDatabaseMissing('users', [
            'id' => 2,
            'email' => 'admin@example.com'
        ]);
    }

    public function testUpdateNotExists()
    {
        $data = $this->getJsonFixture('update_user.json');

        $response = $this->actingAs($this->admin)->json('put', '/users/0', $data);

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function testUpdateNoAuth()
    {
        $data = $this->getJsonFixture('update_user.json');

        $response = $this->json('put', '/users/1', $data);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);

        $this->assertDatabaseMissing('users', $data);
    }

    public function testUpdateProfile()
    {
        $data = $this->getJsonFixture('update_user.json');

        $data['email'] = 'test@example.com';

        $response = $this->actingAs($this->admin)->json('put', '/profile', $data);

        $response->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertDatabaseHas('users', $data);
    }

    public function testUpdateProfileNoAuth()
    {
        $data = $this->getJsonFixture('update_user.json');

        $response = $this->json('put', '/profile', $data);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);

        $this->assertDatabaseMissing('users', $data);
    }

    public function testDelete()
    {
        $response = $this->actingAs($this->admin)->json('delete', '/users/1');

        $response->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertSoftDeleted('users', [
            'id' => 1
        ]);
    }

    public function testDeleteNotExists()
    {
        $response = $this->actingAs($this->admin)->json('delete', '/users/0');

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function testDeleteNoAuth()
    {
        $response = $this->json('delete', '/users/1');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);

        $this->assertDatabaseHas('users', [
            'id' => 1
        ]);
    }

    public function testGetProfile()
    {
        $response = $this->actingAs($this->admin)->json('get', '/profile');

        $response->assertStatus(Response::HTTP_OK);

        $this->assertEqualsFixture('get_user.json', $response->json());
    }

    public function testGet()
    {
        $response = $this->actingAs($this->admin)->json('get', '/users/1');

        $response->assertStatus(Response::HTTP_OK);

        $this->assertEqualsFixture('get_user.json', $response->json());
    }

    public function testGetNotExists()
    {
        $response = $this->actingAs($this->admin)->json('get', '/users/0');

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function getSearchFilters()
    {
        return [
            [
                'filter' => ['all' => 1],
                'result' => 'search_by_all_user.json'
            ],
            [
                'filter' => [
                    'page' => 1,
                    'per_page' => 2,
                ],
                'result' => 'search_by_page_per_page_user.json'
            ],
            [
                'filter' => ['query' => 'Another User'],
                'result' => 'get_users_by_name.json'
            ],
            [
                'filter' => ['query' => 'admin@example.com'],
                'result' => 'get_users_by_email.json'
            ],
            [
                'filter' => ['query' => 'Admin'],
                'result' => 'get_users_by_query.json'
            ],
            [
                'filter' => [
                    'query' => 'Admin',
                    'order_by' => 'created_at',
                    'desc' => false
                ],
                'result' => 'get_users_complex.json'
            ],
            [
                'filter' => [
                    'desc' => false,
                    'order_by' => 'username'
                ],
                'result' => 'get_users_check_order.json'
            ]
        ];
    }

    /**
     * @dataProvider  getSearchFilters
     *
     * @param array $filter
     * @param string $fixture
     */
    public function testSearch($filter, $fixture)
    {
        $response = $this->actingAs($this->admin)->json('get', '/users', $filter);

        $response->assertStatus(Response::HTTP_OK);

        $this->assertEqualsFixture($fixture, Arr::except($response->json(), ['links']));
    }
}
