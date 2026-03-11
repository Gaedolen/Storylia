<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDBODMProxies\__CG__\App\Document\Log;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestMongoController extends AbstractController
{
    #[Route('/test-mongo', name: 'test_mongo')]
    public function index(DocumentManager $dm): Response
    {
        // Vérifie la connexion à la DB
        $db = $dm->getDocumentDatabase(Log::class); // n'importe quel document existant
        /** @var \MongoDB\Collection[] $collections */
        $collections = $db->listCollections();

        return new Response("MongoDB connecté ✅ Collections trouvées: " . count($collections));
    }
}