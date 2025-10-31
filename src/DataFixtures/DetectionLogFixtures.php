<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\AntiFraudBundle\Entity\DetectionLog;
use Tourze\AntiFraudBundle\Enum\RiskLevel;

class DetectionLogFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $detectionLog1 = new DetectionLog();
        $detectionLog1->setUserId('user123');
        $detectionLog1->setSessionId('session456');
        $detectionLog1->setIpAddress('192.168.1.100');
        $detectionLog1->setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        $detectionLog1->setAction('login_attempt');
        $detectionLog1->setRiskLevel(RiskLevel::LOW);
        $detectionLog1->setRiskScore(0.2);
        $detectionLog1->setMatchedRules(['rate_limit_check' => 'passed']);
        $detectionLog1->setDetectionDetails(['request_frequency' => 'normal']);
        $detectionLog1->setRequestPath('/login');
        $detectionLog1->setRequestMethod('POST');
        $detectionLog1->setRequestHeaders(['Content-Type' => 'application/json']);
        $detectionLog1->setCountryCode('CN');
        $detectionLog1->setIsProxy(false);
        $detectionLog1->setIsBot(false);
        $detectionLog1->setResponseTime(150);

        $detectionLog2 = new DetectionLog();
        $detectionLog2->setUserId('user456');
        $detectionLog2->setSessionId('session789');
        $detectionLog2->setIpAddress('192.168.1.200');
        $detectionLog2->setUserAgent('curl/7.68.0');
        $detectionLog2->setAction('api_access');
        $detectionLog2->setRiskLevel(RiskLevel::HIGH);
        $detectionLog2->setRiskScore(0.8);
        $detectionLog2->setMatchedRules(['bot_detection' => 'triggered', 'suspicious_user_agent' => 'detected']);
        $detectionLog2->setDetectionDetails(['bot_score' => 0.9]);
        $detectionLog2->setActionTaken('throttle');
        $detectionLog2->setActionDetails(['retry_after' => 60]);
        $detectionLog2->setRequestPath('/api/data');
        $detectionLog2->setRequestMethod('GET');
        $detectionLog2->setRequestHeaders(['User-Agent' => 'curl/7.68.0']);
        $detectionLog2->setCountryCode('US');
        $detectionLog2->setIsProxy(true);
        $detectionLog2->setIsBot(true);
        $detectionLog2->setResponseTime(50);

        $detectionLog3 = new DetectionLog();
        $detectionLog3->setUserId('user789');
        $detectionLog3->setSessionId('session012');
        $detectionLog3->setIpAddress('10.0.0.50');
        $detectionLog3->setUserAgent('Mozilla/5.0 (compatible; bingbot/2.0)');
        $detectionLog3->setAction('content_scraping');
        $detectionLog3->setRiskLevel(RiskLevel::CRITICAL);
        $detectionLog3->setRiskScore(0.95);
        $detectionLog3->setMatchedRules(['bot_detection' => 'triggered', 'scraping_pattern' => 'detected']);
        $detectionLog3->setDetectionDetails(['scraping_score' => 0.95, 'request_pattern' => 'automated']);
        $detectionLog3->setActionTaken('block');
        $detectionLog3->setActionDetails(['reason' => 'bot_detected', 'block_duration' => 3600]);
        $detectionLog3->setRequestPath('/products');
        $detectionLog3->setRequestMethod('GET');
        $detectionLog3->setRequestHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; bingbot/2.0)']);
        $detectionLog3->setCountryCode('GB');
        $detectionLog3->setIsProxy(false);
        $detectionLog3->setIsBot(true);
        $detectionLog3->setResponseTime(25);

        $manager->persist($detectionLog1);
        $manager->persist($detectionLog2);
        $manager->persist($detectionLog3);

        $manager->flush();
    }
}
