<?php

namespace MDAI;

if (! defined('ABSPATH')) {
    exit;
}

class Report
{
    public static function build_report_data(string $fromDateRaw = '', string $toDateRaw = ''): array
    {
        $range = Analytics::sanitize_date_range($fromDateRaw, $toDateRaw);
        $settings = Settings::get_all();

        $kpis = Analytics::get_kpis($range['from_datetime'], $range['to_datetime']);
        $signatureMetrics = Analytics::get_signature_metrics($range['from_datetime'], $range['to_datetime']);
        $familyBreakdown = Analytics::get_bot_family_breakdown($range['from_datetime'], $range['to_datetime'], 8);
        $topSearchTerms = Analytics::get_top_search_terms($range['from_datetime'], $range['to_datetime'], 10);
        $topPages = Analytics::get_top_pages($range['from_datetime'], $range['to_datetime'], 10);
        $trend = Analytics::get_daily_trend($range['from_datetime'], $range['to_datetime']);
        $suggestions = Suggestions::analyze_recent_posts(15);

        $topIssues = [
            'thin_content' => 0,
            'long_paragraphs' => 0,
            'missing_alt' => 0,
            'low_internal_links' => 0,
            'no_faq' => 0,
        ];

        foreach ($suggestions as $result) {
            foreach ((array) ($result['suggestions'] ?? []) as $issue) {
                $title = strtolower((string) ($issue['title'] ?? ''));
                if (str_contains($title, 'thin')) {
                    $topIssues['thin_content']++;
                } elseif (str_contains($title, 'long paragraph')) {
                    $topIssues['long_paragraphs']++;
                } elseif (str_contains($title, 'alt')) {
                    $topIssues['missing_alt']++;
                } elseif (str_contains($title, 'internal link')) {
                    $topIssues['low_internal_links']++;
                } elseif (str_contains($title, 'faq')) {
                    $topIssues['no_faq']++;
                }
            }
        }

        arsort($topIssues);

        return [
            'generated_at' => gmdate('Y-m-d H:i:s') . ' UTC',
            'site_name' => get_bloginfo('name'),
            'site_url' => home_url('/'),
            'branding' => [
                'brand_name' => (string) ($settings['report_brand_name'] ?? ''),
                'logo_url' => (string) ($settings['report_logo_url'] ?? ''),
                'accent_color' => (string) ($settings['report_accent_color'] ?? '#2271b1'),
            ],
            'range' => $range,
            'kpis' => $kpis,
            'signature_metrics' => $signatureMetrics,
            'family_breakdown' => $familyBreakdown,
            'top_search_terms' => $topSearchTerms,
            'top_pages' => $topPages,
            'trend' => $trend,
            'top_issues' => $topIssues,
        ];
    }
}
