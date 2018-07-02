<?php

namespace App\Http\Controllers;

use App\Http\Requests\Setting\CreateSettingRequest;
use App\Http\Requests\Setting\DeleteSettingRequest;
use App\Http\Requests\Setting\GetSettingRequest;
use App\Http\Requests\Setting\SearchSettingRequest;
use App\Http\Requests\Setting\UpdateSettingRequest;
use App\Services\SettingService;
use Symfony\Component\HttpFoundation\Response;

class SettingController extends Controller
{
    public function create(CreateSettingRequest $request, SettingService $service)
    {
        $data = $request->all();

        $result = $service->create($data);

        return response()->json($result);
    }

    public function get(GetSettingRequest $request, SettingService $service, $key)
    {
        $result = $service->first(['key' => $key]);

        return response()->json($result);
    }

    public function update(UpdateSettingRequest $request, SettingService $service, $key)
    {
        $service->update(
            ['key' => $key],
            ['value' => $request->all()]
        );

        return response('', Response::HTTP_NO_CONTENT);
    }

    public function delete(DeleteSettingRequest $request, SettingService $service, $key)
    {
        $service->delete(['key' => $key]);

        return response('', Response::HTTP_NO_CONTENT);
    }

    public function search(SearchSettingRequest $request, SettingService $service)
    {
        $result = $service->search($request->all());

        return response($result);
    }
}