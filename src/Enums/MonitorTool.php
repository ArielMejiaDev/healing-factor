<?php

namespace ArielMejiaDev\XFactor\Enums;

enum MonitorTool: string
{
    case Nightwatch = 'nightwatch';
    case Bugsnag = 'bugsnag';
    case ExceptionListener = 'exception_listener';
}
