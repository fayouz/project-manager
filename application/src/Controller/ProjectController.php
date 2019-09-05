<?php

namespace App\Controller;

use App\Entity\Project;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class ProjectController extends AbstractController
{
    /**
     * @Route("/project", name="project")
     */
    public function index()
    {
        $repository = $this->getDoctrine()->getRepository(Project::class);
        return $this->render('project/index.html.twig', [
            'projects' => $repository->findAll(),
        ]);
    }

    /**
     * @Route("/project/{id}", name="project_detail")
     */
    public function detail(Project $project)
    {
        return $this->render('project/detail.html.twig', ['project' => $project]);
    }
}
