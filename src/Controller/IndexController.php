<?php
namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use App\Entity\Image;

use App\Entity\Post;
use App\Entity\User;

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
        $posts = $this->getPosts(15);
        return $this->render('User/actualite.html.twig',array(
            "posts" =>$posts,
        ));
    }
    /**
     * @Route("/actualite/{id}", name="actualite_detail", requirements={"id"="\d+"})
     */
    public function actualiteDetail($id)
    {
        $post = $this->getDoctrine()
        ->getRepository(Post::class)
        ->find($id);
        /* A CHANGER */
        if(!isset($post)) return $this->redirectToRoute('index');

        return $this->render('User/actualite_detail.html.twig',array(
            "post"=>$post
        ));
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
        $em = $this->getDoctrine()->getManager();
        $totalAfficher = 50;

        $images=$this->getImages($totalAfficher);

        $sections = $this->getDoctrine()
        ->getRepository(Section::class)
        ->findAll();

        $query=$em->createQuery('SELECT COUNT(i) FROM App\Entity\Image i where i.gallery = 1');


        return $this->render('User/galerie.html.twig',array(
            "images" =>$images,
            "sections" =>$sections,
            "total" =>$query->getResult()[0][1],
            "totalAfficher"=>$totalAfficher
        ));
    }

    /**
     * @Route("/galerie/viewmore", name="viewmore_galerie",methods={"POST"}))
     */
    public function viewmoreImage(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $total=intval($request->get('total'));
        if(!isset($total))return new JsonResponse(array(),500);
        if(!is_int(intval($total)))return new JsonResponse(array(),500);

        $section_id=$request->get('section_id');
        if(!isset($section_id))return new JsonResponse(array(),500);

        $newTotal = $total+50;
        $images=null;
        $query="";
        if($section_id=="none"){
            $images = $this->getImages($newTotal);

            $query=$em->createQuery('SELECT COUNT(i) FROM App\Entity\Image i where i.gallery = 1');
        }else{
            $section = $em->getRepository(Section::class)->find($section_id);
            if(!$section)return new JsonResponse(array(),500);

            $query=$em->createQuery('SELECT COUNT(i) FROM App\Entity\Image i where i.gallery = 1 and i.section = :param');
            
            $query->setParameter("param", $section);
            $images = $this->getImagesBis($newTotal,$section);
        }
        

        

        

        
        $nb = $query->getResult();

        return new JsonResponse(array(
            'html'=>$this->render('User/Ajax/viewmore_gallery.html.twig',array(
                
                "images" => $images,
                'total' => intval($nb[0][1]),
                'totalAfficher' => count($images)
                ))->getContent()
           ),
            200);
    }

    private function getImages($total){
        return $this->getDoctrine()
        ->getRepository(Image::class)
        ->findBy(
            array('gallery'=>1), // Critere
            array('dateCreated' => 'desc'),        // Tri
            $total,                              // Limite
            0                            // Offset
          );
    }

    private function getImagesBis($total,$section){
        return $this->getDoctrine()
        ->getRepository(Image::class)
        ->findBy(
            array('gallery'=>1,'section'=>$section), // Critere
            array('dateCreated' => 'desc'),        // Tri
            $total,                              // Limite
            0                            // Offset
          );
    }

    private function getPosts($total){
        return $this->getDoctrine()
        ->getRepository(Post::class)
        ->findBy(
            array(), // Critere
            array('date' => 'desc'),        // Tri
            $total,                              // Limite
            0                               // Offset
          );
    }

}