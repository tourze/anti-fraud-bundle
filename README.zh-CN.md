# Tourze Anti-Fraud Bundle

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/anti-fraud-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/anti-fraud-bundle)
[![PHP Version](https://img.shields.io/packagist/php-v/tourze/anti-fraud-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/anti-fraud-bundle)
[![License](https://img.shields.io/packagist/l/tourze/anti-fraud-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/anti-fraud-bundle)
[![Build Status](https://img.shields.io/github/workflow/status/tourze/anti-fraud-bundle/CI/master.svg?style=flat-square)](https://github.com/tourze/anti-fraud-bundle/actions)
[![Quality Score](https://img.shields.io/scrutinizer/g/tourze/anti-fraud-bundle.svg?style=flat-square)](https://scrutinizer-ci.com/g/tourze/anti-fraud-bundle)
[![Code Coverage](https://img.shields.io/codecov/c/github/tourze/anti-fraud-bundle.svg?style=flat-square)](https://codecov.io/github/tourze/anti-fraud-bundle)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/anti-fraud-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/anti-fraud-bundle)

一个功能强大的 Symfony Bundle，用于检测和防止Web应用程序中的各种欺诈行为。

## 目录

- [功能特性](#功能特性)
- [依赖要求](#依赖要求)
- [安装](#安装)
- [快速开始](#快速开始)
- [内置检测功能](#内置检测功能)
- [核心组件](#核心组件)
- [默认检测规则](#默认检测规则)
- [自定义规则](#自定义规则)
- [获取检测结果](#获取检测结果)
- [高级用法](#高级用法)
- [性能指标](#性能指标)
- [测试](#测试)
- [故障排除](#故障排除)
- [贡献](#贡献)
- [许可证](#许可证)
- [支持](#支持)

## 功能特性

- **多层检测** - 账号异常、数据异常、操作异常多维度检测
- **智能识别** - 基于HTTP请求和用户行为模式的智能分析
- **动态规则** - 支持实时配置和热更新规则
- **风险等级** - LOW/MEDIUM/HIGH/CRITICAL四级风险评估
- **自动响应** - 支持日志记录、限流、阻断等多种响应动作
- **管理界面** - 提供Web管理后台
- **高性能** - P50 < 50ms, P99 < 100ms 的检测延迟

## 依赖要求

该 Bundle 需要以下包：

- **PHP**: ^8.1
- **Symfony**: ^6.4 || ^7.0
- **Doctrine ORM**: ^2.17 || ^3.0
- **Symfony Cache**: ^6.4 || ^7.0
- **Symfony Validator**: ^6.4 || ^7.0
- **PSR-3 Logger**: ^3.0
- **PSR-6 Cache**: ^3.0

增强功能的可选依赖：

- **Redis**: 用于改进缓存性能
- **Memcached**: 替代缓存后端
- **GeoIP2**: 用于高级地理位置功能

## 安装

### 步骤 1: 通过 Composer 安装

```bash
composer require tourze/anti-fraud-bundle
```

### 步骤 2: 注册 Bundle

在 `config/bundles.php` 中注册 Bundle：

```php
return [
    // ...
    Tourze\AntiFraudBundle\AntiFraudBundle::class => ['all' => true],
];
```

### 步骤 3: 更新数据库结构

```bash
# 创建迁移
php bin/console doctrine:migrations:diff

# 应用迁移
php bin/console doctrine:migrations:migrate
```

## 快速开始

### 零配置启用

Bundle 安装后自动启用核心检测功能，无需额外配置。

### 环境变量配置

通过环境变量自定义检测参数：

```bash
# .env
ANTIFRAUD_ENABLED=true                 # 启用/禁用反欺诈系统
ANTIFRAUD_MULTI_ACCOUNT_DETECTION=true # 多账号检测
ANTIFRAUD_PROXY_DETECTION=true         # 代理检测
ANTIFRAUD_PATTERN_DETECTION=true       # 异常模式检测
ANTIFRAUD_LOG_LEVEL=info              # 日志级别
```

### 基本用法

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
        // 收集请求数据
        $context = $this->dataCollector->collect($request);
        
        // 执行风险检测
        $result = $this->ruleEngine->evaluate($context);
        
        if ($result->getRiskLevel() === RiskLevel::HIGH) {
            // 处理高风险请求
            return new Response('请求被阻止', 429);
        }
        
        // 正常处理请求
        return new Response('OK');
    }
}
```

## 内置检测功能

### 检测引擎

Bundle 包含以下内置检测器：

- **IP限流** - 检测来自单个IP地址的过量请求
- **机器人检测** - 识别爬虫和自动化机器人
- **代理/VPN检测** - 检测代理服务器和VPN连接
- **表单提交速度** - 检测异常快速的表单提交
- **多账号检测** - 检测同设备/IP的多账号登录
- **异常模式检测** - 检测异常用户行为模式
- **分数操控检测** - 检测数据和分数篡改
- **自动化脚本检测** - 检测自动化工具和脚本

### 管理界面

访问 `/admin/antifraud` 查看管理后台：

- **控制台** - 检测统计和概览
- **规则管理** - 查看和编辑检测规则
- **日志查看器** - 查看详细检测日志
- **IP列表** - 管理IP白名单和黑名单
- **配置** - 系统设置和参数

## 核心组件

### 数据模型

- **UserBehavior** - 用户行为数据模型
- **Context** - 检测上下文
- **RiskAssessment** - 风险评估结果
- **DetectionResult** - 检测结果

### 检测器

- **MultiAccountDetector** - 多账号检测器
- **ProxyDetector** - 代理检测器
- **AbnormalPatternDetector** - 异常模式检测器
- **ScoreManipulationDetector** - 分数操控检测器
- **AutomationDetector** - 自动化检测器

### 规则引擎

- **Rule** - 规则实体
- **RuleEvaluator** - 规则评估器
- **DynamicRuleEngine** - 动态规则引擎

### 响应动作

- **LogAction** - 日志记录
- **BlockAction** - 请求阻断
- **ThrottleAction** - 请求限流

## 默认检测规则

| 规则名称 | 风险等级 | 检测条件 | 响应动作 |
|---------|---------|---------|---------|
| IP限流 | CRITICAL | >60 请求/分钟 | 日志 + 阻断 |
| 登录限流 | HIGH | >5 登录/小时 | 日志 + 限流 |
| 可疑用户代理 | MEDIUM | 检测到机器人签名 | 日志 |
| 代理/VPN检测 | MEDIUM | 检测到代理连接 | 日志 + 限流 |
| 表单提交过快 | HIGH | 表单提交 <2 秒 | 日志 + 阻断 |

## 自定义规则

通过代码创建自定义规则：

```php
use Tourze\AntiFraudBundle\Entity\Rule;
use Tourze\AntiFraudBundle\Enum\RiskLevel;

$customRule = new Rule(
    name: '自定义登录规则',
    condition: 'action == "login" and failed_login_count_15m > 3',
    riskLevel: RiskLevel::HIGH,
    actions: [
        ['type' => 'log', 'message' => '检测到多次登录失败'],
        ['type' => 'throttle', 'delay' => 10]
    ]
);

// 保存到数据库
$entityManager->persist($customRule);
$entityManager->flush();
```

### 可用检测变量

- `request_count_1m` - 1分钟内请求数
- `request_count_5m` - 5分钟内请求数
- `login_count_1h` - 1小时内登录数
- `failed_login_count_15m` - 15分钟内失败登录数
- `is_bot` - 是否机器人
- `is_proxy` - 是否使用代理
- `form_submit_time` - 表单提交时间
- `action` - 动作类型
- `method` - HTTP方法
- `path` - 请求路径

## 获取检测结果

### 检测结果

```php
public function handleRequest(Request $request): Response
{
    // 获取检测结果
    $result = $request->attributes->get('_antifraud_result');
    
    if ($result && $result->getRiskLevel() === RiskLevel::HIGH) {
        return new JsonResponse([
            'error' => '请求被反欺诈系统阻止',
            'risk_level' => $result->getRiskLevel()->value
        ], 429);
    }
    
    // 继续正常处理
}
```

### 自定义检测器

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
        // 实现自定义检测逻辑
        if ($this->isSuspicious($context)) {
            return new DetectionResult(
                RiskLevel::HIGH,
                new BlockAction('自定义规则触发'),
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

## 高级用法

### 自定义规则条件

规则引擎支持复杂的条件表达式：

```php
// 基于时间的条件
'hour(now()) between 22 and 6'

// 地理条件
'country_code not in ["US", "CA", "GB"]'

// 行为模式
'request_count_1h > 100 and unique_paths_count < 5'

// 设备指纹
'device_fingerprint_risk_score > 0.8'

// 组合规则
'is_mobile_device and (user_agent matches "/bot/i" or request_count_1m > 20)'
```

### 事件监听器和钩子

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
        
        // 检测后的自定义逻辑
        if ($result->getRiskLevel() === RiskLevel::CRITICAL) {
            // 发送警报、更新黑名单等
        }
    }
}
```

### 缓存策略

```yaml
# config/packages/anti_fraud.yaml
anti_fraud:
    cache:
        adapter: 'cache.app'
        ttl: 3600
        prefix: 'antifraud'
    
    rate_limiting:
        window_size: 300  # 5 分钟
        cleanup_interval: 3600  # 1 小时
```

### 与外部服务集成

```php
// IP情报服务
use Tourze\AntiFraudBundle\Service\IpIntelligenceService;

$ipService = new IpIntelligenceService([
    'maxmind_license_key' => 'your_key',
    'ipqualityscore_api_key' => 'your_api_key',
]);

// 机器学习模型
use Tourze\AntiFraudBundle\ML\RiskScoreCalculator;

$calculator = new RiskScoreCalculator();
$riskScore = $calculator->calculate($behaviorData);
```

## 性能指标

- **检测延迟**: P50 < 50ms, P99 < 100ms
- **内存使用**: < 10MB 增量
- **吞吐量**: 支持 > 10,000 QPS
- **并发用户**: 100万在线用户

## 测试

运行测试套件：

```bash
# 运行所有测试
vendor/bin/phpunit packages/anti-fraud-bundle

# 运行特定测试
vendor/bin/phpunit packages/anti-fraud-bundle/tests/Detector/
vendor/bin/phpunit packages/anti-fraud-bundle/tests/Repository/

# 生成覆盖率报告
vendor/bin/phpunit packages/anti-fraud-bundle --coverage-html coverage/
```

## 故障排除

常见问题和解决方案：

- **数据收集** - 确保自动事件监听器已启用
- **性能问题** - 考虑调整检测频率和缓存策略
- **误报** - 使用白名单排除可信IP
- **规则调试** - 启用调试模式查看详细日志

### 调试模式

```yaml
# config/packages/dev/anti_fraud.yaml
anti_fraud:
    debug: true
    log_level: debug
```

### 性能调优

```yaml
# config/packages/prod/anti_fraud.yaml
anti_fraud:
    performance:
        enable_async_processing: true
        batch_size: 100
        worker_count: 4
```

## 贡献

1. Fork 该仓库
2. 创建你的功能分支 (`git checkout -b feature/amazing-feature`)
3. 提交你的更改 (`git commit -m 'Add amazing feature'`)
4. 推送到分支 (`git push origin feature/amazing-feature`)
5. 创建 Pull Request

## 许可证

该项目采用 MIT 许可证。详见 [LICENSE](LICENSE) 文件。

## 支持

- **Bug 报告**: [GitHub Issues](https://github.com/tourze/anti-fraud-bundle/issues)
- **功能请求**: [GitHub Discussions](https://github.com/tourze/anti-fraud-bundle/discussions)
- **技术支持**: support@tourze.com

---

**享受 Tourze Anti-Fraud Bundle 为您的系统提供的安全应用体验！**