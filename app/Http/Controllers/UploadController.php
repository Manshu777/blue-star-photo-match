<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use Aws\Rekognition\RekognitionClient;
use Aws\S3\S3Client;
use Carbon\Carbon;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UploadController extends Controller
{

    protected $s3;
    protected $rekognition;

      public function __construct()
{
    Log::info('AWS Config', [
        'region' => 'us-west-2',
        'key' => 'AKIAQBN6HXVALETMTXPG',
        'bucket' => 'w6a9N+BJUWVZFoXICfFG/0Xqifu7AxFoltvmxsRQ',
    ]);

    $this->s3 = new S3Client([
        'version' => 'latest',
        'region' =>  'us-west-2',
        'credentials' => [
            'key' => 'AKIAQBN6HXVALETMTXPG',
            'secret' => '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',
        ],
    ]);
    $this->rekognition = new RekognitionClient([
        'version' => 'latest',
        'region' => 'AKIAQBN6HXVALETMTXPG',
        'credentials' => [
            'key' => 'AKIAQBN6HXVALETMTXPG',
            'secret' => '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',
        ],
    ]);
}

   public function store(Request $request)
    {
        try {
            $user = Auth::user();
            $plan = $user->plan;

            if (!$plan->is_active) {
                Log::error('Inactive plan attempted upload', ['user_id' => $user->id, 'plan' => $plan->name]);
                return $this->errorResponse('Your plan is inactive.', 403);
            }

            if ($plan->photo_upload_limit > 0) {
                $todayUploads = Photo::where('user_id', $user->id)
                    ->whereDate('created_at', Carbon::today())
                    ->count();
                $newFilesCount = count($request->file('files') ?? []);
                if ($todayUploads + $newFilesCount > $plan->photo_upload_limit) {
                    Log::error('Daily upload limit exceeded', ['user_id' => $user->id, 'limit' => $plan->photo_upload_limit]);
                    return $this->errorResponse('Daily upload limit exceeded.', 403);
                }
            }

            if ($plan->storage_limit > 0) {
                $usedStorage = Photo::where('user_id', $user->id)->sum('file_size');
                $newFilesSize = 0;
                foreach ($request->file('files') ?? [] as $file) {
                    $newFilesSize += $file->getSize() / (1024 * 1024);
                }
                if (($usedStorage + $newFilesSize) / 1024 > $plan->storage_limit) {
                    Log::error('Storage limit exceeded', ['user_id' => $user->id, 'used' => $usedStorage, 'limit' => $plan->storage_limit]);
                    return $this->errorResponse('Storage limit exceeded.', 403);
                }
            }

            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'price' => 'nullable|numeric|min:0',
                'license_type' => 'nullable|string|in:commercial,personal',
                'is_featured' => 'nullable|boolean',
                'tags' => 'nullable|string|max:500',
                'tour_provider' => 'nullable|string|max:255',
                'location' => 'nullable|string|max:255',
                'folder_name' => 'nullable|string|max:255',
                'files.*' => 'required|file|mimes:jpg,jpeg,png,mp4,mov|max:5120',
            ]);

            $photos = [];
            $files = $request->file('files') ?? [];
            $processedHashes = [];

            if (empty($files)) {
                return $this->errorResponse('No files uploaded.', 422);
            }

            foreach ($files as $file) {
                if (!$file->isValid()) {
                    Log::error('Invalid file uploaded', ['error' => $file->getErrorMessage(), 'user_id' => $user->id]);
                    continue;
                }

                // Check for duplicate file
                $fileHash = hash_file('md5', $file->getRealPath());
                if (in_array($fileHash, $processedHashes)) {
                    Log::warning('Duplicate file detected', ['user_id' => $user->id, 'hash' => $fileHash]);
                    continue;
                }
                $processedHashes[] = $fileHash;

                $fileExtension = $file->getClientOriginalExtension();
                $fileName = Str::random(40) . '.' . $fileExtension;
                $directory = 'uploads';
                $path = "Uploads/$fileName";

                // Upload to S3 with timeout
                $startTime = microtime(true);
                $timeout = 30; // seconds
                $uploadedPath = Storage::disk('s3')->putFileAs($directory, $file, $fileName);
                if (!$uploadedPath || (microtime(true) - $startTime) > $timeout) {
                    Log::error('S3 upload failed or timed out', ['user_id' => $user->id, 'file' => $fileName]);
                    continue;
                }

                // Verify S3 object availability with reduced retries
                $maxRetries = 3;
                $retryDelay = 1;
                $attempt = 0;
                while ($attempt < $maxRetries) {
                    try {
                        Storage::disk('s3')->get($uploadedPath);
                        break;
                    } catch (\Exception $e) {
                        if ($attempt === $maxRetries - 1) {
                            Log::error('S3 object not available', ['user_id' => $user->id, 'path' => $uploadedPath]);
                            continue 2;
                        }
                        sleep($retryDelay);
                        $attempt++;
                    }
                }

                $metadata = [];
                $tags = $validated['tags'] ? trim($validated['tags'], ',') : '';

                if (in_array($file->getMimeType(), ['image/jpeg', 'image/png']) && function_exists('exif_read_data')) {
                    $exif = @exif_read_data($file->getRealPath());
                    if ($exif) {
                        $metadata['date'] = isset($exif['DateTimeOriginal']) ? Carbon::parse($exif['DateTimeOriginal'])->toDateTimeString() : now();
                        if (isset($exif['GPSLatitude'], $exif['GPSLongitude'])) {
                            $metadata['location'] = "{$exif['GPSLatitude']},{$exif['GPSLongitude']}";
                        } else {
                            $metadata['location'] = $validated['location'] ?? null;
                        }
                    } else {
                        $metadata['date'] = now();
                        $metadata['location'] = $validated['location'] ?? null;
                    }
                } else {
                    $metadata['date'] = now();
                    $metadata['location'] = $validated['location'] ?? null;
                }

                if ($plan->facial_recognition_enabled && in_array($file->getMimeType(), ['image/jpeg', 'image/png'])) {
                    if ($plan->name === 'Free') {
                        $todaySearches = Photo::where('user_id', $user->id)
                            ->whereNotNull('metadata->face_match_similarity')
                            ->whereDate('created_at', Carbon::today())
                            ->count();
                        if ($todaySearches >= 5) {
                            Log::error('Facial recognition limit exceeded', ['user_id' => $user->id]);
                            $tags = $tags ? "$tags,facial_recognition_limit_exceeded" : 'facial_recognition_limit_exceeded';
                            continue;
                        }
                    }

                    try {
                        if (!Storage::disk('s3')->exists($uploadedPath)) {
                            Log::error('Uploaded file not found in S3', ['user_id' => $user->id, 'path' => $uploadedPath]);
                            $tags = $tags ? "$tags,s3_object_missing" : 's3_object_missing';
                            continue;
                        }

                        $selfiePath = $user->reference_selfie_path;
                        if ($selfiePath && Storage::disk('s3')->exists($selfiePath)) {
                            $startTime = microtime(true);
                            $detectResult = $this->rekognition->detectFaces([
                                'Image' => [
                                    'S3Object' => [
                                        'Bucket' => 'bluestarface',
                                        'Name' => $uploadedPath,
                                    ],
                                ],
                                'Attributes' => ['ALL'],
                            ]);

                            $faceDetails = $detectResult->get('FaceDetails');
                            if (empty($faceDetails)) {
                                Log::info('No faces detected', ['user_id' => $user->id, 'photo_path' => $uploadedPath]);
                                $tags = $tags ? "$tags,no_face" : 'no_face';
                            } else {
                                Log::info('Faces detected', ['user_id' => $user->id, 'photo_path' => $uploadedPath, 'count' => count($faceDetails)]);
                                $tags = $tags ? "$tags,face_detected" : 'face_detected';

                                $compareResult = $this->rekognition->compareFaces([
                                    'SourceImage' => [
                                        'S3Object' => [
                                            'Bucket' => 'bluestarface',
                                            'Name' => $selfiePath,
                                        ],
                                    ],
                                    'TargetImage' => [
                                        'S3Object' => [
                                            'Bucket' => 'bluestarface',
                                            'Name' => $uploadedPath,
                                        ],
                                    ],
                                    'SimilarityThreshold' => 70,
                                ]);

                                $faceMatches = $compareResult->get('FaceMatches');
                                if (!empty($faceMatches)) {
                                    $similarity = $faceMatches[0]['Similarity'];
                                    Log::info('Face match found', ['user_id' => $user->id, 'similarity' => $similarity]);
                                    $tags = $tags ? "$tags,face_matched" : 'face_matched';
                                    $metadata['face_match_similarity'] = $similarity;
                                } else {
                                    Log::info('No face match', ['user_id' => $user->id, 'photo_path' => $uploadedPath]);
                                    $tags = $tags ? "$tags,face_detected_no_match" : 'face_detected_no_match';
                                }
                            }
                        } else {
                            Log::warning('No reference selfie available or not found', ['user_id' => $user->id, 'selfie_path' => $selfiePath]);
                            $tags = $tags ? "$tags,no_reference_selfie" : 'no_reference_selfie';
                        }
                    } catch (\Aws\Exception\AwsException $e) {
                        Log::error('Rekognition error', [
                            'user_id' => $user->id,
                            'message' => $e->getMessage(),
                            'code' => $e->getAwsErrorCode(),
                        ]);
                        $tags = $tags ? "$tags,rekognition_error" : 'rekognition_error';
                    }
                }

                $photo = Photo::create([
                    'user_id' => $user->id,
                    'title' => $validated['title'],
                    'description' => $validated['description'],
                    'image_path' => $uploadedPath,
                    'price' => 0,
                    'is_featured' => $validated['is_featured'] ?? false,
                    'license_type' => 'personal',
                    'tags' => $tags,
                    'metadata' => json_encode($metadata),
                    'tour_provider' => $validated['tour_provider'] ?? null,
                    'location' => $metadata['location'] ?? $validated['location'] ?? null,
                    'event' => $validated['folder_name'] ?? null,
                    'date' => $metadata['date'] ?? now(),
                    'file_size' => $file->getSize() / (1024 * 1024),
                ]);

                Log::info('Photo uploaded successfully', [
                    'user_id' => $user->id,
                    'photo_id' => $photo->id,
                    'path' => $uploadedPath,
                ]);

                $photos[] = [
                    'photo' => $photo,
                    'url' => Storage::disk('s3')->url($uploadedPath),
                ];
            }

            if (empty($photos)) {
                return $this->errorResponse('No valid files uploaded.', 422);
            }

            return response()->json([
                'success' => true,
                'photos' => $photos,
                'message' => 'Photos uploaded successfully.',
                'urls' => array_column($photos, 'url'),
            ], 201);
        } catch (ValidationException $e) {
            Log::error('Validation failed', ['errors' => $e->errors(), 'user_id' => Auth::id()]);
            return response()->json(['success' => false, 'errors' => $e->errors(), 'message' => 'Validation failed.'], 422);
        } catch (\Exception $e) {
            Log::error('Upload error', [
                'user_id' => Auth::id(),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['success' => false, 'message' => 'Upload failed: ' . $e->getMessage()], 500);
        }
    }
   
    public function findMatches(Request $request)
{
    try {
        // Validate the uploaded selfie
        $validated = $request->validate([
            'selfie' => 'required|file|mimes:jpg,jpeg,png|max:5120',
        ]);

        $selfie = $request->file('selfie');
        if (!$selfie->isValid()) {
            Log::error('Invalid selfie uploaded', [
                'error' => $selfie->getErrorMessage(),
            ]);
            return redirect()->back()->withErrors(['selfie' => 'Invalid selfie file.'])->withInput();
        }

        // Upload selfie to S3 in 'uploads' folder (temporary)
        $fileName = 'selfie_' . Str::random(40) . '.' . $selfie->getClientOriginalExtension();
        $directory = 'uploads';
        $uploadedPath = Storage::disk('s3')->putFileAs($directory, $selfie, $fileName);

        if (!$uploadedPath) {
            Log::error('S3 selfie upload failed', ['file' => $fileName]);
            return redirect()->back()->withErrors(['selfie' => 'Failed to upload selfie.'])->withInput();
        }

        // Verify S3 object availability
        $maxRetries = 3;
        $retryDelay = 1;
        $attempt = 0;
        while ($attempt < $maxRetries) {
            try {
                Storage::disk('s3')->get($uploadedPath);
                break;
            } catch (\Exception $e) {
                if ($attempt === $maxRetries - 1) {
                    Log::error('S3 selfie object not available', ['path' => $uploadedPath]);
                    Storage::disk('s3')->delete($uploadedPath);
                    return redirect()->back()->withErrors(['selfie' => 'Uploaded selfie not available.'])->withInput();
                }
                sleep($retryDelay);
                $attempt++;
            }
        }

        // Fetch all images from S3 'uploads' folder
        $s3Images = Storage::disk('s3')->files('uploads');
        $matches = [];

        foreach ($s3Images as $imagePath) {
            // Filter for image files (jpg, jpeg, png) only
            if (!preg_match('/\.(jpg|jpeg|png)$/i', $imagePath)) {
                continue;
            }

            // Skip the temporary selfie file to avoid self-match
            if ($imagePath === $uploadedPath) {
                continue;
            }

            try {
                if (!Storage::disk('s3')->exists($imagePath)) {
                    Log::warning('Photo not found in S3', ['path' => $imagePath]);
                    continue;
                }

                // Compare faces using Rekognition with 90% similarity threshold
                $compareResult = $this->rekognition->compareFaces([
                    'SourceImage' => [
                        'S3Object' => [
                            'Bucket' => 'bluestarface',
                            'Name' => $uploadedPath,
                        ],
                    ],
                    'TargetImage' => [
                        'S3Object' => [
                            'Bucket' => 'bluestarface',
                            'Name' => $imagePath,
                        ],
                    ],
                    'SimilarityThreshold' => 90,
                ]);

                $faceMatches = $compareResult->get('FaceMatches');
                if (!empty($faceMatches)) {
                    $similarity = $faceMatches[0]['Similarity'];
                    Log::info('Face match found (≥90% similarity)', [
                        'path' => $imagePath,
                        'similarity' => $similarity,
                    ]);

                    $matches[] = [
                        'title' => basename($imagePath),
                        'url' => Storage::disk('s3')->url($imagePath),
                        'similarity' => $similarity,
                    ];
                }
            } catch (\Aws\Exception\AwsException $e) {
                Log::error('Rekognition error during face comparison', [
                    'path' => $imagePath,
                    'message' => $e->getMessage(),
                    'code' => $e->getAwsErrorCode(),
                ]);
                continue;
            }
        }

        // Clean up temporary selfie
        Storage::disk('s3')->delete($uploadedPath);

        // Store matches in session and redirect to results page
        $request->session()->flash('face_matches', $matches);
        return redirect()->route('photos.searchResults');

    } catch (ValidationException $e) {
        Log::error('Validation failed for face search', [
            'errors' => $e->errors(),
        ]);
        return redirect()->back()->withErrors($e->errors())->withInput();
    } catch (\Exception $e) {
        Log::error('Face search error', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        return redirect()->back()->withErrors(['selfie' => 'Face search failed: ' . $e->getMessage()])->withInput();
    }
}

    protected function errorResponse($message, $status)
    {
        if (request()->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], $status);
        }
        return redirect()->back()->with('error', $message)->withInput();
    }


    public function rename(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'event' => 'required|string|max:255',
            'newName' => 'required|string|max:255|different:event',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $user = Auth::user();
        $event = $request->input('event');
        $newName = $request->input('newName');

        // Check if the album exists for the user
        $photos = Photo::where('user_id', $user->id)
            ->where('folder_name', $event)
            ->exists();

        if (!$photos) {
            return response()->json([
                'success' => false,
                'message' => 'Album not found or you do not have permission.',
            ], 404);
        }

        // Check if the new album name already exists
        $nameExists = Photo::where('user_id', $user->id)
            ->where('folder_name', $newName)
            ->exists();

        if ($nameExists) {
            return response()->json([
                'success' => false,
                'message' => 'An album with this name already exists.',
            ], 422);
        }

        // Update the folder_name for all photos in the album
        Photo::where('user_id', $user->id)
            ->where('folder_name', $event)
            ->update(['folder_name' => $newName]);

        // Update collaborator records if any
        Collaborator::where('user_id', $user->id)
            ->where('album_name', $event)
            ->update(['album_name' => $newName]);

        return response()->json([
            'success' => true,
            'message' => 'Album renamed successfully.',
        ]);
    }

    public function invite(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'event' => 'required|string|max:255',
            'email' => 'required|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $user = Auth::user();
        $event = $request->input('event');
        $email = $request->input('email');

        // Check if the album exists for the user
        $albumExists = Photo::where('user_id', $user->id)
            ->where('folder_name', $event)
            ->exists();

        if (!$albumExists) {
            return response()->json([
                'success' => false,
                'message' => 'Album not found or you do not have permission.',
            ], 404);
        }

        // Check if the email is already invited
        $alreadyInvited = Collaborator::where('album_name', $event)
            ->where('email', $email)
            ->exists();

        if ($alreadyInvited) {
            return response()->json([
                'success' => false,
                'message' => 'This user is already invited to the album.',
            ], 422);
        }

        // Create a collaborator record
        Collaborator::create([
            'user_id' => $user->id,
            'album_name' => $event,
            'email' => $email,
        ]);

        // Send notification (assuming a notification class is set up)
        try {
            $recipient = \App\Models\User::where('email', $email)->first();
            if ($recipient) {
                Notification::send($recipient, new AlbumInvitation($event, $user));
            } else {
                // Optionally send an email to non-registered users
                \Illuminate\Support\Facades\Mail::to($email)->send(
                    new \App\Mail\AlbumInvitationMail($event, $user)
                );
            }
        } catch (\Exception $e) {
            // Log the error but don't fail the invitation
            \Log::error('Failed to send invitation notification: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Invitation sent successfully.',
        ]);
    }

    public function index()
    {
        return response()->json(Photo::all());
    }

    /**
     * Store a newly created photo (API).
     */


    

    public function update(Request $request)
    {
        try {
            $user = Auth::user();
            $validated = $request->validate([
                'image' => 'required|string', // Base64 encoded image
                'photo_id' => 'required|exists:photos,id,user_id,' . $user->id,
            ]);

            $photo = Photo::findOrFail($validated['photo_id']);
            $plan = $user->plan;

            // Check storage limit
            if ($plan->storage_limit > 0) {
                $usedStorage = Photo::where('user_id', $user->id)->sum('file_size');
                // Estimate new file size (base64 to bytes, approximate)
                $newFileSize = strlen(base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $validated['image']))) / (1024 * 1024);
                if (($usedStorage - $photo->file_size + $newFileSize) / 1024 > $plan->storage_limit) {
                    Log::error('Storage limit exceeded on update', ['user_id' => $user->id, 'used' => $usedStorage, 'limit' => $plan->storage_limit]);
                    return response()->json(['success' => false, 'message' => 'Storage limit exceeded.'], 403);
                }
            }

            // Decode base64 image
            $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $validated['image']));
            if (!$imageData) {
                Log::error('Invalid base64 image data', ['user_id' => $user->id, 'photo_id' => $photo->id]);
                return response()->json(['success' => false, 'message' => 'Invalid image data.'], 422);
            }

            // Generate new file path
            $fileName = Str::random(40) . '.jpg';
            $directory = 'uploads';
            $newPath = "Uploads/$fileName";

            // Save to S3
            $success = Storage::disk('s3')->put($newPath, $imageData);
            if (!$success) {
                Log::error('S3 upload failed for edited image', ['user_id' => $user->id, 'path' => $newPath]);
                return response()->json(['success' => false, 'message' => 'Failed to save image to S3.'], 500);
            }

            // Verify S3 object
            $maxRetries = 5;
            $retryDelay = 1;
            $attempt = 0;
            while ($attempt < $maxRetries) {
                try {
                    Storage::disk('s3')->get($newPath);
                    break;
                } catch (\Exception $e) {
                    if ($attempt === $maxRetries - 1) {
                        Log::error('S3 object not available after upload', ['user_id' => $user->id, 'path' => $newPath]);
                        return response()->json(['success' => false, 'message' => 'S3 object not available.'], 500);
                    }
                    sleep($retryDelay);
                    $attempt++;
                }
            }

            // Delete old image from S3
            if (Storage::disk('s3')->exists($photo->image_path)) {
                Storage::disk('s3')->delete($photo->image_path);
            }

            // Update photo record
            $photo->update([
                'image_path' => $newPath,
                'file_size' => strlen($imageData) / (1024 * 1024), // Store in MB
                'updated_at' => now(),
            ]);

            Log::info('Photo updated successfully', [
                'user_id' => $user->id,
                'photo_id' => $photo->id,
                'new_path' => $newPath,
            ]);

            return response()->json([
                'success' => true,
                'url' => Storage::disk('s3')->url($newPath),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Photo update error', [
                'user_id' => Auth::id(),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['success' => false, 'message' => 'Failed to save image: ' . $e->getMessage()], 500);
        }
    }

    public function enhance(Request $request)
    {
        try {
            $user = Auth::user();
            $validated = $request->validate([
                'image' => 'required|string',
                'type' => 'required|in:sharpen,color_correct',
                'photo_id' => 'required|exists:photos,id,user_id,' . $user->id,
            ]);

            $plan = $user->plan;
            if (!$plan->facial_recognition_enabled) {
                Log::error('AI enhancement not allowed', ['user_id' => $user->id, 'plan' => $plan->name]);
                return response()->json(['success' => false, 'message' => 'AI enhancements require a Pro plan.'], 403);
            }

            // Decode base64 image
            $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $validated['image']));
            if (!$imageData) {
                Log::error('Invalid base64 image data for enhancement', ['user_id' => $user->id, 'photo_id' => $validated['photo_id']]);
                return response()->json(['success' => false, 'message' => 'Invalid image data.'], 422);
            }

            // Save temporary file to S3 for Rekognition
            $tempFileName = 'temp/' . Str::random(40) . '.jpg';
            $success = Storage::disk('s3')->put($tempFileName, $imageData);
            if (!$success) {
                Log::error('S3 upload failed for AI enhancement', ['user_id' => $user->id, 'path' => $tempFileName]);
                return response()->json(['success' => false, 'message' => 'Failed to upload image for enhancement.'], 500);
            }

            // Use Intervention Image for local processing as a fallback
            $image = Image::make($imageData);
            if ($validated['type'] === 'sharpen') {
                $image->sharpen(10); // Basic sharpening
            } elseif ($validated['type'] === 'color_correct') {
                $image->brightness(10)->contrast(10); // Basic color correction
            }

            // Save enhanced image to S3
            $enhancedFileName = 'Uploads/enhanced_' . Str::random(40) . '.jpg';
            $success = Storage::disk('s3')->put($enhancedFileName, $image->encode('jpg', 80));
            if (!$success) {
                Log::error('S3 upload failed for enhanced image', ['user_id' => $user->id, 'path' => $enhancedFileName]);
                Storage::disk('s3')->delete($tempFileName);
                return response()->json(['success' => false, 'message' => 'Failed to save enhanced image.'], 500);
            }

            // Clean up temporary file
            Storage::disk('s3')->delete($tempFileName);

            // Note: AWS Rekognition does not directly support sharpening or color correction.
            // For true AI-based enhancements, consider using AWS Lambda with a custom image processing library
            // or a third-party service like AWS SageMaker or a dedicated image processing API.
            // The above uses Intervention Image as a fallback.

            Log::info('Image enhanced successfully', [
                'user_id' => $user->id,
                'photo_id' => $validated['photo_id'],
                'type' => $validated['type'],
                'path' => $enhancedFileName,
            ]);

            return response()->json([
                'success' => true,
                'url' => Storage::disk('s3')->url($enhancedFileName),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Image enhancement error', [
                'user_id' => Auth::id(),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['success' => false, 'message' => 'Failed to enhance image: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified photo (API).
     */
    public function show(Photo $photo)
    {
        return response()->json($photo);
    }


    public function analyzeImage(Request $request)
    {
        try {
            // Validate the incoming request
            $request->validate([
                'image' => 'required|file|image|mimes:jpeg,png|max:15360',
            ]);

            // Check if file was uploaded successfully
            if (!$request->hasFile('image') || !$request->file('image')->isValid()) {
                throw new \Exception('Invalid image file upload');
            }

            $imageFile = $request->file('image');

            // Verify file size before processing
            if ($imageFile->getSize() > 15360 * 1024) { // Convert KB to bytes
                throw new \Exception('Image file size exceeds maximum limit');
            }

            // Read image file
            $imageBytes = @file_get_contents($imageFile->getRealPath());
            if ($imageBytes === false) {
                throw new \Exception('Failed to read image file');
            }

            // Initialize AWS Rekognition client
            $rekognition = new RekognitionClient([
                'version' => 'latest',
                'region' => 'us-west-2',
                'credentials' => [
                    'key' => 'AKIAQBN6HXVALETMTXPG',
                    'secret' =>  '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',
                ],
            ]);

           
            // Perform image analysis
            $result = $rekognition->detectLabels([
                'Image' => ['Bytes' => $imageBytes],
                'MaxLabels' => 10,
                'MinConfidence' => 80,
            ]);

            // Process results
            $tags = collect($result['Labels'])->pluck('Name')->toArray();

            if (empty($tags)) {
                return response()->json([
                    'success' => true,
                    'tags' => '',
                    'message' => 'No labels detected with sufficient confidence'
                ]);
            }

            return response()->json([
                'success' => true,
                'tags' => implode(', ', $tags),
            ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        Log::error('Validation error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Invalid input: ' . $e->getMessage(),
            'errors' => $e->errors()
        ], 422);
    } catch (\Aws\Exception\CredentialsException $e) {
        Log::error('AWS credentials error: ' . $e->getAwsErrorMessage());
        return response()->json([
            'success' => false,
            'message' => 'AWS credentials invalid'
        ], 500);
    } catch (\Aws\Exception\AwsException $e) {
        Log::error('Rekognition error: ' . $e->getAwsErrorMessage());
        return response()->json([
            'success' => false,
            'message' => 'AWS analysis failed: ' . $e->getAwsErrorMessage()
        ], 500);
    } catch (\Exception $e) {
        Log::error('General analysis error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Analysis failed: ' . $e->getMessage()
        ], 500);
    }
}





    public function destroy(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $photo = Photo::where('user_id', $user->id)->findOrFail($id);

            // Delete from AWS S3
            if ($photo->public_id) {
                try {
                    Storage::disk('s3')->delete($photo->public_id);
                } catch (\Exception $e) {
                    Log::error('S3 deletion error', [
                        'user_id' => $user->id,
                        'photo_id' => $photo->id,
                        'message' => $e->getMessage(),
                    ]);
                }
            }

            // Delete from database
            $photo->delete();

            Log::info('Photo deleted successfully', [
                'user_id' => $user->id,
                'photo_id' => $id,
            ]);

            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => 'Photo deleted successfully.'], 200);
            }

            return redirect()->back()->with('success', 'Photo deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Delete error', [
                'user_id' => Auth::id(),
                'photo_id' => $id,
                'message' => $e->getMessage(),
            ]);
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Delete failed: ' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Delete failed: ' . $e->getMessage());
        }
    }
}
