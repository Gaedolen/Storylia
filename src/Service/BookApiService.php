<?php

namespace App\Service;

use Exception;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class BookApiService
{
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client; // On injecte le client HTTP de Symfony
    }

    /**
     * Récupère un livre depuis l'API Google Books
     * @param string $title Le titre du livre
     * @param string|null $author Nom de l'auteur
     * @return array|null Tableau avec les infos du livre ou null si pas trouvé
     */
    public function fetchBook(string $title, string $author = null): ?array
    {
        // Préparation de la requête Google Books
        $query = $title;
        if ($author) {
            $query .= '+inauthor:' . $author;
        }

        // URL de recherche Google Books (limité à 1 résultat, langue française)
        $url = 'https://www.googleapis.com/books/v1/volumes?q=' . urlencode($query) . '&langRestrict=fr&maxResults=1';

        try {
            // Envoi de la requête HTTP
            $response = $this->client->request('GET', $url);

            // Vérifie que la requête s’est bien passée
            if ($response->getStatusCode() !== 200) {
                return null;
            }

            // Conversion du JSON en tableau PHP
            $data = $response->toArray();

            // Si aucun livre trouvé
            if (empty($data['items'])) {
                return null;
            }

            // On prend le premier livre trouvé
            $info = $data['items'][0]['volumeInfo'] ?? [];

            // On ne garde que les livres dont la langue est française
            if (($info['language'] ?? '') !== 'fr') {
                return null;
            }

            // Retour d’un tableau simplifié
            return [
                'title' => $info['title'] ?? 'Titre inconnu', // titre principal en français
                'voTitle' => $info['subtitle'] ?? '',          // VO si dispo
                'author' => $info['authors'][0] ?? 'Auteur inconnu',
                'isbn' => $info['industryIdentifiers'][0]['identifier'] ?? null,
                'publishers' => [$info['publisher'] ?? 'Inconnu'],
                'summary' => $info['description'] ?? '',
                'pages' => $info['pageCount'] ?? null,
                'genres' => array_slice($info['categories'] ?? [], 0, 3),
                'publicationDate' => $info['publishedDate'] ?? null,
                'cover' => $info['imageLinks']['thumbnail'] ?? '/images/default_cover.jpg',
                'format' => $this->guessFormat($info),
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Récupère une liste de livres depuis Google Books
     * On ne garde que ceux en français et ayant un ISBN
     *
     * @return array
     */
    public function fetchBookList(int $startIndex = 0, int $maxResults = 20, ?array $subjects = null): array
    {
        $subjectsList = $subjects ?? $this->fetchAllSubjects();
        $books = [];

        foreach ($subjectsList as $subject) {
            $currentIndex = $startIndex;

            while (true) {
                $url = 'https://www.googleapis.com/books/v1/volumes?' . http_build_query([
                    'q' => 'subject:' . $subject,
                    'langRestrict' => 'fr', // langue française
                    'startIndex' => $currentIndex,
                    'maxResults' => $maxResults,
                ]);

                $response = $this->client->request('GET', $url);

                if ($response->getStatusCode() !== 200) break;

                $data = $response->toArray();
                if (empty($data['items'])) break;

                foreach ($data['items'] as $item) {
                    $info = $item['volumeInfo'] ?? [];

                    // On ne garde que les livres en français
                    if (($info['language'] ?? '') !== 'fr') continue;

                    $isbn = $info['industryIdentifiers'][0]['identifier'] ?? null;

                    $books[] = [
                        'title' => $info['title'] ?? 'Titre inconnu',
                        'voTitle' => $info['subtitle'] ?? '',
                        'author' => $info['authors'][0] ?? 'Auteur inconnu',
                        'isbn' => $isbn,
                        'publishers' => [$info['publisher'] ?? 'Inconnu'],
                        'summary' => $info['description'] ?? '',
                        'pages' => $info['pageCount'] ?? null,
                        'genres' => array_slice($info['categories'] ?? [], 0, 3),
                        'subjects' => array_slice($info['categories'] ?? [], 0, 10),
                        'publicationDate' => $info['publishedDate'] ?? null,
                        'cover' => $info['imageLinks']['thumbnail'] ?? '/images/default_cover.jpg',
                        'format' => $this->guessFormat($info),
                    ];
                }

                $currentIndex += $maxResults;

                // Sortir si on dépasse le total d'items ou le maxResults demandé
                if ($currentIndex >= ($data['totalItems'] ?? 0)) break;
            }
        }

        return $books;
    }

    /**
     * Devine le format du livre
     */
    private function guessFormat(array $info): string
    {
        $categories = array_map('strtolower', $info['categories'] ?? []);
        $title = strtolower($info['title'] ?? '');

        if (str_contains($title, 'manga') || in_array('manga', $categories)) {
            return 'Manga';
        }
        if (in_array('comic', $categories) || str_contains($title, 'bd') || str_contains($title, 'bande dessinée')) {
            return 'Bande dessinée';
        }
        if (str_contains($title, 'poche')) {
            return 'Poche';
        }
        if (str_contains($title, 'relié')) {
            return 'Relié';
        }

        return 'Broché';
    }

    /**
     * Retourne une liste de sujets pour compléter les livres
     */
    public function fetchAllSubjects(): array
    {
        return [
            'Science-fiction', 'Fantastique', 'Fantasy', 'Dystopie', 'Steampunk', 'Aventure', 'Uchronie',
            'Policier', 'Thriller', 'Espionnage', 'Horreur', 'Roman noir', 'Suspense psychologique',
            'Young Adult', 'Romance jeunesse', 'Amitié', 'Coming of Age',
            'Romance contemporaine', 'Romance historique', 'Romance érotique', 'Chick-lit', 'Drame',
            'Essai', 'Biographie', 'Philosophie', 'Historique', 'Histoire', 'Science', 'Sociologie',
            'Psychologie', 'Politique', 'Économie', 'Spiritualité', 'Religion',
            'Poésie', 'Théâtre', 'Musique', 'Cinéma', 'Photographie', 'Art',
            'Contes et légendes', 'Mythologie', 'Folklore', 'Épopée',
            'Roman graphique', 'Bande dessinée', 'Manga', 'Comics', 'Webtoon',
            'Cuisine', 'Voyage', 'Nature', 'Animaux', 'Développement personnel',
            'Éducation', 'Sport', 'Technologie'
        ];
    }
}
