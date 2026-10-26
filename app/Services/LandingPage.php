<?php

namespace App\Services;

class LandingPage
{
    public function __construct(private Marketplace $market) {}

    public function content(): array
    {
        $featured = [];
        foreach (array_keys(Marketplace::TYPES) as $service) {
            $query = $this->market->model($service)::query()
                ->with($service === 'raketeer' ? 'provider' : 'owner')
                ->where('status', $service === 'spaces' ? 'active' : 'available');
            if ($service === 'spaces') $query->where('category', 'space_rental');
            foreach ($query->latest()->orderByDesc('id')->limit(2)->get() as $record) {
                $featured[] = $this->market->present($record, $service);
            }
        }

        $reviews = collect(config('aadi_landing.reviews', []))
            ->filter(fn ($review) => is_array($review)
                && ($review['published'] ?? false) === true
                && is_string($review['quote'] ?? null) && trim($review['quote']) !== ''
                && is_string($review['name'] ?? null) && trim($review['name']) !== ''
                && $this->httpsUrl($review['source_url'] ?? null) !== null)
            ->take(3)->map(fn ($review) => [
                'quote' => $review['quote'], 'name' => $review['name'],
                'context' => is_string($review['context'] ?? null) ? $review['context'] : '',
                'sourceUrl' => $review['source_url'],
            ])->values()->all();

        return [
            'featured' => $featured, 'reviews' => $reviews,
            'downloads' => [
                'android' => $this->httpsUrl(config('aadi_landing.google_play_url'), 'play.google.com'),
                'ios' => $this->httpsUrl(config('aadi_landing.app_store_url'), 'apps.apple.com'),
            ],
        ];
    }

    private function httpsUrl(mixed $url, ?string $host = null): ?string
    {
        if (!is_string($url) || !filter_var($url, FILTER_VALIDATE_URL)) return null;
        $parts = parse_url($url);
        if (($parts['scheme'] ?? '') !== 'https' || isset($parts['user']) || isset($parts['pass'])) return null;
        if ($host !== null && strtolower($parts['host'] ?? '') !== $host) return null;
        return $url;
    }
}
