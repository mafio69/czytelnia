<?php

namespace App\Controller;

use App\Entity\ArticleSummary;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;
use App\Service\SummaryFormHandler;

final class SummaryController extends AbstractController
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/summary', name: 'app_summary_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $this->logger->info('Wyświetlono listę streszczeń.');
        $summaries = $entityManager->getRepository(ArticleSummary::class)->findAll();

        return $this->render('summary/index.html.twig', [
            'summaries' => $summaries,
        ]);
    }

    #[Route('/summary/new', name: 'app_summary_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        \Symfony\Component\Form\FormFactoryInterface $formFactory,
        \App\Service\ArticleManager $articleManager,
        \Psr\Log\LoggerInterface $loggerForHandler,
        \Symfony\Component\HttpFoundation\Session\SessionInterface $session,
        \Symfony\Component\Routing\Generator\UrlGeneratorInterface $urlGenerator
    ): Response
    {
        $this->logger->debug('Request content: ' . $request->getContent());

        // Ręczne tworzenie instancji SummaryFormHandler
        $formHandler = new \App\Service\SummaryFormHandler(
            $formFactory,
            $articleManager,
            $loggerForHandler,
            $session,
            $urlGenerator
        );

        $result = $formHandler->handle($request);

        if ($result['success'] && isset($result['redirectToRoute'])) {
            return $this->redirect($result['redirectToRoute']);
        }

        return $this->render('summary/new.html.twig', [
            'form' => $result['form'] ?? null,
        ]);
    }
}
