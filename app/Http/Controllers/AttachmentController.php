<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function show(Request $request, Transaction $transaction, Attachment $attachment): StreamedResponse
    {
        $this->authorize('view', $transaction);

        $entity = $this->activeEntity($request);
        if ($transaction->entity_id !== $entity->id) {
            abort(404);
        }

        if ($attachment->attachable_type !== Transaction::class || $attachment->attachable_id !== $transaction->id) {
            abort(404);
        }

        if (! Storage::disk('local')->exists($attachment->file_path)) {
            abort(404);
        }

        return Storage::disk('local')->download(
            $attachment->file_path,
            $attachment->original_name ?? basename($attachment->file_path),
        );
    }
}
