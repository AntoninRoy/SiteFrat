<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

use App\Service\FacebookService;

use App\Entity\Section;

use App\Entity\Post;
use App\Form\PostType;

use App\Entity\Evenement;

use App\Entity\Image;
use App\Form\ImageType;

use \DateTime;


class AdminController extends Controller
{
    /**
     * @Route("/admin", name="admin")
     */
    public function index()
    {
        return $this->render('Admin/index.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }
    /**
     * @Route("/admin/post", name="admin_post")
     */
    public function post(FacebookService $facebookService)
    {
        $em = $this->getDoctrine()->getManager();

        $sections = $this->getDoctrine()
        ->getRepository(Section::class)
        ->findAll();

        $query=$em->createQuery('SELECT COUNT(p) FROM App\Entity\Post p');
        $posts = $this->getPosts(15);

        //$facebookService->poster("Mon premier post depuis Symfony sur cette page");
        return $this->render('Admin/actualites.html.twig',array(
            'posts'=>$posts,
            "total" =>$query->getResult()[0][1],
            "totalAfficher"=>count($posts),
        ));
    }

    /**
     * @Route("/admin/post/create", name="create_post",methods={"POST"}))
     */
    public function createPost(Request $request)
    {
        
        $em = $this->getDoctrine()->getManager();
        $files = $request->files->get('files');

        $count = 0;
        $images=array();
 
        /* Contrôle des données */
        
        $post_title = $request->get('post_title');
        $post_content = $request->get('post_content');

        $type = $request->get('type');
        if(!isset($post_title))return new JsonResponse(array(),500); 
        if(!isset($post_content))return new JsonResponse(array(),500);

        

        /*Upload des images*/
        $filesNotUploaded= array();
        $filesUploaded= array();
        $mimeTypes = array('jpeg','jpg','png','gif');

        $section = $this->getDoctrine()->getRepository(Section::class)->find(7);

        if(!empty($files) && $section)
        {
            for($count; $count < count($files); $count++)
            {
                $message=$files[$count]->guessClientExtension();
                if(in_array($files[$count]->guessClientExtension(), $mimeTypes)){
                    $temp=array();
                    array_push($temp,$files[$count]);
                    $images=array_merge($images,$this->uploadExec($temp,$section,false));
                    array_push($filesUploaded,$files[$count]->getClientOriginalName());
                }else{
                    array_push($filesNotUploaded,$files[$count]->getClientOriginalName());
                }
                    
            }
        }
        $post = new Post();

        $post->setOwner($this->getUser());
        $post->setDate(new DateTime());
        
        
        $post->setTitle($post_title);
        $post->setContent($post_content);
       
        foreach ($images as $image){
            $post->addImage($image);
        }
        $em->persist($post);
        $em->flush($post);

        $query=$em->createQuery('SELECT COUNT(i) FROM App\Entity\Image i where i.gallery = 1');
        $posts = $this->getPosts(15);
        return new JsonResponse(array(
            'notUploaded'=>$filesNotUploaded,
            'uploaded'=>$filesUploaded,
            'html'=>$this->render('Admin/Ajax/displayPostList.html.twig',array(
                "posts" =>$posts,
                'post'=>$post,
                "total" =>$query->getResult()[0][1],
                "totalAfficher"=>count($posts),
                ))->getContent(),
            //'html'=>$this->render('Admin/Ajax/displayImageList.html.twig',array("images" => $this->getImages(15)))->getContent(),
        ));
    }
     /**
     * @Route("/admin/post/prepareUpdate", name="prepareUpdate_post",methods={"POST"}))
     */
    public function prepareUpdatePost(Request $request)
    {
        
        $postID = $request->get('postID');
        
        if(!isset($postID))return new JsonResponse(array(),500); 

        $em = $this->getDoctrine()->getManager();
        $post = $em->getRepository(Post::class)->find($postID);
        if(!$post)return new JsonResponse(array(),500); 
        
        return new JsonResponse(array(
            'title'=>$post->getTitle(),
            
            'formulaire'=>$this->render('Admin/Ajax/updatePost.html.twig',array("images" => $post->getImages(),'post'=>$post))->getContent(),
        ));
    }
    /**
     * @Route("/admin/post/update", name="update_post",methods={"POST"}))
     */
    public function updatePost(Request $request)
    {
        $em = $this->getDoctrine()->getManager();


        $count = 0;
        $images=array();
        
        /* Contrôle des données */

        $files = $request->files->get('files');
        $post_title = $request->get('post_title');
        $post_content = $request->get('post_content');
        $post_id = $request->get('post_id');
        $deleteImageId = $request->get('deleteImageId');

        if(!isset($post_title))return new JsonResponse(array(),500); 
        if(!isset($post_content))return new JsonResponse(array(),500);
        if(!isset($post_id))return new JsonResponse(array(),500);

        $post = $this->getDoctrine()->getRepository(Post::class)->find($post_id);

        if(!$post)return new JsonResponse(array(),500);
        if($post_content=="")return new JsonResponse(array(),500);
        if($post_title=="")return new JsonResponse(array(),500);

        /*Upload des images*/
        $filesNotUploaded= array();
        $filesUploaded= array();
        $mimeTypes = array('jpeg','jpg','png','gif');

        $section = $this->getDoctrine()->getRepository(Section::class)->find(7);

        if(!empty($files) && $section)
        {
            for($count; $count < count($files); $count++)
            {
                $message=$files[$count]->guessClientExtension();
                if(in_array($files[$count]->guessClientExtension(), $mimeTypes)){
                    $temp=array();
                    array_push($temp,$files[$count]);
                    $images=array_merge($images,$this->uploadExec($temp,$section,false));
                    array_push($filesUploaded,$files[$count]->getClientOriginalName());
                }else{
                    array_push($filesNotUploaded,$files[$count]->getClientOriginalName());
                }
                    
            }
        }     
        if($deleteImageId != ""){
            $fileSystem = new Filesystem();
            $uploadDir = $this->getParameter('images_directory') . DIRECTORY_SEPARATOR . "galery" . DIRECTORY_SEPARATOR;

            $ids = explode(",", $deleteImageId);
            foreach($ids as $id){
                $image = $this->getDoctrine()->getRepository(Image::class)->find($id);
                try {
                    $fileSystem->remove($uploadDir.$image->getFiles());             
                  
                    
                } catch (IOExceptionInterface $exception) {
                    echo "An error occurred while creating your directory at ".$exception->getPath();
                }
                $post->removeImage($image);
                $em->remove($image);
                $em->flush();
            }

        }
        
        $post->setTitle($post_title);
        $post->setContent($post_content);
       
        foreach ($images as $image){
            $post->addImage($image);
        }
        $em->persist($post);
        $em->flush($post);

        return new JsonResponse(array(
            'notUploaded'=>$filesNotUploaded,
            'uploaded'=>$filesUploaded,
        ));
    }

     /**
     * @Route("/admin/post/more", name="more_post",methods={"POST"}))
     */
    public function morePost(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $postID = $request->get('post_id');
        if(!isset($postID))return new JsonResponse(array(),500); 

        $post=$em->getRepository(Post::class)->find($postID);
        if(!$post) return new JsonResponse(array(),500);

        

        return new JsonResponse(array(
            'html'=>$this->render('Admin/Ajax/morePost.html.twig',array(
                "post" => $post,

                ))->getContent()),
            200);
    }

    /**
     * @Route("/admin/post/viewmore", name="viewmore_post",methods={"POST"}))
     */
    public function viewmorePost(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $total=$request->get('total');
        if(!isset($total))return new JsonResponse(array(),500); 

        $query=$em->createQuery('SELECT COUNT(p) FROM App\Entity\Post p');
        $posts = $this->getPosts($total +15);

        return new JsonResponse(array(
            'html'=>$this->render('Admin/Ajax/displayPostList.html.twig',array(
                "posts" => $posts,
                "total" =>$query->getResult()[0][1],
                "totalAfficher"=>count($posts),
                ))->getContent()),
            200);
    }
    /**
     * @Route("/admin/post/delete", name="delete_post",methods={"POST"}))
     */
    public function deletePost(Request $request)
    {
        $fileSystem = new Filesystem();
        $uploadDir = $this->getParameter('images_directory') . DIRECTORY_SEPARATOR . "galery" . DIRECTORY_SEPARATOR;
        $em = $this->getDoctrine()->getManager();
       
        $post_id = $request->get('post_id');
        if(!isset($post_id))return new JsonResponse(array(),500);

        $post=$em->getRepository(Post::class)->find($post_id);
        if(!$post) return new JsonResponse(array(),500);

 
        foreach($post->getImages() as $image){
            try {
                $fileSystem->remove($uploadDir.$image->getFiles());              
                
            } catch (IOExceptionInterface $exception) {
                echo "An error occurred while creating your directory at ".$exception->getPath();
            }
            $post->removeImage($image);
            $em->remove($image);
            $em->flush();
        }
        $em->remove($post);
        $em->flush();

        $query=$em->createQuery('SELECT COUNT(p) FROM App\Entity\Post p');
        $totalAfficher = 15;

        return new JsonResponse(array(
            'html'=>$this->render('Admin/Ajax/displayPostList.html.twig',array(
                "posts" => $this->getPosts(15),
                'post'=>$post,
                "total" =>$query->getResult()[0][1],
                "totalAfficher"=>$totalAfficher
            
                ))->getContent(),

        ));
    }
    /**
     * @Route("/admin/image", name="admin_image")
     */
    public function image()
    {
        $em = $this->getDoctrine()->getManager();

        $sections = $this->getDoctrine()
        ->getRepository(Section::class)
        ->findAll();

        $image = new Image();
        $form = $this->createForm(ImageType::class, $image);

        $query=$em->createQuery('SELECT COUNT(i) FROM App\Entity\Image i where i.gallery = 1');
        $nb = $query->getResult();

        return $this->render('Admin/images.html.twig',array(
            'form' => $form->createView(),
            'sections'=> $sections,
            'images'=>$this->getImages(15),
            'total' => $nb[0][1],
            'totalAfficher' => 15
        ));
    }
    
    /**
     * @Route("/admin/image/delete", name="delete_image",methods={"POST"}))
     */
    public function deleteImage(Request $request)
    {
        $uploadDir = $this->getParameter('images_directory') . DIRECTORY_SEPARATOR . "galery" . DIRECTORY_SEPARATOR;
        $fileSystem = new Filesystem();
        $em = $this->getDoctrine()->getManager();
        $image=$em->getRepository(Image::class)->find($request->get('img_id'));
        
        if(!$image) return new JsonResponse(array(),500);
        try {
            $fileSystem->remove($uploadDir.$image->getFiles());             
            $em->remove($image);
            $em->flush();
            
        } catch (IOExceptionInterface $exception) {
            echo "An error occurred while creating your directory at ".$exception->getPath();
        }

        $query=$em->createQuery('SELECT COUNT(i) FROM App\Entity\Image i where i.gallery = 1');
        $nb = $query->getResult();

        return new JsonResponse(array(
            'code' => 200,
            'probleme'=>$image->getId(),
            'html'=>$this->render('Admin/Ajax/displayImageList.html.twig',array("images" => $this->getImages(15),'total' => $nb[0][1],'totalAfficher' => 15))->getContent(),
            'errors' => array('errors' => array(''))),
            200);
    }
     /**
     * @Route("/admin/image/more", name="more_image",methods={"POST"}))
     */
    public function moreImage(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $image=$em->getRepository(Image::class)->find($request->get('img_id'));
        
        if(!$image) return new JsonResponse(array(),500);

        return new JsonResponse(array(
            'html'=>$this->render('Admin/Ajax/moreImage.html.twig',array("image" => $image))->getContent()),
            200);
    }

    /**
     * @Route("/admin/image/viewmore", name="viewmore_image",methods={"POST"}))
     */
    public function viewmoreImage(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $total=$request->get('total');
        
        $newTotal=$total+15;

        $query=$em->createQuery('SELECT COUNT(i) FROM App\Entity\Image i where i.gallery = 1');
        $nb = $query->getResult();
        return new JsonResponse(array(
            
            'html'=>$this->render('Admin/Ajax/displayImageList.html.twig',array("images" => $this->getImages($newTotal),'total' => $nb[0][1],'totalAfficher' => $newTotal))->getContent(),
            'errors' => array('errors' => array(''))),
            200);
    }

    /**
     * @Route("/admin/image/upload", name="admin_image_upload",methods={"POST"})
     */
    public function uploadImage(Request $request)
    {
        $files = $request->files->get('files');
        $em = $this->getDoctrine()->getManager();
        $count = 0;
        $error = false;
        $uploaded = false; 
        $message = null;
 

        $filesNotUploaded= array();
        $filesUploaded= array();
        $mimeTypes = array('jpeg','jpg','png','gif');
        $section = $this->getDoctrine()->getRepository(Section::class)->find($request->get('sectionId'));
        $debug="";
        if(!empty($files) && $section)
        {
            for($count; $count < count($files); $count++)
            {
                $message=$files[$count]->guessClientExtension();
                if(in_array($files[$count]->guessClientExtension(), $mimeTypes)){
                    $temp=array();
                    array_push($temp,$files[$count]);
                    $uploaded = $this->uploadExec($temp,$section,true);
                    array_push($filesUploaded,$files[$count]->getClientOriginalName());
                }else{
                    array_push($filesNotUploaded,$files[$count]->getClientOriginalName());
                }
                    
            }
        }else{
            $error = true;
        }

        $query=$em->createQuery('SELECT COUNT(i) FROM App\Entity\Image i where i.gallery = 1');
        $nb = $query->getResult();

        return new JsonResponse(array(
            'error'=>$error,
            'message' => $message,
            'notUploaded'=>$filesNotUploaded,
            'uploaded'=>$filesUploaded,
            'html'=>$this->render('Admin/Ajax/displayImageList.html.twig',array("images" => $this->getImages(15),'total' => $nb[0][1],'totalAfficher' => 15))->getContent(),
        ));
    }

    private function uploadExec($args = array(),$section,$inGallery)
    {
        $count = 0;
        $images_uploads = array();
        $doctrine = $this->getDoctrine()->getManager();

        $uploadDir = $this->getParameter('images_directory') . DIRECTORY_SEPARATOR . "galery" . DIRECTORY_SEPARATOR;

        if(!is_dir($uploadDir))
        {
            mkdir($uploadDir, 0775, TRUE);
        }

        if(!empty($args) && count($args) > 0)
        {
            // !!!!!!!!!!!! CETTE BOUCLE NE SERT A RIEN !!!!!!!!!!!!!
            for($count; $count < count($args); $count++)
            {
                $prettyName = $args[$count]->getClientOriginalName();
                $filename[$count] =md5(uniqid()) . '.' . $args[$count]->guessClientExtension();
                
                if(!file_exists($uploadDir . $filename[$count]))
                {
                    if($args[$count]->move($uploadDir, $filename[$count]))
                    {  
                        $size = getimagesize($this->getParameter('images_directory') . DIRECTORY_SEPARATOR . "galery" . DIRECTORY_SEPARATOR.$filename[$count]);
                        /*
                        * Persist Uploaded Image(s) to the Database
                        */
                        $image = new Image();
                        $image->setFiles($filename[$count]);
                        $image->setPublisher($this->getUser());
                        $image->setSection($section);
                        $image->setPrettyName($prettyName);
                        $image->setX($size["0"]);
                        $image->setY($size["1"]);
                        $image->setGallery($inGallery);
                        $image->setDateCreated(new DateTime());

                        $doctrine->persist($image);
                        array_push($images_uploads,$image);
                    }
                }
            }

            
            $doctrine->flush();

            
        }

        return $images_uploads;
    }


    /**
     * @Route("/admin/event", name="admin_event")
     */
    public function event(FacebookService $facebookService)
    {
        $em = $this->getDoctrine()->getManager();
        
        //$facebookService->poster("Mon premier post depuis Symfony sur cette page");
        $events = $this->getEvents(15);
        $query=$em->createQuery('SELECT COUNT(i) FROM App\Entity\Evenement i');
        $nb = $query->getResult();

        return $this->render('Admin/agenda.html.twig',array(

            'events'=>$events,
            'total' => $nb[0][1],
            'totalAfficher' => count($events),
        ));
    }
    /**
     * @Route("/admin/event/delete", name="delete_event",methods={"POST"}))
     */
    public function deleteEvenement(Request $request)
    {
        $fileSystem = new Filesystem();
        $uploadDir = $this->getParameter('images_directory') . DIRECTORY_SEPARATOR . "galery" . DIRECTORY_SEPARATOR;
        $em = $this->getDoctrine()->getManager();
       
        $event_id = $request->get('event_id');
        if(!isset($event_id))return new JsonResponse(array(),500);

        $event=$em->getRepository(Evenement::class)->find($event_id);
        if(!$event) return new JsonResponse(array(),500);

        foreach($event->getPosts() as $post){
            foreach($post->getImages() as $image){
                try {
                    $fileSystem->remove($uploadDir.$image->getFiles());              
                    
                } catch (IOExceptionInterface $exception) {
                    echo "An error occurred while creating your directory at ".$exception->getPath();
                }
                $post->removeImage($image);
                $em->remove($image);
                $em->flush();
            }
            $em->remove($post);
            $em->flush();
        }
        $em->remove($event);
        $em->flush();

        $events = $this->getEvents(15);
        $query=$em->createQuery('SELECT COUNT(i) FROM App\Entity\Evenement i');
        $nb = $query->getResult();

        return new JsonResponse(array(
            'html'=>$this->render('Admin/Ajax/displayEventList.html.twig',array(
                'events'=>$events,
                'total' => $nb[0][1],
                'totalAfficher' => count($events),
                ))->getContent(),

        ));
    }

    /**
     * @Route("/admin/event/create", name="create_event",methods={"POST"}))
     */
    public function createEvent(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
       
        /* Contrôle des données */

        $event_name = $request->get('event_name');
        $event_date = $request->get('event_date');


        if(!isset($event_name))return new JsonResponse(array(),500); 
        if(!isset($event_date))return new JsonResponse(array(),500);


        if($event_name=="")return new JsonResponse(array(),500);
        if($event_date=="")return new JsonResponse(array(),500);

        $ymd = DateTime::createFromFormat('d/m/Y', $event_date);

        $event = new Evenement();

        $event->setName($event_name);
        $event->setDate($ymd);
        
        $em->persist($event);
        $em->flush();

        $events = $this->getEvents(15);
        $query=$em->createQuery('SELECT COUNT(i) FROM App\Entity\Evenement i');
        $nb = $query->getResult();

        return new JsonResponse(array(
            'html'=>$this->render('Admin/Ajax/displayEventList.html.twig',array(
                'events'=>$events,
                'total' => $nb[0][1],
                'totalAfficher' => count($events),
                ))->getContent(),

        ));
    }
    /**
     * @Route("/admin/event/createbis", name="create_event_bis",methods={"POST"}))
     */
    public function createEventBis(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $files = $request->files->get('files');

        $count = 0;
        $images=array();
 
        /* Contrôle des données */
        
        $post_title = $request->get('post_title');
        $post_content = $request->get('post_content');
        $event_name = $request->get('event_name');
        $event_date = $request->get('event_date');

        if(!isset($post_title))return new JsonResponse(array(),500); 
        if(!isset($post_content))return new JsonResponse(array(),500);
        if(!isset($event_name))return new JsonResponse(array(),500); 
        if(!isset($event_date))return new JsonResponse(array(),500);


        if($event_name=="")return new JsonResponse(array(),500);
        if($event_date=="")return new JsonResponse(array(),500);

        

        /*Upload des images*/
        $filesNotUploaded= array();
        $filesUploaded= array();
        $mimeTypes = array('jpeg','jpg','png','gif');

        $section = $this->getDoctrine()->getRepository(Section::class)->find(7);

        if(!empty($files) && $section)
        {
            for($count; $count < count($files); $count++)
            {
                $message=$files[$count]->guessClientExtension();
                if(in_array($files[$count]->guessClientExtension(), $mimeTypes)){
                    $temp=array();
                    array_push($temp,$files[$count]);
                    $images=array_merge($images,$this->uploadExec($temp,$section,false));
                    array_push($filesUploaded,$files[$count]->getClientOriginalName());
                }else{
                    array_push($filesNotUploaded,$files[$count]->getClientOriginalName());
                }
                    
            }
        }
        $post = new Post();

        $post->setOwner($this->getUser());
        $post->setDate(new DateTime());
        
        
        $post->setTitle($post_title);
        $post->setContent($post_content);
       
        foreach ($images as $image){
            $post->addImage($image);
        }
        $em->persist($post);
        $em->flush();

        $event = new Evenement();

        $event->setName($event_name);

        $ymd = DateTime::createFromFormat('d/m/Y', $event_date);
        $event->setDate($ymd);

        $event->addPost($post);

        $em->persist($event);
        $em->flush();
        
        $events = $this->getEvents(15);
        $query=$em->createQuery('SELECT COUNT(i) FROM App\Entity\Evenement i');
        $nb = $query->getResult();

        return new JsonResponse(array(
            'html'=>$this->render('Admin/Ajax/displayEventList.html.twig',array(
                'events'=>$events,
                'total' => $nb[0][1],
                'totalAfficher' => count($events),
                ))->getContent(),
            'notUploaded'=>$filesNotUploaded,
            'uploaded'=>$filesUploaded,
        ));
    }

    private function getImages($total){
        return $this->getDoctrine()
        ->getRepository(Image::class)
        ->findBy(
            array('gallery'=>1), // Critere
            array('dateCreated' => 'desc'),        // Tri
            $total,                              // Limite
            0                               // Offset
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
    private function getEvents($total){
        return $this->getDoctrine()
        ->getRepository(Evenement::class)
        ->findBy(
            array(), // Critere
            array('date' => 'desc'),        // Tri
            $total,                              // Limite
            0                               // Offset
          );
    }
}
