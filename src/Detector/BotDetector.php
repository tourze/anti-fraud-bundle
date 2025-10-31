<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Detector;

use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\AntiFraudBundle\Model\Context;
use Tourze\AntiFraudBundle\Model\DetectionResult;
use Tourze\AntiFraudBundle\Model\RiskAssessment;

class BotDetector extends AbstractDetector
{
    private const BOT_PATTERNS = [
        'bot', 'crawler', 'spider', 'scraper', 'facebook', 'twitter', 'linkedin',
        'whatsapp', 'telegram', 'slack', 'google', 'bing', 'yahoo', 'baidu',
        'yandex', 'duckduck', 'alexa', 'semrush', 'ahrefs', 'mj12', 'dotbot',
    ];

    private const HIGH_RISK_PATTERNS = [
        'python-requests', 'curl', 'wget', 'apache-httpclient', 'java/',
        'scrapy', 'headlesschrome', 'phantomjs', 'node-fetch', 'http_request2',
        'guzzle', 'libwww-perl', 'lwp', 'urllib', 'httpclient',
    ];

    public function __construct(bool $enabled = true)
    {
        parent::__construct('bot-detector', $enabled);
    }

    protected function performDetection(Context $context): DetectionResult
    {
        $userAgent = strtolower($context->getUserAgent());

        $metadata = [
            'user_agent' => $context->getUserAgent(),
            'is_bot' => false,
            'detected_patterns' => [],
        ];

        // Check for empty user agent
        if ('' === trim($userAgent)) {
            $metadata['is_bot'] = true;
            $assessment = new RiskAssessment(
                RiskLevel::HIGH->getScore(),
                RiskLevel::HIGH,
                [],
                ['reasons' => ['Empty or missing user agent detected']]
            );

            return new DetectionResult($assessment, $metadata);
        }

        // Check for high-risk scraping tools
        foreach (self::HIGH_RISK_PATTERNS as $pattern) {
            if (str_contains($userAgent, $pattern)) {
                $metadata['is_bot'] = true;
                $metadata['detected_patterns'][] = $pattern;
                $assessment = new RiskAssessment(
                    RiskLevel::HIGH->getScore(),
                    RiskLevel::HIGH,
                    [],
                    ['reasons' => ["Suspicious automated tool detected: {$pattern}"]]
                );

                return new DetectionResult($assessment, $metadata);
            }
        }

        // Check for general bot patterns
        foreach (self::BOT_PATTERNS as $pattern) {
            if (str_contains($userAgent, $pattern)) {
                $metadata['is_bot'] = true;
                $metadata['detected_patterns'][] = $pattern;
                $assessment = new RiskAssessment(
                    RiskLevel::MEDIUM->getScore(),
                    RiskLevel::MEDIUM,
                    [],
                    ['reasons' => ["Bot user agent detected: {$pattern}"]]
                );

                return new DetectionResult($assessment, $metadata);
            }
        }

        // Normal user agent
        $assessment = new RiskAssessment(
            RiskLevel::LOW->getScore(),
            RiskLevel::LOW,
            [],
            ['reasons' => ['Normal user agent detected']]
        );

        return new DetectionResult($assessment, $metadata);
    }
}
