<?php

namespace Jeylabs\Recruiter\Facades;
use Illuminate\Support\Facades\Facade;

class Recruiter extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'recruiter';
    }
}