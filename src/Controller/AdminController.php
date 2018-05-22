<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

use App\Entity\Section;

use App\Entity\Post;
use App\Form\PostType;

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
    public function post()
    {
     
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        return $this->render('Admin/actualites.html.twig',array(
            'form' => $form->createView()    
        ));
    }

    /**
     * @Route("/admin/post/create", name="create_post",methods={"POST"}))
     */
    public function createPost(Request $request)
    {
        
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);
        $errors = array();
        
        if ($form->isSubmitted()) {
            $validator = $this->get('validator');
            $errorsValidator = $validator->validate($post);

            foreach ($errorsValidator as $error) {
                array_push($errors, $error->getMessage());
            }


            if (count($errors) == 0) {
                $post->setOwner($this->getUser());
                $post->setDate(new DateTime());
                $em = $this->getDoctrine()->getManager();
                $em->persist($post);
                $em->flush();

                return new JsonResponse(array(
                    'code' => 200,
                    'message' => $this->render('Admin/Ajax/createPost.html.twig')->getContent(),
                    'errors' => array('errors' => array(''))),
                    200);
            }

        }

        return new JsonResponse(array(
            'code' => 400,
            'message' => 'error',
            'errors' => array('errors' => $errors)),
            400);
        
    }

    
    

    /**
     * @Route("/admin/image", name="admin_image")
     */
    public function image()
    {
        $sections = $this->getDoctrine()
        ->getRepository(Section::class)
        ->findAll();



        $image = new Image();
        $form = $this->createForm(ImageType::class, $image);
        return $this->render('Admin/images.html.twig',array(
            'form' => $form->createView(),
            'sections'=> $sections,
            'images'=>$this->getImages(15),
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

        return new JsonResponse(array(
            'code' => 200,
            'probleme'=>$image->getId(),
            'html'=>$this->render('Admin/Ajax/displayImageList.html.twig',array("images" => $this->getImages(15)))->getContent(),
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
            'probleme'=>$image->getId(),
            'html'=>$this->render('Admin/Ajax/morePost.html.twig',array("image" => $image))->getContent()),
            200);
    }

    /**
     * @Route("/admin/image/viewmore", name="viewmore_image",methods={"POST"}))
     */
    public function viewmoreImage(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $total=$request->get('total');
        
        

        return new JsonResponse(array(
            'html'=>$this->render('Admin/Ajax/displayImageList.html.twig',array("images" => $this->getImages($total+15)))->getContent(),
            'errors' => array('errors' => array(''))),
            200);
    }

    /**
     * @Route("/admin/image/upload", name="admin_image_upload",methods={"POST"})
     */
    public function uploadImage(Request $request)
    {
        $files = $request->files->get('files');

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
                    $uploaded = $this->uploadExec($temp,$section);
                    array_push($filesUploaded,$files[$count]->getClientOriginalName());
                    $debug .=$files[$count]->getClientSize()."-----";
                }else{
                    array_push($filesNotUploaded,$files[$count]->getClientOriginalName());
                }
                    
            }
        }else{
            $error = true;
        }


        return new JsonResponse(array(
            'error'=>$error,
            'uploaded' => $uploaded,
            'message' => $message,
            'notUploaded'=>$filesNotUploaded,
            'uploaded'=>$filesUploaded,
            'html'=>$this->render('Admin/Ajax/displayImageList.html.twig',array("images" => $this->getImages(15)))->getContent(),
        ));
    }

    private function uploadExec($args = array(),$section)
    {
        $count = 0;
        $image_files = [];
        $doctrine = $this->getDoctrine()->getManager();

        $uploadDir = $this->getParameter('images_directory') . DIRECTORY_SEPARATOR . "galery" . DIRECTORY_SEPARATOR;

        if(!is_dir($uploadDir))
        {
            mkdir($uploadDir, 0775, TRUE);
        }

        if(!empty($args) && count($args) > 0)
        {
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
                        $productImages = new Image();
                        $productImages->setFiles($filename[$count]);
                        $productImages->setPublisher($this->getUser());
                        $productImages->setSection($section);
                        $productImages->setPrettyName($prettyName);
                        $productImages->setX($size["0"]);
                        $productImages->setY($size["1"]);
                        $productImages->setDateCreated(new DateTime());

                        $doctrine->persist($productImages);
                    }
                }
            }

            $jsonEncodeFiles = json_encode($image_files);
            
            $doctrine->flush();

            if( NULL != $productImages->getId() )return TRUE;
        }

        return FALSE;
    }

    private function getImages($total){
        return $this->getDoctrine()
        ->getRepository(Image::class)
        ->findBy(
            array(), // Critere
            array('dateCreated' => 'desc'),        // Tri
            $total,                              // Limite
            0                               // Offset
          );
    }
}
