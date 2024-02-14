<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class VendorRoleType extends Enum
{
    const Guardian = 1;
    const Student = 2;
    const Vendor = 3;
    const VendorStaff = 4;
    const SuperAdmin = 50;
}
