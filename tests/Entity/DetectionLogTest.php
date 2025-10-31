<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\AntiFraudBundle\Entity\DetectionLog;
use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(DetectionLog::class)]
final class DetectionLogTest extends AbstractEntityTestCase
{
    /**
     * 创建被测实体的实例
     */
    protected function createEntity(): object
    {
        return new DetectionLog();
    }

    /**
     * 提供属性及其样本值的 Data Provider
     */
    /**
     * @return iterable<array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'userId' => ['userId', 'user123'];
        yield 'sessionId' => ['sessionId', 'sess123'];
        yield 'ipAddress' => ['ipAddress', '192.168.1.1'];
        yield 'userAgent' => ['userAgent', 'Mozilla/5.0 Test'];
        yield 'action' => ['action', 'login'];
        yield 'riskLevel' => ['riskLevel', RiskLevel::HIGH];
        yield 'riskScore' => ['riskScore', 0.75];
        yield 'matchedRules' => ['matchedRules', ['rule1' => 'value1', 'rule2' => 'value2']];
        yield 'detectionDetails' => ['detectionDetails', ['detail1' => 'value1', 'detail2' => 'value2']];
        yield 'actionTaken' => ['actionTaken', 'block'];
        yield 'actionDetails' => ['actionDetails', ['reason' => 'high_risk']];
        yield 'requestPath' => ['requestPath', '/api/login'];
        yield 'requestMethod' => ['requestMethod', 'POST'];
        yield 'requestHeaders' => ['requestHeaders', ['User-Agent' => 'Test', 'Accept' => 'application/json']];
        yield 'countryCode' => ['countryCode', 'US'];
        yield 'responseTime' => ['responseTime', 150];
    }

    public function testConstructorSetsDefaultValues(): void
    {
        $log = new DetectionLog();

        $this->assertInstanceOf(\DateTimeImmutable::class, $log->getCreatedAt());
        $this->assertNull($log->getId());
        $this->assertNull($log->getUserAgent());
        $this->assertNull($log->getActionTaken());
        $this->assertNull($log->getActionDetails());
        $this->assertNull($log->getRequestPath());
        $this->assertNull($log->getRequestMethod());
        $this->assertNull($log->getRequestHeaders());
        $this->assertNull($log->getCountryCode());
        $this->assertNull($log->getResponseTime());
        $this->assertFalse($log->isProxy());
        $this->assertFalse($log->isBot());
        $this->assertEquals([], $log->getMatchedRules());
        $this->assertEquals([], $log->getDetectionDetails());
    }

    public function testUserIdGetterAndSetter(): void
    {
        $log = new DetectionLog();
        $userId = 'user123';

        $log->setUserId($userId);

        $this->assertEquals($userId, $log->getUserId());
    }

    public function testSessionIdGetterAndSetter(): void
    {
        $log = new DetectionLog();
        $sessionId = 'sess123';

        $log->setSessionId($sessionId);

        $this->assertEquals($sessionId, $log->getSessionId());
    }

    public function testIpAddressGetterAndSetter(): void
    {
        $log = new DetectionLog();
        $ipAddress = '192.168.1.1';

        $log->setIpAddress($ipAddress);

        $this->assertEquals($ipAddress, $log->getIpAddress());
    }

    public function testUserAgentGetterAndSetter(): void
    {
        $log = new DetectionLog();
        $userAgent = 'Mozilla/5.0 Test';

        $log->setUserAgent($userAgent);

        $this->assertEquals($userAgent, $log->getUserAgent());
    }

    public function testActionGetterAndSetter(): void
    {
        $log = new DetectionLog();
        $action = 'login';

        $log->setAction($action);

        $this->assertEquals($action, $log->getAction());
    }

    public function testRiskLevelGetterAndSetter(): void
    {
        $log = new DetectionLog();
        $riskLevel = RiskLevel::HIGH;

        $log->setRiskLevel($riskLevel);

        $this->assertEquals($riskLevel, $log->getRiskLevel());
    }

    public function testRiskScoreGetterAndSetter(): void
    {
        $log = new DetectionLog();
        $riskScore = 0.75;

        $log->setRiskScore($riskScore);

        $this->assertEquals($riskScore, $log->getRiskScore());
    }

    public function testMatchedRulesGetterAndSetter(): void
    {
        $log = new DetectionLog();
        $matchedRules = ['rule1' => 'value1', 'rule2' => 'value2'];

        $log->setMatchedRules($matchedRules);

        $this->assertEquals($matchedRules, $log->getMatchedRules());
    }

    public function testDetectionDetailsGetterAndSetter(): void
    {
        $log = new DetectionLog();
        $detectionDetails = ['detail1' => 'value1', 'detail2' => 'value2'];

        $log->setDetectionDetails($detectionDetails);

        $this->assertEquals($detectionDetails, $log->getDetectionDetails());
    }

    public function testActionTakenGetterAndSetter(): void
    {
        $log = new DetectionLog();
        $actionTaken = 'block';

        $log->setActionTaken($actionTaken);

        $this->assertEquals($actionTaken, $log->getActionTaken());
    }

    public function testActionDetailsGetterAndSetter(): void
    {
        $log = new DetectionLog();
        $actionDetails = ['reason' => 'high_risk'];

        $log->setActionDetails($actionDetails);

        $this->assertEquals($actionDetails, $log->getActionDetails());
    }

    public function testRequestPathGetterAndSetter(): void
    {
        $log = new DetectionLog();
        $requestPath = '/api/login';

        $log->setRequestPath($requestPath);

        $this->assertEquals($requestPath, $log->getRequestPath());
    }

    public function testRequestMethodGetterAndSetter(): void
    {
        $log = new DetectionLog();
        $requestMethod = 'POST';

        $log->setRequestMethod($requestMethod);

        $this->assertEquals($requestMethod, $log->getRequestMethod());
    }

    public function testRequestHeadersGetterAndSetter(): void
    {
        $log = new DetectionLog();
        $requestHeaders = ['User-Agent' => 'Test', 'Accept' => 'application/json'];

        $log->setRequestHeaders($requestHeaders);

        $this->assertEquals($requestHeaders, $log->getRequestHeaders());
    }

    public function testCountryCodeGetterAndSetter(): void
    {
        $log = new DetectionLog();
        $countryCode = 'US';

        $log->setCountryCode($countryCode);

        $this->assertEquals($countryCode, $log->getCountryCode());
    }

    public function testIsProxyGetterAndSetter(): void
    {
        $log = new DetectionLog();

        $log->setIsProxy(true);

        $this->assertTrue($log->isProxy());
    }

    public function testIsBotGetterAndSetter(): void
    {
        $log = new DetectionLog();

        $log->setIsBot(true);

        $this->assertTrue($log->isBot());
    }

    public function testResponseTimeGetterAndSetter(): void
    {
        $log = new DetectionLog();
        $responseTime = 150;

        $log->setResponseTime($responseTime);

        $this->assertEquals($responseTime, $log->getResponseTime());
    }
}
