<?php

namespace Rogierw\RwAcme\Enums;

enum AuthorizationChallengeEnum: string
{
    case HTTP = 'http-01';
    case DNS = 'dns-01';
}