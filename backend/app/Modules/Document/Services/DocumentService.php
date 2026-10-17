<?php

namespace App\Modules\Document\Services;

use App\Core\Services\BaseService;
use App\Modules\Document\Models\Document;
use App\Modules\Document\Repositories\DocumentRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class DocumentService extends BaseService
{
    public function __construct(private DocumentRepositoryInterface $repo) {}

    public function create(array $data, UploadedFile $file): Document
    {
        $path = $file->store('documents', 'public');
        $data['company_id'] = auth()->user()->company_id;
        $data['file_path']  = $path;
        return $this->repo->create($data);
    }

    public function delete(Document $document): void
    {
        Storage::disk('public')->delete($document->file_path);
        $this->repo->delete($document);
    }

    public function getExpiringSoon(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repo->getExpiringSoon(30);
    }
}
