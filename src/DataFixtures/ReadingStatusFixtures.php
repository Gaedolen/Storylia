<?php

namespace App\DataFixtures;

use App\Entity\ReadingStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ReadingStatusFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $statuses = [
            'en_train_de_lire',
            'coup_de_coeur',
            'adore',
            'apprecie',
            'mitige',
            'pas_aime',
            'lu_aussi',
            'pal',
            'envies',
        ];

        foreach ($statuses as $status) {
            $readingStatus = new ReadingStatus();
            $readingStatus->setLabel($status);
            $manager->persist($readingStatus);
        }

        $manager->flush();
    }
}
