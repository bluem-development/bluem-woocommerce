<?php

namespace Bluem\Wordpress\Users;

final class BluemUserIndexer
{
    /**
     * @param array<int, object> $users
     * @return array<int|string, object>
     */
    public function index(array $users): array
    {
        $usersById = [];

        foreach ($users as $user) {
            $usersById[$user->ID] = $user;
        }

        return $usersById;
    }
}
