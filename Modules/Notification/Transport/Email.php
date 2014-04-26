<?php

namespace Modules\Notification\Transport;

class Email extends AbstractTransport
{
    /**
     * @var \Swift_Mailer
     */
    protected $mailer = null;

    public function __construct()
    {
        $config = \Bazalt\Config::container();
        $transport = null;
        if(isset($config['config']['email']) && $config['config']['email']['transport'] == 'smtp') {
            $transport = \Swift_SmtpTransport::newInstance($config['config']['email']['host'],
                (int)$config['config']['email']['port'], $config['config']['email']['security'])
                ->setUsername($config['config']['email']['username'])
                ->setPassword($config['config']['email']['password']);
        }
        if(!$transport) {
            $transport = \Swift_MailTransport::newInstance();
        }
        $this->mailer = \Swift_Mailer::newInstance($transport);
    }

    public function send($from, $to, $subject, $body)
    {
        $message = \Swift_Message::newInstance()
            ->setFrom($from)
            ->setTo($to)
            ->setSubject($subject)
            ->setBody($body, 'text/html');

        return $this->mailer->send($message);
    }
}