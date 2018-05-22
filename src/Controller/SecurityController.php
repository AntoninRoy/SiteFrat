<?php
namespace App\Controller;

use App\Form\ChangeParametersType;
use App\Entity\ChangeParameters;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;



class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="security_login")
     */
    public function login(AuthenticationUtils $helper): Response
    {
        return $this->render('Security/login.html.twig', [
            // dernier username saisi (si il y en a un)
            'last_username' => $helper->getLastUsername(),
            // La derniere erreur de connexion (si il y en a une)
            'error' => $helper->getLastAuthenticationError(),
        ]);
    }

    /**
     * La route pour se deconnecter.
     * 
     * Mais celle ci ne doit jamais être executé car symfony l'interceptera avant.
     *
     *
     * @Route("/logout", name="security_logout")
     */
    public function logout(): void
    {
        throw new \Exception('This should never be reached!');
    }

     /**
     *
     *
     * @Route("/parameters", name="parameters")
     */
    public function registerAction(Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        $changeParameters = new ChangeParameters();
        $form = $this->createForm(ChangeParametersType::class, $changeParameters);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            
            $password = $passwordEncoder->encodePassword($this->getUser(), $changeParameters->getNewPassword());
            $user= $this->getUser();
            $user->setPassword($password);
            $em = $this->getDoctrine()->getManager();
            $em->flush();

            return $this->redirectToRoute('index');
        }

        return $this->render(
            'Security/change_parameters.html.twig',
            array('form' => $form->createView())
        );
    }
}