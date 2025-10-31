# Symfony Bundle作弊行为识别系统完整技术方案

## 一、技术方案设计

### 1.1 系统概述

本方案设计了一个**零配置、自动化**的Symfony Bundle，用于作弊行为识别和用户行为风险识别。系统通过**自动化事件监听**和**动态规则配置**，实现接入即用，无需修改业务代码。

### 1.2 核心设计理念

#### 无感接入原则
1. **自动注册**：Bundle安装后自动注册所有必要的监听器
2. **零侵入**：不需要修改任何现有代码
3. **智能默认**：内置常用检测规则，开箱即用
4. **后台配置**：所有规则通过管理界面配置，无需重启

#### 自动化架构
```
┌─────────────────────────────────────────────────────────┐
│                    自动化采集层                          │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐    │
│  │Event Listener│  │ Middleware  │  │ Subscriber  │    │
│  └─────────────┘  └─────────────┘  └─────────────┘    │
├─────────────────────────────────────────────────────────┤
│                    规则引擎层                           │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐    │
│  │Dynamic Rules│  │Rule Loader  │  │Rule Cache   │    │
│  └─────────────┘  └─────────────┘  └─────────────┘    │
├─────────────────────────────────────────────────────────┤
│                    决策执行层                           │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐    │
│  │Risk Scorer  │  │Action Engine│  │ Notifier    │    │
│  └─────────────┘  └─────────────┘  └─────────────┘    │
├─────────────────────────────────────────────────────────┤
│                    管理界面层                           │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐    │
│  │Rule Manager │  │ Dashboard   │  │ Log Viewer  │    │
│  └─────────────┘  └─────────────┘  └─────────────┘    │
└─────────────────────────────────────────────────────────┘
```

#### 技术选型
- **自动采集**: Symfony EventSubscriber + CompilerPass
- **规则引擎**: 动态规则加载器 + Redis缓存
- **数据存储**: Redis (实时) + MySQL (规则配置)
- **管理界面**: EasyAdmin或自定义Admin Panel

### 1.3 核心功能模块设计

#### 1.3.1 账号异常检测模块
```php
namespace Tourze\AntiFraudBundle\Detector\Account;

interface AccountAnomalyDetectorInterface
{
    public function detectMultiAccount(UserBehavior $behavior): RiskScore;
    public function detectAccountTrading(UserBehavior $behavior): RiskScore;
    public function detectProxyUsage(UserBehavior $behavior): RiskScore;
}
}
```

**检测技术**：
- **多开检测**: 设备指纹匹配 + IP地址分析
- **代练检测**: 登录时间模式分析 + 操作习惯统计
- **账号买卖**: 设备切换频率 + 地理位置变化检测

#### 1.3.2 数据异常检测模块
```php
namespace Tourze\AntiFraudBundle\Detector\Data;

interface DataAnomalyDetectorInterface
{
    public function detectScoreManipulation(GameData $data): RiskScore;
    public function detectCheatTools(SystemData $data): RiskScore;
    public function detectAbnormalPatterns(UserData $data): RiskScore;
}
```

**检测算法**：
- **刷分识别**: 时间序列统计分析 + 异常值检测
- **作弊工具**: 请求签名验证 + 行为模式匹配
- **异常模式**: 基于规则的异常检测 + 统计阈值

#### 1.3.3 操作异常检测模块
```php
namespace Tourze\AntiFraudBundle\Detector\Operation;

interface OperationAnomalyDetectorInterface
{
    public function detectAutomation(MouseData $data): RiskScore;
    public function detectBotBehavior(ClickData $data): RiskScore;
    public function detectAbnormalClicks(InteractionData $data): RiskScore;
}
```

**检测方法**：
- **自动化脚本**: 请求频率分析 + UserAgent检测
- **机器人行为**: Session行为分析 + 访问模式识别  
- **异常点击**: 表单提交速度 + 操作序列检测

### 1.4 自动化接入机制

```php
namespace Tourze\AntiFraudBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * 自动注册所有必要的服务和监听器
 */
class AutoRegisterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // 自动标记所有控制器
        foreach ($container->findTaggedServiceIds('controller.service_arguments') as $id => $tags) {
            $definition = $container->getDefinition($id);
            $definition->addTag('antifraud.monitored');
        }
        
        // 自动注册中间件
        if ($container->hasDefinition('security.firewall.map')) {
            $this->registerSecurityListeners($container);
        }
    }
}
```

## 二、零配置产品需求文档（PRD）

