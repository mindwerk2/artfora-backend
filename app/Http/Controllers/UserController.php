<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use App\Http\Requests\Users\GetUserRequest;
use App\Http\Requests\Users\CreateUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Http\Requests\Users\DeleteUserRequest;
use App\Http\Requests\Users\SearchUserRequest;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\Users\UpdateProfileRequest;
use App\Http\Requests\Users\GetUserProfileRequest;

class UserController extends Controller
{
    public function create(CreateUserRequest $request, UserService $service)
    {
        $data = $request->onlyValidated();

        $result = $service->create($data);

        return response()->json($result);
    }

    public function get(GetUserRequest $request, UserService $service, $id)
    {
        $result = $service->find($id);

        return response()->json($result);
    }

    public function update(UpdateUserRequest $request, UserService $service, $id)
    {
        $service->update($id, $request->onlyValidated());

        return response('', Response::HTTP_NO_CONTENT);
    }

    public function profile(GetUserProfileRequest $request, UserService $service)
    {
        $result = $service
            ->with(['avatar_image', 'background_image'])
            ->find($request->user()->id);

        return response()->json($result);
    }

    public function updateProfile(UpdateProfileRequest $request, UserService $service)
    {
        $service->update($request->user()->id, $request->onlyValidated());

        return response('', Response::HTTP_NO_CONTENT);
    }

    public function delete(DeleteUserRequest $request, UserService $service, $id)
    {
        $service->delete($id);

        return response('', Response::HTTP_NO_CONTENT);
    }

    public function search(SearchUserRequest $request, UserService $service)
    {
        $result = $service->search($request->onlyValidated());

        return response()->json($result);
    }
}
