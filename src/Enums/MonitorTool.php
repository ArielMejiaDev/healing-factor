<?php

namespace ArielMejiaDev\HealingFactor\Enums;

enum MonitorTool: string
{
    case Nightwatch = 'nightwatch';
    case Bugsnag = 'bugsnag';
    case ExceptionListener = 'exception_listener';
}