### 2.1 产品定位
- **产品名称**: Tourze AntiFraud Bundle
- **核心价值**: 一行命令安装，零配置启动，全自动防护
- **目标用户**: 所有Symfony项目，特别是需要快速上线防护的团队

### 2.2 核心特性

#### 2.2.1 一键安装
```bash
composer require tourze/antifraud-bundle
# 自动完成所有配置，无需任何额外步骤
```

#### 2.2.2 自动防护
- 安装即激活，自动监控所有请求
- 内置10+常用防护规则
- 智能学习正常行为模式

#### 2.2.3 可视化管理
- 提供Web管理界面（/admin/antifraud）
- 实时查看检测日志
- 动态调整规则，无需重启

### 2.3 功能需求

#### FR001: 自动化数据采集
- **描述**: 自动监听框架事件，无需修改代码
- **采集内容**:
  - HTTP请求（自动）
  - 登录事件（自动）
  - 表单提交（自动）
  - API调用（自动）

#### FR002: 智能规则引擎
- **描述**: 预置规则 + 自定义规则
- **功能点**:
  - 开箱即用的默认规则
  - 基于DSL的规则编写
  - 规则优先级管理
  - 实时生效机制

#### FR003: 响应动作系统
- **描述**: 根据风险等级自动执行动作
- **动作类型**:
  - 记录（默认）
  - 限流
  - 验证码
  - 阻断
  - 自定义动作

#### FR004: 管理控制台
- **描述**: Web界面管理所有功能
- **功能模块**:
  - Dashboard（统计概览）
  - 规则管理
  - 日志查看
  - 白名单/黑名单
  - 配置管理

### 2.4 非功能需求

#### NFR001: 性能要求
- 检测延迟: P50<50ms, P99<100ms
- 吞吐量: >10000 QPS
- 并发用户: 支持100万在线用户

#### NFR002: 可用性要求
- 系统可用性: 99.99%
- 故障恢复时间: <5分钟
- 数据不丢失率: 99.999%

#### NFR003: 安全要求
- 数据加密传输（TLS 1.3）
- 访问控制（RBAC）
- 审计日志完整性

#### NFR004: 扩展性要求
- 水平扩展能力
- 插件式架构
- 多租户支持

### 2.5 Bundle架构设计

#### 2.5.1 目录结构
```
src/Tourze/AntiFraudBundle/
├── TourzeAntiFraudBundle.php        # Bundle主类
├── DependencyInjection/
│   ├── TourzeAntiFraudExtension.php # 依赖注入扩展
│   └── Configuration.php            # 配置定义
├── Command/
│   ├── TrainModelCommand.php      # 模型训练命令
│   └── ImportRulesCommand.php     # 规则导入命令
├── Controller/
│   ├── DetectionController.php    # 检测API控制器
│   └── AdminController.php        # 管理界面控制器
├── Detector/
│   ├── Account/
│   │   ├── MultiAccountDetector.php
│   │   ├── ProxyDetector.php
│   │   └── TradingDetector.php
│   ├── Data/
│   │   ├── ScoreManipulationDetector.php
│   │   ├── CheatToolDetector.php
│   │   └── AbnormalPatternDetector.php
│   └── Operation/
│       ├── AutomationDetector.php
│       ├── BotDetector.php
│       └── ClickAnomalyDetector.php
├── Engine/
│   ├── Rule/
│   │   ├── RuleEngine.php
│   │   ├── RuleCompiler.php
│   │   └── RuleRepository.php
│   └── Statistics/
│       ├── StatisticsEngine.php
│       ├── AnomalyDetector.php
│       └── MetricsCalculator.php
├── Event/
│   ├── DetectionEvent.php
│   └── RiskAlertEvent.php
├── Model/
│   ├── UserBehavior.php
│   ├── RiskAssessment.php
│   └── DetectionResult.php
├── Repository/
│   ├── BehaviorLogRepository.php
│   └── RuleRepository.php
├── Service/
│   ├── DeviceFingerprintService.php
│   ├── IPAnalysisService.php
│   └── NotificationService.php
└── Resources/
    ├── config/
    │   └── routes.yaml
    ├── views/
    │   └── admin/
    └── public/
        └── js/
```

## 三、核心功能模块设计

### 2.4 非功能需求

#### NFR001: 零配置要求
- 安装即用，无需配置文件
- 自动识别Symfony版本并适配
- 默认规则覆盖90%常见场景

#### NFR002: 性能影响
- 对正常请求延迟影响<5ms
- 内存占用增加<50MB
- CPU使用率增加<5%

