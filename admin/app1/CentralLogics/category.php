<?php

namespace App\CentralLogics;

use App\Models\Item;
use App\Models\Store;
use App\Models\Category;
use App\Models\PriorityList;
use App\Models\BusinessSetting;
use Illuminate\Support\Facades\DB;

class CategoryLogic
{
    public static function parents()
    {
        return Category::where('position', 0)->get();
    }

    public static function child($parent_id)
    {
        return Category::where(['parent_id' => $parent_id])->get();
    }

    public static function products($category_id, $zone_id, int $limit, int $offset, $type)
    {
        $category_sub_category_item_default_status = BusinessSetting::where('key', 'category_sub_category_item_default_status')->first()?->value ?? 1;
        $category_sub_category_item_sort_by_general = PriorityList::where('name', 'category_sub_category_item_sort_by_general')->where('type', 'general')->first()?->value ?? '';
        $category_sub_category_item_sort_by_unavailable = PriorityList::where('name', 'category_sub_category_item_sort_by_unavailable')->where('type', 'unavailable')->first()?->value ?? '';
        $category_sub_category_item_sort_by_temp_closed = PriorityList::where('name', 'category_sub_category_item_sort_by_temp_closed')->where('type', 'temp_closed')->first()?->value ?? '';
    
        // استعلام المنتجات الرئيسي
        $query = Item::whereHas('module.zones', function ($query) use ($zone_id) {
            $query->whereIn('zones.id', json_decode($zone_id, true));
        })
        ->whereHas('store', function ($query) use ($zone_id) {
            $query->whereIn('zone_id', json_decode($zone_id, true))
                  ->whereHas('zone.modules', function ($query) {
                      $query->when(config('module.current_module_data'), function ($query) {
                          $query->where('modules.id', config('module.current_module_data')['id']);
                      });
                  });
        });
    
        // إذا كان category_id = 0، يتم تجاوز القيود المتعلقة بالفئات
        if ($category_id != 0) {
            $query = $query->whereHas('category', function ($q) use ($category_id) {
                return $q->when(is_numeric($category_id), function ($query) use ($category_id) {
                    return $query->whereId($category_id)->orWhere('parent_id', $category_id);
                })
                ->when(!is_numeric($category_id), function ($query) use ($category_id) {
                    $query->where('slug', $category_id);
                });
            });
        }
    
        $query = $query->select(['items.*'])
                       ->selectSub(function ($subQuery) {
                           $subQuery->selectRaw('active as temp_available')
                                    ->from('stores')
                                    ->whereColumn('stores.id', 'items.store_id');
                       }, 'temp_available')
                       ->active()->type($type);
    
        if ($category_sub_category_item_default_status == '1') {
            $query = $query->latest();
        } else {
            if (config('module.current_module_data')['module_type'] !== 'food') {
                if ($category_sub_category_item_sort_by_unavailable == 'remove') {
                    $query = $query->where('stock', '>', 0);
                } elseif ($category_sub_category_item_sort_by_unavailable == 'last') {
                    $query = $query->orderByRaw('CASE WHEN stock = 0 THEN 1 ELSE 0 END');
                }
            }
    
            if ($category_sub_category_item_sort_by_temp_closed == 'remove') {
                $query = $query->having('temp_available', '>', 0);
            } elseif ($category_sub_category_item_sort_by_temp_closed == 'last') {
                $query = $query->orderByDesc('temp_available');
            }
    
            if ($category_sub_category_item_sort_by_general == 'rating') {
                $query = $query->orderByDesc('avg_rating');
            } elseif ($category_sub_category_item_sort_by_general == 'review_count') {
                $query = $query->withCount('reviews')->orderByDesc('reviews_count');
            } elseif ($category_sub_category_item_sort_by_general == 'a_to_z') {
                $query = $query->orderBy('name');
            } elseif ($category_sub_category_item_sort_by_general == 'z_to_a') {
                $query = $query->orderByDesc('name');
            } elseif ($category_sub_category_item_sort_by_general == 'order_count') {
                $query = $query->orderByDesc('order_count');
            }
        }
    
        // Pagination
        $paginator = $query->paginate($limit, ['*'], 'page', $offset);
    
        return [
            'total_size' => $paginator->total(),
            'limit' => $limit,
            'offset' => $offset,
            'products' => $paginator->items()
        ];
    }
    


