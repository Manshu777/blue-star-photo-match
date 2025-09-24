<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use Aws\Exception\AwsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class S3FileUploadController extends Controller
{
    public function showForm()
    {
        $user = Auth::user();
        $plan = $user->plan;

        $photos = Photo::where('user_id', $user->id)
            ->latest()
            ->get()
            ->groupBy('event')
            ->toArray();

        // Format photos
        $photos = array_map(function ($eventPhotos) {
            return array_map(function ($photo) {
                return [
                    'url'      => Storage::disk('s3')->url($photo['image_path']),
                    'title'    => $photo['title'],
                    'id'       => $photo['id'],
                    'location' => $photo['location'] ?? 'No Location',
                    'tags'     => $photo['tags'] ? array_map('trim', explode(',', $photo['tags'])) : [],
                    'date'     => $photo['date'] ? \Carbon\Carbon::parse($photo['date'])->diffForHumans() : '',
                ];
            }, $eventPhotos);
        }, $photos);

        // Get 10 most recent uploads
        $recentUploads = Photo::where('user_id', $user->id)
    ->latest()
    ->take(10)
    ->get()
    ->map(function ($photo) {
        return [
            'url'      => Storage::disk('s3')->url($photo->image_path),
            'title'    => $photo->title,
            'id'       => $photo->id,
            'location' => $photo->location ?? 'No Location',
            'tags'     => $photo['tags'] ? array_map('trim', explode(',', $photo['tags'])) : [],
            'date'     => $photo->date ? \Carbon\Carbon::parse($photo->date)->diffForHumans() : '',
           'event' => $photo->event ?: 'Uncategorized',
        ];
    });


        // Calculate totals and usage
        $totalUploads = Photo::where('user_id', $user->id)->count();
        $storageUsedMB = Photo::where('user_id', $user->id)->sum('file_size');
        $storageUsedGB  = round($storageUsedMB / 1024, 2);
        $storageLimitGB = $plan->storage_limit ?? 0; // Assuming limit in GB, 0 for unlimited
        $storageUsagePercent = $storageLimitGB > 0 ? round(($storageUsedGB / $storageLimitGB) * 100, 2) : 0;

        $todayUploads = Photo::where('user_id', $user->id)
            ->whereDate('created_at', Carbon::today())
            ->count();
        $dailyUploadLimit = $plan->photo_upload_limit ?? 0; // 0 for unlimited
        $dailyUploadUsagePercent = $dailyUploadLimit > 0 ? round(($todayUploads / $dailyUploadLimit) * 100, 2) : 0;

        // Additional stats
        $totalEvents = count($photos); // Number of unique events/albums
        $featuredPhotos = Photo::where('user_id', $user->id)
            ->where('is_featured', true)
            ->count();
        $photosWithFaces = Photo::where('user_id', $user->id)
            ->whereNotNull('metadata->face_match_similarity')
            ->count();

        return view('upload-form', compact(
            'photos',
            'recentUploads',
            'totalUploads',
            'storageUsedGB',
            'storageLimitGB',
            'storageUsagePercent',
            'todayUploads',
            'dailyUploadLimit',
            'dailyUploadUsagePercent',
            'totalEvents',
            'featuredPhotos',
            'photosWithFaces',
            'plan'
        ));
    }

    public function index(Request $request) {}

    public function upload(Request $request)
    {
        try {
            // Multiple files validation
            $request->validate([
                'files' => 'required|array',
                'files.*' => 'file|mimes:jpg,jpeg,png,pdf,doc,docx,mp4,mov|max:20480', // 20MB
                'title' => 'nullable|string|max:255',
                'folder_name' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'tags' => 'nullable|string',
                'tour_provider' => 'nullable|string|max:255',
                'location' => 'nullable|string|max:255',
                'is_featured' => 'nullable|boolean',
            ]);

            $user = Auth::user();
            $uploaded = [];
            $directory = 'uploads';

            foreach ($request->file('files') as $file) {
                if (!$file->isValid()) {
                    \Log::error('Invalid file uploaded: ' . $file->getErrorMessage());
                    continue;
                }

                $fileName = Str::random(40) . '.' . $file->getClientOriginalExtension();

                \Log::info('Attempting S3 upload', [
                    'original_name' => $file->getClientOriginalName(),
                    'size'          => $file->getSize(),
                    'mime'          => $file->getMimeType(),
                    'target_path'   => $directory . '/' . $fileName,
                    'bucket'        => config('filesystems.disks.s3.bucket'),
                    'region'        => config('filesystems.disks.s3.region'),
                ]);

                // Upload to S3
                $path = Storage::disk('s3')->putFileAs($directory, $file, $fileName);

                if (empty($path)) {
                    \Log::error('S3 upload failed: Empty path returned');
                    continue;
                }

                // Get public URL (doesn't force an existence check)
                $url = Storage::disk('s3')->url($path);

                // OPTIONAL: persist to DB if you have Photo model/columns
                if (class_exists(\App\Models\Photo::class)) {
                    Photo::create([
                        'user_id'     => $user->id,
                        'image_path'  => $path,
                        'title'       => $request->input('title') ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                        'event'       => $request->input('folder_name'),
                        'description' => $request->input('description'),
                        'tags'        => $request->input('tags'),
                        'tour_provider' => $request->input('tour_provider'),
                        'location'    => $request->input('location'),
                        'is_featured' => (bool) $request->input('is_featured'),
                        'file_size'   => round($file->getSize() / 1024, 2), // KB or keep MB as per your schema
                        'date'        => now(),
                    ]);
                }

                $uploaded[] = ['path' => $path, 'url' => $url];
            }

            if (empty($uploaded)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No files were uploaded.'
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Files uploaded successfully',
                'files'   => $uploaded,
            ], 201);
        } catch (AwsException $e) {
            \Log::error('S3 AWS Exception: ' . $e->getMessage(), [
                'aws_error'   => $e->getAwsErrorCode(),
                'aws_message' => $e->getAwsErrorMessage(),
                'trace'       => $e->getTraceAsString(),
            ]);
            return response()->json(['success' => false, 'message' => 'S3 upload failed: ' . $e->getMessage()], 500);
        } catch (\Throwable $e) {
            \Log::error('S3 Upload Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Upload failed: ' . $e->getMessage()], 500);
        }
    }
}
