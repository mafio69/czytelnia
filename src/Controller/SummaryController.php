<?php

namespace App\Controller;

use App\Entity\ArticleSummary;
use App\Form\SummaryFormType;
use App\Service\SummarizationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SummaryController extends AbstractController
{
    #[Route('/summary', name: 'app_summary_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $summaries = $entityManager->getRepository(ArticleSummary::class)->findAll();

        return $this->render('summary/index.html.twig', [
            'summaries' => $summaries,
        ]);
    }

    #[Route('/summary/new', name: 'app_summary_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SummarizationService $summarizationService): Response
    {
        $articleSummary = new ArticleSummary();
        $form = $this->createForm(SummaryFormType::class, $articleSummary);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $originalUrl = $form->get('originalUrl')->getData();

            $summary = $summarizationService->summarizeUrl($originalUrl);

            if (null !== $summary) {
                $articleSummary->setOriginalUrl($originalUrl);
                $articleSummary->setSummary($summary);
                $articleSummary->setCreatedAt(new \DateTimeImmutable());

                $entityManager->persist($articleSummary);
                $entityManager->flush();

                $this->addFlash('success', 'Artykuł został pomyślnie streszczony i zapisany!');

                return $this->redirectToRoute('app_summary_index');
            } else {
                $this->addFlash('error', 'Nie udało się streszczyć artykułu. Sprawdź URL lub spróbuj ponownie.');
            }
        }

        return $this->render('summary/new.html.twig', [
            'form' => $form,
        ]);
    }
}
