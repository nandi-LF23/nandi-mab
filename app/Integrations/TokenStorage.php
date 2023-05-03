<?php

namespace App\Integrations;

use MacsiDigital\OAuth2\Support\Token\DB;
use MacsiDigital\OAuth2\Integration;

class TokenStorage extends DB {
    public function __construct($integration)
    {
        $company_id = session('company_id');
        $int_name = "{$integration}-{$company_id}";

        $this->integration = $int_name;
        $this->model = Integration::where('name', $int_name)->firstOrNew();
        $this->setFromModel($this->model);

        return $this;
    }
}