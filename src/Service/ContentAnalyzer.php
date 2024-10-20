<?php


namespace App\Service;

class ContentAnalyzer
{
    public function cleanText(string $text): string
    {
        $text = strtolower($text);
        $text = preg_replace('/[^\w\s]/', '', $text);
        return $text;
    }

    public function getWordFrequency(string $text, array $banned): array
    {
        $cleanText = $this->cleanText($text);
        $words = explode(' ', $cleanText);
        $wordCount = [];

        foreach ($words as $word) {
            if (!in_array($word, $banned) && !empty($word)) {
                if (isset($wordCount[$word])) {
                    $wordCount[$word]++;
                } else {
                    $wordCount[$word] = 1;
                }
            }
        }

        return $wordCount;
    }

    public function getTop3Words(array $wordCount): array
    {
        arsort($wordCount);
        return array_slice(array_keys($wordCount), 0, 3);
    }

    public function validateContent(string $text, array $banned): bool
    {
        $cleanText = $this->cleanText($text);
        $words = explode(' ', $cleanText);

        foreach ($words as $word) {
            if (in_array($word, $banned)) {
                return false;
            }
        }

        return true;
    }
}
