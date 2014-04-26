<?php

namespace Modules\Notification\Transport;

abstract class AbstractTransport
{
    abstract public function send($from, $to, $subject, $body);
}