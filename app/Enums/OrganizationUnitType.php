<?php

namespace App\Enums;

enum OrganizationUnitType: string
{
    case Campus = 'campus';
    case Office = 'office';
    case College = 'college';
    case Department = 'department';
    case Program = 'program';
}
