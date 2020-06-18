<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class TwController extends AbstractController
{
    /**
     * @Route("/tw", name="tw")
     */
    public function index()
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/TwController.php',
            'as' => 'adasdsadsa'
        ]);
    }
}
