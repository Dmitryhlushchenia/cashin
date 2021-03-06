<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\MailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils, Request $request): Response
    {


        $admin = false;
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        if ($request->getHost() == 'admin.1pxl.ru'){
            $admin = true;
        };

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }


    /**
     * @Route("/login", name="app_login_admin", host="admin.1pxl.ru")
     */
    public function loginAdmin(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();


        $admin = true;

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error, 'admin'=>$admin]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }




    /**
     * @Route("/registartion", name="app_registration")
     */
    public function registration(Request $request, MailService $mailService): Response
    {
        $em = $this->getDoctrine()->getManager();

        $email = $request->get('email');


        $error = null;


        if ($email) {

            $user = $em->getRepository('App\Entity\User')->findOneBy(['email'=>$email]);

            if ($user){
                $error = 'Пользователь с таким email уже зарегистрирован';
            }else{
                $user = new User();
                $user->setEmail($email);
                $user->setRoles([]);
                $user->setPassword('');
                $token = md5(microtime() . $email . time());
                $user->setToken($token);
                $user->setDate(new \DateTime());



                $em->persist($user);

                $em->flush();

                $message = 'Добрый день

Для регистрации осталось только нажать на ссылку: '.$_ENV['URL'].'/setPassword?token='.$token.'

Что дает регистрация:

-	личный кабинет с историей операций
-	автоматическое заполнение формы обмена данными клиента
-	возможность получить индивидуальные условия обмена


Поддержка:
Не стесняйтесь задавать нам любые вопросы:
Ссылки на контакты:'.MailService::CONTACTS;


                $mailService->sendMail($email, $message, '1PXL.RU: Подтверждение регистрации');




                return $this->render('security/registrationStep1.html.twig');

            }


        }



        return $this->render('security/registration.html.twig', [ 'error' => $error]);
    }




    /**
     * @Route("/passwordRecovery", name="passwordRecovery")
     */
    public function passwordRecovery(Request $request, MailService $mailService): Response
    {
        $em = $this->getDoctrine()->getManager();

        $email = $request->get('email');


        $error = null;


        if ($email) {

            $user = $em->getRepository('App\Entity\User')->findOneBy(['email'=>$email]);

            if (!$user){
                $error = 'Пользователь с таким email не зарегистрирован в системе';
            }else{

                $token = md5(microtime() . $email . time());
                $user->setToken($token);



                $em->persist($user);

                $em->flush();


                $message = 'Добрый день

На сайте 1pxl.ru Вы заказали восстановление пароля к личному кабинету.
Для этого достаточно пройти по ссылке: '.$_ENV['URL'].'/setPassword?recovery=1&token='.$token.'
и указать новый пароль

Поддержка: 
Не стесняйтесь задавать нам любые вопросы:
Ссылки на контакты:' .MailService::CONTACTS;

                $mailService->sendMail($email, $message, '1PXL.RU: Восстановление пароля');




                return $this->render('security/registrationStep1.html.twig');

            }


        }



        return $this->render('security/passwordRecovery.html.twig', [ 'error' => $error]);
    }






    /**
     * @Route("/setPassword", name="setPassword")
     */
    public function setPassword(Request $request): Response
    {
        $em = $this->getDoctrine()->getManager();

        $pass = $request->get('password');
        $repeatPass = $request->get('repeatPassword');
        $token = $request->get('token');
        $recovery  = $request->get('recovery');

        if (!$token) return new Response('Страница не найдена!', 404);

        $user = $em->getRepository('App\Entity\User')->findOneBy(['token'=>$token]);


        $error = null;

        if (!$user){

            $error = 'Информация устарела!';

            return $this->render('security/errorRegistration.html.twig', [ 'error' => $error]);
        }


        if ($repeatPass && $pass) {

            if ($repeatPass === $pass) {


                $user->setPassword(password_hash($pass, PASSWORD_BCRYPT));

                $user->setActive(true);

                $user->setToken(null);

                $em->flush();
                $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
                $this->container->get('security.token_storage')->setToken($token);
                $this->container->get('session')->set('_security_main', serialize($token));

                return $this->render('security/successRegistration.html.twig', ['recovery'=>$recovery]);

            }else{

                $error = 'Пароли не совпадают!';
            }



        }



        return $this->render('security/setPassword.html.twig', [ 'error' => $error, 'recovery'=>$recovery]);
    }








}
