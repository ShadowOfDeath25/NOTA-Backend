<?php

namespace App\Helpers;

use Illuminate\Http\Request;

final class ClientDetector
{
    public const TYPE_MOBILE = 'mobile';

    public const TYPE_SPA = 'spa';

    public const HEADER_CLIENT_TYPE = 'X-Client-Type';

    public function detect(?Request $request = null): string
    {
        $request ??= request();

        if ($this->hasMobileHeader($request)) {
            return self::TYPE_MOBILE;
        }

        if ($this->isAuthenticatedViaBearerToken($request)) {
            return self::TYPE_MOBILE;
        }

        if ($this->isRequestFromStatefulDomain($request)) {
            return self::TYPE_SPA;
        }

        return self::TYPE_SPA;
    }

    public function isMobile(?Request $request = null): bool
    {
        return $this->detect($request) === self::TYPE_MOBILE;
    }

    public function isSPA(?Request $request = null): bool
    {
        return $this->detect($request) === self::TYPE_SPA;
    }

    private function hasMobileHeader(Request $request): bool
    {
        return $request->header(self::HEADER_CLIENT_TYPE) === self::TYPE_MOBILE;
    }

    private function isAuthenticatedViaBearerToken(Request $request): bool
    {
        return $request->bearerToken() !== null;
    }

    private function isRequestFromStatefulDomain(Request $request): bool
    {
        $origin = $request->header('Origin');
        $referer = $request->header('Referer');

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
        return array_filter(array_unique(config('sanctum.stateful', [])));
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
