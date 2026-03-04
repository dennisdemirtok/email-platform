<?php

namespace App\Models;

use App\Libraries\SupabaseClient;

class AnalyticsModel
{
    private $supabase;

    function __construct()
    {
        $this->supabase = new SupabaseClient();
    }

    /**
     * Generate and store analytics for the active domain.
     *
     * Calls the RPC function get_analytics_by_domain to compute campaign-level
     * metrics, deletes any previously stored analytics for this domain,
     * then inserts the fresh snapshot.
     */
    function insertAnalytics()
    {
        try {
            $activeDomain = get_active_domain();
            $domainId = $activeDomain ? $activeDomain['id'] : null;

            if (!$domainId) {
                return;
            }

            // Compute analytics via the PostgreSQL function
            $analyticsData = $this->supabase->rpc('get_analytics_by_domain', [
                'p_domain_id' => $domainId,
            ]);

            // Delete previous analytics rows for this domain
            $this->supabase->delete('analytics', [
                'domain_id' => 'eq.' . $domainId,
            ]);

            // Store the new snapshot
            $this->supabase->insert('analytics', [
                'domain_id'    => $domainId,
                'data'         => json_encode($analyticsData),
                'generated_at' => date('c'),
            ]);
        } catch (\Exception $ex) {
            log_message('error', 'Error while generating analytics: ' . $ex->getMessage());
            return false;
        }
    }

    /**
     * Retrieve the most recent analytics snapshot for a domain.
     *
     * Returns ['generated_at' => ..., 'data' => [...]] or null.
     */
    function getAnalyticsByDomain($domainId = null)
    {
        if ($domainId === null) {
            $activeDomain = get_active_domain();
            $domainId = $activeDomain ? $activeDomain['id'] : null;
        }

        try {
            $row = $this->supabase->select('analytics', [
                'domain_id' => 'eq.' . $domainId,
                'order'     => 'generated_at.desc',
                'limit'     => 1,
            ], true);

            if (!$row) {
                return null;
            }

            // Decode the JSONB data column
            $data = $row['data'];
            if (is_string($data)) {
                $data = json_decode($data, true);
            }

            return [
                'generated_at' => $row['generated_at'],
                'data'         => $data,
            ];
        } catch (\Exception $ex) {
            log_message('error', 'Error while fetching analytics: ' . $ex->getMessage());
            return null;
        }
    }
}
