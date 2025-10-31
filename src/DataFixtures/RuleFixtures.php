<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\AntiFraudBundle\Entity\Rule;
use Tourze\AntiFraudBundle\Enum\RiskLevel;

class RuleFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $botDetectionRule = new Rule();
        $botDetectionRule->setName('bot_user_agent_detection');
        $botDetectionRule->setCondition('user_agent contains "bot" or user_agent contains "crawler" or user_agent contains "spider"');
        $botDetectionRule->setRiskLevel(RiskLevel::HIGH);
        $botDetectionRule->setActions(['throttle' => ['retry_after' => 60]]);
        $botDetectionRule->setPriority(80);
        $botDetectionRule->setTerminal(false);
        $botDetectionRule->setDescription('检测机器人用户代理字符串');

        $highFrequencyRule = new Rule();
        $highFrequencyRule->setName('ip_rate_limit');
        $highFrequencyRule->setCondition('request_count(ip_address, "5m") > 100');
        $highFrequencyRule->setRiskLevel(RiskLevel::MEDIUM);
        $highFrequencyRule->setActions(['throttle' => ['retry_after' => 30]]);
        $highFrequencyRule->setPriority(70);
        $highFrequencyRule->setTerminal(false);
        $highFrequencyRule->setDescription('IP地址请求频率限制');

        $criticalBotRule = new Rule();
        $criticalBotRule->setName('malicious_bot_block');
        $criticalBotRule->setCondition('user_agent contains "scrapy" or user_agent contains "curl" or user_agent contains "wget"');
        $criticalBotRule->setRiskLevel(RiskLevel::CRITICAL);
        $criticalBotRule->setActions(['block' => ['message' => 'Access denied']]);
        $criticalBotRule->setPriority(90);
        $criticalBotRule->setTerminal(true);
        $criticalBotRule->setDescription('阻止恶意爬虫和自动化工具');

        $proxyDetectionRule = new Rule();
        $proxyDetectionRule->setName('proxy_ip_detection');
        $proxyDetectionRule->setCondition('is_proxy_ip = true');
        $proxyDetectionRule->setRiskLevel(RiskLevel::MEDIUM);
        $proxyDetectionRule->setActions(['log' => ['level' => 'warning', 'message' => 'Proxy IP detected']]);
        $proxyDetectionRule->setPriority(60);
        $proxyDetectionRule->setTerminal(false);
        $proxyDetectionRule->setDescription('检测代理IP地址');

        $loginBruteForceRule = new Rule();
        $loginBruteForceRule->setName('login_brute_force_protection');
        $loginBruteForceRule->setCondition('action = "login" and request_count(ip_address, "1h") > 10');
        $loginBruteForceRule->setRiskLevel(RiskLevel::HIGH);
        $loginBruteForceRule->setActions([
            'throttle' => ['retry_after' => 300],
            'log' => ['level' => 'alert', 'message' => 'Potential brute force attack'],
        ]);
        $loginBruteForceRule->setPriority(85);
        $loginBruteForceRule->setTerminal(false);
        $loginBruteForceRule->setDescription('登录暴力破解防护');

        $automationDetectionRule = new Rule();
        $automationDetectionRule->setName('automation_behavior_detection');
        $automationDetectionRule->setCondition('form_submit_time < 2 and user_agent not contains "Mobile"');
        $automationDetectionRule->setRiskLevel(RiskLevel::MEDIUM);
        $automationDetectionRule->setActions(['log' => ['level' => 'info', 'message' => 'Automated behavior detected']]);
        $automationDetectionRule->setPriority(50);
        $automationDetectionRule->setTerminal(false);
        $automationDetectionRule->setDescription('检测自动化行为');

        $whitelistRule = new Rule();
        $whitelistRule->setName('whitelist_bypass');
        $whitelistRule->setCondition('is_whitelisted = true');
        $whitelistRule->setRiskLevel(RiskLevel::LOW);
        $whitelistRule->setActions(['log' => ['level' => 'info', 'message' => 'Whitelisted user']]);
        $whitelistRule->setPriority(10);
        $whitelistRule->setTerminal(true);
        $whitelistRule->setDescription('白名单用户直接通过');

        $blacklistRule = new Rule();
        $blacklistRule->setName('blacklist_block');
        $blacklistRule->setCondition('is_blacklisted = true');
        $blacklistRule->setRiskLevel(RiskLevel::CRITICAL);
        $blacklistRule->setActions(['block' => ['message' => 'Access denied - blacklisted']]);
        $blacklistRule->setPriority(100);
        $blacklistRule->setTerminal(true);
        $blacklistRule->setDescription('黑名单用户直接阻止');

        $manager->persist($botDetectionRule);
        $manager->persist($highFrequencyRule);
        $manager->persist($criticalBotRule);
        $manager->persist($proxyDetectionRule);
        $manager->persist($loginBruteForceRule);
        $manager->persist($automationDetectionRule);
        $manager->persist($whitelistRule);
        $manager->persist($blacklistRule);

        $manager->flush();
    }
}
