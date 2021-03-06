<?php
namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

class MailService
{

    const CONTACTS = '
    Whatsapp: +79255212648
    Telegram: @cashin_support
    Viber: +79255212648';

    private $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    public function sendMail($address, $message, $theme)
    {



        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
//        try {
        $mail->isSMTP();
        $mail->Host = 'smtp.yandex.ru';
        $mail->SMTPAuth = true;
        $mail->Username = 'service@1pxl.ru';    //Логин
        $mail->Password = 'Cmnn3r820242!@';                   //Пароль
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;

        $mail->setFrom('service@1pxl.ru', '1PXL.RU');
        $mail->addAddress($address);

        $mail->CharSet = \PHPMailer\PHPMailer\PHPMailer::CHARSET_UTF8;

        $mail->Subject = $theme;
        $mail->Body = $message;

        $mail->send();
//        } catch (\Exception $e) {
//            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
//        }

        return true;
    }

}
