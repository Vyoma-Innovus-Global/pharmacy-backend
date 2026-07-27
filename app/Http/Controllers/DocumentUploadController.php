<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DocumentUploadController extends Controller
{
    /**
     * Upload document and return file path for database storage
     *
     * POST /api/document/upload
     * Body: multipart/form-data with 'document' field
     *
     * Stores file in: storage/app/public/uploads/
     * Returns: uploads/{filename} for database storage
     */
    public function upload(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'document' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240', // Max 10MB
        ], [
            'document.required' => 'Please select a document to upload.',
            'document.mimes' => 'Only PDF, DOC, DOCX, JPG, JPEG, and PNG files are allowed.',
            'document.max' => 'File size must not exceed 10MB.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('document');

            // Get file details
            $originalName = $file->getClientOriginalName();
            $fileSize = $file->getSize(); // in bytes
            $fileSizeKB = round($fileSize / 1024, 2);

            $storedFile = $this->storeUploadedFile($file, $request);
            $filename = $storedFile['stored_name'];
            $filePath = $storedFile['file_path'];
            $storagePath = $filePath;
            $fullUrl = $this->publicFileUrl($filePath);

            Log::channel('daily')->info('[DocumentUpload] File uploaded successfully', [
                'original_name' => $originalName,
                'stored_name' => $filename,
                'file_path' => $filePath,
                'file_size' => "{$fileSizeKB} KB",
                'ip' => $request->ip()
            ]);

            return response()->json([
                'error' => false,
                'message' => 'Document uploaded successfully',
                'data' => [
                    'original_name' => $originalName,
                    'stored_name' => $filename,
                    'file_path' => $filePath,              // Relative path, for example uploads/file.pdf
                    'storage_path' => $storagePath,        // Path to store in database
                    'full_url' => $fullUrl,                // Full public URL
                    'file_size' => $fileSizeKB,            // Size in KB
                    'file_size_bytes' => $fileSize,        // Size in bytes
                    'uploaded_at' => now()->format('Y-m-d H:i:s'),
                    'directory' => 'uploads'
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error('[DocumentUpload] Upload failed', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return response()->json([
                'error' => true,
                'message' => 'Failed to upload document',
                'details' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * General purpose document upload.
     *
     * POST /api/document/upload-general
     * Body: multipart/form-data with 'file' field
     *
     * Stores file in: storage/app/public/uploads/
     * Returns: uploads/{filename} for database storage
     */
    public function uploadGeneral(Request $request)
    {
        $fileField = $request->hasFile('file') ? 'file' : 'document';

        $validator = Validator::make($request->all(), [
            $fileField => 'required|file|mimes:pdf,png,jpg,jpeg|max:300',
        ], [
            "{$fileField}.required" => 'Please select a file to upload.',
            "{$fileField}.mimes" => 'Only PDF, PNG, JPG, and JPEG files are allowed.',
            "{$fileField}.max" => 'File size must not exceed 300KB.',
        ]);

        if ($validator->fails()) {
            $response = [
                'error' => true,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ];

            if ($request->hasFile($fileField)) {
                $file = $request->file($fileField);
                $fileSizeBytes = $file->getSize();

                $response['file_size'] = round($fileSizeBytes / 1024, 2) . ' KB';
                $response['max_file_size'] = '300 KB';
            }

            return response()->json($response, 422);
        }

        try {
            $file = $request->file($fileField);
            $storedFile = $this->storeUploadedFile($file, $request);
            $fileSize = $file->getSize();

            Log::channel('daily')->info('[DocumentUpload] General file uploaded successfully', [
                'original_name' => $file->getClientOriginalName(),
                'stored_name' => $storedFile['stored_name'],
                'file_path' => $storedFile['file_path'],
                'ip' => $request->ip()
            ]);

            return response()->json([
                'error' => false,
                'message' => 'File uploaded successfully',
                'data' => [
                    'filename' => $storedFile['stored_name'],
                    'stored_name' => $storedFile['stored_name'],
                    'path' => $storedFile['file_path'],
                    'file_path' => $storedFile['file_path'],
                    'storage_path' => $storedFile['file_path'],
                    'full_url' => $this->publicFileUrl($storedFile['file_path']),
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'file_size' => round($fileSize / 1024, 2),
                    'file_size_bytes' => $fileSize,
                    'uploaded_at' => now()->format('Y-m-d H:i:s')
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::channel('daily')->error('[DocumentUpload] General upload failed', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return response()->json([
                'error' => true,
                'message' => 'Failed to upload file',
                'details' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Upload multiple documents
     *
     * POST /api/document/upload-multiple
     * Body: multipart/form-data with 'documents[]' field (array of files)
     */
    public function uploadMultiple(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'documents' => 'required|array|min:1|max:10',
            'documents.*' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ], [
            'documents.required' => 'Please select at least one document to upload.',
            'documents.array' => 'Documents must be an array of files.',
            'documents.max' => 'Maximum 10 files allowed at once.',
            'documents.*.mimes' => 'Only PDF, DOC, DOCX, JPG, JPEG, and PNG files are allowed.',
            'documents.*.max' => 'Each file size must not exceed 10MB.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $uploadedFiles = [];
            $failedFiles = [];

            foreach ($request->file('documents') as $index => $file) {
                try {
                    // Get file details
                    $originalName = $file->getClientOriginalName();
                    $fileSize = $file->getSize();
                    $fileSizeKB = round($fileSize / 1024, 2);

                    $storedFile = $this->storeUploadedFile($file, $request);
                    $filename = $storedFile['stored_name'];
                    $filePath = $storedFile['file_path'];
                    $fullUrl = $this->publicFileUrl($filePath);
                    $storagePath = $filePath;

                    $uploadedFiles[] = [
                        'original_name' => $originalName,
                        'stored_name' => $filename,
                        'file_path' => $filePath,
                        'storage_path' => $storagePath,
                        'full_url' => $fullUrl,
                        'file_size' => $fileSizeKB,
                        'file_size_bytes' => $fileSize,
                    ];

                } catch (\Exception $e) {
                    $failedFiles[] = [
                        'index' => $index,
                        'original_name' => $file->getClientOriginalName(),
                        'error' => $e->getMessage()
                    ];
                }
            }

            $totalUploaded = count($uploadedFiles);
            $totalFailed = count($failedFiles);

            Log::channel('daily')->info('[DocumentUpload] Multiple upload completed', [
                'total_uploaded' => $totalUploaded,
                'total_failed' => $totalFailed,
                'ip' => $request->ip()
            ]);

            return response()->json([
                'error' => $totalFailed > 0 && $totalUploaded === 0,
                'message' => $totalFailed === 0
                    ? 'All documents uploaded successfully'
                    : ($totalUploaded === 0
                        ? 'All uploads failed'
                        : "Uploaded {$totalUploaded} files, {$totalFailed} failed"),
                'data' => [
                    'uploaded_files' => $uploadedFiles,
                    'failed_files' => $failedFiles,
                    'total_uploaded' => $totalUploaded,
                    'total_failed' => $totalFailed,
                    'uploaded_at' => now()->format('Y-m-d H:i:s')
                ]
            ], $totalFailed === 0 ? 200 : 207); // 207 = Multi-Status

        } catch (\Exception $e) {
            Log::channel('daily')->error('[DocumentUpload] Multiple upload failed', [
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'error' => true,
                'message' => 'Failed to upload documents',
                'details' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Delete uploaded document
     *
     * DELETE /api/document/delete
     * Body: { "file_path": "uploads/20260522120000_abc123.pdf" }
     */
    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file_path' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $filePath = $request->input('file_path');
            $fullPath = $this->uploadedFileFullPath($filePath);

            // Check if file exists
            if (!file_exists($fullPath)) {
                return response()->json([
                    'error' => true,
                    'message' => 'File not found'
                ], 404);
            }

            // Delete file
            unlink($fullPath);

            Log::channel('daily')->info('[DocumentUpload] File deleted', [
                'file_path' => $filePath,
                'ip' => $request->ip()
            ]);

            return response()->json([
                'error' => false,
                'message' => 'Document deleted successfully',
                'data' => [
                    'file_path' => $filePath,
                    'deleted_at' => now()->format('Y-m-d H:i:s')
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error('[DocumentUpload] Delete failed', [
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'error' => true,
                'message' => 'Failed to delete document',
                'details' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get document info
     *
     * GET /api/document/info?file_path=uploads/20260522120000_abc123.pdf
     */
    public function getInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file_path' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $filePath = $request->input('file_path');
            $fullPath = $this->uploadedFileFullPath($filePath);

            // Check if file exists
            if (!file_exists($fullPath)) {
                return response()->json([
                    'error' => true,
                    'message' => 'File not found'
                ], 404);
            }

            // Get file info
            $size = filesize($fullPath);
            $lastModified = filemtime($fullPath);
            $mimeType = mime_content_type($fullPath);
            $fullUrl = $this->publicFileUrl($filePath);
            $storagePath = $filePath;

            return response()->json([
                'error' => false,
                'message' => 'Document info retrieved successfully',
                'data' => [
                    'file_path' => $filePath,
                    'storage_path' => $storagePath,
                    'full_url' => $fullUrl,
                    'file_size' => round($size / 1024, 2),
                    'file_size_bytes' => $size,
                    'mime_type' => $mimeType,
                    'last_modified' => date('Y-m-d H:i:s', $lastModified),
                    'exists' => true
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error('[DocumentUpload] Get info failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => true,
                'message' => 'Failed to get document info',
                'details' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    private function storeUploadedFile($file, Request $request): array
    {
        $filename = $this->buildFilename($file, $request);

        $stored = $file->storeAs('uploads', $filename, 'public');

        if (!$stored) {
            throw new \RuntimeException('Unable to store uploaded document.');
        }

        return [
            'stored_name' => $filename,
            'file_path' => "uploads/{$filename}",
        ];
    }

    private function buildFilename($file, Request $request): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $docDetails = json_decode($request->input('docDetails', '{}'), true);
        $requestedName = is_array($docDetails)
            ? ($docDetails['filename'] ?? $docDetails['file_name'] ?? $docDetails['fileName'] ?? null)
            : null;

        if ($requestedName) {
            $requestedName = pathinfo(basename($requestedName), PATHINFO_FILENAME);
            $requestedName = Str::slug($requestedName, '_');

            if ($requestedName !== '') {
                return "{$requestedName}.{$extension}";
            }
        }

        $timestamp = now()->format('YmdHis');
        $randomString = Str::random(8);
        $originalBaseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeOriginalName = Str::slug($originalBaseName, '_');

        return "{$timestamp}_{$randomString}_{$safeOriginalName}.{$extension}";
    }

    private function publicFileUrl(string $filePath): string
    {
        if (Str::startsWith($filePath, 'storage/')) {
            return url($filePath);
        }

        return url("storage/{$filePath}");
    }

    private function uploadedFileFullPath(string $filePath): string
    {
        if (Str::startsWith($filePath, 'storage/')) {
            return public_path($filePath);
        }

        $storageDiskPath = storage_path("app/public/{$filePath}");

        if (file_exists($storageDiskPath)) {
            return $storageDiskPath;
        }

        return public_path($filePath);
    }
}
