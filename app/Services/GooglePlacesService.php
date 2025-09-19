<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use App\Models\GmbEntity;

class GooglePlacesService
{
    protected $client;
    protected $apiKey;
    protected $baseUrl = 'https://places.googleapis.com/v1/places';
    
    /**
     * البحث السريع للاقتراحات (للواجهة الأمامية)
     */
    public function quickSearch($query)
    {
        if (strlen($query) < 3) {
            return [];
        }
        
        try {
            $requestBody = [
                'textQuery' => $query,
                'maxResultCount' => 5,
                'languageCode' => 'ar'
            ];
            
            $response = $this->client->post($this->baseUrl . ':searchText', [
                'json' => $requestBody
            ]);
            
            $data = json_decode($response->getBody(), true);
            $places = $data['places'] ?? [];
            
            // تنسيق النتائج للواجهة الأمامية
            return array_map(function($place) {
                return [
                    'name' => $place['displayName']['text'] ?? '',
                    'address' => $place['formattedAddress'] ?? '',
                    'place_id' => $place['id'] ?? '',
                    'rating' => $place['rating'] ?? 0,
                    'types' => $place['types'] ?? []
                ];
            }, array_slice($places, 0, 5));
            
        } catch (\Exception $e) {
            Log::error('Google Places quick search failed', ['error' => $e->getMessage(), 'query' => $query]);
            
            // لا نرجع بيانات وهمية لتجنب فساد التقارير
            // في بيئة التطوير فقط، يمكن إرجاع بيانات تجريبية
            if (app()->environment('local')) {
                return [
                    [
                        'name' => '[تجريبي] ' . $query . ' - مؤسسة تجارية',
                        'address' => '[تجريبي] عنوان تجريبي',
                        'place_id' => 'dev_sample_' . md5($query),
                        'rating' => 4.0,
                        'types' => ['establishment']
                    ]
                ];
            }
            
            // في بيئة الإنتاج، نرجع قائمة فارغة
            return [];
        }
    }
    
    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Goog-Api-Key' => config('services.google.maps_api_key', env('GOOGLE_MAPS_API_KEY')),
                'X-Goog-FieldMask' => 'places.id,places.displayName,places.formattedAddress,places.location,places.rating,places.userRatingCount,places.businessStatus,places.priceLevel,places.websiteUri,places.nationalPhoneNumber,places.regularOpeningHours,places.photos,places.reviews,places.primaryTypeDisplayName,places.types'
            ]
        ]);
        
        $this->apiKey = config('services.google.maps_api_key', env('GOOGLE_MAPS_API_KEY'));
    }
    
    /**
     * البحث عن الأعمال باسم الشركة
     */
    public function searchByBusinessName($businessName, $location = null)
    {
        try {
            $requestBody = [
                'textQuery' => $businessName,
                'maxResultCount' => 20
            ];
            
            if ($location) {
                $requestBody['locationBias'] = [
                    'circle' => [
                        'center' => [
                            'latitude' => $location['lat'],
                            'longitude' => $location['lng']
                        ],
                        'radius' => 50000 // 50km radius
                    ]
                ];
            }
            
            $response = $this->client->post($this->baseUrl . ':searchText', [
                'json' => $requestBody
            ]);
            
            $data = json_decode($response->getBody(), true);
            
            return [
                'success' => true,
                'places' => $data['places'] ?? [],
                'count' => count($data['places'] ?? [])
            ];
            
        } catch (GuzzleException $e) {
            Log::error('Google Places API search error', [
                'business_name' => $businessName,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'فشل في البحث عن الأعمال: ' . $e->getMessage(),
                'places' => []
            ];
        }
    }
    
    /**
     * الحصول على تفاصيل مكان محدد
     */
    public function getPlaceDetails($placeId)
    {
        try {
            $response = $this->client->get($this->baseUrl . '/' . $placeId, [
                'query' => [
                    'fields' => 'id,displayName,formattedAddress,location,rating,userRatingCount,businessStatus,priceLevel,websiteUri,nationalPhoneNumber,regularOpeningHours,photos,reviews,primaryTypeDisplayName,types,editorialSummary'
                ]
            ]);
            
            $data = json_decode($response->getBody(), true);
            
            return [
                'success' => true,
                'place' => $data
            ];
            
        } catch (GuzzleException $e) {
            Log::error('Google Places API details error', [
                'place_id' => $placeId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'فشل في الحصول على تفاصيل المكان: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * البحث عن الأعمال المنافسة القريبة
     */
    public function findNearbyCompetitors($latitude, $longitude, $businessTypes = [], $radius = 5000)
    {
        try {
            $requestBody = [
                'includedTypes' => !empty($businessTypes) ? $businessTypes : ['establishment'],
                'maxResultCount' => 20,
                'locationRestriction' => [
                    'circle' => [
                        'center' => [
                            'latitude' => $latitude,
                            'longitude' => $longitude
                        ],
                        'radius' => $radius
                    ]
                ],
                'rankPreference' => 'POPULARITY'
            ];
            
            $response = $this->client->post($this->baseUrl . ':searchNearby', [
                'json' => $requestBody
            ]);
            
            $data = json_decode($response->getBody(), true);
            
            return [
                'success' => true,
                'competitors' => $data['places'] ?? [],
                'count' => count($data['places'] ?? [])
            ];
            
        } catch (GuzzleException $e) {
            Log::error('Google Places API nearby search error', [
                'lat' => $latitude,
                'lng' => $longitude,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'فشل في البحث عن المنافسين: ' . $e->getMessage(),
                'competitors' => []
            ];
        }
    }
    
    /**
     * تحويل بيانات Google Places إلى تنسيق قاعدة البيانات
     */
    public function formatPlaceForDatabase($placeData)
    {
        return [
            'place_id' => $placeData['id'] ?? null,
            'name' => $placeData['displayName']['text'] ?? '',
            'address' => $placeData['formattedAddress'] ?? '',
            'latitude' => $placeData['location']['latitude'] ?? null,
            'longitude' => $placeData['location']['longitude'] ?? null,
            'phone' => $placeData['nationalPhoneNumber'] ?? null,
            'website' => $placeData['websiteUri'] ?? null,
            'rating' => $placeData['rating'] ?? null,
            'total_reviews' => $placeData['userRatingCount'] ?? 0,
            'business_hours' => $this->formatBusinessHours($placeData['regularOpeningHours'] ?? null),
            'photos' => $this->formatPhotos($placeData['photos'] ?? []),
            'price_level' => $this->formatPriceLevel($placeData['priceLevel'] ?? null),
            'categories' => $this->formatCategories($placeData),
            'types' => $placeData['types'] ?? [],
            'recent_reviews' => $this->formatReviews($placeData['reviews'] ?? []),
            'is_verified' => ($placeData['businessStatus'] ?? '') === 'OPERATIONAL',
            'status' => strtolower($placeData['businessStatus'] ?? 'unknown'),
            'last_updated_from_google' => now()
        ];
    }
    
    /**
     * تنسيق ساعات العمل
     */
    protected function formatBusinessHours($hoursData)
    {
        if (!$hoursData || !isset($hoursData['periods'])) {
            return null;
        }
        
        $formattedHours = [];
        foreach ($hoursData['periods'] as $period) {
            $day = $period['open']['day'] ?? null;
            $openTime = $period['open']['time'] ?? null;
            $closeTime = $period['close']['time'] ?? null;
            
            if ($day !== null) {
                $formattedHours[] = [
                    'day' => $day,
                    'open' => $openTime,
                    'close' => $closeTime
                ];
            }
        }
        
        return $formattedHours;
    }
    
    /**
     * تنسيق الصور
     */
    protected function formatPhotos($photosData)
    {
        $photos = [];
        foreach ($photosData as $photo) {
            if (isset($photo['name'])) {
                $photos[] = [
                    'name' => $photo['name'],
                    'width' => $photo['widthPx'] ?? null,
                    'height' => $photo['heightPx'] ?? null
                ];
            }
        }
        
        return $photos;
    }
    
    /**
     * تنسيق مستوى الأسعار
     */
    protected function formatPriceLevel($priceLevel)
    {
        $levels = [
            'PRICE_LEVEL_FREE' => '$',
            'PRICE_LEVEL_INEXPENSIVE' => '$',
            'PRICE_LEVEL_MODERATE' => '$$',
            'PRICE_LEVEL_EXPENSIVE' => '$$$',
            'PRICE_LEVEL_VERY_EXPENSIVE' => '$$$$'
        ];
        
        return $levels[$priceLevel] ?? null;
    }
    
    /**
     * تنسيق الفئات
     */
    protected function formatCategories($placeData)
    {
        $categories = [];
        
        if (isset($placeData['primaryTypeDisplayName']['text'])) {
            $categories[] = $placeData['primaryTypeDisplayName']['text'];
        }
        
        return $categories;
    }
    
    /**
     * تنسيق المراجعات
     */
    protected function formatReviews($reviewsData)
    {
        $reviews = [];
        foreach ($reviewsData as $review) {
            $reviews[] = [
                'author' => $review['authorAttribution']['displayName'] ?? 'مجهول',
                'rating' => $review['rating'] ?? null,
                'text' => $review['text']['text'] ?? '',
                'time' => $review['publishTime'] ?? null,
                'relative_time' => $review['relativePublishTimeDescription'] ?? null
            ];
        }
        
        return $reviews;
    }
    
    /**
     * حفظ أو تحديث بيانات GMB Entity
     */
    public function saveOrUpdateGmbEntity($placeData)
    {
        $formattedData = $this->formatPlaceForDatabase($placeData);
        
        if (!$formattedData['place_id']) {
            return null;
        }
        
        return GmbEntity::updateOrCreate(
            ['place_id' => $formattedData['place_id']],
            $formattedData
        );
    }
}