<?php

namespace Modules\Notification\Transport;

class Mock extends AbstractTransport
{
    public function __construct()
    {
    }

    public function send($from, $to, $subject, $body)
    {
        if(is_array($from)) {
            $from = current(array_values($from)) .' <'.current(array_keys($from)).'>';
        }
        file_put_contents(
            TEMP_DIR . '/email_' . md5($to) . '_' . date('Y.m.d.H.i.s') . 'txt',
            sprintf("From: %s\nTo: %s\nSubject: %s\nMessage: %s", $from, $to, $subject, $body)
        );

        return true;
    }
}