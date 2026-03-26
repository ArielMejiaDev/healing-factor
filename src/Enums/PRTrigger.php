<?php

namespace ArielMejiaDev\HealingFactor\Enums;

enum PRTrigger: string
{
    case Webhook = 'webhook';
    case ExceptionListener = 'exception_listener';
}
