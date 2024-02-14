<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class RoleType extends Enum
{
    // const Guardian = 1;
    // const Student = 2;
    // const Vendor = 3;
    // const VendorStaff = 4;
    // const SuperAdmin = 5;
    // const SchoolRepresentative = 6;
    // const User = 7;

    const Guardian = 1;
    const Student = 2;
    const Vendor = 3;
    const VendorStaff = 4;
    const SuperAdmin = 50;
    const SchoolRepresentative = 6;
    const User = 7;
    const School = 8;
}
