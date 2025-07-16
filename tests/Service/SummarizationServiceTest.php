<?php

namespace App\Tests\Service;

use App\Service\SummarizationService;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class SummarizationServiceTest extends TestCase
{
    private HttpClientInterface|\PHPUnit\Framework\MockObject\MockObject $httpClientMock;
    private SummarizationService $summarizationService;

    protected function setUp(): void
    {
        $this->httpClientMock = $this->createMock(HttpClientInterface::class);
        $this->summarizationService = new SummarizationService($this->httpClientMock, 'test_api_key');
    }

    public function testFetchAndCleanArticleSuccess(): void
    {
        $htmlContent = '<!DOCTYPE html><html><head><title>Test</title></head><body><article><h1>Test Article</h1><p>This is some test content.</p></article></body></html>';
        $expectedCleanedText = 'Test Article This is some test content.';

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getContent')->willReturn($htmlContent);

        $this->httpClientMock->method('request')->willReturn($responseMock);

        // Access the private method using reflection
        $reflection = new \ReflectionClass(SummarizationService::class);
        $method = $reflection->getMethod('fetchAndCleanArticle');
        $method->setAccessible(true);

        $cleanedText = $method->invoke($this->summarizationService, 'http://example.com');

        $this->assertEquals($expectedCleanedText, $cleanedText);
    }

    public function testFetchAndCleanArticleFailure(): void
    {
        $this->httpClientMock->method('request')->willThrowException(new \Exception('Network error'));

        $reflection = new \ReflectionClass(SummarizationService::class);
        $method = $reflection->getMethod('fetchAndCleanArticle');
        $method->setAccessible(true);

        $cleanedText = $method->invoke($this->summarizationService, 'http://example.com');

        $this->assertNull($cleanedText);
    }

    public function testCallGeminiApiSuccess(): void
    {
        $geminiResponse = [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'Streszczenie testowe.']
                        ]
                    ]
                ]
            ]
        ];

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('toArray')->willReturn($geminiResponse);

        $this->httpClientMock->method('request')->willReturn($responseMock);

        $reflection = new \ReflectionClass(SummarizationService::class);
        $method = $reflection->getMethod('callGeminiApi');
        $method->setAccessible(true);

        $summary = $method->invoke($this->summarizationService, 'Testowy tekst do streszczenia.');

        $this->assertEquals('Streszczenie testowe.', $summary);
    }

    public function testCallGeminiApiFailure(): void
    {
        $this->httpClientMock->method('request')->willThrowException(new \Exception('API error'));

        $reflection = new \ReflectionClass(SummarizationService::class);
        $method = $reflection->getMethod('callGeminiApi');
        $method->setAccessible(true);

        $summary = $method->invoke($this->summarizationService, 'Testowy tekst do streszczenia.');

        $this->assertNull($summary);
    }

    public function testSummarizeUrlIntegration(): void
    {
        $htmlContent = '<!DOCTYPE html><html><body><article><h1>Integration Test</h1><p>This is content for integration test.</p></article></body></html>';
        $geminiSummary = 'Zintegrowane streszczenie.';

        $responseMockHtml = $this->createMock(ResponseInterface::class);
        $responseMockHtml->method('getContent')->willReturn($htmlContent);

        $responseMockGemini = $this->createMock(ResponseInterface::class);
        $responseMockGemini->method('toArray')->willReturn([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => $geminiSummary]
                        ]
                    ]
                ]
            ]
        ]);

        $this->httpClientMock->expects($this->exactly(2))
                             ->method('request')
                             ->willReturnOnConsecutiveCalls($responseMockHtml, $responseMockGemini);

        $summary = $this->summarizationService->summarizeUrl('http://example.com/integration');

        $this->assertEquals($geminiSummary, $summary);
    }
}
