<?php

namespace App\Services;

use App\Models\User;
use App\Support\CaptainDocumentCollections;
use Illuminate\Http\Request;

class CaptainDocumentService
{
    public function syncFromRequest(User $user, Request $request): void
    {
        if ($user->type !== 'captain') {
            return;
        }

        foreach (CaptainDocumentCollections::keys() as $collection) {
            if ($request->hasFile($collection)) {
                // clean all media from the collection
                $user->clearMediaCollection($collection);
                $media = $user->addMediaFromRequest($collection)->toMediaCollection($collection);
                // dd($media);
            }
        }

        if ($request->hasFile('image')) {
            $user->addMediaFromRequest('image')->toMediaCollection('image');
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function toResourceArray(User $user, string $locale): array
    {
        if ($user->type !== 'captain') {
            return [];
        }

        app()->setLocale($locale);

        $documents = [];
        foreach (CaptainDocumentCollections::keys() as $collection) {
            $media = $user->getFirstMedia($collection);
            $documents[$collection] = [
                'collection' => $collection,
                'label' => __('api.captain_documents.'.$collection),
                'file' => $media === null ? null : [
                    'url' => $media->getUrl(),
                    'mime_type' => $media->mime_type,
                    'file_name' => $media->file_name,
                    'size' => $media->size,
                    'uploaded_at' => $media->created_at,
                ],
            ];
        }

        return $documents;
    }
}
