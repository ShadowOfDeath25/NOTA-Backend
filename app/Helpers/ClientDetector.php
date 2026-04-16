<?php

namespace App\Helpers;

use Illuminate\Http\Request;

final class ClientDetector
{
    public const TYPE_MOBILE = 'mobile';

    public const TYPE_SPA = 'spa';

    public const HEADER_CLIENT_TYPE = 'X-Client-Type';

    public function __construct(
        private readonly Request $request
    ) {}

    public function detect(): string
    {
        if ($this->hasMobileHeader()) {
            return self::TYPE_MOBILE;
        }

        if ($this->isAuthenticatedViaBearerToken()) {
            return self::TYPE_MOBILE;
        }

        if ($this->isRequestFromStatefulDomain()) {
            return self::TYPE_SPA;
        }

        return self::TYPE_SPA;
    }

    public function isMobile(): bool
    {
        return $this->detect() === self::TYPE_MOBILE;
    }

    public function isSPA(): bool
    {
        return $this->detect() === self::TYPE_SPA;
    }

    private function hasMobileHeader(): bool
    {
        return $this->request->header(self::HEADER_CLIENT_TYPE) === self::TYPE_MOBILE;
    }

    private function isAuthenticatedViaBearerToken(): bool
    {
        return $this->request->bearerToken() !== null;
    }

    private function isRequestFromStatefulDomain(): bool
    {
        $origin = $this->request->header('Origin');
        $referer = $this->request->header('Referer');

        if (! $origin && ! $referer) {
            return false;
        }

        $statefulDomains = $this->getStatefulDomains();

        foreach ($statefulDomains as $domain) {
            $domain = $this->normalizeDomain($domain);

            if ($origin && $this->domainsMatch($origin, $domain)) {
                return true;
            }

            if ($referer && $this->domainsMatch($referer, $domain)) {
                return true;
            }
        }

        return false;
    }

    private function getStatefulDomains(): array
    {
        $domains = config('sanctum.stateful', []);
        $envDomains = env('SANCTUM_STATEFUL_DOMAINS', '');

        if ($envDomains) {
            $domains = array_merge($domains, explode(',', $envDomains));
        }

        return array_filter(array_unique($domains));
    }

    private function normalizeDomain(string $domain): string
    {
        $domain = str_replace(['https://', 'http://'], '', $domain);
        $domain = rtrim($domain, '/');
        $domain = parse_url('https://'.$domain, PHP_URL_HOST) ?: $domain;

        return strtolower($domain);
    }

    private function domainsMatch(string $url, string $domain): bool
    {
        $urlHost = parse_url('https://'.ltrim($url, '/'), PHP_URL_HOST);

        if (! $urlHost) {
            return false;
        }

        $urlHost = strtolower($urlHost);
        $domain = strtolower($domain);

        if ($urlHost === $domain) {
            return true;
        }

        if (str_ends_with($urlHost, '.'.$domain)) {
            return true;
        }

        return false;
    }

    public function getAcceptableClientTypes(): array
    {
        return [self::TYPE_MOBILE, self::TYPE_SPA];
    }
}
