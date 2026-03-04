<?php

namespace App\Models;

use App\Libraries\SupabaseClient;

class CampaignsModel
{
    private $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseClient();
    }

    /**
     * Map snake_case DB columns to camelCase keys expected by views.
     */
    private function mapCampaign(array $row): array
    {
        return [
            'id'                => $row['id'] ?? null,
            'name'              => $row['name'] ?? null,
            'subject'           => $row['subject'] ?? null,
            'status'            => $row['status'] ?? null,
            'templateHTML'      => $row['template_html'] ?? null,
            'templatePlainText' => $row['template_plain_text'] ?? null,
            'templateTitle'     => $row['template_title'] ?? null,
            'grapesJSData'      => $row['grapes_js_data'] ?? null,
            'domain_id'         => $row['domain_id'] ?? null,
            'created_at'        => $row['created_at'] ?? null,
            'sent_at'           => $row['sent_at'] ?? null,
            'updated_at'        => $row['updated_at'] ?? null,
        ];
    }

    public function insertCampaign($data)
    {
        try {
            $result = $this->supabase->insert('email_campaigns', $data);

            if (is_array($result) && isset($result[0]['id'])) {
                return $result[0]['id'];
            } elseif (is_array($result) && isset($result['id'])) {
                return $result['id'];
            }

            return false;
        } catch (\Exception $ex) {
            log_message('error', 'Error while inserting campaign: ' . $ex->getMessage());
            return false;
        }
    }

    public function updateCampaign($id, $data)
    {
        try {
            // Extract audiences if present
            $audienceIds = null;
            if (isset($data['audiences'])) {
                $audienceIds = $data['audiences'];
                unset($data['audiences']);
            }

            if (!empty($data)) {
                $this->supabase->update('email_campaigns', ['id' => 'eq.' . $id], $data);
            }

            // Update audience associations if provided
            if ($audienceIds !== null) {
                $this->setCampaignAudiences($id, $audienceIds);
            }

            return true;
        } catch (\Exception $ex) {
            log_message('error', 'Error while updating campaign: ' . $ex->getMessage());
            return false;
        }
    }

    public function deleteCampaign($id)
    {
        try {
            // Delete junction table entries first
            $this->supabase->delete('campaign_audiences', ['campaign_id' => 'eq.' . $id]);
            // Delete the campaign
            $this->supabase->delete('email_campaigns', ['id' => 'eq.' . $id]);
            return true;
        } catch (\Exception $ex) {
            log_message('error', 'Error while deleting campaign: ' . $ex->getMessage());
            return false;
        }
    }

    public function getCampaign($id)
    {
        try {
            $row = $this->supabase->select('email_campaigns', ['id' => 'eq.' . $id], true);

            if (!$row) {
                return null;
            }

            $campaign = $this->mapCampaign($row);

            // Fetch associated audience IDs from junction table
            $junctionRows = $this->supabase->select('campaign_audiences', [
                'campaign_id' => 'eq.' . $id,
                'select'      => 'audience_id',
            ]);

            $audiences = [];
            foreach ($junctionRows as $jr) {
                $audiences[] = $jr['audience_id'];
            }
            $campaign['audiences'] = $audiences;

            return $campaign;
        } catch (\Exception $ex) {
            log_message('error', 'Error while fetching campaign: ' . $ex->getMessage());
            return null;
        }
    }

    public function getAllCampaigns()
    {
        try {
            $rows = $this->supabase->select('email_campaigns', [
                'order' => 'created_at.asc',
            ]);

            return array_map([$this, 'mapCampaign'], $rows);
        } catch (\Exception $ex) {
            log_message('error', 'Error while fetching all campaigns: ' . $ex->getMessage());
            return [];
        }
    }

    public function getCampaignsByDomain($domainId = null)
    {
        if ($domainId === null) {
            $activeDomain = get_active_domain();
            $domainId = $activeDomain ? $activeDomain['id'] : null;
        }

        try {
            $params = [];
            if ($domainId) {
                $params['domain_id'] = 'eq.' . $domainId;
            }

            $rows = $this->supabase->select('email_campaigns', $params);

            return array_map([$this, 'mapCampaign'], $rows);
        } catch (\Exception $ex) {
            log_message('error', 'Error while fetching campaigns by domain: ' . $ex->getMessage());
            return [];
        }
    }

    /**
     * Replace all audience associations for a campaign.
     * Deletes existing junction rows then inserts new ones.
     */
    public function setCampaignAudiences($campaignId, array $audienceIds)
    {
        try {
            // Remove existing associations
            $this->supabase->delete('campaign_audiences', ['campaign_id' => 'eq.' . $campaignId]);

            if (empty($audienceIds)) {
                return;
            }

            // Build batch of junction rows
            $rows = [];
            foreach ($audienceIds as $audienceId) {
                $rows[] = [
                    'campaign_id' => $campaignId,
                    'audience_id' => $audienceId,
                ];
            }

            $this->supabase->insert('campaign_audiences', $rows);
        } catch (\Exception $ex) {
            log_message('error', 'Error while setting campaign audiences: ' . $ex->getMessage());
            throw $ex; // Re-throw so calling code can handle it
        }
    }

    // ── campaign_sends tracking ──────────────────────────────────

    /**
     * Insert a single campaign_send row after a successful email send.
     */
    public function insertCampaignSend(array $data): array
    {
        return $this->supabase->insert('campaign_sends', $data);
    }

    /**
     * Get all campaign_sends rows for a given campaign.
     */
    public function getCampaignSends(string $campaignId): array
    {
        try {
            return $this->supabase->select('campaign_sends', [
                'campaign_id' => 'eq.' . $campaignId,
            ]);
        } catch (\Exception $ex) {
            log_message('error', 'Error fetching campaign_sends: ' . $ex->getMessage());
            return [];
        }
    }

    /**
     * Update a campaign_send row (e.g. set delivered_at, opened_at, etc.).
     */
    public function updateCampaignSend(string $id, array $data): bool
    {
        try {
            $this->supabase->update('campaign_sends', ['id' => 'eq.' . $id], $data);
            return true;
        } catch (\Exception $ex) {
            log_message('error', 'Error updating campaign_send: ' . $ex->getMessage());
            return false;
        }
    }

    /**
     * Get aggregated stats for a single campaign from campaign_sends.
     * Returns: total, delivered, opened, clicked, bounced + rates.
     */
    public function getCampaignStats(string $campaignId): array
    {
        $sends = $this->getCampaignSends($campaignId);
        $total = count($sends);

        $delivered = 0;
        $opened = 0;
        $clicked = 0;
        $bounced = 0;

        foreach ($sends as $s) {
            if (!empty($s['delivered_at'])) $delivered++;
            if (!empty($s['opened_at']))    $opened++;
            if (!empty($s['clicked_at']))   $clicked++;
            if (!empty($s['bounced_at']))   $bounced++;
        }

        return [
            'total'        => $total,
            'delivered'    => $delivered,
            'opened'       => $opened,
            'clicked'      => $clicked,
            'bounced'      => $bounced,
            'deliveryRate' => $total > 0 ? round(($delivered / $total) * 100, 2) : 0,
            'openRate'     => $total > 0 ? round(($opened / $total) * 100, 2) : 0,
            'clickRate'    => $total > 0 ? round(($clicked / $total) * 100, 2) : 0,
            'bounceRate'   => $total > 0 ? round(($bounced / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Get dashboard-level stats across ALL campaigns for the active domain.
     * Counts total delivered / opened / clicked across all campaign_sends
     * where campaign_id belongs to the active domain.
     */
    public function getDashboardStats(?string $domainId = null): array
    {
        if ($domainId === null) {
            $activeDomain = get_active_domain();
            $domainId = $activeDomain ? $activeDomain['id'] : null;
        }

        $stats = [
            'totalSent'      => 0,
            'totalDelivered'  => 0,
            'totalOpened'     => 0,
            'totalClicked'    => 0,
            'totalBounced'    => 0,
        ];

        // Get all campaigns for this domain
        $campaigns = $this->getCampaignsByDomain($domainId);
        $campaignIds = array_map(fn($c) => $c['id'], $campaigns);

        if (empty($campaignIds)) {
            return $stats;
        }

        // Fetch campaign_sends for all campaigns
        foreach ($campaignIds as $cid) {
            $sends = $this->getCampaignSends($cid);
            foreach ($sends as $s) {
                $stats['totalSent']++;
                if (!empty($s['delivered_at'])) $stats['totalDelivered']++;
                if (!empty($s['opened_at']))    $stats['totalOpened']++;
                if (!empty($s['clicked_at']))   $stats['totalClicked']++;
                if (!empty($s['bounced_at']))   $stats['totalBounced']++;
            }
        }

        return $stats;
    }

    // ── Saved email templates ──────────────────────────────────

    /**
     * Get all saved templates for the active domain (from DB).
     */
    public function getTemplatesWithContent(?string $domainId = null): array
    {
        if ($domainId === null) {
            $activeDomain = get_active_domain();
            $domainId = $activeDomain ? $activeDomain['id'] : null;
        }

        try {
            $params = ['order' => 'created_at.desc'];
            if ($domainId) {
                $params['domain_id'] = 'eq.' . $domainId;
            }

            $rows = $this->supabase->select('email_templates', $params);

            return array_map(function ($row) {
                return [
                    'id'      => $row['id'] ?? null,
                    'title'   => $row['name'] ?? 'Untitled',
                    'content' => $row['html'] ?? '',
                    'created_at' => $row['created_at'] ?? null,
                ];
            }, $rows);
        } catch (\Exception $ex) {
            log_message('error', 'Error fetching templates: ' . $ex->getMessage());
            return [];
        }
    }

    /**
     * Save a new template to the DB.
     */
    public function saveTemplate(array $data): ?string
    {
        try {
            $result = $this->supabase->insert('email_templates', $data);
            if (is_array($result) && isset($result[0]['id'])) {
                return $result[0]['id'];
            }
            if (is_array($result) && isset($result['id'])) {
                return $result['id'];
            }
            return null;
        } catch (\Exception $ex) {
            log_message('error', 'Error saving template: ' . $ex->getMessage());
            return null;
        }
    }

    /**
     * Delete a saved template.
     */
    public function deleteTemplate(string $id): bool
    {
        try {
            $this->supabase->delete('email_templates', ['id' => 'eq.' . $id]);
            return true;
        } catch (\Exception $ex) {
            log_message('error', 'Error deleting template: ' . $ex->getMessage());
            return false;
        }
    }
}
