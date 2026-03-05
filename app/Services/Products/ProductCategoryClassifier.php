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

    public function inferCategoryIdFromSuggestion(?string $categorySuggestion): ?int
    {
        $normalizedSuggestion = $this->normalizeText($categorySuggestion);

        if ($normalizedSuggestion === '') {
            return null;
        }

        $match = $this->categories()
            ->map(function (Category $category): array {
                return [
                    'id' => (int) $category->id,
                    'name' => $this->normalizeText((string) $category->name),
                    'slug' => $this->normalizeText((string) Str::slug((string) $category->name)),
                ];
            })
            ->first(function (array $candidate) use ($normalizedSuggestion): bool {
                if (($candidate['name'] ?? '') === $normalizedSuggestion || ($candidate['slug'] ?? '') === $normalizedSuggestion) {
                    return true;
                }

                return str_contains($normalizedSuggestion, (string) ($candidate['name'] ?? ''))
                    || str_contains((string) ($candidate['name'] ?? ''), $normalizedSuggestion);
            });

        return is_array($match) ? (int) ($match['id'] ?? 0) ?: null : null;
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
