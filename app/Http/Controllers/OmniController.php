<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OmniTicketService;

class OmniController extends Controller
{
    public function forwardToOmni(Request $request, OmniTicketService $omniService)
    {
        $payload = $request->all();
        $result = $omniService->sendToOmni($payload);

        return response()->json($result, $result['success'] ? 200 : 400);
    }
}