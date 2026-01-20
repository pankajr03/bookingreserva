<?php

namespace BookneticApp\Backend\Mobile\Mappers;

class PlanMapper
{
    public static function map(array $data): array
    {
        $plans = [];
        foreach ($data as $plan) {
            $plans[] = [
                'id' => $plan['id'] ?? null,
                'name' => $plan['name'] ?? null,
                'description' => $plan['description'] ?? null,
                'slug' => $plan['slug'] ?? null,
                'badge_text' => $plan['badge_text'] ?? null,
                'price' => $plan['price'] ?? null,
                'currency' => $plan['currency'] ?? null,
                'discount_price' => $plan['discount_price'] ?? null,
                'extra_seat_price' => $plan['extra_seat_price'] ?? null,
                'extra_seat_limit' => $plan['extra_seat_limit'] ?? null,
                'seat_count' => $plan['seat_count'] ?? null,
                'features' => $plan['features'] ?? [],
                'order_by' => $plan['order_by'] ?? null,
            ];
        }

        return $plans;
    }
}
