<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File; // <--- Import ito para sa mimeType
use Symfony\Component\HttpFoundation\Response;

class FileController extends Controller
{
    public function viewFile(Request $request)
    {
        $path = $request->query('path'); // attachments/hash.pdf
        $name = $request->query('name'); // Original_Name.pdf
        $action = $request->query('action', 'view'); // Default ay view, pwedeng 'download'

        // 1. Check kung may laman ang path
        if (!$path) {
            return response()->json(['message' => 'Path is required.'], 400);
        }

        // 2. Check kung existing ang file sa storage/app/public
        if (!Storage::disk('public')->exists($path)) {
            return response()->json(['message' => 'File not found on server.'], 404);
        }

        // 3. I-define ang Full Path (Dapat mauna ito!)
        $fullPath = storage_path('app/public/' . $path);

        // 4. Kunin ang Mime Type (e.g., image/jpeg, application/pdf)
        $mimeType = File::mimeType($fullPath);

        // 5. Pagpili kung i-VIEW o i-DOWNLOAD
        if ($action === 'download') {
            return response()->download($fullPath, $name);
        }

        // DEFAULT: I-VIEW (Preview sa browser)
        return response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.$name.'"'
        ]);
    }
}