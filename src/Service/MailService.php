<?php

namespace App\Service;

use App\Entity\Rental;
use Swift_Attachment;
use Swift_Mailer;
use Swift_Message;
use Twig\Environment as Templating;

class MailService
{
    private $eMotionMail = ['reservation.emotion@gmail.com' => 'eMotion'];

    /**
     * @var Swift_Mailer
     */
    private $mailer;
    /**
     * @var Templating
     */
    private $templating;

    /**
     * MailService constructor.
     */
    public function __construct(Swift_Mailer $mailer, Templating $templating)
    {
        $this->mailer = $mailer;
        $this->templating = $templating;
    }

    /**
     * ========================================================================
     *                             OLD Function
     * ========================================================================
     */

    public function sendMail(Swift_Mailer $mailer, $subject, $from, $to, $body)
    {
        $message = (new Swift_Message($subject))
            ->setFrom($from)
            ->setTo($to)
            ->setBody($body, 'text/html');

        $mailer->send($message);
    }


    /**
     * ========================================================================
     *                        General Mail Function
     * ========================================================================
     */

    public function prepareEmail(string $subject, array $to, string $body): Swift_Message
    {
        return (new Swift_Message())->setSubject($subject)
            ->setFrom($this->eMotionMail)
            ->setTo($to)
            ->setBcc($this->eMotionMail)
            ->setBody($body, 'text/html');
    }

    public function sendEmail(Swift_Message $mail): bool
    {
        return $this->mailer->send($mail);
    }

    public function sendEmailWithAttachment(Swift_Message $mail, array $attachments): bool
    {
        foreach ($attachments as $name => $path) {
            $mail->attach(
                Swift_Attachment::fromPath(
                    $path,
                    'application/pdf'
                )->setFilename($name)
            );
        }

        return $this->sendEmail($mail);
    }


    /**
     * ========================================================================
     *                        Prepared Mail Function
     * ========================================================================
     */

    public function sendMailContrat(Rental $rental): bool
    {
        $subject = 'Facture de votre réservation du '.$rental->getStartRentalDate(
            )->format('d/m/Y');

        $body = $this->templating->render(
            'emails/contract.html.twig',
            [
                'rental' => $rental,
            ]
        );

        $to = [$rental->getClient()->getEmail()];

        $mail = $this->prepareEmail($subject, $to, $body);

        $attachment = [];

        foreach ($rental->getPdf()['contract'] as $pdf) {

            foreach (array_keys($pdf) as $date) {
                $attachment[] = $pdf[$date];
            }
        }

        $attachment = [
            'Contrat de location n°'.$rental->getId()
            => $attachment[count($attachment) - 1],
        ];

        return $this->sendEmailWithAttachment($mail, $attachment);
    }

    public function sendMailContact(string $firstname, string $lastname, $email, $message)
    {
        $subject = 'Demande de contact';

        $body = $this->templating->render(
            'emails/contact.html.twig',
            [
                'firstname' => $firstname,
                'lastname' => $lastname,
                'mail' => $email,
                'message' => $message,
            ]
        );

        $mail = $this->prepareEmail(
            $subject,
            array_merge([$email], $this->eMotionMail),
            $body
        );

        return $this->sendEmail($mail);

    }
}
