<?php

namespace App\Models;

use App\Libraries\SupabaseClient;

class ContactsModel
{
    private $supabase;

    function __construct()
    {
        $this->supabase = new SupabaseClient();
    }

    /**
     * Map a snake_case DB row to camelCase keys expected by views.
     */
    private function mapContact(array $row): array
    {
        return [
            'id'         => $row['id'] ?? null,
            'email'      => $row['email'] ?? null,
            'firstName'  => $row['first_name'] ?? null,
            'lastName'   => $row['last_name'] ?? null,
            'subscribed' => $row['subscribed'] ?? null,
            'domain_id'  => $row['domain_id'] ?? null,
            'created_at' => $row['created_at'] ?? null,
            'updated_at' => $row['updated_at'] ?? null,
        ];
    }

    public function insertContact($data): string
    {
        try {
            $result = $this->supabase->insert('contacts', $data);
            if (is_array($result) && isset($result[0]['id'])) {
                return $result[0]['id'];
            }
            if (is_array($result) && isset($result['id'])) {
                return $result['id'];
            }
            return '';
        } catch (\Exception $ex) {
            log_message('error', 'Error while inserting contact: ' . $ex->getMessage());
            return false;
        }
    }

    public function insertManyContacts($data): array
    {
        try {
            $result = $this->supabase->insert('contacts', $data);
            $ids = [];
            if (is_array($result)) {
                foreach ($result as $row) {
                    $ids[] = $row['id'] ?? null;
                }
            }
            return $ids;
        } catch (\Exception $ex) {
            log_message('error', 'Error while inserting contacts: ' . $ex->getMessage());
            return [];
        }
    }

    public function updateContact($id, $data)
    {
        try {
            $this->supabase->update('contacts', ['id' => 'eq.' . $id], $data);
        } catch (\Exception $ex) {
            log_message('error', 'Error while updating contact: ' . $ex->getMessage());
            return false;
        }
    }

    public function updateManyContacts($operations)
    {
        try {
            $modifiedCount = 0;
            foreach ($operations as $op) {
                // Each operation has 'filter' and 'update' keys.
                // Convert MongoDB-style filter {field: value} to PostgREST filters {field: 'eq.value'}
                $filters = [];
                foreach ($op['filter'] as $field => $value) {
                    $filters[$field] = 'eq.' . $value;
                }

                // Extract the $set data from MongoDB update format
                $updateData = $op['update']['$set'] ?? $op['update'];

                $result = $this->supabase->update('contacts', $filters, $updateData);
                if (is_array($result)) {
                    $modifiedCount += count($result);
                }
            }
            return $modifiedCount;
        } catch (\Exception $ex) {
            log_message('error', 'Error while updating multiple contacts: ' . $ex->getMessage());
            return false;
        }
    }

    public function deleteContact($id)
    {
        try {
            $this->supabase->delete('contacts', ['id' => 'eq.' . $id]);
        } catch (\Exception $ex) {
            log_message('error', 'Error while deleting contact: ' . $ex->getMessage());
            return false;
        }
    }

    public function getContact($id)
    {
        try {
            $row = $this->supabase->select('contacts', ['id' => 'eq.' . $id], true);
            return $row ? $this->mapContact($row) : null;
        } catch (\Exception $ex) {
            log_message('error', 'Error while fetching contact: ' . $ex->getMessage());
            return null;
        }
    }

    public function getContactByEmail($email)
    {
        try {
            $row = $this->supabase->select('contacts', ['email' => 'eq.' . $email], true);
            return $row ? $this->mapContact($row) : null;
        } catch (\Exception $ex) {
            log_message('error', 'Error while fetching contact by email: ' . $ex->getMessage());
            return null;
        }
    }

    public function getAllContacts()
    {
        try {
            $activeDomain = get_active_domain();
            $domainId = $activeDomain ? $activeDomain['id'] : null;

            $params = [];
            if ($domainId) {
                $params['domain_id'] = 'eq.' . $domainId;
            }

            $rows = $this->supabase->select('contacts', $params);
            return array_map([$this, 'mapContact'], $rows);
        } catch (\Exception $ex) {
            log_message('error', 'Error while fetching all contacts: ' . $ex->getMessage());
            return [];
        }
    }

    public function countSubscribedContacts()
    {
        try {
            $activeDomain = get_active_domain();
            $domainId = $activeDomain ? $activeDomain['id'] : null;

            $params = [
                'subscribed' => 'eq.true',
                'select'     => 'id',
            ];

            if ($domainId) {
                $params['domain_id'] = 'eq.' . $domainId;
            }

            $result = $this->supabase->select(
                'contacts',
                $params,
                false,
                true
            );
            return $result['count'] ?? 0;
        } catch (\Exception $ex) {
            log_message('error', 'Error while counting subscribed contacts: ' . $ex->getMessage());
            return null;
        }
    }
}
