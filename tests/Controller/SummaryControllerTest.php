<?php

namespace App\Tests\Controller;

use App\Entity\ArticleSummary;
use App\Repository\ArticleSummaryRepository;
use App\Service\ArticleManager;
use App\Service\SummaryFormHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class SummaryControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/summary/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Lista Streszczeń');
    }

    public function testNewSuccess()
    {
        $client = static::createClient();

        // Mock SummaryFormHandler for success
        $summaryFormHandler = $this->createMock(SummaryFormHandler::class);
        $summaryFormHandler->expects($this->once())
            ->method('handle')
            ->willReturn([
                'success' => true,
                'redirectToRoute' => '/summary/'
            ]);
        self::getContainer()->set(SummaryFormHandler::class, $summaryFormHandler);

        $crawler = $client->request('GET', '/summary/new');

        $this->assertResponseRedirects('/summary/');
    }

    public function testNewFailure()
    {
        $client = static::createClient();

        // Mock SummaryFormHandler for failure
        $summaryFormHandler = $this->createMock(SummaryFormHandler::class);
        $mockForm = $this->createMock(FormInterface::class);
        $summaryFormHandler->expects($this->once())
            ->method('handle')
            ->willReturn([
                'success' => false,
                'form' => $mockForm
            ]);
        self::getContainer()->set(SummaryFormHandler::class, $summaryFormHandler);

        $crawler = $client->request('GET', '/summary/new');

        $this->assertResponseIsSuccessful();
        // We expect the form to be rendered again with an error message
        $this->assertSelectorExists('.alert-danger');
    }

    public function testShow()
    {
        $client = static::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $summary = new ArticleSummary();
        $summary->setOriginalUrl('http://example.com');
        $summary->setSummary('Test summary');
        $summary->setCreatedAt(new \DateTimeImmutable());
        $entityManager->persist($summary);
        $entityManager->flush();


        $client->request('GET', '/summary/' . $summary->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h5', 'Streszczenie artykułu');
    }
}