#### NFR003: 兼容性
- Symfony 5.4+
- PHP 8.0+
- 支持所有主流Session存储

### 2.5 Bundle架构设计

#### 2.5.1 自动化Bundle结构
```
src/Tourze/AntiFraudBundle/
├── TourzeAntiFraudBundle.php              # Bundle主类（自动注册）
├── DependencyInjection/
│   ├── TourzeAntiFraudExtension.php      # 零配置扩展
│   ├── Compiler/
│   │   ├── AutoRegisterPass.php          # 自动注册服务
│   │   └── RuleLoaderPass.php            # 加载默认规则
│   └── Configuration.php                  # 可选配置
├── EventSubscriber/
│   ├── AutoCollectorEventSubscriber.php  # 核心：自动数据采集
│   ├── SecuritySubscriber.php             # 安全事件监听
│   └── FormSubscriber.php                 # 表单事件监听
├── Rule/
│   ├── Engine/
│   │   ├── DynamicRuleEngine.php         # 动态规则引擎
│   │   └── RuleEvaluator.php             # 规则评估器
│   ├── Defaults/
│   │   ├── LoginRules.php                # 登录相关规则
│   │   ├── ApiRules.php                  # API防护规则
│   │   └── GeneralRules.php              # 通用防护规则
│   └── Action/
│       ├── LogAction.php                 # 记录动作
│       ├── BlockAction.php               # 阻断动作
│       └── ChallengeAction.php           # 验证动作
├── Admin/
│   ├── Controller/
│   │   ├── DashboardController.php       # 管理面板
│   │   └── RuleController.php            # 规则管理
│   └── Templates/
│       └── admin/
├── Entity/
│   ├── Rule.php                          # 规则实体
│   ├── DetectionLog.php                  # 检测日志
│   └── RiskProfile.php                   # 风险档案
├── Repository/
│   └── RuleRepository.php                # 规则仓库
├── Service/
│   ├── DataCollector.php                 # 数据收集器
│   ├── RiskScorer.php                    # 风险评分器
│   └── ActionExecutor.php                # 动作执行器
└── Resources/
    ├── config/
    │   ├── services.yaml                  # 自动加载的服务
    │   └── routes.yaml                    # 管理界面路由
    └── migrations/
        └── Version001_CreateTables.php    # 自动建表
```

## 三、核心功能实现

### 3.1 自动数据采集器
```php
namespace Tourze\AntiFraudBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;

class AutoCollectorEventSubscriber implements EventSubscriberInterface
{
    private DataCollector $collector;
    private DynamicRuleEngine $ruleEngine;
    private bool $enabled;
    
    public function __construct(DataCollector $collector, DynamicRuleEngine $ruleEngine)
    {
        $this->collector = $collector;
        $this->ruleEngine = $ruleEngine;
        // 通过环境变量控制，默认启用
        $this->enabled = $_ENV['ANTIFRAUD_ENABLED'] ?? true;
    }
    
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 255],
            KernelEvents::RESPONSE => ['onKernelResponse', -255],
            'security.authentication.success' => 'onAuthSuccess',
            'security.authentication.failure' => 'onAuthFailure',
            KernelEvents::EXCEPTION => 'onException',
        ];
    }
    
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$this->enabled || !$event->isMainRequest()) {
            return;
        }
        
        $request = $event->getRequest();
        
        // 跳过静态资源和管理界面
        if ($this->shouldSkip($request)) {
            return;
        }
        
        // 自动收集数据
        $context = $this->collector->collect($request);
        
        // 实时检测
        $result = $this->ruleEngine->evaluate($context);
        
        // 如果检测到高风险，立即执行动作
        if ($result->shouldTakeAction()) {
            $response = $result->getAction()->execute($request);
            if ($response) {
                $event->setResponse($response);
            }
        }
        
        // 将结果存储在请求属性中，供后续使用
        $request->attributes->set('_antifraud_result', $result);
    }
    
    private function shouldSkip(Request $request): bool
    {
        $path = $request->getPathInfo();
        
        // 跳过静态资源
        if (preg_match('/\.(js|css|png|jpg|gif|ico|woff|woff2|ttf)$/i', $path)) {
            return true;
        }
        
        // 跳过管理界面
        if (str_starts_with($path, '/admin/antifraud')) {
            return true;
        }
        
        // 跳过健康检查
        if ($path === '/health' || $path === '/ping') {
            return true;
        }
        
        return false;
    }
}
```

