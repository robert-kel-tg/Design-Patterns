<?php

abstract class Mail
{
    abstract function send(MailTransport $mailTransport);
}

class NotificationMail extends Mail
{
    function send(MailTransport $mailTransport)
    {
        return sprintf("Notify %s. ", $mailTransport->body());
    }
}

class SubscriptionMail extends Mail
{
    function send(MailTransport $mailTransport)
    {
        return sprintf("Subscribe %s. ", $mailTransport->body());
    }
}

class UnsubscriptionMail extends Mail
{
    function send(MailTransport $mailTransport)
    {
        return sprintf("Unsubscribe %s. ", $mailTransport->body());
    }
}

interface MailTransport
{
    public function body();
}

class SmtpTransport implements MailTransport
{
    public function body()
    {
        return 'SmtpTransport';
    }
}

class PopTransport implements MailTransport
{
    public function body()
    {
        return 'PopTransport';
    }
}

#######################################################################################################################

$mail = new NotificationMail();
echo $mail->send(new PopTransport());

$mail = new SubscriptionMail();
echo $mail->send(new PopTransport());

echo '<br>';

$mail = new NotificationMail();
echo $mail->send(new SmtpTransport());

$mail = new SubscriptionMail();
echo $mail->send(new SmtpTransport());

$mail = new UnsubscriptionMail();
echo $mail->send(new SmtpTransport());