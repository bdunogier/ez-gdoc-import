<?php

namespace BD\GoogleDocImportBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('BDGoogleDocImportBundle:Default:index.html.twig', array('name' => $name));
    }
}
