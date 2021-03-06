<?php

namespace App\Controller;

use App\Entity\CurrencyRate;
use App\Entity\Payment;
use App\Entity\Tariff;
use App\Service\MailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    /**
     * @Route("/", name="admin", host="admin.1pxl.ru")
     */
    public function index()
    {

        $em = $this->getDoctrine()->getManager();

        $payments = $em->getRepository('App\Entity\Payment')->findAll();


        return $this->render('admin/index.html.twig', [
            'payments' => $payments,
        ]);

    }


    /**
     * @Route("/rates", name="admin_rates", host="admin.1pxl.ru")
     */
    public function rates()
    {
        $em = $this->getDoctrine()->getManager();

        $rates = $em->getRepository('App\Entity\CurrencyRate')->findBy([], ['date' => 'DESC']);


        return $this->render('admin/rates.html.twig', [
            'rates' => $rates,
        ]);

    }


    /**
     * @Route("/confirmPayment", name="admin_confirm_payment", host="admin.1pxl.ru")
     */
    public function confirmPayment(Request $request, MailService $mailService)
    {
        $em = $this->getDoctrine()->getManager();

        $id = $request->get('id');

        /* @var Payment $payment */
        $payment = $em->getRepository('App\Entity\Payment')->find($id);


        $payment->setStatus(true);

        $em->flush();


        $message = 'Добрый день

Операция: '.$payment->formatId().'
WMZ: ' . $payment->getSumPay() . ' wmz
Сумма в АльфаБанк: ' . $payment->getSumTransaction() . ' ' . $payment->getCurrency() . ' 
Статус: Выполнено.

Поддержка: 
Не стесняйтесь задавать нам любые вопросы: ' . MailService::CONTACTS;


        $mailService->sendMail($payment->getUser()->getEmail(), $message, '1PXL.RU: Выполнение заявки');


        return new JsonResponse(['status' => 'success']);


    }


    /**
     * @Route("/addRate", name="admin_add_rate", host="admin.1pxl.ru")
     */
    public function addRate(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $rate = $request->get('rate');

        $currencyRate = new CurrencyRate();

        $currencyRate->setDate(new \DateTime());

        $currencyRate->setRate($rate);


        $em->persist($currencyRate);

        $em->flush();

        return new JsonResponse(['date' => $currencyRate->getDate()->format('d.m.Y H:i'), 'rate' => $currencyRate->getRate()]);

    }


    /**
     * @Route("/vip", name="admin_vip", host="admin.1pxl.ru")
     */
    public function vip()
    {

        $em = $this->getDoctrine()->getManager();

        $users = $em->getRepository('App\Entity\User')->findBy(['vip' => true], ['vipDate'=>'DESC']);


        return $this->render('admin/vip.html.twig', [
            'users' => $users,
        ]);

    }


    /**
     * @Route("/addVip", name="admin_add_vip", host="admin.1pxl.ru")
     */
    public function addVip(Request $request)
    {

        $em = $this->getDoctrine()->getManager();

        $email = $request->get('email');


        $user = $em->getRepository('App\Entity\User')->findOneBy(['email' => $email]);


        if ($user->getVip()) {
           return new JsonResponse(['status'=>'error']);
        }
        $user->setVip(true);
        $user->setVipDate(new \DateTime());

        $em->flush();


        return new JsonResponse(['status' => 'success']);

    }


    /**
     * @Route("/users", name="admin_users", host="admin.1pxl.ru")
     */
    public function users()
    {

        $em = $this->getDoctrine()->getManager();

        $users = $em->getRepository('App\Entity\User')->findBy([], ['id'=>'DESC']);


        return $this->render('admin/users.html.twig', [
            'users' => $users,
        ]);

    }


    /**
     * @Route("/passwordRecovery", name="passwordRecoveryAdmin", host="admin.1pxl.ru")
     */
    public function passwordRecoveryAdmin(Request $request, MailService $mailService): Response
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
     * @Route("/tariff", name="admin_tariff", host="admin.1pxl.ru")
     */
    public function tariff(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $vip = $request->get('vip');

        $tariffs = $em->getRepository('App\Entity\Tariff')->findBy(['vip'=>$vip], ['date' => 'DESC']);


        return $this->render('admin/tariff.html.twig', [
            'tariffs' => $tariffs,
            'vip'=>$vip
        ]);

    }


    /**
     * @Route("/addTariff", name="admin_add_tariff", host="admin.1pxl.ru")
     */
    public function addTariff(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $rate = $request->get('rate');

        $vip = $request->get('vip', false);

        $tariff = new Tariff();

        $tariff->setDate(new \DateTime());

        $tariff->setRate($rate);

        $tariff->setVip($vip);


        $em->persist($tariff);

        $em->flush();

        return new JsonResponse(['date' => $tariff->getDate()->format('d.m.Y H:i'), 'rate' => $tariff->getRate()]);

    }

}
