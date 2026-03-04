<?php

namespace App\Models;

use App\Libraries\SupabaseClient;

class AudiencesModel
{
    private $supabase;

    function __construct()
    {
        $this->supabase = new SupabaseClient();
    }

    public function insertAudience($data)
    {
        try {
            $result = $this->supabase->insert('audiences', $data);
            if (is_array($result) && isset($result[0]['id'])) {
                return $result[0]['id'];
            }
            if (is_array($result) && isset($result['id'])) {
                return $result['id'];
            }
            return null;
        } catch (\Exception $ex) {
            log_message('error', 'Error while inserting audience: ' . $ex->getMessage());
            return false;
        }
    }

    public function updateAudience($id, $data)
    {
        try {
            $this->supabase->update('audiences', ['id' => 'eq.' . $id], $data);
        } catch (\Exception $ex) {
            log_message('error', 'Error while updating audience: ' . $ex->getMessage());
            return false;
        }
    }

    public function deleteAudience($id)
    {
        try {
            // Delete junction table entries first
            $this->supabase->delete('audience_contacts', ['audience_id' => 'eq.' . $id]);
            // Delete the audience itself
            $this->supabase->delete('audiences', ['id' => 'eq.' . $id]);
        } catch (\Exception $ex) {
            log_message('error', 'Error while deleting audience: ' . $ex->getMessage());
            return false;
        }
    }

    public function getAudience($id)
    {
        try {
            return $this->supabase->select('audiences', ['id' => 'eq.' . $id], true);
        } catch (\Exception $ex) {
            log_message('error', 'Error while fetching audience: ' . $ex->getMessage());
            return null;
        }
    }

    /**
     * Get all contacts that belong to a given audience via the junction table.
     * Uses PostgREST resource embedding: audience_contacts with contacts(...).
     */
    public function getAudienceContacts($id)
    {
        try {
            $rows = $this->supabase->select('audience_contacts', [
                'audience_id' => 'eq.' . $id,
                'select'      => 'contacts(id,email,first_name,last_name,subscribed)',
            ]);

            // Flatten: each row is { contacts: { id, email, ... } }
            $contacts = [];
            foreach ($rows as $row) {
                if (isset($row['contacts'])) {
                    $contacts[] = $row['contacts'];
                }
            }

            return $contacts;
        } catch (\Exception $ex) {
            log_message('error', 'Error while fetching audience contacts: ' . $ex->getMessage());
            return [];
        }
    }

    /**
     * Replace all contact associations for an audience.
     * Deletes existing junction rows then inserts new ones.
     */
    public function setAudienceContacts($audienceId, array $contactIds)
    {
        try {
            // Remove existing associations
            $this->supabase->delete('audience_contacts', ['audience_id' => 'eq.' . $audienceId]);

            if (empty($contactIds)) {
                return;
            }

            // Build batch of junction rows
            $rows = [];
            foreach ($contactIds as $contactId) {
                $rows[] = [
                    'audience_id' => $audienceId,
                    'contact_id'  => $contactId,
                ];
            }

            $this->supabase->insert('audience_contacts', $rows);
        } catch (\Exception $ex) {
            log_message('error', 'Error while setting audience contacts: ' . $ex->getMessage());
            return false;
        }
    }

    /**
     * Get all audiences for the active domain, each with a contactsCount.
     * Uses PostgREST count embedding on the junction table.
     */
    public function getAllAudiences()
    {
        try {
            $activeDomain = get_active_domain();
            $domainId = $activeDomain ? $activeDomain['id'] : null;

            $params = [
                'select' => 'id,name,audience_contacts(count)',
            ];

            if ($domainId) {
                $params['domain_id'] = 'eq.' . $domainId;
            }

            $rows = $this->supabase->select('audiences', $params);

            $allAudiences = [];
            foreach ($rows as $row) {
                $contactsCount = 0;
                if (isset($row['audience_contacts'][0]['count'])) {
                    $contactsCount = (int) $row['audience_contacts'][0]['count'];
                }

                $allAudiences[] = [
                    'id'            => $row['id'],
                    'name'          => $row['name'],
                    'contactsCount' => $contactsCount,
                ];
            }

            return $allAudiences;
        } catch (\Exception $ex) {
            log_message('error', 'Error while fetching all audiences: ' . $ex->getMessage());
            return [];
        }
    }

    /**
     * Add a single contact to an audience (additive, not replace).
     */
    public function addContactToAudience($audienceId, $contactId)
    {
        try {
            $this->supabase->insert('audience_contacts', [
                'audience_id' => $audienceId,
                'contact_id'  => $contactId,
            ]);
        } catch (\Exception $ex) {
            // Silently ignore duplicate key errors (contact already in audience)
            if (strpos($ex->getMessage(), 'duplicate') !== false || strpos($ex->getMessage(), '23505') !== false) {
                return;
            }
            log_message('error', 'Error adding contact to audience: ' . $ex->getMessage());
        }
    }

    /**
     * Remove a single contact from an audience (does not delete the contact itself).
     */
    public function removeContactFromAudience($audienceId, $contactId)
    {
        try {
            $this->supabase->delete('audience_contacts', [
                'audience_id' => 'eq.' . $audienceId,
                'contact_id'  => 'eq.' . $contactId,
            ]);
        } catch (\Exception $ex) {
            log_message('error', 'Error removing contact from audience: ' . $ex->getMessage());
        }
    }

    /**
     * Get unique subscribed contacts across multiple audiences.
     * Uses PostgREST resource embedding with an inner join on contacts
     * filtered to subscribed=true.
     */
    public function getUniqueContactsFromAudiences($audienceIds)
    {
        try {
            if (empty($audienceIds)) {
                return [];
            }

            $idList = implode(',', $audienceIds);

            $rows = $this->supabase->select('audience_contacts', [
                'audience_id' => 'in.(' . $idList . ')',
                'select'      => 'contacts!inner(id,email,first_name,last_name,subscribed)',
                'contacts.subscribed' => 'eq.true',
            ]);

            // De-duplicate by contact id
            $uniqueContacts = [];
            foreach ($rows as $row) {
                if (isset($row['contacts'])) {
                    $contact = $row['contacts'];
                    $contactId = $contact['id'];
                    if (!isset($uniqueContacts[$contactId])) {
                        $uniqueContacts[$contactId] = $contact;
                    }
                }
            }

            return array_values($uniqueContacts);
        } catch (\Exception $ex) {
            log_message('error', 'Error getting unique contacts from audiences: ' . $ex->getMessage());
            return [];
        }
    }
}
