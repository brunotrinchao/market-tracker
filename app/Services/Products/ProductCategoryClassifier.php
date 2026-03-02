<?php

namespace App\Services\Products;

use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProductCategoryClassifier
{
    public function inferCategoryId(?string $productName): ?int
    {
        $normalizedName = $this->normalizeText($productName);

        if ($normalizedName === '') {
            return null;
        }

        $bestMatch = $this->categories()
            ->map(function (Category $category) use ($normalizedName): array {
                $score = $this->scoreCategory($normalizedName, $category);

                return [
                    'id' => (int) $category->id,
                    'score' => $score,
                ];
            })
            ->sortByDesc('score')
            ->first();

        if (! is_array($bestMatch) || ($bestMatch['score'] ?? 0) <= 0) {
            return null;
        }

        return (int) $bestMatch['id'];
    }

    private function categories(): Collection
    {
        return Category::query()
            ->select(['id', 'name', 'keywords'])
            ->get();
    }

    private function scoreCategory(string $normalizedName, Category $category): int
    {
        $score = 0;
        $categoryName = $this->normalizeText((string) $category->name);

        if ($categoryName !== '' && str_contains($normalizedName, $categoryName)) {
            $score += 2;
        }

        $keywords = array_filter(
            array_map(
                fn ($keyword): string => $this->normalizeText((string) $keyword),
                (array) ($category->keywords ?? [])
            ),
            fn (string $keyword): bool => $keyword !== ''
        );

        foreach ($keywords as $keyword) {
            if ($this->containsKeyword($normalizedName, $keyword)) {
                $score += max(1, strlen($keyword));
            }
        }

        return $score;
    }

    private function containsKeyword(string $normalizedName, string $keyword): bool
    {
        if ($keyword === '') {
            return false;
        }

        if (str_contains($keyword, ' ')) {
            return str_contains($normalizedName, $keyword);
        }

        return preg_match('/\b' . preg_quote($keyword, '/') . '\b/u', $normalizedName) === 1;
    }

    private function normalizeText(?string $value): string
    {
        $value = Str::ascii((string) $value);
        $value = mb_strtolower($value);
        $value = preg_replace('/[^a-z0-9\s]/', ' ', $value) ?? '';

        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }
}

