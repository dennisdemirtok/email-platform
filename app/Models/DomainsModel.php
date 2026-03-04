<?php

namespace App\Models;

use App\Libraries\SupabaseClient;

class DomainsModel
{
    private $supabase;

    public function __construct()
    {
        $this->supabase = new SupabaseClient();
    }

    /**
     * Get active domains from Resend API, merged with Supabase data.
     */
    public function getActiveDomains()
    {
        helper('resend');
        $response = resend_api_request('/domains');

        if ($response && isset($response['data'])) {
            $resendDomains = array_values(array_filter($response['data'], function ($domain) {
                return $domain['status'] !== 'failure' && $domain['status'] !== 'not_started';
            }));

            return array_map(function ($domain) {
                $supabaseData = $this->findByDomainId($domain['id']);
                if ($supabaseData) {
                    return array_merge($supabaseData, $domain);
                }
                return $domain;
            }, $resendDomains);
        }

        return [];
    }

    /**
     * Get all domains from Resend API (basic info only).
     */
    public function getAllDomains()
    {
        helper('resend');
        $response = resend_api_request('/domains');

        if ($response && isset($response['data'])) {
            return array_map(function ($domain) {
                return [
                    'domain_id'    => $domain['id'],
                    'domain_name'  => $domain['name'],
                    'status'       => $domain['status'],
                    'created_at'   => $domain['created_at'],
                    'region'       => $domain['region'] ?? null,
                    'dns_provider' => $domain['dns_provider'] ?? null,
                ];
            }, $response['data']);
        }

        return [];
    }

    /**
     * Get all domains stored in Supabase.
     */
    public function findAll()
    {
        try {
            return $this->supabase->select('domains');
        } catch (\Exception $ex) {
            log_message('error', 'Error while fetching all domains: ' . $ex->getMessage());
            return [];
        }
    }

    /**
     * Find a domain by its Resend domain_id (not the UUID primary key).
     */
    public function findByDomainId($domainId)
    {
        try {
            return $this->supabase->select('domains', [
                'domain_id' => 'eq.' . $domainId,
            ], true);
        } catch (\Exception $ex) {
            return null;
        }
    }

    /**
     * Get a single domain from Resend API, merged with Supabase data.
     */
    public function find($id)
    {
        helper('resend');
        $response = resend_api_request('/domains/' . $id);

        if ($response && isset($response['data'])) {
            $supabaseData = $this->findByDomainId($id);
            if ($supabaseData) {
                return array_merge($supabaseData, $response['data']);
            }
            return $response['data'];
        }

        return null;
    }

    /**
     * Trigger domain verification via Resend API, merged with Supabase data.
     */
    public function verify($id)
    {
        helper('resend');
        $response = resend_api_request('/domains/' . $id . '/verify', 'POST');

        if ($response) {
            $supabaseData = $this->findByDomainId($id);
            if ($supabaseData) {
                return array_merge($supabaseData, $response);
            }
            return $response;
        }

        return null;
    }

    /**
     * Insert a new domain record into Supabase.
     */
    public function insertDomain($data)
    {
        try {
            $result = $this->supabase->insert('domains', $data);
            if (is_array($result) && isset($result[0]['id'])) {
                return $result[0]['id'];
            }
            if (is_array($result) && isset($result['id'])) {
                return $result['id'];
            }
            return null;
        } catch (\Exception $ex) {
            log_message('error', 'Error while inserting domain: ' . $ex->getMessage());
            return false;
        }
    }

    /**
     * Update a domain record in Supabase by its Resend domain_id.
     */
    public function updateByDomainId($domainId, $data)
    {
        try {
            $this->supabase->update('domains', ['domain_id' => 'eq.' . $domainId], $data);
        } catch (\Exception $ex) {
            log_message('error', 'Error while updating domain: ' . $ex->getMessage());
            return false;
        }
    }
}
