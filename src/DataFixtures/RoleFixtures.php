<?php

namespace App\DataFixtures;

use App\Entity\Role;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class RoleFixtures extends Fixture
{
    public const ROLE_ADMIN = 'role_admin';
    public const ROLE_EMPLOYE = 'role_employe';
    public const ROLE_USER = 'role_user';

    public function load(ObjectManager $manager): void
    {
        $roles = [
            'ROLE_ADMIN',
            'ROLE_EMPLOYE',
            'ROLE_USER'
        ];

        foreach ($roles as $label) {
            $role = new Role();
            $role->setLabel($label);
            $manager->persist($role);

            // Pour pouvoir les référencer dans d'autres fixtures
            $this->addReference($label, $role);
        }

        $manager->flush();
    }
}
