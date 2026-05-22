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
     * Upload PDF document and return full file path
     *
     * POST /api/document/upload
     * Body: multipart/form-data with 'document' field
     *
     * Stores file in: storage/app/public/documents/{year}/{month}/
     * Returns: Full file path accessible via public URL
     */
    public function upload(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'document' => 'required|file|mimes:pdf|max:10240', // Max 10MB
        ], [
            'document.required' => 'Please select a PDF file to upload.',
            'document.mimes' => 'Only PDF files are allowed.',
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

            // Generate unique filename
            $timestamp = now()->format('YmdHis');
            $randomString = Str::random(8);
            $extension = $file->getClientOriginalExtension();
            $filename = "{$timestamp}_{$randomString}.{$extension}";

            // Create directory path: storage/documents/YYYY/MM/
            $year = now()->format('Y');
            $month = now()->format('m');
            $directory = "storage/documents/{$year}/{$month}";

            // Store file directly in public/storage/documents/YYYY/MM/
            $publicPath = public_path($directory);
            if (!file_exists($publicPath)) {
                mkdir($publicPath, 0755, true);
            }
            $file->move($publicPath, $filename);
            $filePath = "{$directory}/{$filename}";

            // Generate full URL
            $fullUrl = url($filePath);

            // Generate storage path (for database storage)
            $storagePath = $filePath;

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
                    'file_path' => $filePath,              // Relative path in storage
                    'storage_path' => $storagePath,        // Path to store in database
                    'full_url' => $fullUrl,                // Full public URL
                    'file_size' => $fileSizeKB,            // Size in KB
                    'file_size_bytes' => $fileSize,        // Size in bytes
                    'uploaded_at' => now()->format('Y-m-d H:i:s'),
                    'directory' => $directory
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
     * Upload multiple PDF documents
     *
     * POST /api/document/upload-multiple
     * Body: multipart/form-data with 'documents[]' field (array of files)
     */
    public function uploadMultiple(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'documents' => 'required|array|min:1|max:10',
            'documents.*' => 'required|file|mimes:pdf|max:10240',
        ], [
            'documents.required' => 'Please select at least one PDF file to upload.',
            'documents.array' => 'Documents must be an array of files.',
            'documents.max' => 'Maximum 10 files allowed at once.',
            'documents.*.mimes' => 'Only PDF files are allowed.',
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

                    // Generate unique filename
                    $timestamp = now()->format('YmdHis');
                    $randomString = Str::random(8);
                    $extension = $file->getClientOriginalExtension();
                    $filename = "{$timestamp}_{$randomString}.{$extension}";

                    // Create directory path: storage/documents/YYYY/MM/
                    $year = now()->format('Y');
                    $month = now()->format('m');
                    $directory = "storage/documents/{$year}/{$month}";

                    // Store file directly in public/storage/documents/YYYY/MM/
                    $publicPath = public_path($directory);
                    if (!file_exists($publicPath)) {
                        mkdir($publicPath, 0755, true);
                    }
                    $file->move($publicPath, $filename);
                    $filePath = "{$directory}/{$filename}";
                    $fullUrl = url($filePath);
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
     * Body: { "file_path": "documents/2026/05/20260522120000_abc123.pdf" }
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
            $fullPath = public_path($filePath);

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
     * GET /api/document/info?file_path=documents/2026/05/20260522120000_abc123.pdf
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
            $fullPath = public_path($filePath);

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
            $fullUrl = url($filePath);
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
}
