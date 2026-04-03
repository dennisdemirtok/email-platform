<?php

namespace App\Models;

use App\Libraries\SupabaseClient;

class CrmModel
{
    private $supabase;

    function __construct()
    {
        $this->supabase = new SupabaseClient();
    }

    /**
     * Get all CRM contacts for the active domain, optionally filtered by category.
     */
    public function getCrmContacts(?string $category = null, ?string $search = null): array
    {
        try {
            helper('domain');
            $activeDomain = get_active_domain();
            $domainId = $activeDomain ? $activeDomain['id'] : null;
            if (!$domainId) return [];

            $params = [
                'domain_id' => 'eq.' . $domainId,
                'select'    => '*, contacts(id, email, first_name, last_name, subscribed)',
                'order'     => 'company_name.asc',
            ];

            if ($category) {
                $params['category'] = 'eq.' . $category;
            }

            $rows = $this->supabase->select('crm_data', $params);

            // Flatten contacts data into each row
            $result = [];
            foreach ($rows as $row) {
                $contact = $row['contacts'] ?? [];
                unset($row['contacts']);
                $row['contact_email'] = $contact['email'] ?? '';
                $row['first_name'] = $contact['first_name'] ?? '';
                $row['last_name'] = $contact['last_name'] ?? '';
                $row['subscribed'] = $contact['subscribed'] ?? false;

                // Client-side search filter
                if ($search) {
                    $haystack = strtolower(
                        ($row['company_name'] ?? '') . ' ' .
                        ($row['contact_person'] ?? '') . ' ' .
                        ($row['contact_email'] ?? '') . ' ' .
                        ($row['notes'] ?? '')
                    );
                    if (strpos($haystack, strtolower($search)) === false) {
                        continue;
                    }
                }

                $result[] = $row;
            }

            return $result;
        } catch (\Exception $ex) {
            log_message('error', 'CRM getCrmContacts error: ' . $ex->getMessage());
            return [];
        }
    }

    /**
     * Get CRM data for a specific contact.
     */
    public function getCrmDataByContactId(string $contactId): ?array
    {
        try {
            helper('domain');
            $activeDomain = get_active_domain();
            $domainId = $activeDomain ? $activeDomain['id'] : null;
            if (!$domainId) return null;

            return $this->supabase->select('crm_data', [
                'contact_id' => 'eq.' . $contactId,
                'domain_id'  => 'eq.' . $domainId,
            ], true);
        } catch (\Exception $ex) {
            log_message('error', 'CRM getCrmDataByContactId error: ' . $ex->getMessage());
            return null;
        }
    }

    /**
     * Insert or update CRM data for a contact.
     */
    public function upsertCrmData(string $contactId, array $data): bool
    {
        try {
            helper('domain');
            $activeDomain = get_active_domain();
            $domainId = $activeDomain ? $activeDomain['id'] : null;
            if (!$domainId) return false;

            $data['updated_at'] = date('c');

            // Try update first
            $existing = $this->supabase->select('crm_data', [
                'contact_id' => 'eq.' . $contactId,
                'domain_id'  => 'eq.' . $domainId,
            ], true);

            if ($existing) {
                $this->supabase->update('crm_data', ['id' => 'eq.' . $existing['id']], $data);
                return true;
            }

            // Insert
            $data['contact_id'] = $contactId;
            $data['domain_id'] = $domainId;
            $this->supabase->insert('crm_data', $data);
            return true;
        } catch (\Exception $ex) {
            log_message('error', 'CRM upsertCrmData error: ' . $ex->getMessage());
            return false;
        }
    }

    /**
     * Get distinct categories for the active domain.
     */
    public function getCategories(): array
    {
        try {
            helper('domain');
            $activeDomain = get_active_domain();
            $domainId = $activeDomain ? $activeDomain['id'] : null;
            if (!$domainId) return [];

            $rows = $this->supabase->select('crm_data', [
                'domain_id' => 'eq.' . $domainId,
                'select'    => 'category',
            ]);

            $categories = [];
            foreach ($rows as $row) {
                $cat = trim($row['category'] ?? '');
                if ($cat !== '' && !in_array($cat, $categories)) {
                    $categories[] = $cat;
                }
            }
            sort($categories);
            return $categories;
        } catch (\Exception $ex) {
            log_message('error', 'CRM getCategories error: ' . $ex->getMessage());
            return [];
        }
    }

