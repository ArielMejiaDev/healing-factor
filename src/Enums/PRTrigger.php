<?php

namespace ArielMejiaDev\XFactor\Enums;

enum PRTrigger: string
{
    case Webhook = 'webhook';
    case ExceptionListener = 'exception_listener';
}
