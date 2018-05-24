<?php
namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

use App\Entity\Image;

use App\Entity\Section;

class IndexController extends Controller
{
    /**
     * @Route("/", name="index")
     */
    public function index()
    {
        return $this->render('User/accueil.html.twig');
    }

    /**
     * @Route("/actualite", name="actualite")
     */
    public function actualite()
    {

        return $this->render('User/actualite.html.twig');
    }

    /**
     * @Route("/agenda", name="agenda")
     */
    public function agenda()
    {
        return $this->render('User/agenda.html.twig');
    }

    /**
     * @Route("/contact", name="contact")
     */
    public function contact()
    {
        return $this->render('User/contact.html.twig');
    }

    /**
     * @Route("/galerie", name="galerie")
     */
    public function galerie()
    {
        $image=$this->getDoctrine()
        ->getRepository(Image::class)
        ->findAll();

        $sections = $this->getDoctrine()
        ->getRepository(Section::class)
        ->findAll();
        return $this->render('User/galerie.html.twig',array("images" =>$image,"sections" =>$sections));
    }


}