<?php

use MDAI\Bot_Detector;
use PHPUnit\Framework\TestCase;

final class BotDetectorTest extends TestCase
{
    public function testDetectsKnownBotFamilies(): void
    {
        $this->assertSame('openai', Bot_Detector::detect_family('Mozilla/5.0 GPTBot/1.2'));
        $this->assertSame('anthropic', Bot_Detector::detect_family('ClaudeBot/2.0'));
        $this->assertSame('google', Bot_Detector::detect_family('Google-Extended'));
        $this->assertSame('perplexity', Bot_Detector::detect_family('PerplexityBot'));
    }

    public function testReturnsUnknownForUnmappedUserAgent(): void
    {
        $this->assertSame('unknown', Bot_Detector::detect_family('SomeCustomCrawler/1.0'));
    }

    public function testReturnsMissingWhenUserAgentBlank(): void
    {
        $this->assertSame('missing', Bot_Detector::detect_family(''));
    }
}
