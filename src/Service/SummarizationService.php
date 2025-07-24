<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DomCrawler\Crawler;

class SummarizationService
{
    private HttpClientInterface $httpClient;
    private string $googleApiKey;
    private LoggerInterface $logger;

    public function __construct(HttpClientInterface $httpClient, string $googleApiKey, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->googleApiKey = $googleApiKey;
        $this->logger = $logger;
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
            $response = $this->httpClient->request('GET', $url, [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/115.0',
                ],
            ]);
            $html = $response->getContent();
            $crawler = new Crawler($html);

            // Extract text from each block element and join with a space
            $parts = $crawler->filter('p, h1, h2, h3, h4, h5, h6, li')->each(function (Crawler $node) {
                return $node->text();
            });

            $text = implode(' ', $parts);

            // Normalize whitespace
            $text = preg_replace('/\s+/u', ' ', trim($text));

            if (empty($text)) {
                $this->logger->warning('SummarizationService: Could not extract any text from the URL.');
                return null;
            }

            if (mb_strlen($text) > 15000) {
                $text = mb_substr($text, 0, 15000);
            }

            return $text;
        } catch (\Exception $e) {
            $this->logger->error('SummarizationService: Exception while fetching/cleaning article: ' . $e->getMessage());
            return null;
        }
    }

    private function callGeminiApi(string $text): ?string
    {
        $prompt = "Streszcz ten tekst w języku polskim, w maksymalnie 3-4 zdaniach: " . $text;

        try {
            $response = $this->httpClient->request('POST', "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=" . $this->googleApiKey, [
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