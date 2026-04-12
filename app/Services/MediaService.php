<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaService
{
    public function storeAvatar(User $user, UploadedFile $file): Media
    {
        return $user->addMedia($file)->toMediaCollection('avatar');
    }

    public function storeDocument(User $user, UploadedFile $file): Media
    {
        return $user->addMedia($file)->toMediaCollection('documents');
    }

    public function deleteAvatar(User $user): void
    {
        $user->clearMediaCollection('avatar');
    }

    public function deleteDocument(User $user, int $mediaId): void
    {
        $media = $user->media()
            ->where('id', $mediaId)
            ->where('collection_name', 'documents')
            ->first();

        if (! $media) {
            throw (new ModelNotFoundException)->setModel(Media::class, [$mediaId]);
        }

        $media->delete();
    }
}
