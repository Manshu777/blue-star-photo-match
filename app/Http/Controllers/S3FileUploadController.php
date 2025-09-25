<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use Aws\Exception\AwsException;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class S3FileUploadController extends Controller
{
    public function showForm()
    {
        $user = Auth::user();
        $plan = $user->plan; // assume relations loaded

        // Group photos by event (album)
        $photos = Photo::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy(fn ($p) => $p->event ?: 'Uncategorized')
            ->map(function ($collection) {
                return $collection->map(function (Photo $p) {
                    return [
                        'id'       => $p->id,
                        'title'    => $p->title,
                        'url'      => Storage::disk('s3')->url($p->image_path),
                        'location' => $p->location ?: 'No Location',
                        'tags'     => $p->tags ? array_map('trim', explode(',', $p->tags)) : [],
                        'date'     => optional($p->date)->format('Y-m-d'),
                    ];
                })->values();
            });

        // Recent uploads (10)
        $recentUploads = Photo::where('user_id', $user->id)
            ->latest()->take(10)->get()->map(function (Photo $p) {
                return [
                    'id'    => $p->id,
                    'title' => $p->title,
                    'url'   => Storage::disk('s3')->url($p->image_path),
                    'date'  => optional($p->date)->format('Y-m-d'),
                    'location' => $p->location ?: 'No Location',
                    'tags'  => $p->tags ? array_map('trim', explode(',', $p->tags)) : [],
                ];
            });

        // Stats
        $totalUploads = Photo::where('user_id', $user->id)->count();
        $storageUsedMB = (float) Photo::where('user_id', $user->id)->sum('file_size'); // MB (see upload())
        $storageUsedGB = round($storageUsedMB / 1024, 2);

        $storageLimitGB = (float) ($plan->storage_limit ?? 0); // 0 => unlimited
        $storageUsagePercent = $storageLimitGB > 0
            ? min(100, round(($storageUsedGB / $storageLimitGB) * 100, 2))
            : 0;

        $todayUploads = Photo::where('user_id', $user->id)
            ->whereDate('created_at', Carbon::today())->count();
        $dailyUploadLimit = (int) ($plan->photo_upload_limit ?? 0); // 0 => unlimited
        $dailyUploadUsagePercent = $dailyUploadLimit > 0
            ? min(100, round(($todayUploads / $dailyUploadLimit) * 100, 2))
            : 0;

        $totalEvents = $photos->count();
        $featuredPhotos = Photo::where('user_id', $user->id)->where('is_featured', true)->count();
        $photosWithFaces = Photo::where('user_id', $user->id)
            ->whereNotNull('metadata->face_match_similarity')->count();

        // Pass to Blade
        return view('upload-form', [
            'plan' => $plan,
            'photos' => $photos,
            'recentUploads' => $recentUploads,
            'totalUploads' => $totalUploads,
            'storageUsedGB' => $storageUsedGB,
            'storageLimitGB' => $storageLimitGB,
            'storageUsagePercent' => $storageUsagePercent,
            'todayUploads' => $todayUploads,
            'dailyUploadLimit' => $dailyUploadLimit,
            'dailyUploadUsagePercent' => $dailyUploadUsagePercent,
            'totalEvents' => $totalEvents,
            'featuredPhotos' => $featuredPhotos,
            'photosWithFaces' => $photosWithFaces,
        ]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'file|mimes:jpg,jpeg,png,mp4,mov,pdf,doc,docx|max:20480', // 20MB
            'title' => 'nullable|string|max:255',
            'folder_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'tags' => 'nullable|string',
            'tour_provider' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'is_featured' => 'nullable|boolean',
        ]);

        $user = Auth::user();
        $plan = $user->plan;

        // Enforce daily upload limit (if any)
        $dailyLimit = (int) ($plan->photo_upload_limit ?? 0);
        if ($dailyLimit > 0) {
            $today = Photo::where('user_id', $user->id)->whereDate('created_at', Carbon::today())->count();
            if ($today + count($request->file('files')) > $dailyLimit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Daily upload limit exceeded for your plan.',
                ], 422);
            }
        }

        // Enforce storage cap (if any)
        $storageCapGB = (float) ($plan->storage_limit ?? 0);
        if ($storageCapGB > 0) {
            $currentGB = (float) Photo::where('user_id', $user->id)->sum('file_size') / 1024;
            $incomingMB = array_sum(array_map(fn ($f) => $f->getSize() / 1024 / 1024, $request->file('files'))); // MB
            if ($currentGB + ($incomingMB / 1024) > $storageCapGB + 0.0001) {
                return response()->json([
                    'success' => false,
                    'message' => 'Storage limit exceeded for your plan.',
                ], 422);
            }
        }

        $uploaded = [];
        $directory = 'uploads/' . $user->id;

        try {
            foreach ($request->file('files') as $file) {
                if (!$file->isValid()) {
                    continue;
                }

                $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();

                $path = Storage::disk('s3')->putFileAs($directory, $file, $fileName, [
                    'visibility' => 'public',
                    'ContentType' => $file->getMimeType(),
                ]);

                $url = Storage::disk('s3')->url($path);

                Photo::create([
                    'user_id'       => $user->id,
                    'image_path'    => $path,
                    'title'         => $request->input('title') ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                    'event'         => $request->input('folder_name'),
                    'description'   => $request->input('description'),
                    'tags'          => $request->input('tags'),
                    'tour_provider' => $request->input('tour_provider'),
                    'location'      => $request->input('location'),
                    'is_featured'   => (bool) $request->boolean('is_featured'),
                    // Store MB so stats math is easy
                    'file_size'     => round($file->getSize() / 1024 / 1024, 2), // MB
                    'date'          => now(),
                ]);

                $uploaded[] = ['path' => $path, 'url' => $url];
            }

            if (!$uploaded) {
                return response()->json(['success' => false, 'message' => 'No files uploaded'], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Files uploaded successfully',
                'files'   => $uploaded,
            ], 201);
        } catch (AwsException $e) {
            return response()->json(['success' => false, 'message' => 'S3 error: ' . $e->getMessage()], 500);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Upload failed: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(Photo $photo)
{
    $this->authorizePhoto($photo);

    try {
        // Try deleting from S3 but don't block DB deletion
        try {
            if ($photo->image_path) {
                Storage::disk('s3')->delete($photo->image_path);
            }
        } catch (\Throwable $e) {
            \Log::warning('S3 delete failed (continuing with DB delete): '.$e->getMessage(), [
                'path' => $photo->image_path,
            ]);
        }

        // Always delete DB row
        $photo->delete();

        return response()->json(['success' => true, 'message' => 'Photo deleted']);
    } catch (\Throwable $e) {
        return response()->json(['success' => false, 'message' => 'Delete failed: '.$e->getMessage()], 500);
    }
}


    public function renameAlbum(Request $request)
    {
        $data = $request->validate([
            'old_event' => 'required|string',
            'new_event' => 'required|string|max:255',
        ]);

        $userId = Auth::id();

        Photo::where('user_id', $userId)
            ->where(function ($q) use ($data) {
                // "Uncategorized" UI maps to NULL/'' in DB
                if ($data['old_event'] === 'Uncategorized') {
                    $q->whereNull('event')->orWhere('event', '');
                } else {
                    $q->where('event', $data['old_event']);
                }
            })
            ->update(['event' => $data['new_event']]);

        return response()->json(['success' => true, 'message' => 'Album renamed']);
    }

    public function deleteAlbum(Request $request)
{
    $data = $request->validate([
        'event' => 'required|string',
    ]);

    $userId = Auth::id();
    $eventIn = trim($data['event']);

    // Fetch all photos matching the album (case-insensitive, trimmed)
    $photos = Photo::where('user_id', $userId)
        ->where(function ($q) use ($eventIn) {
            if ($eventIn === 'Uncategorized') {
                $q->whereNull('event')->orWhere('event', '');
            } else {
                $q->whereRaw('LOWER(TRIM(event)) = ?', [mb_strtolower($eventIn)]);
            }
        })->get();

    foreach ($photos as $photo) {
        try {
            try {
                if ($photo->image_path) {
                    Storage::disk('s3')->delete($photo->image_path);
                }
            } catch (\Throwable $e) {
                \Log::warning('S3 delete failed (album): '.$e->getMessage(), ['path' => $photo->image_path]);
            }

            // Always delete DB row even if S3 fails
            $photo->delete();

        } catch (\Throwable $e) {
            \Log::error('Album delete DB error: '.$e->getMessage(), ['photo_id' => $photo->id]);
        }
    }

    return response()->json(['success' => true, 'message' => 'Album deleted']);
}
}
