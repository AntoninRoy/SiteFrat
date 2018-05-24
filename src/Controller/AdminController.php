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
        $sections = $this->getDoctrine()
        ->getRepository(Section::class)
        ->findAll();
        return $this->render('Admin/actualites.html.twig',array(
            'posts'=>$this->getPosts(15),
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
        if(!$post_title)return new JsonResponse(array(),500); 
        if(!$post_content)return new JsonResponse(array(),500);

        

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
        return new JsonResponse(array(
            'notUploaded'=>$filesNotUploaded,
            'uploaded'=>$filesUploaded,
            'html'=>$this->render('Admin/Ajax/displayPostList.html.twig',array("posts" => $this->getPosts(15),'post'=>$post))->getContent(),
            //'html'=>$this->render('Admin/Ajax/displayImageList.html.twig',array("images" => $this->getImages(15)))->getContent(),
        ));
    }
     /**
     * @Route("/admin/post/prepareUpdate", name="prepareUpdate_post",methods={"POST"}))
     */
    public function prepareUpdatePost(Request $request)
    {
        
        $postID = $request->get('postID');
        
        if(!$postID)return new JsonResponse(array(),500); 

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

        if(!$post_title)return new JsonResponse(array(),500); 
        if(!$post_content)return new JsonResponse(array(),500);
        if(!$post_id)return new JsonResponse(array(),500);

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
     * @Route("/admin/post/delete", name="delete_post",methods={"POST"}))
     */
    public function deletePost(Request $request)
    {
        $fileSystem = new Filesystem();
        $uploadDir = $this->getParameter('images_directory') . DIRECTORY_SEPARATOR . "galery" . DIRECTORY_SEPARATOR;
        $em = $this->getDoctrine()->getManager();
       
        $post_id = $request->get('post_id');
        if(!$post_id)return new JsonResponse(array(),500);

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

        return new JsonResponse(array(
            'html'=>$this->render('Admin/Ajax/displayPostList.html.twig',array("posts" => $this->getPosts(15),'post'=>$post))->getContent(),

        ));
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
                    $uploaded = $this->uploadExec($temp,$section,true);
                    array_push($filesUploaded,$files[$count]->getClientOriginalName());
                }else{
                    array_push($filesNotUploaded,$files[$count]->getClientOriginalName());
                }
                    
            }
        }else{
            $error = true;
        }


        return new JsonResponse(array(
            'error'=>$error,
            'message' => $message,
            'notUploaded'=>$filesNotUploaded,
            'uploaded'=>$filesUploaded,
            'html'=>$this->render('Admin/Ajax/displayImageList.html.twig',array("images" => $this->getImages(15)))->getContent(),
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
