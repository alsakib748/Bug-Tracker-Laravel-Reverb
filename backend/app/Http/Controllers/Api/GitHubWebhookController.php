<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Integrations\GitHub\Services\GitHubWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GitHubWebhookController extends Controller
{
    public function __construct(private readonly GitHubWebhookService $service) {}

    /**
     * Signature-authenticated webhook endpoint (no Sanctum — GitHub cannot
     * hold a session token; the HMAC signature is the authentication).
     */
    public function handle(Request $request): JsonResponse
    {
        if (! $this->service->verifySignature($request)) {
            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        $this->service->handleDelivery($request);

        return response()->json(['message' => 'Accepted'], 202);
    }
}
