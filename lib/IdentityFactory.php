<?php

use LTO\AccountFactory;

class IdentityFactory
{
    protected $af;

    public function __construct($network = 'W')
    {
        $this->af = new AccountFactory($network);
    }

    public function initiateIdentity($id, $seed, $role='participant'): Identity
    {
        $account = $this->af->seed($seed);
        return new Identity($id, $account, $role);
    }
}