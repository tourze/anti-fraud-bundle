<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\AntiFraudBundle\Entity\RiskProfile;
use Tourze\AntiFraudBundle\Enum\RiskLevel;

class RiskProfileFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $userProfile1 = new RiskProfile();
        $userProfile1->setIdentifierType(RiskProfile::TYPE_USER);
        $userProfile1->setIdentifierValue('user123');
        $userProfile1->setRiskLevel(RiskLevel::LOW);
        $userProfile1->setRiskScore(0.25);
        $userProfile1->incrementTotalDetections();
        $userProfile1->incrementLowRiskDetections();
        $userProfile1->setRiskFactors(['login_frequency' => 'normal', 'device_trust' => 'high']);
        $userProfile1->setBehaviorPatterns(['login_times' => ['09:00', '18:00'], 'common_locations' => ['office', 'home']]);
        $userProfile1->setIsWhitelisted(true);
        $userProfile1->setNotes('Regular user with consistent behavior');
        $userProfile1->setMetadata(['account_age' => 365, 'verification_status' => 'verified']);

        $ipProfile1 = new RiskProfile();
        $ipProfile1->setIdentifierType(RiskProfile::TYPE_IP);
        $ipProfile1->setIdentifierValue('192.168.1.100');
        $ipProfile1->setRiskLevel(RiskLevel::MEDIUM);
        $ipProfile1->setRiskScore(0.45);
        $ipProfile1->incrementTotalDetections();
        $ipProfile1->incrementTotalDetections();
        $ipProfile1->incrementMediumRiskDetections();
        $ipProfile1->incrementLowRiskDetections();
        $ipProfile1->setRiskFactors(['geo_location' => 'CN', 'proxy_detected' => false]);
        $ipProfile1->setBehaviorPatterns(['request_frequency' => 'moderate', 'access_hours' => 'business']);
        $ipProfile1->setNotes('Corporate IP address');
        $ipProfile1->setMetadata(['organization' => 'Example Corp', 'ip_type' => 'corporate']);

        $suspiciousProfile = new RiskProfile();
        $suspiciousProfile->setIdentifierType(RiskProfile::TYPE_IP);
        $suspiciousProfile->setIdentifierValue('10.0.0.99');
        $suspiciousProfile->setRiskLevel(RiskLevel::HIGH);
        $suspiciousProfile->setRiskScore(0.85);
        $suspiciousProfile->incrementTotalDetections();
        $suspiciousProfile->incrementTotalDetections();
        $suspiciousProfile->incrementTotalDetections();
        $suspiciousProfile->incrementHighRiskDetections();
        $suspiciousProfile->incrementHighRiskDetections();
        $suspiciousProfile->incrementMediumRiskDetections();
        $suspiciousProfile->incrementBlockedActions();
        $suspiciousProfile->incrementThrottledActions();
        $suspiciousProfile->setRiskFactors(['bot_detected' => true, 'scraping_behavior' => true]);
        $suspiciousProfile->setBehaviorPatterns(['request_pattern' => 'automated', 'user_agent_rotation' => true]);
        $suspiciousProfile->setLastHighRiskAt(new \DateTimeImmutable('-1 hour'));
        $suspiciousProfile->setLastDetectionAt(new \DateTimeImmutable('-30 minutes'));
        $suspiciousProfile->setIsBlacklisted(true);
        $suspiciousProfile->setNotes('Suspicious automated behavior detected');
        $suspiciousProfile->setMetadata(['threat_type' => 'bot', 'first_seen' => '2024-01-01']);

        $deviceProfile = new RiskProfile();
        $deviceProfile->setIdentifierType(RiskProfile::TYPE_DEVICE);
        $deviceProfile->setIdentifierValue('device_fingerprint_abc123');
        $deviceProfile->setRiskLevel(RiskLevel::LOW);
        $deviceProfile->setRiskScore(0.15);
        $deviceProfile->incrementTotalDetections();
        $deviceProfile->incrementLowRiskDetections();
        $deviceProfile->setRiskFactors(['browser' => 'Chrome', 'os' => 'Windows', 'screen_resolution' => '1920x1080']);
        $deviceProfile->setBehaviorPatterns(['timezone' => 'UTC+8', 'language' => 'zh-CN']);
        $deviceProfile->setNotes('Trusted device');
        $deviceProfile->setMetadata(['device_age' => 180, 'last_update' => '2024-06-01']);

        $manager->persist($userProfile1);
        $manager->persist($ipProfile1);
        $manager->persist($suspiciousProfile);
        $manager->persist($deviceProfile);

        $manager->flush();
    }
}