### 3.2 设备指纹模块
### 3.2 动态规则引擎
```php
namespace Tourze\AntiFraudBundle\Rule\Engine;

use Tourze\AntiFraudBundle\Rule\Context;
use Tourze\AntiFraudBundle\Rule\Result;

class DynamicRuleEngine
{
    private RuleRepository $repository;
    private RuleEvaluator $evaluator;
    private CacheInterface $cache;
    private array $defaultRules;
    
    public function __construct(
        RuleRepository $repository,
        RuleEvaluator $evaluator,
        CacheInterface $cache
    ) {
        $this->repository = $repository;
        $this->evaluator = $evaluator;
        $this->cache = $cache;
        $this->loadDefaultRules();
    }
    
    /**
     * 评估请求风险
     */
    public function evaluate(Context $context): Result
    {
        $rules = $this->getActiveRules();
        $matchedRules = [];
        $highestPriority = null;
        
        foreach ($rules as $rule) {
            if ($this->evaluator->matches($rule, $context)) {
                $matchedRules[] = $rule;
                
                // 找到最高优先级的规则
                if ($highestPriority === null || $rule->getPriority() > $highestPriority->getPriority()) {
                    $highestPriority = $rule;
                }
                
                // 如果是终止规则，立即返回
                if ($rule->isTerminal()) {
                    break;
                }
            }
        }
        
        if ($highestPriority) {
            return new Result(
                $highestPriority->getRiskLevel(),
                $highestPriority->getAction(),
                $matchedRules
            );
        }
        
        return new Result(RiskLevel::LOW, new LogAction(), []);
    }
    
    /**
     * 获取所有激活的规则（包括默认规则和自定义规则）
     */
    private function getActiveRules(): array
    {
        return $this->cache->get('antifraud.rules', function() {
            $customRules = $this->repository->findActiveRules();
            return array_merge($this->defaultRules, $customRules);
        });
    }
    
    /**
     * 加载默认规则
     */
    private function loadDefaultRules(): void
    {
        $this->defaultRules = [
            new Rule(
                'rate_limit_login',
                'request.path = "/login" AND request_count(5m) > 5',
                RiskLevel::HIGH,
                new BlockAction('Too many login attempts'),
                100
            ),
            new Rule(
                'rate_limit_api',
                'request.path MATCHES "^/api/" AND request_count(1m) > 100',
                RiskLevel::MEDIUM,
                new ThrottleAction(),
                90
            ),
            new Rule(
                'suspicious_user_agent',
                'request.user_agent MATCHES "(bot|crawler|spider)" AND request.path NOT MATCHES "^/robots.txt"',
                RiskLevel::MEDIUM,
                new ChallengeAction(),
                80
            ),
            new Rule(
                'rapid_form_submit',
                'form.submit_time < 2',
                RiskLevel::HIGH,
                new BlockAction('Form submitted too quickly'),
                95
            ),
        ];
    }
    
    /**
     * 刷新规则缓存（管理界面修改规则后调用）
     */
    public function refreshRules(): void
    {
        $this->cache->delete('antifraud.rules');
    }
}
```

### 3.3 规则DSL解析器
```php
namespace Tourze\AntiFraudBundle\Rule;

/**
 * 简单易用的规则DSL
 * 示例：
 * - request.path = "/login" AND request_count(5m) > 5
 * - ip.country IN ["CN", "RU"] OR ip.is_proxy = true
 * - form.submit_time < 2 AND user.is_new = true
 */
class RuleEvaluator
{
    private ExpressionLanguage $expressionLanguage;
    private MetricsCollector $metrics;
    
    public function __construct(MetricsCollector $metrics)
    {
        $this->metrics = $metrics;
        $this->expressionLanguage = new ExpressionLanguage();
        $this->registerFunctions();
    }
    
    public function matches(Rule $rule, Context $context): bool
    {
        try {
            $variables = $this->prepareVariables($context);
            return $this->expressionLanguage->evaluate($rule->getCondition(), $variables);
        } catch (\Exception $e) {
            // 规则解析失败，记录日志但不影响请求
            return false;
        }
    }
    
    private function prepareVariables(Context $context): array
    {
        return [
            'request' => [
                'path' => $context->getPath(),
                'method' => $context->getMethod(),
                'user_agent' => $context->getUserAgent(),
                'ip' => $context->getIp(),
            ],
            'user' => [
                'id' => $context->getUserId(),
                'is_new' => $context->isNewUser(),
                'login_count' => $this->metrics->getLoginCount($context->getUserId()),
            ],
            'ip' => [
                'country' => $context->getIpCountry(),
                'is_proxy' => $context->isProxyIp(),
            ],
            'form' => [
                'submit_time' => $context->getFormSubmitTime(),
            ],
        ];
    }
    
    private function registerFunctions(): void
    {
        // 注册自定义函数：request_count(时间窗口)
        $this->expressionLanguage->register('request_count', 
            function ($time) {
                return sprintf('request_count("%s")', $time);
            },
            function ($arguments, $time) use ($context) {
                return $this->metrics->getRequestCount($context->getIp(), $time);
            }
        );
        
        // 注册更多实用函数...
    }
}
```

