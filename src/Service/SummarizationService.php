<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DomCrawler\Crawler;

class SummarizationService
{
    private HttpClientInterface $httpClient;
    private string $googleApiKey;

    public function __construct(HttpClientInterface $httpClient, string $googleApiKey)
    {
        $this->httpClient = $httpClient;
        $this->googleApiKey = $googleApiKey;
    }

    public function summarizeUrl(string $url): ?string
    {
        $articleContent = $this->fetchAndCleanArticle($url);

        if (null === $articleContent) {
            return null;
        }

        return $this->callGeminiApi($articleContent);
    }

    private function fetchAndCleanArticle(string $url): ?string
    {
        try {
            $response = $this->httpClient->request('GET', $url);
            $html = $response->getContent();

            $crawler = new Crawler($html);

            // Próba znalezienia głównej treści artykułu na podstawie typowych selektorów
            $articleNode = $crawler->filter('article, .article-content, .post-content, #main-content');

            $targetNode = $articleNode->count() > 0 ? $articleNode : $crawler->filter('body');
            $htmlContent = $targetNode->count() > 0 ? $targetNode->html() : ''; // Ensure htmlContent is always a string
            if (empty($htmlContent)) {
                return null; // If no content found after filtering
            }

            $text = strip_tags($htmlContent);

            // Usunięcie nadmiarowych białych znaków i pustych linii
            $text = preg_replace('/\s+/u', ' ', $text); // Zastąp wiele spacji jedną
            $text = trim($text);

            // Ograniczenie tekstu do rozsądnej długości dla API (np. 10000 znaków)
            if (mb_strlen($text) > 10000) {
                $text = mb_substr($text, 0, 10000);
            }

            return $text;
        } catch (\Exception $e) {
            // Logowanie błędu, np. do pliku logów Symfony
            // $this->logger->error('Błąd podczas pobierania lub czyszczenia artykułu: ' . $e->getMessage());
            return null;
        }
    }

    private function callGeminiApi(string $text): ?string
    {
        $prompt = "Streszcz ten tekst w języku polskim, w maksymalnie 3-4 zdaniach: " . $text;

        try {
            $response = $this->httpClient->request('POST', "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=" . $this->googleApiKey, [
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ]
                ]
            ]);

            $data = $response->toArray();

            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                return $data['candidates'][0]['content']['parts'][0]['text'];
            }

            return null;
        } catch (\Exception $e) {
            // Logowanie błędu
            // $this->logger->error('Błąd podczas wywoływania Gemini API: ' . $e->getMessage());
            return null;
        }
    }
}