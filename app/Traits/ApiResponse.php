<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Symfony\Component\HttpFoundation\Response;

trait ApiResponse
{
    protected function successResponse(
        string $message,
        JsonResource|ResourceCollection|array|null $data = null,
        int $statusCode = Response::HTTP_OK
    ): JsonResponse {
        $response = [
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    protected function errorResponse(
        string $message,
        int $statusCode = Response::HTTP_BAD_REQUEST,
        array $errors = []
    ): JsonResponse {
        $response = [
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    protected function createdResponse(
        string $message,
        JsonResource|array|null $data = null
    ): JsonResponse {
        return $this->successResponse($message, $data, Response::HTTP_CREATED);
    }

    protected function acceptedResponse(
        string $message,
        JsonResource|array|null $data = null
    ): JsonResponse {
        return $this->successResponse($message, $data, Response::HTTP_ACCEPTED);
    }

    protected function noContentResponse(): JsonResponse
    {
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    protected function paginatedResponse(
        string $message,
        ResourceCollection $resourceCollection,
        int $statusCode = Response::HTTP_OK
    ): JsonResponse {
        $data = $resourceCollection->response()->getData(true);

        return response()->json([
            'message' => $message,
            'data'    => $data['data'] ?? [],
            'links'   => $data['links'] ?? [],
            'meta'    => $data['meta'] ?? [],
        ], $statusCode);
    }
}