    public static function category_stores($category_ids, $zone_id, int $limit,int $offset, $type,$longitude=0,$latitude=0,$filter=null,$rating_count=null)
    {
        $category_ids = isset($category_ids)?(is_array($category_ids)?$category_ids:json_decode($category_ids)):[];
        $paginator = Store::
        WithOpenWithDeliveryTime($longitude??0,$latitude??0)
            ->withCount(['items','campaigns'])
            ->whereHas('items.category',function($q)use($category_ids){
                return $q->whereIn('id',$category_ids)->orWhereIn('parent_id', $category_ids);
            })
            ->when(config('module.current_module_data'), function($query)use($zone_id){
                return  $query->whereHas('zone.modules', function($query){
                    return $query->where('modules.id', config('module.current_module_data')['id']);
                })->module(config('module.current_module_data')['id']);
                if(!config('module.current_module_data')['all_zone_service']) {
                    return  $query->whereIn('zone_id', json_decode($zone_id, true));
                }
            })
            ->active()->type($type)

            ->when($filter && in_array('free_delivery',$filter),function ($qurey){
                return $qurey->where('free_delivery',1);
            })
            ->when($filter && in_array('coupon',$filter),function ($qurey){
                return $qurey->has('activeCoupons');
            })

            ->when($rating_count, function($query) use ($rating_count){
                return $query->selectSub(function ($query) use ($rating_count){
                    return  $query->selectRaw('AVG(reviews.rating)')
                        ->from('reviews')
                        ->join('items', 'items.id', '=', 'reviews.item_id')
                        ->whereColumn('items.store_id', 'stores.id')
                        ->groupBy('items.store_id')
                        ->havingRaw('AVG(reviews.rating) >= ?', [$rating_count]);
                }, 'avg_r')->having('avg_r', '>=', $rating_count);
            })
            ->when($filter && in_array('top_rated',$filter),function ($qurey){
                return $qurey->whereNotNull('rating')->whereRaw("LENGTH(rating) > 0");
            })
            ->when($filter && in_array('discounted',$filter),function ($qurey){
                return  $qurey->where(function ($query) {
                    return  $query->whereHas('items', function ($q) {
                        return $q->Discounted();
                    });
                });
            })
            ->when($filter && in_array('currently_open',$filter),function ($qurey){
                return $qurey->having('open', '>', 0);
            })
            ->orderBy('open', 'desc')
            ->when($filter && in_array('popular',$filter),function ($qurey){
                return $qurey->withCount('orders')->orderBy('orders_count', 'desc');
            })
            ->when(($filter && in_array('nearby',$filter)) ,function ($qurey){
                return $qurey->orderBy('distance');
            })
            ->when($filter && in_array('fast_delivery',$filter),function ($qurey){
                return $qurey->orderBy('min_delivery_time');
            })
            ->latest()
            ->paginate($limit, ['*'], 'page', $offset);


        $paginator->each(function ($store) {
            $category_ids = DB::table('items')
                ->join('categories', 'items.category_id', '=', 'categories.id')
                ->selectRaw('
                CAST(categories.id AS UNSIGNED) as id,
                categories.parent_id
            ')
                ->where('items.store_id', $store->id)
                ->where('categories.status', 1)
                ->groupBy('id', 'categories.parent_id')
                ->get();

            $data = json_decode($category_ids, true);

            $mergedIds = [];

            foreach ($data as $item) {
                if ($item['id'] != 0) {
                    $mergedIds[] = $item['id'];
                }
                if ($item['parent_id'] != 0) {
                    $mergedIds[] = $item['parent_id'];
                }
            }

            $category_ids = array_values(array_unique($mergedIds));

            $store->category_ids = $category_ids;
            $store->discount_status = !empty($store->items->where('discount', '>', 0));
            unset($store['items']);
        });

        return [
            'total_size' => $paginator->total(),
            'limit' => $limit,
            'offset' => $offset,
            'stores' => $paginator->items()
        ];
    }


    public static function stores($category_id, $zone_id, int $limit,int $offset, $type,$longitude=0,$latitude=0)
    {
        $paginator = Store::
        withOpen($longitude??0,$latitude??0)
            ->withCount(['items','campaigns'])
            ->whereHas('items.category',function($q)use($category_id){
                return $q->when(is_numeric($category_id),function ($qurey) use($category_id){
                    return $qurey->whereId($category_id)->orWhere('parent_id', $category_id);
                })
                    ->when(!is_numeric($category_id),function ($qurey) use($category_id){
                        $qurey->where('slug', $category_id);
                    });
            })
            ->when(config('module.current_module_data'), function($query)use($zone_id){
                $query->whereHas('zone.modules', function($query){
                    $query->where('modules.id', config('module.current_module_data')['id']);
                })->module(config('module.current_module_data')['id']);
                if(!config('module.current_module_data')['all_zone_service']) {
                    $query->whereIn('zone_id', json_decode($zone_id, true));
                }
            })
            ->active()->type($type)
            ->latest()->paginate($limit, ['*'], 'page', $offset);


        $paginator->each(function ($store) {
            $category_ids = DB::table('items')
                ->join('categories', 'items.category_id', '=', 'categories.id')
                ->selectRaw('
                CAST(categories.id AS UNSIGNED) as id,
                categories.parent_id
            ')
                ->where('items.store_id', $store->id)
                ->where('categories.status', 1)
                ->groupBy('id', 'categories.parent_id')
                ->get();

            $data = json_decode($category_ids, true);

            $mergedIds = [];

            foreach ($data as $item) {
                if ($item['id'] != 0) {
                    $mergedIds[] = $item['id'];
                }
                if ($item['parent_id'] != 0) {
                    $mergedIds[] = $item['parent_id'];
                }
            }

            $category_ids = array_values(array_unique($mergedIds));

            $store->category_ids = $category_ids;
            $store->discount_status = !empty($store->items->where('discount', '>', 0));
            unset($store['items']);
        });

        return [
            'total_size' => $paginator->total(),
            'limit' => $limit,
            'offset' => $offset,
            'stores' => $paginator->items()
        ];
    }


    public static function all_products($id, $zone_id)
    {
        $cate_ids=[];
        array_push($cate_ids,(int)$id);
        foreach (CategoryLogic::child($id) as $ch1){
            array_push($cate_ids,$ch1['id']);
            foreach (CategoryLogic::child($ch1['id']) as $ch2){
                array_push($cate_ids,$ch2['id']);
            }
        }

        return Item::whereIn('category_id', $cate_ids)
            ->whereHas('module.zones', function($query)use($zone_id){
                $query->whereIn('zones.id', json_decode($zone_id, true));
            })
            ->whereHas('store', function($query)use($zone_id){
                $query->whereIn('zone_id', json_decode($zone_id, true))->whereHas('zone.modules',function($query){
                    $query->when(config('module.current_module_data'), function($query){
                        $query->where('modules.id', config('module.current_module_data')['id']);
                    });
                });
            })
            ->get();
    }


    public static function featured_category_products($zone_id, int $limit,int $offset, $type)
    {
        $paginator = Item::active()->type($type)
            ->whereHas('module.zones', function($query)use($zone_id){
                $query->whereIn('zones.id', json_decode($zone_id, true));
            })
            ->whereHas('store', function($query)use($zone_id){
                $query->whereIn('zone_id', json_decode($zone_id, true))->whereHas('zone.modules',function($query){
                    $query->when(config('module.current_module_data'), function($query){
                        $query->where('modules.id', config('module.current_module_data')['id']);
                    });
                });
            })
            ->where(function($query){
                $query->whereHas('category',function($q){
                    return $q->where(['featured' => 1 , 'status' => 1 , 'module_id' => config('module.current_module_data')['id']]);
                })
                ->orwherehas('category.parent',function($query){
                    $query->where(['featured' => 1 , 'status' => 1 , 'module_id' => config('module.current_module_data')['id']]);
                });
            })

            ->latest()->paginate($limit, ['*'], 'page', $offset);

        $item_categories = Item::active()->type($type)
            ->whereHas('module.zones', function($query)use($zone_id){
                $query->whereIn('zones.id', json_decode($zone_id, true));
            })
            ->whereHas('store', function($query)use($zone_id){
                $query->whereIn('zone_id', json_decode($zone_id, true))->whereHas('zone.modules',function($query){
                    $query->when(config('module.current_module_data'), function($query){
                        $query->where('modules.id', config('module.current_module_data')['id']);
                    });
                });
            })
            ->where(function($query){
                $query->whereHas('category',function($q){
                    return $q->where(['featured' => 1 , 'status' => 1 , 'module_id' => config('module.current_module_data')['id']]);
                })
                ->orwherehas('category.parent',function($query){
                    $query->where(['featured' => 1 , 'status' => 1 , 'module_id' => config('module.current_module_data')['id']]);
                });
            })


            ->pluck('category_id')->toArray();

        $item_categories = array_unique($item_categories);

        $categories = Category::where(['status' => 1])->whereIn('id',$item_categories)->get(['id','name','image']);

        return [
            'total_size' => $paginator->total(),
            'limit' => $limit,
            'offset' => $offset,
            'categories' => $categories,
            'products' => $paginator->items()
        ];
    }
}