### 3.4 动作执行系统
```php
namespace Tourze\AntiFraudBundle\Rule\Action;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

interface ActionInterface
{
    public function execute(Request $request): ?Response;
    public function getName(): string;
}

/**
 * 记录日志（默认动作）
 */
class LogAction implements ActionInterface
{
    public function execute(Request $request): ?Response
    {
        // 仅记录，不影响请求
        return null;
    }
    
    public function getName(): string
    {
        return 'log';
    }
}

/**
 * 阻断请求
 */
class BlockAction implements ActionInterface
{
    private string $message;
    
    public function __construct(string $message = 'Access denied')
    {
        $this->message = $message;
    }
    
    public function execute(Request $request): ?Response
    {
        return new Response($this->message, 403);
    }
    
    public function getName(): string
    {
        return 'block';
    }
}

/**
 * 验证码挑战
 */
class ChallengeAction implements ActionInterface
{
    private ChallengeService $challengeService;
    
    public function execute(Request $request): ?Response
    {
        // 返回验证码页面
        return $this->challengeService->createChallengeResponse($request);
    }
    
    public function getName(): string
    {
        return 'challenge';
    }
}

/**
 * 限流
 */
class ThrottleAction implements ActionInterface
{
    private int $retryAfter;
    
    public function __construct(int $retryAfter = 60)
    {
        $this->retryAfter = $retryAfter;
    }
    
    public function execute(Request $request): ?Response
    {
        return new Response('Too Many Requests', 429, [
            'Retry-After' => $this->retryAfter
        ]);
    }
    
    public function getName(): string
    {
        return 'throttle';
    }
}
```

### 3.4 实时特征聚合
```php
namespace AntiFraud\Bundle\Feature;

class RealTimeAggregator
{
    private Redis $redis;
    private array $windows = [60, 300, 3600, 86400]; // 1分钟, 5分钟, 1小时, 1天
    
    public function aggregate(string $userId, string $metric, float $value): array
    {
        $timestamp = time();
        $aggregates = [];
        
        foreach ($this->windows as $window) {
            $key = $this->getWindowKey($userId, $metric, $window);
            
            // 使用Redis的时间序列功能
            $this->redis->zadd($key, $timestamp, $value);
            
            // 清理过期数据
            $this->redis->zremrangebyscore($key, 0, $timestamp - $window);
            
            // 计算聚合统计
            $aggregates[$window] = [
                'count' => $this->redis->zcard($key),
                'sum' => array_sum($this->redis->zrange($key, 0, -1)),
                'avg' => $this->calculateAverage($key),
                'max' => $this->redis->zrevrange($key, 0, 0, true)[0] ?? 0,
                'min' => $this->redis->zrange($key, 0, 0, true)[0] ?? 0
            ];
        }
        
        return $aggregates;
    }
## 四、零配置安装流程

### 4.1 Composer安装后自动配置
```json
{
    "name": "tourze/antifraud-bundle",
    "type": "symfony-bundle",
    "autoload": {
        "psr-4": {
            "Tourze\\AntiFraudBundle\\": "src/"
        }
    },
    "scripts": {
        "post-install-cmd": [
            "Tourze\\AntiFraudBundle\\Installer::postInstall"
        ],
        "post-update-cmd": [
            "Tourze\\AntiFraudBundle\\Installer::postUpdate"
        ]
    }
}
```

### 4.2 自动安装器
```php
namespace Tourze\AntiFraudBundle;

use Symfony\Component\Filesystem\Filesystem;

