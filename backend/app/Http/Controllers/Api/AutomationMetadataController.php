<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Automation\AutomationRegistry;
use Illuminate\Http\JsonResponse;

class AutomationMetadataController extends Controller
{
    public function __invoke(AutomationRegistry $registry): JsonResponse
    {
        return response()->json($registry->metadata());
    }
}
