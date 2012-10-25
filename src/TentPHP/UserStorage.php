<?php

namespace TentPHP;

interface UserStorage
{
    /**
     * Load a user object from an entity url.
     *
     * @param string $entityUrl
     * @return User|null
     */
    public function load($entityUrl);

    /**
     * Save a user object
     *
     * @param User $user
     */
    public function save(User $user);
}

