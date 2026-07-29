<?php
namespace VMP\Modules\VendorRegistration\Events;

class VendorApproved {
    public function __construct(public object $request, public int $approvedBy) {}
}
