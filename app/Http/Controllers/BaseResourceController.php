<?php

namespace App\Http\Controllers;

abstract class BaseResourceController extends Controller
{
    protected string $modelClass;

    public function __construct()
    {
        $this->middleware('auth');
        $this->authorizeResource($this->modelClass); 
    }
}