class Installer
{
    public static function postInstall(): void
    {
        $filesystem = new Filesystem();
        
        // 1. 自动注册Bundle到bundles.php
        $bundlesFile = 'config/bundles.php';
        if ($filesystem->exists($bundlesFile)) {
            $bundles = require $bundlesFile;
            if (!isset($bundles[TourzeAntiFraudBundle::class])) {
                $bundles[TourzeAntiFraudBundle::class] = ['all' => true];
                $content = "<?php\n\nreturn " . var_export($bundles, true) . ";\n";
                $filesystem->dumpFile($bundlesFile, $content);
            }
        }
        
        // 2. 创建默认环境变量
        $envFile = '.env';
        if ($filesystem->exists($envFile)) {
            $envContent = file_get_contents($envFile);
            if (!str_contains($envContent, 'ANTIFRAUD_ENABLED')) {
                $filesystem->appendToFile($envFile, "\n# Tourze AntiFraud Bundle\nANTIFRAUD_ENABLED=true\n");
            }
        }
        
        // 3. 运行数据库迁移
        echo "Please run 'php bin/console doctrine:migrations:migrate' to create required tables.\n";
        echo "AntiFraud Bundle installed successfully! Access admin panel at: /admin/antifraud\n";
    }
}
```

## 五、在Symfony项目中使用

### 5.1 完全自动化使用（推荐）

```bash
# 1. 安装
composer require tourze/antifraud-bundle

# 2. 运行迁移（仅此一步需要手动）
php bin/console doctrine:migrations:migrate

# 3. 完成！Bundle已经在保护你的应用
```

**自动保护的内容**：
- 所有登录尝试
- API调用频率
- 表单提交速度
- 可疑UserAgent
- 异常IP地址

### 5.2 查看管理界面

访问 `/admin/antifraud` 查看：
- 实时检测统计
- 触发的规则
- 风险日志
- 规则管理

### 5.3 自定义规则（可选）

虽然默认规则已经覆盖大部分场景，但你可以通过管理界面添加自定义规则：

```
# 规则示例

# 1. 限制特定API的调用频率
条件: request.path = "/api/sensitive" AND request_count(1h) > 10
动作: block
优先级: 100

# 2. 检测批量注册
条件: request.path = "/register" AND ip.request_count(10m) > 3
动作: challenge
优先级: 90

# 3. 防止信用卡测试
条件: request.path = "/payment" AND payment.failed_count(5m) > 3
动作: block
优先级: 100
```

### 5.4 高级集成（可选）

如果需要在代码中获取检测结果：

```php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class PaymentController extends AbstractController
{
    #[Route('/payment/process', methods: ['POST'])]
    public function process(Request $request): Response
    {
        // 获取自动检测的结果
        $antifraudResult = $request->attributes->get('_antifraud_result');
        
        if ($antifraudResult && $antifraudResult->getRiskLevel() >= RiskLevel::HIGH) {
            // 高风险交易，需要额外验证
            return $this->json([
                'status' => 'verification_required',
                'message' => 'Additional verification needed'
            ]);
        }
        
        // 正常处理支付...
    }
}
```

### 5.5 环境变量配置（可选）

```bash
# .env
# 启用/禁用Bundle（默认启用）
ANTIFRAUD_ENABLED=true

# 自定义阈值（可选，有合理默认值）
ANTIFRAUD_LOGIN_RATE_LIMIT=5
ANTIFRAUD_API_RATE_LIMIT=100
ANTIFRAUD_MIN_FORM_TIME=2
```

## 六、技术实现路线图

### 第一阶段：核心框架（2周）
1. **Week 1**: 自动化框架
   - 实现自动事件监听
   - 开发数据收集器
   - 创建基础规则引擎

2. **Week 2**: 默认规则集
   - 实现内置规则
   - 开发动作系统
   - 创建缓存机制

### 第二阶段：管理界面（2周）
1. **Week 3**: 管理后台
   - Dashboard开发
   - 规则管理界面
   - 日志查看器

2. **Week 4**: 规则编辑器
   - DSL解析器
   - 规则测试工具
   - 实时预览

### 第三阶段：优化和完善（2周）
1. **Week 5**: 性能优化
   - 请求处理优化
   - 缓存策略优化
   - 数据库查询优化

2. **Week 6**: 测试和文档
   - 单元测试
   - 集成测试
   - 使用文档

## 七、总结

本技术方案提供了一个真正**零配置、自动化**的Symfony Bundle反作弊系统：

1. **极简接入**：一行命令安装，自动开始保护
2. **智能默认**：内置规则覆盖90%常见攻击
3. **灵活扩展**：通过Web界面随时调整规则
4. **性能优异**：对正常请求几乎无影响
5. **完全无感**：不需要修改任何业务代码

通过自动化设计理念，让开发者专注于业务开发，而安全防护静默运行在后台，真正实现了"安装即忘记"的使用体验。
                $this->bus->dispatch(new AlertMessage($result));
            }
            
            // 异步存储检测结果
            $this->bus->dispatch(
                new StoreResultMessage($result),
                [new DelayStamp(1000)] // 延迟1秒
            );
        } catch (\Exception $e) {
            // 错误处理
            throw new \RuntimeException('Detection failed: ' . $e->getMessage());
        }
    }
}
```

