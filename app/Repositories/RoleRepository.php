<?php

namespace App\Repositories;

use App\Models\Role;
use RonasIT\Support\Repositories\BaseRepository;

/**
 * @property  Role $model
*/
class RoleRepository extends BaseRepository
{
    const ADMIN_ROLE = 1;
    const USER_ROLE = 2;

    public function __construct()
    {
        $this->setModel(Role::class);
    }

    public function search($filters)
    {
        return $this
            ->searchQuery($filters)
            ->filterByQuery(['name'])
            ->getSearchResults();
    }
}
