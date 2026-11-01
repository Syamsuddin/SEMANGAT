<?php

namespace App\Modules\Idp\Enums;

/** Templat IDP (IDP-08, SEBAIKNYA — F3 hanya memakai `standard`). */
enum IdpTemplate: string
{
    case Standard = 'standard';
    case Acceleration = 'acceleration';
    case Improvement = 'improvement';
}
