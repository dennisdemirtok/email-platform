<?php

namespace App\Models;

use App\Libraries\SupabaseClient;
use App\Models\CampaignsModel;

class EmailEventsModel
{
    private $supabase;

    function __construct()
    {
        $this->supabase = new SupabaseClient();
    }

    /**
     * Get recent email events that have a campaign_id, ordered by event date descending.
     */
    function getEmailEvents($limit = 50)
    {
        try {
            $rows = $this->supabase->select('email_events', [
                'campaign_id' => 'not.is.null',
                'order'       => 'event_created_at.desc',
                'limit'       => $limit,
            ]);

            return $rows;
        } catch (\Exception $ex) {
            log_message('error', 'Error while fetching emailEvents: ' . $ex->getMessage());
            return [];
        }
    }

    /**
     * Get a single email event by ID.
     */
    function getEmailEvent($id)
    {
        try {
            return $this->supabase->select('email_events', ['id' => 'eq.' . $id], true);
        } catch (\Exception $ex) {
            log_message('error', 'Error while fetching emailEvent with ID: ' . $id . ' ' . $ex->getMessage());
            return null;
        }
    }

    /**
     * Get the count of unique contacted email addresses.
     * Uses the PostgreSQL RPC function get_unique_contacts_sum().
     */
    function getUniqueContactsSum()
    {
        try {
            $result = $this->supabase->rpc('get_unique_contacts_sum');

            if (is_array($result) && isset($result[0]['totalContacts'])) {
                return (int) $result[0]['totalContacts'];
            }
            if (is_array($result) && isset($result[0]['totalcontacts'])) {
                return (int) $result[0]['totalcontacts'];
            }

            return 0;
        } catch (\Exception $ex) {
            log_message('error', 'Error while fetching unique contacts sum: ' . $ex->getMessage());
            return null;
        }
    }

    /**
     * Get total event counts grouped by event type.
     * Uses the PostgreSQL RPC function get_total_per_event_type().
     * Returns array of [{eventType, count}].
     */
    function getTotalPerEventType()
    {
        try {
            $result = $this->supabase->rpc('get_total_per_event_type');

            // Normalize key casing — PostgreSQL may return lowercase keys
            $normalized = [];
            if (is_array($result)) {
                foreach ($result as $row) {
                    $normalized[] = [
                        'eventType' => $row['eventType'] ?? $row['eventtype'] ?? null,
                        'count'     => (int) ($row['count'] ?? 0),
                    ];
                }
            }

            return $normalized;
        } catch (\Exception $ex) {
            log_message('error', 'Error while fetching total per event type: ' . $ex->getMessage());
            return [];
        }
    }

    /**
     * Get campaign-level analytics.
     * Uses the campaign_analytics_view (SQL view) or rpc get_analytics_by_domain.
     */
    function getAnalytics()
    {
        try {
            $activeDomain = get_active_domain();
            $domainId = $activeDomain ? $activeDomain['id'] : null;

            if ($domainId) {
                return $this->supabase->rpc('get_analytics_by_domain', [
                    'p_domain_id' => $domainId,
                ]);
            }

            // Fallback: query the view directly
            return $this->supabase->select('campaign_analytics_view');
        } catch (\Exception $ex) {
            log_message('error', 'Error while fetching analytics: ' . $ex->getMessage());
            return [];
        }
    }

    /**
     * Get email events grouped by unique recipient email.
     * Uses the PostgreSQL RPC function get_events_grouped_by_mail().
     * Returns array of [{_id, events}].
     */
    public function getEventsGroupedByUniqueMail()
    {
        try {
            return $this->supabase->rpc('get_events_grouped_by_mail');
        } catch (\Exception $ex) {
            log_message('error', 'Error while fetching events grouped by mail: ' . $ex->getMessage());
            return [];
        }
    }

    /**
     * Import events fetched from the Resend API.
     * Converts each Resend event into a flat email_events row, checks for duplicates,
     * and batch-inserts new events.
     */
    public function insertResendEvents($resendEvents, $campaignId)
    {
        try {
            $campaign = (new CampaignsModel())->getCampaign($campaignId);
            if (!$campaign) {
                throw new \Exception("Campaign not found with ID: $campaignId");
            }

            $insertedCount = 0;
            $skippedCount = 0;
            $errors = [];

            // Build a list of email_id + event_type combos to check for duplicates
            $emailIdsToCheck = [];
            foreach ($resendEvents as $event) {
                $eventType = 'email.' . $event['last_event'];
                $emailIdsToCheck[] = $event['id'] . '::' . $eventType;
            }

            // Fetch existing events matching this campaign to detect duplicates
            $existingEvents = [];
            if (!empty($emailIdsToCheck)) {
                $rows = $this->supabase->select('email_events', [
                    'campaign_id' => 'eq.' . $campaignId,
                    'select'      => 'email_id,event_type',
                ]);
                foreach ($rows as $row) {
                    $key = ($row['email_id'] ?? '') . '::' . ($row['event_type'] ?? '');
                    $existingEvents[$key] = true;
                }
            }

            // Process each Resend event
            $newRows = [];
            foreach ($resendEvents as $event) {
                $eventType = 'email.' . $event['last_event'];
                $key = $event['id'] . '::' . $eventType;

                if (isset($existingEvents[$key])) {
                    $skippedCount++;
                    continue;
                }

                try {
                    $newRows[] = $this->convertResendToRow($event, $campaignId);
                    $insertedCount++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'email_id' => $event['id'],
                        'error'    => $e->getMessage(),
                    ];
                }
            }

            // Batch insert all new events
            if (!empty($newRows)) {
                $this->supabase->insert('email_events', $newRows);
            }

            return [
                'success'        => true,
                'inserted_count' => $insertedCount,
                'skipped_count'  => $skippedCount,
                'errors'         => $errors,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Convert a Resend API email object to a flat email_events row.
     *
     * Resend format:
     *   id, last_event, created_at, subject, to[], from, headers[], tags[]
     *
     * Target columns:
     *   event_type, event_created_at, email_id, from_address, subject,
     *   recipient, campaign_id, raw_headers (JSONB), raw_tags (JSONB)
     */
    private function convertResendToRow(array $resendEmail, string $campaignId): array
    {
        $recipient = null;
        if (isset($resendEmail['to']) && is_array($resendEmail['to']) && !empty($resendEmail['to'])) {
            $recipient = $resendEmail['to'][0];
        }

        return [
            'event_type'       => 'email.' . ($resendEmail['last_event'] ?? 'unknown'),
            'event_created_at' => $resendEmail['created_at'] ?? date('c'),
            'email_id'         => $resendEmail['id'] ?? null,
            'from_address'     => $resendEmail['from'] ?? null,
            'subject'          => $resendEmail['subject'] ?? null,
            'recipient'        => $recipient,
            'campaign_id'      => $campaignId,
            'raw_headers'      => json_encode($resendEmail['headers'] ?? []),
            'raw_tags'         => json_encode($resendEmail['tags'] ?? [['campaign_id' => $campaignId]]),
        ];
    }
}