### 3.7 环境变量使用
```php
namespace Tourze\AntiFraudBundle\Service;

use Tourze\AntiFraudBundle\Model\UserBehavior;
use Tourze\AntiFraudBundle\Model\RiskAssessment;

class DetectionManager
{
    private RuleEngine $ruleEngine;
    private StatisticsEngine $statisticsEngine;
    private Redis $redis;
    
    public function __construct(
        RuleEngine $ruleEngine,
        StatisticsEngine $statisticsEngine
    ) {
        $this->ruleEngine = $ruleEngine;
        $this->statisticsEngine = $statisticsEngine;
        
        // 直接使用环境变量
        $this->redis = new Redis();
        $this->redis->connect(parse_url($_ENV['REDIS_DSN'], PHP_URL_HOST));
    }
    
    public function detect(string $userId, UserBehavior $behavior): RiskAssessment
    {
        // 检查是否启用
        if ($_ENV['ANTIFRAUD_ENABLED'] === 'false') {
            return new RiskAssessment(0, RiskLevel::LOW);
        }
        
        // 使用环境变量中的配置
        $timeout = (int)($_ENV['ANTIFRAUD_TIMEOUT'] ?? 100);
        $threshold = (float)($_ENV['ANTIFRAUD_THRESHOLD'] ?? 0.7);
        
        // 执行检测逻辑
        $context = new Context($userId, $behavior);
        
        // 执行规则和统计检测
        $ruleResult = $this->ruleEngine->evaluate($context);
        $statsResult = $this->statisticsEngine->analyze($behavior);
        
        // 融合结果
        return $this->fuseResults($ruleResult, $statsResult, $threshold);
    }
}

// 在统计引擎中直接使用环境变量
namespace Tourze\AntiFraudBundle\Engine\Statistics;

class ThresholdManager
{
    private array $thresholds;
    
    public function __construct()
    {
        $this->thresholds = [
            'click_rate' => (float)($_ENV['ANTIFRAUD_CLICK_RATE_THRESHOLD'] ?? 10),
            'request_rate' => (float)($_ENV['ANTIFRAUD_REQUEST_RATE_THRESHOLD'] ?? 100),
            'login_attempts' => (int)($_ENV['ANTIFRAUD_LOGIN_ATTEMPTS_THRESHOLD'] ?? 5),
        ];
    }
}
```

## 四、在Symfony项目中集成使用

### 4.1 安装Bundle

```bash
# 通过Composer安装
composer require your-vendor/antifraud-bundle

# 或者在composer.json中添加
{
    "require": {
        "your-vendor/antifraud-bundle": "^1.0"
    }
}
```

### 4.2 注册Bundle

```php
// config/bundles.php
return [
    // ... 其他bundles
    Tourze\AntiFraudBundle\TourzeAntiFraudBundle::class => ['all' => true],
];
```

### 4.3 配置环境变量

```bash
# .env
ANTIFRAUD_ENABLED=true
ANTIFRAUD_TIMEOUT=100
ANTIFRAUD_THRESHOLD=0.7
ANTIFRAUD_CLICK_RATE_THRESHOLD=10
ANTIFRAUD_REQUEST_RATE_THRESHOLD=100
ANTIFRAUD_LOGIN_ATTEMPTS_THRESHOLD=5
REDIS_DSN=redis://localhost:6379
DATABASE_URL=mysql://root:password@localhost:3306/antifraud
```

### 4.4 在控制器中使用

