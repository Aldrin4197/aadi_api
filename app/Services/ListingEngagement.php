<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;

class ListingEngagement {
    public function saves(string $service, string $id) {
        return $service === 'spaces'
            ? DB::table('favorites')->where('listing_id', $id)
            : DB::table('marketplace_saves')->where('service', $service)->where('listing_id', $id);
    }
    public function reviews(string $service, string $id) {
        return DB::table('marketplace_reviews')->where('service', $service)->where('listing_id', $id);
    }
    public function summary(string $service, string $id, ?string $userId = null): array {
        $counts = $this->reviews($service, $id)->selectRaw('rating, COUNT(*) as total')->groupBy('rating')->pluck('total', 'rating');
        $total = (int) $counts->sum();
        $sum = $counts->reduce(fn ($sum, $count, $rating) => $sum + $rating * $count, 0);
        $reviews = $this->reviews($service, $id)->join('users', 'users.id', '=', 'marketplace_reviews.user_id')
            ->select('marketplace_reviews.id', 'rating', 'body', 'marketplace_reviews.updated_at', 'users.name', 'users.avatar_url')
            ->orderByDesc('marketplace_reviews.id')->paginate(10, ['*'], 'reviewPage')->withQueryString();
        return [
            'saved' => $userId ? $this->saves($service, $id)->where('user_id', $userId)->exists() : false,
            'average' => $total ? round($sum / $total, 1) : null,
            'count' => $total,
            'breakdown' => collect(range(5, 1))->map(fn ($rating) => ['rating'=>$rating, 'count'=>(int) ($counts[$rating] ?? 0)]),
            'reviews' => $reviews,
            'myReview' => $userId ? $this->reviews($service, $id)->where('user_id', $userId)->first(['rating', 'body']) : null,
        ];
    }
}
