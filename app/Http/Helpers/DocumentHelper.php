<?php

use App\Models\Document;
use Illuminate\Support\Str;

/**
 * Function to Save Document as an instance of Document.
 *
 * @param $file
 * @return Document
 */
function saveDocument($file): Document
{
    $slug = Str::random(32);
    $path = (config('constants.path.document_url') . date('y/m/'));

    $doc = new Document([
        'filename' => $file->getClientOriginalName(),
        'path' => $path,
        'slug' => $slug,
        'size' => $file->getSize(),
        'type' => $file->getType(),
        'mimetype' => $file->getMimeType(),
        'extension' => $file->getClientOriginalExtension(),
        'created_by' => auth()->user() ? auth()->user()->id : 0,
    ]);

    $file->move(storage_path('app/' . $path), $slug . '.' . $file->getClientOriginalExtension());
    $doc->save();
    return $doc;
}

/**
 * Function to Remove Document with the instance.
 *
 * @param $doc
 * @return boolean
 */
function removeDocument($doc): bool
{
    if(!$doc){
        return false;
    }
    $document = Document::find($doc->id);
    $path = storage_path('app/' . $document->path) . $document->slug . '.' . $document->extension;
    try {
        \Illuminate\Support\Facades\File::delete($path);
        $document->delete();
    } catch (Exception $e){
        return false;
    }
    return true;
}
