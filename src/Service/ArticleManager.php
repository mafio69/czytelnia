<?php

namespace App\Service;

use App\Entity\ArticleSummary;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ArticleManager
{
    private SummarizationService $summarizationService;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(SummarizationService $summarizationService, EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->summarizationService = $summarizationService;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function summarizeAndSave(ArticleSummary $articleSummary): void
    {
        $originalUrl = $articleSummary->getOriginalUrl();
        if (!$originalUrl) {
            throw new \InvalidArgumentException('URL nie może być pusty.');
        }

        $this->logger->info(sprintf('ArticleManager: Próba streszczenia URL: %s', $originalUrl));
        $summaryText = $this->summarizationService->summarizeUrl($originalUrl);

        if (null === $summaryText) {
            $this->logger->error(sprintf('ArticleManager: Nie udało się uzyskać streszczenia dla URL: %s', $originalUrl));
            throw new \RuntimeException('Nie udało się wygenerować streszczenia.');
        }

        $articleSummary->setSummary($summaryText);
        $articleSummary->setCreatedAt(new \DateTimeImmutable());

        try {
            $this->entityManager->persist($articleSummary);
            $this->entityManager->flush();
            $this->logger->info(sprintf('ArticleManager: Artykuł z URL %s został pomyślnie streszczony i zapisany.', $originalUrl));
        } catch (\Exception $e) {
            $this->logger->error(sprintf('ArticleManager: Błąd podczas zapisywania streszczenia dla URL %s: %s', $originalUrl, $e->getMessage()));
            throw $e; // Re-throw the exception to be caught by the controller
        }
    }
}
