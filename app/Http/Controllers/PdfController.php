<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class PdfController extends Controller
{
    public function serve($filename)
    {
        // Ensure the filename is decoded properly (though Laravel routing usually handles this)
        // $filename is caught by the wildcard route, e.g. "books/filename.pdf"
        
        // We assume files are stored in the 'public' disk
        if (!Storage::disk('public')->exists($filename)) {
            abort(404, 'File not found');
        }

        $path = Storage::disk('public')->path($filename);
        $mimeType = Storage::disk('public')->mimeType($filename);

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
        ]);
    }
}
