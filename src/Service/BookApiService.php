<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class BookApiService
{
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client; // On injecte le client HTTP de Symfony
    }

    /**
     * Récupère un livre depuis l'API Open Library
     * @param string $title Le titre du livre
     * @param string|null $author Nom de l'auteur
     * @return array|null Tableau avec les infos du livre ou null si pas trouvé
     */
    public function fetchBook(string $title, string $author = null): ?array 
    {
        //Préparation de la requête
        $query = ['title' => $title];
        if ($author) {
            $query['author'] = $author;
        }

        // URL de recherche Open Library
        $url = 'https://openlibrary.org/search.json?' . http_build_query($query);

        // Envoi de la requête HTTP avec Symfony HttpClient
        $response = $this->client->request('GET', $url);

        // Vérification du code HTTP
        if($response->getStatusCode() !== 200) {
            return null; // Erreur lors de l'appel de l'API
        }

        // Conversion de la réponse JSON en tableau PHP
        $data = $response->toArray();

        // Si aucun livre trouvé, on renvoie null
        if(empty($data['docs'])) return null;

        // On prend le premier résultat de la recherche
        $bookData = $data['docs'][0];

        // Retour d'un tableau simplifié avec les infos utiles
        return [
            'title' => $bookData['title'] ?? null,
            'author' => $bookData['author_name'] ?? [],
            'publish_date' => $bookData['publish_date'][0] ?? null,
            'edition' => $bookData['edition'] ?? null,
            'cover' => isset($bookData['cover_i'])
                ? "https://covers.openlibrary.org/b/id/{$bookData['cover_i']}-L.jpg"
                : null,
            'summary' => $bookData['subtitle'] ?? null,
            'genres' => $bookData['subject'] ?? null,
        ];
    }
}