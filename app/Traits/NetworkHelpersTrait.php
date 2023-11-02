<?php

namespace App\Traits;

trait NetworkHelpersTrait
{
    public function validateHostname(string $hostname): bool
    {
        return (bool) filter_var($hostname, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6);
    }
}
