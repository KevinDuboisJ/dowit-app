<?php

namespace App\Contracts;
use App\Models\Chain;

interface ChainAction
{
    /**
     * Handle a matched chain.
     *
     * @param  mixed  $context  Either a Task model (internal) or array (API payload)
     * @param  \App\Models\Chain  $chain
     * @return void
     */
    public function handle($context, Chain $chain);
}