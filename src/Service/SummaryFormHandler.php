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
            try {
                $this->articleManager->summarizeAndSave($articleSummary);

                $this->session->get('flash_bag')->add('success', 'Artykuł został pomyślnie streszczony i zapisany!');
                return [
                    'success' => true,
                    'redirectToRoute' => $this->urlGenerator->generate('app_summary_index')
                ];
            } catch (\Exception $e) {
                $this->logger->error(sprintf('Błąd podczas obsługi formularza streszczenia: %s', $e->getMessage()));
                $this->session->get('flash_bag')->add('error', 'Wystąpił błąd podczas streszczania artykułu. Sprawdź logi aplikacji.');
                return ['success' => false, 'form' => $form];
            }
        }

        return ['success' => false, 'form' => $form];
    }
}