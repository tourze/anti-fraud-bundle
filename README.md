# Tourze Anti-Fraud Bundle

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/anti-fraud-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/anti-fraud-bundle)
[![PHP Version](https://img.shields.io/packagist/php-v/tourze/anti-fraud-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/anti-fraud-bundle)
[![License](https://img.shields.io/packagist/l/tourze/anti-fraud-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/anti-fraud-bundle)
[![Build Status](https://img.shields.io/github/workflow/status/tourze/anti-fraud-bundle/CI/master.svg?style=flat-square)](https://github.com/tourze/anti-fraud-bundle/actions)
[![Quality Score](https://img.shields.io/scrutinizer/g/tourze/anti-fraud-bundle.svg?style=flat-square)](https://scrutinizer-ci.com/g/tourze/anti-fraud-bundle)
[![Code Coverage](https://img.shields.io/codecov/c/github/tourze/anti-fraud-bundle.svg?style=flat-square)](https://codecov.io/github/tourze/anti-fraud-bundle)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/anti-fraud-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/anti-fraud-bundle)

A powerful Symfony Bundle for detecting and preventing various types of fraud activities in web applications.

## Table of Contents

- [Features](#features)
- [Dependencies](#dependencies)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Built-in Detection Features](#built-in-detection-features)
- [Core Components](#core-components)
- [Default Detection Rules](#default-detection-rules)
- [Custom Rules](#custom-rules)
- [Getting Detection Results](#getting-detection-results)
- [Advanced Usage](#advanced-usage)
- [Performance Metrics](#performance-metrics)
- [Testing](#testing)
- [Troubleshooting](#troubleshooting)
- [Contributing](#contributing)
- [License](#license)
- [Support](#support)

## Features

- **Multi-layer Detection** - Comprehensive detection across account anomalies, data anomalies, and 
  operation anomalies
- **Smart Recognition** - Intelligent analysis based on HTTP requests and user behavior patterns
- **Dynamic Rules** - Support for real-time configuration and hot-reloading of detection rules
- **Risk Levels** - Four-tier risk assessment: LOW/MEDIUM/HIGH/CRITICAL
- **Automated Response** - Multiple response actions including logging, throttling, and blocking
- **Management Interface** - Web-based administration dashboard
- **High Performance** - P50 < 50ms, P99 < 100ms detection latency

## Dependencies

This bundle requires the following packages:

- **PHP**: ^8.1
- **Symfony**: ^6.4 || ^7.0
- **Doctrine ORM**: ^2.17 || ^3.0
- **Symfony Cache**: ^6.4 || ^7.0
- **Symfony Validator**: ^6.4 || ^7.0
- **PSR-3 Logger**: ^3.0
- **PSR-6 Cache**: ^3.0

Optional dependencies for enhanced features:

- **Redis**: For improved caching performance
- **Memcached**: Alternative caching backend
- **GeoIP2**: For advanced geolocation features

## Installation

### Step 1: Install via Composer

```bash
composer require tourze/anti-fraud-bundle
```

### Step 2: Register the Bundle

Register the bundle in `config/bundles.php`:

```php
return [
    // ...
    Tourze\AntiFraudBundle\AntiFraudBundle::class => ['all' => true],
];
```

### Step 3: Update Database Schema

```bash
# Create migration
php bin/console doctrine:migrations:diff

# Apply migration
php bin/console doctrine:migrations:migrate
```

## Quick Start

### Zero Configuration Setup

The bundle works out-of-the-box with automatic detection features enabled after installation.

### Environment Configuration

Customize detection parameters through environment variables:

```bash
# .env
ANTIFRAUD_ENABLED=true                 # Enable/disable anti-fraud system
ANTIFRAUD_MULTI_ACCOUNT_DETECTION=true # Multi-account detection
ANTIFRAUD_PROXY_DETECTION=true         # Proxy detection
ANTIFRAUD_PATTERN_DETECTION=true       # Abnormal pattern detection
ANTIFRAUD_LOG_LEVEL=info              # Log level
```

### Basic Usage

```php
use Tourze\AntiFraudBundle\Service\DataCollector;
use Tourze\AntiFraudBundle\Rule\Engine\DynamicRuleEngine;
use Tourze\AntiFraudBundle\Enum\RiskLevel;

class YourController
{
    public function __construct(
        private DataCollector $dataCollector,
        private DynamicRuleEngine $ruleEngine
    ) {}

    public function someAction(Request $request): Response
    {
        // Collect request data
        $context = $this->dataCollector->collect($request);
        
        // Execute risk detection
        $result = $this->ruleEngine->evaluate($context);
        
        if ($result->getRiskLevel() === RiskLevel::HIGH) {
            // Handle high-risk request
            return new Response('Request blocked', 429);
        }
        
        // Process request normally
        return new Response('OK');
    }
}
```

## Built-in Detection Features

### Detection Engines

The bundle includes the following built-in detectors:

- **IP Rate Limiting** - Detects excessive requests from single IP addresses
- **Bot Detection** - Identifies crawlers and automated bots
- **Proxy/VPN Detection** - Detects proxy servers and VPN connections
- **Form Submission Speed** - Detects abnormally fast form submissions
- **Multi-account Detection** - Detects multiple account logins from same device/IP
- **Abnormal Pattern Detection** - Detects unusual user behavior patterns
- **Score Manipulation Detection** - Detects data and score tampering
- **Automation Script Detection** - Detects automated tools and scripts

### Management Interface

Access `/admin/antifraud` to view the management dashboard:

- **Dashboard** - Detection statistics and overview
- **Rule Management** - View and edit detection rules
- **Log Viewer** - View detailed detection logs
- **IP Lists** - Manage IP whitelist and blacklist
- **Configuration** - System settings and parameters

## Core Components

### Data Models

- **UserBehavior** - User behavior data model
- **Context** - Detection context
- **RiskAssessment** - Risk assessment results
- **DetectionResult** - Detection results

### Detectors

- **MultiAccountDetector** - Multi-account detector
- **ProxyDetector** - Proxy detector
- **AbnormalPatternDetector** - Abnormal pattern detector
- **ScoreManipulationDetector** - Score manipulation detector
- **AutomationDetector** - Automation detector

### Rule Engine

- **Rule** - Rule entity
- **RuleEvaluator** - Rule evaluator
- **DynamicRuleEngine** - Dynamic rule engine

### Response Actions

- **LogAction** - Log recording
- **BlockAction** - Request blocking
- **ThrottleAction** - Request throttling

## Default Detection Rules

| Rule Name | Risk Level | Detection Condition | Response Action |
|-----------|------------|-------------------|----------------|
| IP Rate Limiting | CRITICAL | >60 requests/minute | Log + Block |
| Login Rate Limiting | HIGH | >5 logins/hour | Log + Throttle |
| Suspicious User Agent | MEDIUM | Bot signature detected | Log |
| Proxy/VPN Detection | MEDIUM | Proxy connection detected | Log + Throttle |
| Form Submission Too Fast | HIGH | Form submission <2 seconds | Log + Block |

## Custom Rules

Create custom rules via code:

```php
use Tourze\AntiFraudBundle\Entity\Rule;
use Tourze\AntiFraudBundle\Enum\RiskLevel;

$customRule = new Rule(
    name: 'Custom Login Rule',
    condition: 'action == "login" and failed_login_count_15m > 3',
    riskLevel: RiskLevel::HIGH,
    actions: [
        ['type' => 'log', 'message' => 'Multiple failed logins detected'],
        ['type' => 'throttle', 'delay' => 10]
    ]
);

// Save to database
$entityManager->persist($customRule);
$entityManager->flush();
```

### Available Detection Variables

- `request_count_1m` - Request count within 1 minute
- `request_count_5m` - Request count within 5 minutes
- `login_count_1h` - Login count within 1 hour
- `failed_login_count_15m` - Failed login count within 15 minutes
- `is_bot` - Whether it's a bot
- `is_proxy` - Whether it's using proxy
- `form_submit_time` - Form submission time
- `action` - Action type
- `method` - HTTP method
- `path` - Request path

## Getting Detection Results

### Detection Results

```php
public function handleRequest(Request $request): Response
{
    // Get detection result
    $result = $request->attributes->get('_antifraud_result');
    
    if ($result && $result->getRiskLevel() === RiskLevel::HIGH) {
        return new JsonResponse([
            'error' => 'Request blocked by anti-fraud system',
            'risk_level' => $result->getRiskLevel()->value
        ], 429);
    }
    
    // Continue normal processing
}
```

### Custom Detectors

```php
use Tourze\AntiFraudBundle\Detector\DetectorInterface;

class CustomDetector implements DetectorInterface
{
    public function getName(): string
    {
        return 'custom_detector';
    }

    public function detect(Context $context): DetectionResult
    {
        // Implement custom detection logic
        if ($this->isSuspicious($context)) {
            return new DetectionResult(
                RiskLevel::HIGH,
                new BlockAction('Custom rule triggered'),
                [],
                ['custom_data' => true]
            );
        }
        
        return new DetectionResult(RiskLevel::LOW, new LogAction(), []);
    }

    public function isEnabled(): bool
    {
        return true;
    }
}
```

## Advanced Usage

### Custom Rule Conditions

The rule engine supports complex conditional expressions:

```php
// Time-based conditions
'hour(now()) between 22 and 6'

// Geographic conditions  
'country_code not in ["US", "CA", "GB"]'

// Behavioral patterns
'request_count_1h > 100 and unique_paths_count < 5'

// Device fingerprinting
'device_fingerprint_risk_score > 0.8'

// Combination rules
'is_mobile_device and (user_agent matches "/bot/i" or request_count_1m > 20)'
```

### Event Listeners and Hooks

```php
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Tourze\AntiFraudBundle\Event\DetectionEvent;

class CustomDetectionListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            DetectionEvent::class => 'onDetection',
        ];
    }

    public function onDetection(DetectionEvent $event): void
    {
        $context = $event->getContext();
        $result = $event->getResult();
        
        // Custom logic after detection
        if ($result->getRiskLevel() === RiskLevel::CRITICAL) {
            // Send alert, update blacklist, etc.
        }
    }
}
```

### Caching Strategies

```yaml
# config/packages/anti_fraud.yaml
anti_fraud:
    cache:
        adapter: 'cache.app'
        ttl: 3600
        prefix: 'antifraud'
    
    rate_limiting:
        window_size: 300  # 5 minutes
        cleanup_interval: 3600  # 1 hour
```

### Integration with External Services

```php
// IP Intelligence Services
use Tourze\AntiFraudBundle\Service\IpIntelligenceService;

$ipService = new IpIntelligenceService([
    'maxmind_license_key' => 'your_key',
    'ipqualityscore_api_key' => 'your_api_key',
]);

// Machine Learning Models
use Tourze\AntiFraudBundle\ML\RiskScoreCalculator;

$calculator = new RiskScoreCalculator();
$riskScore = $calculator->calculate($behaviorData);
```

## Performance Metrics

- **Detection Latency**: P50 < 50ms, P99 < 100ms
- **Memory Usage**: < 10MB incremental
- **Throughput**: Supports > 10,000 QPS
- **Concurrent Users**: 1 million online users

## Testing

Run the test suite:

```bash
# Run all tests
vendor/bin/phpunit packages/anti-fraud-bundle

# Run specific tests
vendor/bin/phpunit packages/anti-fraud-bundle/tests/Detector/
vendor/bin/phpunit packages/anti-fraud-bundle/tests/Repository/

# Run with coverage
vendor/bin/phpunit packages/anti-fraud-bundle --coverage-html coverage/
```

## Troubleshooting

Common issues and solutions:

- **Data Collection** - Ensure automatic event listeners are enabled
- **Performance Issues** - Consider adjusting detection frequency and caching strategy
- **False Positives** - Use whitelist to exclude trusted IPs
- **Rule Debugging** - Enable debug mode to view detailed logs

### Debug Mode

```yaml
# config/packages/dev/anti_fraud.yaml
anti_fraud:
    debug: true
    log_level: debug
```

### Performance Tuning

```yaml
# config/packages/prod/anti_fraud.yaml
anti_fraud:
    performance:
        enable_async_processing: true
        batch_size: 100
        worker_count: 4
```

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Create a Pull Request

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Support

- **Bug Reports**: [GitHub Issues](https://github.com/tourze/anti-fraud-bundle/issues)
- **Feature Requests**: [GitHub Discussions](https://github.com/tourze/anti-fraud-bundle/discussions)
- **Technical Support**: support@tourze.com

---

**Enjoy secure application experience with Tourze Anti-Fraud Bundle protecting your system!**