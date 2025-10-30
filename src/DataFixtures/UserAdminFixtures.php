<?php

namespace App\DataFixtures;

use App\Entity\Role;
use App\Entity\Utilisateur;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserAdminFixtures extends Fixture implements DependentFixtureInterface
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $admin = new Utilisateur();
        $admin->setEmail('admin@admin.com');
        $admin->setFirstName('Admin');
        $admin->setFamilyName('Storylia');
        $admin->setPseudo('SuperAdmin');
        $admin->setBirthDate(new \DateTime('1999-01-04'));
        $admin->setProfilePicture('default.png');

        // Mot de passe sécurisé
        $hashedPassword = $this->passwordHasher->hashPassword($admin, 'Admin123!');
        $admin->setPassword($hashedPassword);

        // Ajout du rôle ADMIN via référence RoleFixtures
        $roleAdmin = $manager->getRepository(Role::class)->findOneBy(['label' => 'ROLE_ADMIN']);
        $admin->setRole($roleAdmin);

        $manager->persist($admin);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            RoleFixtures::class
        ];
    }
}
