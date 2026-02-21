<?php

namespace MDAI;

if (! defined('ABSPATH')) {
    exit;
}

class Bot_Detector
{
    public static function detect_family(string $userAgent): string
    {
        $ua = strtolower($userAgent);

        $map = [
            'openai' => ['gptbot', 'chatgpt-user', 'oai-searchbot'],
            'anthropic' => ['claudebot', 'anthropic-ai'],
            'google' => ['google-extended', 'googleother', 'googlebot'],
            'perplexity' => ['perplexitybot'],
            'microsoft' => ['bingbot', 'bingpreview'],
            'commoncrawl' => ['ccbot'],
            'meta' => ['facebookexternalhit', 'meta-externalagent'],
        ];

        foreach ($map as $family => $needles) {
            foreach ($needles as $needle) {
                if (str_contains($ua, $needle)) {
                    return $family;
                }
            }
        }

        return $ua !== '' ? 'unknown' : 'missing';
    }
}
