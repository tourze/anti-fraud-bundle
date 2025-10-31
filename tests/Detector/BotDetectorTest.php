<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Tests\Detector;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Tourze\AntiFraudBundle\Detector\BotDetector;
use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\AntiFraudBundle\Model\Context;
use Tourze\AntiFraudBundle\Model\UserBehavior;

/**
 * @internal
 */
#[CoversClass(BotDetector::class)]
final class BotDetectorTest extends TestCase
{
    private BotDetector $detector;

    protected function setUp(): void
    {
        $this->detector = new BotDetector();
    }

    public function testDetectorName(): void
    {
        $this->assertSame('bot-detector', $this->detector->getName());
    }

    public function testDetectorIsEnabledByDefault(): void
    {
        $this->assertTrue($this->detector->isEnabled());
    }

    public function testDetectLowRiskForNormalUserAgent(): void
    {
        $context = $this->createContext('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

        $result = $this->detector->detect($context);

        $this->assertNotNull($result->getRiskAssessment());
        $this->assertSame(RiskLevel::LOW, $result->getRiskAssessment()->getRiskLevel());
        $this->assertStringContainsString('Normal user agent', $result->getRiskAssessment()->getReason());
        $this->assertFalse($result->getMetadata()['is_bot']);
        $metadata = $result->getMetadata();
        $this->assertIsString($metadata['user_agent']);
        $this->assertStringContainsString('Mozilla', $metadata['user_agent']);
    }

    public function testDetectMediumRiskForBotUserAgent(): void
    {
        $context = $this->createContext('Googlebot/2.1 (+http://www.google.com/bot.html)');

        $result = $this->detector->detect($context);

        $this->assertNotNull($result->getRiskAssessment());
        $this->assertSame(RiskLevel::MEDIUM, $result->getRiskAssessment()->getRiskLevel());
        $this->assertStringContainsString('Bot user agent detected', $result->getRiskAssessment()->getReason());
        $this->assertTrue($result->getMetadata()['is_bot']);
    }

    public function testDetectHighRiskForSuspiciousBot(): void
    {
        $context = $this->createContext('python-requests/2.28.1');

        $result = $this->detector->detect($context);

        $this->assertNotNull($result->getRiskAssessment());
        $this->assertSame(RiskLevel::HIGH, $result->getRiskAssessment()->getRiskLevel());
        $this->assertStringContainsString('Suspicious automated tool detected', $result->getRiskAssessment()->getReason());
        $this->assertTrue($result->getMetadata()['is_bot']);
    }

    public function testDetectVariousBotPatterns(): void
    {
        $botUserAgents = [
            'Googlebot/2.1 (+http://www.google.com/bot.html)',
            'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)',
            'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
            'Twitterbot/1.0',
            'LinkedInBot/1.0 (compatible; Mozilla/5.0; Apache-HttpClient +http://www.linkedin.com/)',
            'Slackbot-LinkExpanding 1.0 (+https://api.slack.com/robots)',
            'WhatsApp/2.19.81',
            'TelegramBot (like TwitterBot)',
            'ia_archiver (+http://www.alexa.com/site/help/webmasters; crawler@alexa.com)',
            'SemrushBot/7~bl',
            'AhrefsBot/7.0',
            'MJ12bot/v1.4.8',
            'DotBot/1.1',
        ];

        foreach ($botUserAgents as $userAgent) {
            $context = $this->createContext($userAgent);
            $result = $this->detector->detect($context);

            $this->assertNotNull($result->getRiskAssessment());
            $this->assertTrue(
                RiskLevel::MEDIUM === $result->getRiskAssessment()->getRiskLevel()
                || RiskLevel::HIGH === $result->getRiskAssessment()->getRiskLevel(),
                "Failed to detect bot for user agent: {$userAgent}"
            );
            $this->assertTrue($result->getMetadata()['is_bot']);
        }
    }

    public function testDetectScrapingTools(): void
    {
        $scrapingUserAgents = [
            'python-requests/2.28.1',
            'curl/7.68.0',
            'wget/1.20.3',
            'Apache-HttpClient/4.5.13',
            'Java/1.8.0_291',
            'Scrapy/2.5.1',
            'HeadlessChrome/91.0.4472.77',
            'PhantomJS/2.1.1',
            'node-fetch/1.0',
        ];

        foreach ($scrapingUserAgents as $userAgent) {
            $context = $this->createContext($userAgent);
            $result = $this->detector->detect($context);

            $this->assertNotNull($result->getRiskAssessment());
            $this->assertSame(RiskLevel::HIGH, $result->getRiskAssessment()->getRiskLevel(),
                "Failed to detect scraping tool for user agent: {$userAgent}");
            $this->assertTrue($result->getMetadata()['is_bot']);
        }
    }

    public function testDetectEmptyUserAgent(): void
    {
        $context = $this->createContext('');

        $result = $this->detector->detect($context);

        $this->assertNotNull($result->getRiskAssessment());
        $this->assertSame(RiskLevel::HIGH, $result->getRiskAssessment()->getRiskLevel());
        $this->assertStringContainsString('Empty or missing user agent', $result->getRiskAssessment()->getReason());
        $this->assertTrue($result->getMetadata()['is_bot']);
    }

    public function testDetectNormalBrowsers(): void
    {
        $normalUserAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Safari/605.1.15',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36 Edg/91.0.864.59',
        ];

        foreach ($normalUserAgents as $userAgent) {
            $context = $this->createContext($userAgent);
            $result = $this->detector->detect($context);

            $this->assertNotNull($result->getRiskAssessment());
            $this->assertSame(RiskLevel::LOW, $result->getRiskAssessment()->getRiskLevel(),
                "Incorrectly flagged normal browser: {$userAgent}");
            $this->assertFalse($result->getMetadata()['is_bot']);
        }
    }

    public function testDetectorCanBeDisabled(): void
    {
        $detector = new BotDetector(false);
        $context = $this->createContext('Googlebot/2.1');

        $result = $detector->detect($context);

        $this->assertNotNull($result->getRiskAssessment());
        $this->assertSame(RiskLevel::LOW, $result->getRiskAssessment()->getRiskLevel());
        $this->assertSame('Detector disabled', $result->getRiskAssessment()->getReason());
    }

    private function createContext(string $userAgent): Context
    {
        $behavior = new UserBehavior(
            userId: 'test-user',
            sessionId: 'test-session',
            ip: '192.168.1.1',
            userAgent: $userAgent,
            action: 'test'
        );

        $request = Request::create('/test');

        return new Context($behavior, $request);
    }
}
