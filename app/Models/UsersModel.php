<?php

namespace App\Models;

use App\Libraries\SupabaseClient;

class UsersModel
{
    private $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseClient();
    }

    /**
     * Find a user by username (for login).
     */
    public function findByUsername(string $username): ?array
    {
        try {
            return $this->supabase->select('users', [
                'username' => 'eq.' . $username,
            ], true);
        } catch (\Exception $ex) {
            log_message('error', 'Error finding user by username: ' . $ex->getMessage());
            return null;
        }
    }

    /**
     * Find a user by ID.
     */
    public function findById(string $id): ?array
    {
        try {
            return $this->supabase->select('users', [
                'id' => 'eq.' . $id,
            ], true);
        } catch (\Exception $ex) {
            log_message('error', 'Error finding user by ID: ' . $ex->getMessage());
            return null;
        }
    }

    /**
     * Get all users.
     */
    public function getAll(): array
    {
        try {
            return $this->supabase->select('users', [
                'select' => 'id,username,role,created_at',
                'order'  => 'created_at.asc',
            ]);
        } catch (\Exception $ex) {
            log_message('error', 'Error fetching all users: ' . $ex->getMessage());
            return [];
        }
    }

    /**
     * Create a new user.
     *
     * @param array $data ['username', 'password_hash', 'role']
     * @return string|false User ID on success, false on failure
     */
    public function create(array $data)
    {
        try {
            $result = $this->supabase->insert('users', $data);

            if (is_array($result) && isset($result[0]['id'])) {
                return $result[0]['id'];
            } elseif (is_array($result) && isset($result['id'])) {
                return $result['id'];
            }

            return false;
        } catch (\Exception $ex) {
            log_message('error', 'Error creating user: ' . $ex->getMessage());
            return false;
        }
    }

    /**
     * Update a user by ID.
     */
    public function update(string $id, array $data): bool
    {
        try {
            $this->supabase->update('users', ['id' => 'eq.' . $id], $data);
            return true;
        } catch (\Exception $ex) {
            log_message('error', 'Error updating user: ' . $ex->getMessage());
            return false;
        }
    }

    /**
     * Delete a user by ID.
     */
    public function delete(string $id): bool
    {
        try {
            // user_domains will cascade-delete automatically
            $this->supabase->delete('users', ['id' => 'eq.' . $id]);
            return true;
        } catch (\Exception $ex) {
            log_message('error', 'Error deleting user: ' . $ex->getMessage());
            return false;
        }
    }

    /**
     * Get domain IDs assigned to a user.
     *
     * @return string[] Array of domain_id values
     */
    public function getUserDomainIds(string $userId): array
    {
        try {
            $rows = $this->supabase->select('user_domains', [
                'user_id' => 'eq.' . $userId,
                'select'  => 'domain_id',
            ]);

            return array_column($rows, 'domain_id');
        } catch (\Exception $ex) {
            log_message('error', 'Error fetching user domains: ' . $ex->getMessage());
            return [];
        }
    }

    /**
     * Replace all domain assignments for a user.
     * Deletes existing rows then inserts new ones.
     */
    public function setUserDomains(string $userId, array $domainIds): bool
    {
        try {
            // Remove existing
            $this->supabase->delete('user_domains', ['user_id' => 'eq.' . $userId]);

            if (empty($domainIds)) {
                return true;
            }

            // Build batch rows
            $rows = [];
            foreach ($domainIds as $domainId) {
                $rows[] = [
                    'user_id'   => $userId,
                    'domain_id' => $domainId,
                ];
            }

            $this->supabase->insert('user_domains', $rows);
            return true;
        } catch (\Exception $ex) {
            log_message('error', 'Error setting user domains: ' . $ex->getMessage());
            return false;
        }
    }
}