    /**
     * Get email history for a contact (individual CRM emails).
     */
    public function getCrmEmailHistory(string $contactId): array
    {
        try {
            return $this->supabase->select('crm_emails', [
                'contact_id' => 'eq.' . $contactId,
                'order'      => 'sent_at.desc',
            ]);
        } catch (\Exception $ex) {
            log_message('error', 'CRM getCrmEmailHistory error: ' . $ex->getMessage());
            return [];
        }
    }

    /**
     * Get campaign email history for a contact email.
     */
    public function getCampaignEmailHistory(string $contactEmail): array
    {
        try {
            return $this->supabase->select('campaign_sends', [
                'contact_email' => 'eq.' . $contactEmail,
                'order'         => 'created_at.desc',
            ]);
        } catch (\Exception $ex) {
            log_message('error', 'CRM getCampaignEmailHistory error: ' . $ex->getMessage());
            return [];
        }
    }

    /**
     * Log a sent CRM email.
     */
    public function insertCrmEmail(array $data): ?string
    {
        try {
            $result = $this->supabase->insert('crm_emails', $data);
            if (is_array($result) && isset($result[0]['id'])) {
                return $result[0]['id'];
            }
            if (is_array($result) && isset($result['id'])) {
                return $result['id'];
            }
            return null;
        } catch (\Exception $ex) {
            log_message('error', 'CRM insertCrmEmail error: ' . $ex->getMessage());
            return null;
        }
    }

    /**
     * Bulk import CRM data. Creates contacts if needed.
     */
    public function importBulkCrmData(array $rows, string $domainId): array
    {
        $contactsModel = new ContactsModel();
        $created = 0;
        $updated = 0;
        $errors = [];

        foreach ($rows as $i => $row) {
            $email = trim($row['email'] ?? '');
            if (empty($email) || strpos($email, '@') === false) {
                continue; // Skip rows without valid email
            }

            try {
                // Find or create contact
                $contact = $contactsModel->getContactByEmail($email);
                if (!$contact) {
                    $contactId = $contactsModel->insertContact([
                        'email'      => $email,
                        'first_name' => $row['contact_person'] ?? '',
                        'last_name'  => '',
                        'subscribed' => true,
                        'domain_id'  => $domainId,
                    ]);
                } else {
                    $contactId = $contact['id'];
                }

                if (empty($contactId)) {
                    $errors[] = "Row {$i}: Could not create contact for {$email}";
                    continue;
                }

                // Check if CRM data already exists
                $existing = $this->supabase->select('crm_data', [
                    'contact_id' => 'eq.' . $contactId,
                    'domain_id'  => 'eq.' . $domainId,
                ], true);

                $crmRow = [
                    'company_name'   => $row['company_name'] ?? '',
                    'contact_person' => $row['contact_person'] ?? '',
                    'category'       => $row['category'] ?? '',
                    'needs'          => $row['needs'] ?? '',
                    'last_contact'   => $row['last_contact'] ?? '',
                    'notes'          => $row['notes'] ?? '',
                    'updated_at'     => date('c'),
                ];

                if ($existing) {
                    $this->supabase->update('crm_data', ['id' => 'eq.' . $existing['id']], $crmRow);
                    $updated++;
                } else {
                    $crmRow['contact_id'] = $contactId;
                    $crmRow['domain_id'] = $domainId;
                    $this->supabase->insert('crm_data', $crmRow);
                    $created++;
                }
            } catch (\Exception $ex) {
                $errors[] = "Row {$i} ({$email}): " . $ex->getMessage();
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'errors'  => $errors,
        ];
    }
}
