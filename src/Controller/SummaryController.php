<?php

namespace App\Controller;

use App\Entity\ArticleSummary;
use App\Form\SummaryFormType;
use App\Service\ArticleManager;
use App\Service\SummaryFormHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/summary')]
class SummaryController extends AbstractController
{
    #[Route('/', name: 'app_summary_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $sort = $request->query->get('sort', 'createdAt');
        $direction = $request->query->get('direction', 'DESC');
        $page = $request->query->getInt('page', 1);
        $limit = 6; // Items per page

        $allowedSorts = ['createdAt', 'originalUrl'];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'createdAt';
        }

        $repository = $entityManager->getRepository(ArticleSummary::class);
        $summaries = $repository->findBy([], [$sort => $direction], $limit, ($page - 1) * $limit);
        $totalSummaries = $repository->count([]);
        $totalPages = ceil($totalSummaries / $limit);

        return $this->render('summary/index.html.twig', [
            'summaries' => $summaries,
            'current_sort' => $sort,
            'current_direction' => $direction,
            'current_page' => $page,
            'total_pages' => $totalPages,
        ]);
    }

    #[Route('/new', name: 'app_summary_new', methods: ['GET', 'POST'])]
    public function new(Request $request, SummaryFormHandler $summaryFormHandler): Response
    {
        $result = $summaryFormHandler->handle($request);

        if ($result['success']) {
            return $this->redirectToRoute('app_summary_index');
        }

        return $this->render('summary/new.html.twig', [
            'form' => $result['form']->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_summary_show', methods: ['GET'])]
    public function show(ArticleSummary $summary): Response
    {
        return $this->render('summary/show.html.twig', [
            'summary' => $summary,
        ]);
    }
}
