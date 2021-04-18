<?php

namespace App\Helpers;

Class ExportToExcel {

    public $model;

    public function __construct($model)
    {
            $this->model = $model;
    }
}