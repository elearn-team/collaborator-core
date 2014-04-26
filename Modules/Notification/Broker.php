<?php

namespace Modules\Notification;

class Broker
{
    private static $_defaultTransport = null;

    public  static function setDefaultTransport($transport)
    {
        self::$_defaultTransport = $transport;
    }

    protected  static function getDefaultTransport()
    {
        if(self::$_defaultTransport == null) {
            self::$_defaultTransport = new Transport\Email();
        }
        return self::$_defaultTransport;
    }

    public static function onNotification($name, $variables)
    {
        $file = __DIR__.'/templates/'. $name.'.tpl';
        if(!file_exists($file)) {
            throw new \Exception(sprintf('Unknown template for notification "%s"', $name));
        }
        $templateCont = file_get_contents($file);
        foreach($variables as $varName => $varValue) {
            $templateCont = str_replace('{{'.$varName.'}}', $varValue, $templateCont);
        }

        $config = \Bazalt\Config::container();
        $fromName = 'Collaborator';
        if(isset($config['config']['email']) && isset($config['config']['email']['fromName'])) {
            $fromName = $config['config']['email']['fromName'];
        }
        $fromEmail = 'no-reply@domain.com';
        if(isset($config['config']['email']) && isset($config['config']['email']['fromEmail'])) {
            $fromEmail = $config['config']['email']['fromEmail'];
        }
        $subject = 'Els';
        if(isset($variables['subject'])) {
            $subject = $variables['subject'];
        }
        $result = self::getDefaultTransport()
            ->send(array($fromEmail => $fromName), $variables['email'], $subject, $templateCont);
    }
}