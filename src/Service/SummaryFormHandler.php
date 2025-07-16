<?php

namespace App\Service;

use App\Entity\ArticleSummary;
use App\Form\SummaryFormType;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SummaryFormHandler
{
    private FormFactoryInterface $formFactory;
    private ArticleManager $articleManager;
    private LoggerInterface $logger;
    private SessionInterface $session;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(
        FormFactoryInterface $formFactory,
        ArticleManager $articleManager,
        LoggerInterface $logger,
        SessionInterface $session,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->formFactory = $formFactory;
        $this->articleManager = $articleManager;
        $this->logger = $logger;
        $this->session = $session;
        $this->urlGenerator = $urlGenerator;
    }

    public function handle(Request $request): array
    {
        $articleSummary = new ArticleSummary();
        $form = $this->formFactory->create(SummaryFormType::class, $articleSummary);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->logger->info('Formularz streszczania został przesłany i jest poprawny.');
            $originalUrl = $form->get('originalUrl')->getData();

            $articleSummary = $this->articleManager->createSummaryFromUrl($originalUrl);

            if (null !== $articleSummary) {
                $this->session->getFlashBag()->add('success', 'Artykuł został pomyślnie streszczony i zapisany!');
                return [
                    'success' => true,
                    'redirectToRoute' => $this->urlGenerator->generate('app_summary_index')
                ];
            } else {
                $this->session->getFlashBag()->add('error', 'Nie udało się streszczyć artykułu. Sprawdź URL lub spróbuj ponownie.');
                return ['success' => false];
            }
        }

        return ['success' => false, 'form' => $form];
    }
}
