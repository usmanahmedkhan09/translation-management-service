<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\Translation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TranslationController extends Controller
{
    /**
     * Display a listing of translations with search functionality.
     */
    public function index(Request $request)
    {
        $query = Translation::with('tags');

        // Search by key
        if ($request->has('key')) {
            $query->where('key', 'like', '%' . $request->key . '%');
        }

        // Search by content/value
        if ($request->has('content')) {
            $query->where('value', 'like', '%' . $request->content . '%');
        }

        // Filter by locale
        if ($request->has('locale')) {
            $query->where('locale', $request->locale);
        }

        // Filter by tags
        if ($request->has('tags')) {
            $tags = is_array($request->tags) ? $request->tags : explode(',', $request->tags);
            $query->whereHas('tags', function ($q) use ($tags) {
                $q->whereIn('name', $tags);
            });
        }

        // Pagination
        $perPage = min($request->get('per_page', 15), 100);
        $translations = $query->paginate($perPage);

        return response()->json($translations);
    }

    /**
     * Store a newly created translation.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string|max:255',
            'value' => 'required|string',
            'locale' => 'required|string|max:10',
            'tags' => 'array',
            'tags.*' => 'string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check for duplicate key-locale combination
        $exists = Translation::where('key', $request->key)
            ->where('locale', $request->locale)
            ->exists();

        if ($exists) {
            return response()->json([
                'error' => 'Translation with this key and locale already exists'
            ], 409);
        }

        DB::beginTransaction();
        try {
            $translation = Translation::create([
                'key' => $request->key,
                'value' => $request->value,
                'locale' => $request->locale,
            ]);

            // Attach tags
            if ($request->has('tags')) {
                $tagIds = [];
                foreach ($request->tags as $tagName) {
                    $tag = Tag::firstOrCreate(['name' => $tagName]);
                    $tagIds[] = $tag->id;
                }
                $translation->tags()->sync($tagIds);
            }

            // Clear relevant caches
            $this->clearExportCache($request->locale);

            DB::commit();

            return response()->json($translation->load('tags'), 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Failed to create translation'], 500);
        }
    }

    /**
     * Display the specified translation.
     */
    public function show(string $id)
    {
        $translation = Translation::with('tags')->find($id);

        if (!$translation) {
            return response()->json(['error' => 'Translation not found'], 404);
        }

        return response()->json($translation);
    }

    /**
     * Update the specified translation.
     */
    public function update(Request $request, string $id)
    {
        $translation = Translation::find($id);

        if (!$translation) {
            return response()->json(['error' => 'Translation not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'key' => 'sometimes|required|string|max:255',
            'value' => 'sometimes|required|string',
            'locale' => 'sometimes|required|string|max:10',
            'tags' => 'array',
            'tags.*' => 'string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check for duplicate key-locale combination if key or locale is being updated
        if ($request->has('key') || $request->has('locale')) {
            $key = $request->get('key', $translation->key);
            $locale = $request->get('locale', $translation->locale);

            $exists = Translation::where('key', $key)
                ->where('locale', $locale)
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'error' => 'Translation with this key and locale already exists'
                ], 409);
            }
        }

        DB::beginTransaction();
        try {
            $oldLocale = $translation->locale;

            $translation->update($request->only(['key', 'value', 'locale']));

            // Update tags if provided
            if ($request->has('tags')) {
                $tagIds = [];
                foreach ($request->tags as $tagName) {
                    $tag = Tag::firstOrCreate(['name' => $tagName]);
                    $tagIds[] = $tag->id;
                }
                $translation->tags()->sync($tagIds);
            }

            // Clear relevant caches
            $this->clearExportCache($oldLocale);
            if ($request->has('locale') && $request->locale !== $oldLocale) {
                $this->clearExportCache($request->locale);
            }

            DB::commit();

            return response()->json($translation->load('tags'));
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Failed to update translation'], 500);
        }
    }

    /**
     * Remove the specified translation.
     */
    public function destroy(string $id)
    {
        $translation = Translation::find($id);

        if (!$translation) {
            return response()->json(['error' => 'Translation not found'], 404);
        }

        $locale = $translation->locale;
        $translation->delete();

        // Clear relevant caches
        $this->clearExportCache($locale);

        return response()->json(['message' => 'Translation deleted successfully']);
    }

    /**
     * Export translations as JSON for frontend applications.
     */
    public function export(Request $request)
    {
        $locale = $request->get('locale', 'en');
        $tags = $request->get('tags');

        // Create cache key
        $cacheKey = 'translations_export_' . $locale;
        if ($tags) {
            $tagsArray = is_array($tags) ? $tags : explode(',', $tags);
            sort($tagsArray);
            $cacheKey .= '_tags_' . implode('_', $tagsArray);
        }

        return Cache::remember($cacheKey, 300, function () use ($locale, $tags) {
            $query = Translation::where('locale', $locale);

            if ($tags) {
                $tagsArray = is_array($tags) ? $tags : explode(',', $tags);
                $query->whereHas('tags', function ($q) use ($tagsArray) {
                    $q->whereIn('name', $tagsArray);
                });
            }

            $translations = $query->pluck('value', 'key');

            return response()->json([
                'locale' => $locale,
                'translations' => $translations,
                'count' => $translations->count(),
                'generated_at' => now()->toISOString(),
            ]);
        });
    }

    /**
     * Get available locales.
     */
    public function locales()
    {
        $locales = Cache::remember('available_locales', 3600, function () {
            return Translation::select('locale')
                ->distinct()
                ->orderBy('locale')
                ->pluck('locale');
        });

        return response()->json(['locales' => $locales]);
    }

    /**
     * Get available tags.
     */
    public function tags()
    {
        $tags = Cache::remember('available_tags', 3600, function () {
            return Tag::orderBy('name')->pluck('name');
        });

        return response()->json(['tags' => $tags]);
    }

    /**
     * Clear export cache for a specific locale.
     */
    private function clearExportCache(string $locale)
    {
        // Clear specific cache patterns
        $cacheKeys = [
            'translations_export_' . $locale,
            'available_locales',
            'available_tags'
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }

        // Try to clear pattern-based cache keys if using Redis
        try {
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $pattern = 'translations_export_' . $locale . '*';
                $keys = Cache::getRedis()->keys($pattern);
                
                if (!empty($keys)) {
                    foreach ($keys as $key) {
                        Cache::forget(str_replace(config('cache.prefix') . ':', '', $key));
                    }
                }
            }
        } catch (\Exception $e) {
            // Fallback: just clear the main cache keys
        }
    }
}
