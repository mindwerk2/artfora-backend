<?php

namespace App\Services;

use Illuminate\Support\Arr;
use App\Repositories\SettingRepository;
use Artel\Support\Services\EntityService;

/**
 * @property SettingRepository $repository
 */
class SettingService extends EntityService
{
    public function __construct()
    {
        $this->setRepository(SettingRepository::class);
    }

    public function search($filters)
    {
        return $this->repository
            ->searchQuery($filters)
            ->filterByQuery(['name'])
            ->orderBy('name')
            ->getSearchResults();
    }

    public function get($key, $default = null)
    {
        $explodedKey = explode('.', $key);
        $primaryKey = array_shift($explodedKey);

        $setting = $this->repository->findBy('name', $primaryKey);

        if (empty($setting)) {
            return $default;
        }

        if (empty($explodedKey)) {
            return $setting['value'];
        }

        array_unshift($explodedKey, 'value');
        $valuePath = implode('.', $explodedKey);

        return Arr::get($setting, $valuePath);
    }

    public function set($key, $value)
    {
        $explodedKey = explode('.', $key);
        $primaryKey = array_shift($explodedKey);

        $setting = $this->repository->findBy('name', $primaryKey);

        if (empty($setting)) {
            return $this->repository->create([
                'name' => $key,
                'value' => $value
            ]);
        }

        array_unshift($explodedKey, 'value');
        $valuePath = implode('.', $explodedKey);

        Arr::set($setting, $valuePath, $value);

        return $this->repository->update([
            'name' => $primaryKey
        ], [
            'value' => $setting['value']
        ]);
    }
}
