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

            $container = $crawler->filter('article, .post, .entry-content');
            if ($container->count() === 0) {
                $container = $crawler->filter('body');
            }

            if ($container->count() === 0) {
                return null;
            }

            // Extract text from each block element and join with a space
            $parts = $container->filter('p, h1, h2, h3, h4, h5, h6, li')->each(function (Crawler $node) {
                return $node->text();
            });

            $text = implode(' ', $parts);

            // Normalize whitespace
            $text = preg_replace('/\s+/u', ' ', trim($text));

            if (empty($text)) {
                return null;
            }

            if (mb_strlen($text) > 15000) {
                $text = mb_substr($text, 0, 15000);
            }

            return $text;
        } catch (\Exception $e) {
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