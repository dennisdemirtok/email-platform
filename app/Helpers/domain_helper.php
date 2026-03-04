<?php

use App\Models\DomainsModel;

define('DOMAIN_CACHE_TTL', 300); // 5 minutes

if (!function_exists('get_all_active_domains')) {
    /**
     * Get all active domains, cached in session for performance.
     * Previously made a Resend API call on every page load (~1-2s).
     * Now reads from session cache (0ms) and refreshes every 5 minutes.
     */
    function get_all_active_domains()
    {
        $session = session();
        $cached = $session->get('cached_domains');
        $cachedAt = $session->get('cached_domains_at');

        // Return cached data if still fresh
        if ($cached !== null && $cachedAt !== null && (time() - $cachedAt) < DOMAIN_CACHE_TTL) {
            return $cached;
        }

        // Fetch fresh data from Resend API
        try {
            $domainsModel = new \App\Models\DomainsModel();
            $domains = $domainsModel->getActiveDomains();
        } catch (\Exception $e) {
            log_message('error', 'Failed to fetch domains: ' . $e->getMessage());
            // Return stale cache if available, otherwise empty
            return $cached ?? [];
        }

        // Role-based domain filtering
        $role = $session->get('user_role') ?? 'super'; // Backwards compatible
        if ($role !== 'super') {
            $allowedIds = $session->get('allowed_domain_ids') ?? [];
            $domains = array_values(array_filter($domains, function ($d) use ($allowedIds) {
                return in_array($d['id'] ?? '', $allowedIds);
            }));
        }

        // Store in session
        $session->set('cached_domains', $domains);
        $session->set('cached_domains_at', time());

        return $domains;
    }
}

if (!function_exists('get_active_domain')) {
    /**
     * Get the currently active domain.
     * Previously called Resend verify API on every page load (~500-2000ms).
     * Now looks up the active domain in the cached domain list (0ms).
     */
    function get_active_domain()
    {
        $activeDomainId = get_cookie('active_domain_id');
        $allDomains = get_all_active_domains();

        if (empty($allDomains)) {
            return null;
        }

        // Re-index to ensure numeric keys start at 0
        $allDomains = array_values($allDomains);

        // If no active domain cookie set, use the first domain
        if (!$activeDomainId) {
            set_active_domain($allDomains[0]['id']);
            return $allDomains[0];
        }

        // Find the active domain in the cached list
        foreach ($allDomains as $domain) {
            if (($domain['id'] ?? '') == $activeDomainId) {
                return $domain;
            }
        }

        // If the active domain ID is not in the list, default to first
        set_active_domain($allDomains[0]['id']);
        return $allDomains[0];
    }
}

if (!function_exists('set_active_domain')) {
    function set_active_domain($domainId)
    {
        $cookie = [
            'name'     => 'active_domain_id',
            'value'    => $domainId,
            'expire'   => 86400,
            'path'     => '/',
            'secure'   => false,
            'httponly'  => true,
            'samesite' => 'Lax'
        ];

        set_cookie($cookie);
    }
}

if (!function_exists('invalidate_domain_cache')) {
    /**
     * Clear the domain cache so next page load fetches fresh data.
     * Call this after domain changes (import, edit, set active).
     */
    function invalidate_domain_cache()
    {
        $session = session();
        $session->remove('cached_domains');
        $session->remove('cached_domains_at');
    }
}
