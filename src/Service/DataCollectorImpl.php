<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Service;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\AntiFraudBundle\Contract\Service\DataCollectorInterface;
use Tourze\AntiFraudBundle\Model\Context;
use Tourze\AntiFraudBundle\Model\UserBehavior;

#[Autoconfigure(public: true)]
readonly class DataCollectorImpl implements DataCollectorInterface
{
    public function __construct(
        private Security $security,
    ) {
    }

    public function collect(Request $request): Context
    {
        // Get user information
        $userId = $this->getUserId();
        $sessionId = $request->hasSession() ? $request->getSession()->getId() : 'no-session';

        // Extract request information
        $ip = $this->extractIp($request);
        $userAgent = $request->headers->get('User-Agent') ?? '';
        $action = $this->determineAction($request);

        // Create user behavior model
        $behavior = new UserBehavior(
            userId: $userId,
            sessionId: $sessionId,
            ip: $ip,
            userAgent: $userAgent,
            action: $action
        );

        // Create context
        $context = new Context($behavior, $request);

        // Enrich context with additional data
        $this->enrichContext($context, $request);

        return $context;
    }

    private function getUserId(): string
    {
        $token = $this->security->getToken();

        if (null !== $token && $token->getUser() instanceof UserInterface) {
            return $token->getUser()->getUserIdentifier();
        }

        return 'anonymous';
    }

    private function extractIp(Request $request): string
    {
        // Check for forwarded IP first
        $forwardedFor = $request->headers->get('X-Forwarded-For');
        if (null !== $forwardedFor) {
            /** @var string[] $ips */
            $ips = explode(',', $forwardedFor);

            return trim($ips[0]);
        }

        $realIp = $request->headers->get('X-Real-IP');
        if (null !== $realIp) {
            return $realIp;
        }

        return $request->getClientIp() ?? '127.0.0.1';
    }

    private function determineAction(Request $request): string
    {
        $path = $request->getPathInfo();

        // Extract meaningful action from path
        if (str_contains($path, 'login')) {
            return 'login';
        }

        if (str_contains($path, 'register')) {
            return 'register';
        }

        if (str_contains($path, 'logout')) {
            return 'logout';
        }

        if (str_starts_with($path, '/api/')) {
            return 'api_call';
        }

        return 'page_view';
    }

    private function enrichContext(Context $context, Request $request): void
    {
        // Add proxy detection
        $context->setAttribute('is_proxy', $this->isProxyRequest($request));

        // Add referer information
        $referer = $request->headers->get('Referer');
        if (null !== $referer) {
            $context->setAttribute('referer', $referer);
        }

        // Add form submission timing
        $formRenderedAt = $request->request->get('form_rendered_at');
        if (null !== $formRenderedAt && is_numeric($formRenderedAt)) {
            $submitTime = time() - (int) $formRenderedAt;
            $context->setAttribute('form_submit_time', $submitTime);
        }

        // Add request method
        $context->setAttribute('method', $request->getMethod());

        // Add user agent analysis
        $this->analyzeUserAgent($context, $request->headers->get('User-Agent') ?? '');
    }

    private function isProxyRequest(Request $request): bool
    {
        /** @var string[] $proxyHeaders */
        $proxyHeaders = [
            'X-Forwarded-For',
            'X-Real-IP',
            'X-Forwarded-Host',
            'X-Forwarded-Proto',
            'CF-Connecting-IP', // Cloudflare
            'True-Client-IP',   // Cloudflare Enterprise
        ];

        foreach ($proxyHeaders as $header) {
            if ($request->headers->has($header)) {
                return true;
            }
        }

        return false;
    }

    private function analyzeUserAgent(Context $context, string $userAgent): void
    {
        $isBot = (bool) preg_match('/(bot|crawler|spider|scraper)/i', $userAgent);
        $context->setAttribute('is_bot', $isBot);

        $isMobile = (bool) preg_match('/(mobile|android|iphone|ipad)/i', $userAgent);
        $context->setAttribute('is_mobile', $isMobile);
    }
}
