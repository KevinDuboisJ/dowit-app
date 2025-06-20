<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ChainService;
use App\Models\Chain;

class ChainController extends Controller
{
    protected $chainService;

    public function __construct(ChainService $chainService)
    {
        $this->chainService = $chainService;
    }

    public function handleApiChain(Chain $chain, Request $request)
    {
        dd($chain);
        $this->chainService->execute($chain, $request);

        return response()->json(['status' => 'ok']);
    }
}
