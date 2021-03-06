<?php

namespace App\Controller;


use App\Entity\Payment;
use App\Entity\User;
use App\Service\MailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ExchangeController extends AbstractController
{
    /**
     * @Route("/", name="index")
     */
    public function index()
    {


        return $this->render('exchange/index.html.twig', [
            'controller_name' => 'ExchangeController',
        ]);
    }


    /**
     * @Route("/exchange/event", name="exchange_event")
     */
    public function event(Request $request, MailService $mailService)
    {
        $data = $request->request->all();


        $em = $this->getDoctrine()->getManager();

        /* @var User $user */
        $user = $this->getUser();
        $key = '';

        if (isset($data['LMI_PREREQUEST']) && $data['LMI_PREREQUEST']){
            $result = $this->_WMXML19($data['LMI_PAYMENT_AMOUNT'], $data['LMI_PAYER_WM'], $data['secondName'], $data['firstName'], 'Альфа', $data['account']);


            if ($result['retval'] == "0"){
                return new Response('YES');
            }

            return new Response('NO');
        }




        if (isset($data['LMI_PAYEE_PURSE'])) {



            $key = hash('sha256', $data['LMI_PAYEE_PURSE'] . $data['LMI_PAYMENT_AMOUNT'] . $data['LMI_PAYMENT_AMOUNT'] . 'ghj34g27295452k5b34mn53j5yo21jkh4k2n5b#$@YH2342h4k2k234');















            $registration = '';

            if (!$user) {
                $user = $em->getRepository('App\Entity\User')->findOneBy(['email' => $data['email']]);
            }


            if (!$user) {

                $user = new User();
                $user->setEmail($data['email']);
                $user->setPassword('');
                $user->setActive(false);
                $token = md5(microtime() . $data['email'] . time());
                $user->setToken($token);
                $user->setRoles([]);

                $em->persist($user);


                $registration = 'Для вас был создан личный кабинет, где вы сможете следить за ходом выполнения заявки. Для завершения регистрации осталось только придумать пароль: ' . $_ENV['URL'] . '/setPassword?token=' . $token . '
            Что дает регистрация:

- личный кабинет с историей операций
- автоматическое заполнение формы обмена данными клиента
- возможность получить индивидуальные условия обмена ';

            }


            $user->setFirstName($data['firstName']);
            $user->setSecondName($data['secondName']);
            $user->setMiddleName($data['middleName']);
            $user->setAccount($data['account']);


            if ($data['currency'] == 'USD') {
//                $sumPay = round($data['sum'] / 0.98, 2);
                $sumPay = $data['LMI_PAYMENT_AMOUNT'];

            } elseif ($data['currency'] == 'RUR') {
                $rate = json_decode($this->currency()->getContent(), true);

//                $sumPay = round(($data['sum'] / $rate['rate']) / 0.98, 2);
                $sumPay = $data['LMI_PAYMENT_AMOUNT'];
            }


            $payment = new Payment();
            $payment->setDate(new \DateTime());
            $payment->setCurrency($data['currency']);
            $payment->setAccount($data['account']);
            $payment->setSumTransaction($data['sum']);
            $payment->setSumPay($sumPay);
            $payment->setStatus(false);
            $payment->setUser($user);

            $em->persist($payment);


            $em->flush();


            $message = 'Добрый день

Операция: ' . $payment->formatId() . '
WMZ: ' . $sumPay . '
Сумма в АльфаБанк: ' . $data['sum'] . ' ' . $data['currency'] . '
Cтатус: Ожидаемое время выполнения 1 час.
После оплаты заявки вы получите дополнительное письмо.
' . $registration

                . '

Поддержка:
Не стесняйтесь задавать нам любые вопросы: ' . MailService::CONTACTS;


            $mailService->sendMail($data['email'], $message, '1PXL.RU: Данные операции');

        }

        return new \Symfony\Component\HttpFoundation\Response($key);
    }


    /**
     * @Route("/exchange/success", name="exchange_success")
     */
    public function success()
    {




        return $this->render('exchange/success.html.twig');
    }


    /**
     * @Route("/exchange/error", name="exchange_error")
     */
    public function error()
    {


        return $this->render('exchange/error.html.twig');
    }


    /**
     * @Route("/calculationAmountPaid", name="calculation_amount_paid")
     */
    public function calculationAmountPaid(Request $request)
    {

        $email = $request->get('email');
        $currency = $request->get('currency');
        $sum = $request->get('sum');

        $sumPay = 0;

        $em = $this->getDoctrine()->getManager();

        $user = $em->getRepository('App\Entity\User')->findOneBy(['email' => $email]);

        $vip = false;

        if ($user && $user->getVip()) {

            $vip = true;
        }


        $tariff = $em->getRepository('App\Entity\Tariff')->findOneBy(['vip' => $vip], ['date' => 'DESC']);

        if ($tariff) {
            $rate = $tariff->getRate();
        } else {
            $rate = 0;
        }

        if (!$sum) {
            $sum = 0;
        }

        if ($currency == 'USD') {
            $sumPay = $sum / ((100 - $rate) / 100);
        } elseif ($currency == 'RUR') {
            $currencyRate = $em->getRepository('App\Entity\CurrencyRate')->findOneBy([], ['date' => 'DESC']);

            $sumPay = $sum / $currencyRate->getRate() / ((100 - $rate) / 100);
        }


        return new JsonResponse(['sumPay' => round($sumPay, 2)]);

    }


    /**
     * @Route("/serviceRules", name="service_rules")
     */
    public function serviceRules()
    {

        return $this->render('exchange/serviceRules.html.twig');
    }




    public function _GetReqn()
    {
        $time = microtime();
        $int = substr($time, 11);
        $flo = substr($time, 2, 5);
        return $int . $flo;
    }


    public function _GetAnswer($address, $xml){

//        $Path_Certs = '/home/projects/WMunited.cer';

        // Инициализируем сеанс CURL
        $ch = curl_init($address);
        // В выводе CURL http-заголовки не нужны
        curl_setopt($ch, CURLOPT_HEADER, 0);
        // Возвращать результат, а не выводить его в браузер
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        // Метод http-запроса - POST
        curl_setopt($ch, CURLOPT_POST,1);
        // Что передаем?
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);

//        curl_setopt($ch, CURLOPT_CAINFO, $Path_Certs);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
        // Выполняем запрос, ответ помещаем в переменную $result;
        $result=curl_exec($ch);
        // Раскомментировать следующую строку, чтобы посмотреть ошибки выполнения curl запроса
        	if(curl_errno($ch)) { file_put_contents('data2.txt', "Curl Error number='".curl_errno($ch)."' err desc='".curl_error($ch)."' \n"); };


        return $result;
    }



    public function _WMXML19 ($amount, $wmid, $fname, $iname, $bank_name, $bank_account) {
        $ourWMID = '353748377045';
        $XML_addr = 'https://apipassport.webmoney.ru/XMLCheckUser.aspx';



        $reqn=$this->_GetReqn();
//        $fname=iconv("CP1251", "UTF-8", $fname);
//        $iname=iconv("CP1251", "UTF-8", $iname);
//        $bank_name=iconv("CP1251", "UTF-8", $bank_name);

        $rsign = $this->_GetSign($reqn.'3'.$wmid);

        $xml="
	<passport.request>
		<reqn>$reqn</reqn>
		<signerwmid>$ourWMID</signerwmid>
		<sign>$rsign</sign>
		<operation>
			<type>3</type>
			<pursetype>WMZ</pursetype>
			<amount>$amount</amount>
		</operation>
		<userinfo>
			<wmid>$wmid</wmid>
			<fname>$fname</fname>
			<iname>$iname</iname>
			<bank_name>$bank_name</bank_name>
			<bank_account>$bank_account</bank_account>
		</userinfo>
	</passport.request>";
        $resxml=$this->_GetAnswer($XML_addr, $xml);
        // echo $resxml;
        $xmlres = simplexml_load_string($resxml);
        if(!$xmlres) {
            $result['retval']=1000;
            $result['retdesc']="Не получен XML-ответ";
            return $result;
        }
        $result['retval']=strval($xmlres->retval);
        $result['retdesc']=iconv("UTF-8", "CP1251", strval($xmlres->retdesc));
        $result['iname']=iconv("UTF-8", "CP1251", strval($xmlres->userinfo->iname));
        $result['oname']=iconv("UTF-8", "CP1251", strval($xmlres->userinfo->oname));
        $result['retid']=strval($xmlres->retid);


        return $result;
    }

    public function _GetSign($inStr) {
        $Path_Folder = '/home/projects/key/';
        $Path_Signer = '/home/projects/Signer/wmsigner';
        chdir($Path_Folder);
        $descriptorspec = array(
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
            2 => array("pipe", "r") );
        $process = proc_open($Path_Signer, $descriptorspec, $pipes);
        fwrite($pipes[0], "$inStr\004\r\n");
        fclose($pipes[0]);
        $s = fgets($pipes[1], 133);
        fclose($pipes[1]);
        $return_value = proc_close($process);
        return $s;
    }
}