```php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Tourze\AntiFraudBundle\Service\DetectionManager;
use Tourze\AntiFraudBundle\Model\UserBehavior;
use Tourze\AntiFraudBundle\Model\RequestMetadata;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class UserController extends AbstractController
{
    public function __construct(
        private DetectionManager $detectionManager
    ) {}
    
    #[Route('/api/login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        $data = json_decode($request->getContent(), true);
        
        // 构建请求元数据
        $requestMetadata = new RequestMetadata(
            method: $request->getMethod(),
            path: $request->getPathInfo(),
            referer: $request->headers->get('referer'),
            headers: $request->headers->all(),
            responseTime: microtime(true) - $startTime
        );
        
        // 构建用户行为对象
        $behavior = new UserBehavior(
            userId: $data['username'],
            sessionId: $request->getSession()->getId(),
            ip: $request->getClientIp(),
            userAgent: $request->headers->get('User-Agent', ''),
            action: 'login'
        );
        $behavior->setRequestMetadata($requestMetadata);
        
        // 记录表单渲染到提交的时间（防机器人）
        if (isset($data['form_rendered_at'])) {
            $formTime = time() - $data['form_rendered_at'];
            $behavior->setMetadata(['form_submit_time' => $formTime]);
        }
        
        // 执行风险检测
        $riskAssessment = $this->detectionManager->detect(
            $data['username'],
            $behavior
        );
        
        // 根据风险等级处理
        if ($riskAssessment->getRiskLevel() === RiskLevel::HIGH) {
            // 需要额外验证
            return new JsonResponse([
                'status' => 'challenge_required',
                'challenge_type' => 'captcha'
            ], 403);
        }
        
        if ($riskAssessment->getRiskLevel() === RiskLevel::CRITICAL) {
            // 阻止登录
            return new JsonResponse([
                'status' => 'blocked',
                'reason' => 'suspicious_activity'
            ], 403);
        }
        
        // 正常登录流程...
    }
}
```

### 4.5 在事件监听器中使用

```php
namespace App\EventListener;

use Tourze\AntiFraudBundle\Service\DetectionManager;
use Tourze\AntiFraudBundle\Model\UserBehavior;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LoginListener implements EventSubscriberInterface
{
    public function __construct(
        private DetectionManager $detectionManager
    ) {}
    
    public static function getSubscribedEvents(): array
    {
        return [
            InteractiveLoginEvent::class => 'onLogin',
        ];
    }
    
    public function onLogin(InteractiveLoginEvent $event): void
    {
        $request = $event->getRequest();
        $user = $event->getAuthenticationToken()->getUser();
        
        $behavior = new UserBehavior(
            userId: $user->getId(),
            sessionId: $request->getSession()->getId(),
            ip: $request->getClientIp(),
            userAgent: $request->headers->get('User-Agent'),
            action: 'login'
        );
        
        $assessment = $this->detectionManager->detect(
            $user->getId(),
            $behavior
        );
        
        // 记录风险信息到session
        $request->getSession()->set('risk_level', $assessment->getRiskLevel()->getLabel());
    }
}
```

### 4.6 中间件集成

```php
namespace App\Middleware;

use Tourze\AntiFraudBundle\Service\DetectionManager;
use Tourze\AntiFraudBundle\Model\UserBehavior;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class AntiFraudMiddleware
{
    public function __construct(
        private DetectionManager $detectionManager
    ) {}
    
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        
        $request = $event->getRequest();
        
        // 跳过静态资源
        if (preg_match('/\.(js|css|png|jpg|gif|ico)$/i', $request->getPathInfo())) {
            return;
        }
        
        // 获取用户ID（如果已登录）
        $userId = $request->getSession()->get('user_id', 'anonymous');
        
        // 构建行为对象
        $behavior = new UserBehavior(
            userId: $userId,
            sessionId: $request->getSession()->getId(),
            ip: $request->getClientIp(),
            userAgent: $request->headers->get('User-Agent', ''),
            action: $request->getPathInfo()
        );
        
        // 添加额外的元数据
        $behavior->setMetadata([
            'method' => $request->getMethod(),
            'referer' => $request->headers->get('referer'),
            'timestamp' => microtime(true),
        ]);
        
        // 异步检测（不阻塞请求）
        $this->detectionManager->detectAsync($userId, $behavior);
    }
}
```

## 五、总结

本技术方案提供了一个完整的Symfony Bundle作弊行为识别系统设计，通过规则引擎与统计分析的结合，实现了高性能、可扩展、易维护的风险识别能力。系统具有以下特点：

1. **聚焦Symfony**：专为Symfony项目设计，深度集成框架特性
2. **检测全面**：覆盖账号、数据、操作三大异常类型
3. **技术实用**：基于HTTP请求分析，无需前端SDK
4. **性能优异**：支持毫秒级检测和万级QPS
5. **易于集成**：标准的Symfony Bundle架构

通过分阶段实施，可以在5个月内完成整个系统的开发，为Symfony应用提供强大的反作弊能力。