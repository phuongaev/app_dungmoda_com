<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\MediaList;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MediaController extends Controller
{
    /**
     * Lấy danh sách media_url theo variations_code
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getMediaByVariationsCode(Request $request): JsonResponse
    {
        try {
            // Validate input
            $request->validate([
                'variations_code' => 'required|string'
            ]);

            $variationsCode = $request->input('variations_code');

            // Tìm product theo variations_code
            $product = Product::where('variations_code', $variationsCode)->first();

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found with variations_code: ' . $variationsCode,
                    'data' => []
                ], 404);
            }

            // Lấy tất cả media_list_id liên kết với product này
            $mediaListIds = $product->mediaLists()->pluck('media_lists.media_list_id');

            if ($mediaListIds->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No media found for this product',
                    'data' => []
                ], 200);
            }

            // Lấy source_id từ media_list đầu tiên (giả sử cùng source_id)
            $firstMediaList = MediaList::find($mediaListIds->first());
            $sourceId = $firstMediaList->source_id;

            // Lấy tất cả media_url có cùng source_id
            $mediaUrls = MediaList::where('source_id', $sourceId)
                ->pluck('media_url')
                ->unique()
                ->values()
                ->toArray();

            return response()->json([
                'success' => true,
                'message' => 'Media URLs retrieved successfully',
                'data' => [
                    'variations_code' => $variationsCode,
                    'source_id' => $sourceId,
                    'media_urls' => $mediaUrls,
                    'total_count' => count($mediaUrls)
                ]
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Alternative method: Lấy media theo variations_code (query param)
     * 
     * @param string $variationsCode
     * @return JsonResponse
     */
    public function getMediaByVariationsCodeParam($variationsCode): JsonResponse
    {
        try {
            $product = Product::where('variations_code', $variationsCode)->first();
            
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy hình ảnh cho mã sản phẩm: ' . $variationsCode,
                    'data' => []
                ], 404);
            }
            
            // Lấy media với source info
            $mediaData = MediaList::select([
                'media_lists.id',
                'media_lists.source_id',
                'media_lists.media_url',
                'media_lists.local_url',
                'media_sources.source_key'
            ])
            ->leftJoin('media_sources', 'media_lists.source_id', '=', 'media_sources.id')
            ->whereHas('products', function ($query) use ($variationsCode) {
                $query->where('variations_code', $variationsCode);
            })
            ->distinct()
            ->get()
            ->groupBy('source_id')
            ->map(function ($group) {
                return $group->map(function ($item) {
                    return [
                        'id'         => $item->id,
                        'media_url'  => $item->media_url,
                        'local_url'  => $item->local_url,
                        'source_key' => $item->source_key ?? 'unknown'
                    ];
                })->unique('media_url')->values();
            });
            
            return response()->json([
                'success' => true,
                'message' => 'Media URLs retrieved successfully',
                'data' => [
                    'variations_code' => $variationsCode,
                    'media_by_source' => $mediaData,
                    'total_sources' => $mediaData->count()
                ]
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Method 3: Raw SQL approach for better performance
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getMediaByVariationsCodeSQL(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'variations_code' => 'required|string'
            ]);

            $variationsCode = $request->input('variations_code');

            // Raw SQL query for optimal performance
            $results = \DB::select("
                SELECT DISTINCT ml.media_url, ml.source_id
                FROM media_lists ml
                INNER JOIN media_products mp ON ml.media_list_id = mp.media_list_id
                INNER JOIN products p ON mp.product_id = p.product_id
                WHERE p.variations_code = ?
                ORDER BY ml.source_id, ml.media_url
            ", [$variationsCode]);

            if (empty($results)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No media found for variations_code: ' . $variationsCode,
                    'data' => []
                ], 404);
            }

            // Group by source_id
            $groupedResults = collect($results)->groupBy('source_id')->map(function ($group) {
                return $group->pluck('media_url')->unique()->values();
            });

            return response()->json([
                'success' => true,
                'message' => 'Media URLs retrieved successfully',
                'data' => [
                    'variations_code' => $variationsCode,
                    'media_by_source' => $groupedResults,
                    'total_sources' => $groupedResults->count(),
                    'total_media_urls' => collect($results)->count()
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }


    // Get source_name by media_id
    public function getSourceNameByMediaId($mediaId): JsonResponse
    {
        try {
            $result = MediaList::select([
                'media_sources.source_name as source_name',
                'media_sources.source_key as source_key'
            ])
                ->leftJoin('media_sources', 'media_lists.source_id', '=', 'media_sources.id')
                ->where('media_lists.id', $mediaId)
                ->first();
            
            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Media not found',
                    'data' => []
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Source information retrieved successfully',
                'data' => [
                    'source_name' => $result->source_name ?? 'Unknown',
                    'source_key' => $result->source_key ?? 'Unknown'
                ]
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }
















}