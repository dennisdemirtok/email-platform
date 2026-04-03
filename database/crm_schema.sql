-- ============================================================
-- CRM Feature - Supabase PostgreSQL Schema
-- Run this in Supabase SQL Editor
-- ============================================================

-- ============================================================
-- 1. CRM DATA (linked to contacts)
-- ============================================================
CREATE TABLE IF NOT EXISTS crm_data (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    contact_id      UUID NOT NULL REFERENCES contacts(id) ON DELETE CASCADE,
    domain_id       VARCHAR(255) NOT NULL,
    company_name    VARCHAR(500) DEFAULT '',
    contact_person  VARCHAR(500) DEFAULT '',
    category        VARCHAR(100) DEFAULT '',
    needs           TEXT DEFAULT '',
    last_contact    VARCHAR(100) DEFAULT '',
    notes           TEXT DEFAULT '',
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE UNIQUE INDEX IF NOT EXISTS idx_crm_data_contact_domain ON crm_data(contact_id, domain_id);
CREATE INDEX IF NOT EXISTS idx_crm_data_domain_id ON crm_data(domain_id);
CREATE INDEX IF NOT EXISTS idx_crm_data_category ON crm_data(category);
CREATE INDEX IF NOT EXISTS idx_crm_data_contact_id ON crm_data(contact_id);

-- ============================================================
-- 2. CRM EMAILS (individual emails sent from profile)
-- ============================================================
CREATE TABLE IF NOT EXISTS crm_emails (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    contact_id      UUID NOT NULL REFERENCES contacts(id) ON DELETE CASCADE,
    domain_id       VARCHAR(255) NOT NULL,
    from_address    VARCHAR(500) NOT NULL,
    to_address      VARCHAR(500) NOT NULL,
    subject         VARCHAR(1000) NOT NULL,
    body_html       TEXT DEFAULT '',
    body_text       TEXT DEFAULT '',
    resend_email_id VARCHAR(255) DEFAULT '',
    status          VARCHAR(50) NOT NULL DEFAULT 'sent',
    sent_at         TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_crm_emails_contact_id ON crm_emails(contact_id);
CREATE INDEX IF NOT EXISTS idx_crm_emails_domain_id ON crm_emails(domain_id);
CREATE INDEX IF NOT EXISTS idx_crm_emails_sent_at ON crm_emails(sent_at DESC);

-- ============================================================
-- 3. ROW LEVEL SECURITY
-- ============================================================
ALTER TABLE crm_data ENABLE ROW LEVEL SECURITY;
ALTER TABLE crm_emails ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Allow all operations on crm_data"
    ON crm_data FOR ALL
    USING (true)
    WITH CHECK (true);

CREATE POLICY "Allow all operations on crm_emails"
    ON crm_emails FOR ALL
    USING (true)
    WITH CHECK (true);
