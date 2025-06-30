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
        try {
            $this->chainService->execute($chain, $request);
        } catch (\Exception $e) {

            logger()->error('Chain execution failed', [
                'chain_id' => $chain->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => method_exists($e, 'getUserMessage') ? $e->getUserMessage() : 'Er trad een onverwachte fout op.',
            ], 400);

        } catch (\Throwable $e) {
            logger()->error('Unexpected chain failure', [
                'chain_id' => $chain->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Er trad een onverwachte fout op.',
            ], 500);
        }

        return response()->json(['status' => 'ok']);
    }
}
