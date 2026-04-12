<?php

namespace App\Http\Controllers;

use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserMediaController extends Controller
{
    public function __construct(private readonly MediaService $mediaService) {}

    public function storeAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'image', 'max:5120'],
        ]);

        $media = $this->mediaService->storeAvatar($request->user(), $request->file('file'));

        return response()->json([
            'message' => __('api.media.avatar_uploaded'),
            'url' => $media->getUrl(),
            'thumb_url' => $media->getUrl('thumb'),
        ], Response::HTTP_CREATED);
    }

    public function destroyAvatar(Request $request): JsonResponse
    {
        $this->mediaService->deleteAvatar($request->user());

        return response()->json([
            'message' => __('api.media.avatar_removed'),
        ]);
    }

    public function storeDocument(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx,jpg,jpeg,png,gif,webp,txt'],
        ]);

        $media = $this->mediaService->storeDocument($request->user(), $request->file('file'));

        return response()->json([
            'message' => __('api.media.file_uploaded'),
            'id' => $media->id,
            'file_name' => $media->file_name,
            'mime_type' => $media->mime_type,
            'size' => $media->size,
            'url' => $media->getUrl(),
        ], Response::HTTP_CREATED);
    }

    public function destroyDocument(Request $request, int $media): JsonResponse
    {
        $this->mediaService->deleteDocument($request->user(), $media);

        return response()->json([
            'message' => __('api.media.file_deleted'),
        ]);
    }
}
