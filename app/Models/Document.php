<?php

namespace App\Models;

use App\Services\DocumentTextExtractor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'uploaded_by',
        'title',
        'filename',
        'path',
        'mime_type',
        'size',
        'extracted_text',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public static function storeUpload(Organization $organization, User $user, UploadedFile $file): self
    {
        $path = $file->store(
            "documents/{$organization->id}",
            'local'
        );

        $document = self::create([
            'organization_id' => $organization->id,
            'uploaded_by' => $user->id,
            'title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        if (DocumentTextExtractor::extractable($document->mime_type)) {
            $text = DocumentTextExtractor::extract($document);
            $document->update(['extracted_text' => $text]);
        }

        return $document;
    }
}
