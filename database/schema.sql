-- ============================================================
-- Flattered Email Platform - Supabase PostgreSQL Schema
-- Migrated from MongoDB
-- Run this in Supabase SQL Editor
-- ============================================================

-- Enable UUID generation
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- ============================================================
-- 1. CONTACTS
-- ============================================================
CREATE TABLE IF NOT EXISTS contacts (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    email       VARCHAR(255) NOT NULL UNIQUE,
    first_name  VARCHAR(255) NOT NULL DEFAULT '',
    last_name   VARCHAR(255) NOT NULL DEFAULT '',
    subscribed  BOOLEAN NOT NULL DEFAULT true,
    domain_id   VARCHAR(255),
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_contacts_email ON contacts(email);
CREATE INDEX IF NOT EXISTS idx_contacts_subscribed ON contacts(subscribed);
CREATE INDEX IF NOT EXISTS idx_contacts_domain_id ON contacts(domain_id);

-- ============================================================
-- 2. AUDIENCES
-- ============================================================
CREATE TABLE IF NOT EXISTS audiences (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name        VARCHAR(255) NOT NULL,
    domain_id   VARCHAR(255),
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_audiences_domain_id ON audiences(domain_id);

-- ============================================================
-- 3. AUDIENCE_CONTACTS (many-to-many junction)
-- ============================================================
CREATE TABLE IF NOT EXISTS audience_contacts (
    audience_id UUID NOT NULL REFERENCES audiences(id) ON DELETE CASCADE,
    contact_id  UUID NOT NULL REFERENCES contacts(id) ON DELETE CASCADE,
    PRIMARY KEY (audience_id, contact_id)
);

CREATE INDEX IF NOT EXISTS idx_ac_audience ON audience_contacts(audience_id);
CREATE INDEX IF NOT EXISTS idx_ac_contact ON audience_contacts(contact_id);

-- ============================================================
-- 4. EMAIL CAMPAIGNS
-- ============================================================
CREATE TABLE IF NOT EXISTS email_campaigns (
    id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name                VARCHAR(255) NOT NULL,
    subject             VARCHAR(500) NOT NULL DEFAULT '',
    status              VARCHAR(50) NOT NULL DEFAULT 'unsent',
    template_html       TEXT DEFAULT '',
    template_plain_text TEXT DEFAULT '',
    template_title      VARCHAR(500) DEFAULT '',
    grapes_js_data      TEXT DEFAULT '',
    domain_id           VARCHAR(255),
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    sent_at             TIMESTAMPTZ,
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_ec_domain_id ON email_campaigns(domain_id);
CREATE INDEX IF NOT EXISTS idx_ec_status ON email_campaigns(status);
CREATE INDEX IF NOT EXISTS idx_ec_created_at ON email_campaigns(created_at);

-- ============================================================
-- 5. CAMPAIGN_AUDIENCES (many-to-many junction)
-- ============================================================
CREATE TABLE IF NOT EXISTS campaign_audiences (
    campaign_id UUID NOT NULL REFERENCES email_campaigns(id) ON DELETE CASCADE,
    audience_id UUID NOT NULL REFERENCES audiences(id) ON DELETE CASCADE,
    PRIMARY KEY (campaign_id, audience_id)
);

CREATE INDEX IF NOT EXISTS idx_ca_campaign ON campaign_audiences(campaign_id);
CREATE INDEX IF NOT EXISTS idx_ca_audience ON campaign_audiences(audience_id);

-- ============================================================
-- 6. EMAIL EVENTS (flattened from MongoDB nested structure)
-- ============================================================
CREATE TABLE IF NOT EXISTS email_events (
    id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    event_type          VARCHAR(50) NOT NULL,
    event_created_at    TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    email_id            VARCHAR(255),
    from_address        VARCHAR(255),
    subject             VARCHAR(500),
    recipient           VARCHAR(500),
    campaign_id         VARCHAR(255),
    raw_headers         JSONB DEFAULT '[]',
    raw_tags            JSONB DEFAULT '[]',
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_ee_event_type ON email_events(event_type);
CREATE INDEX IF NOT EXISTS idx_ee_campaign_id ON email_events(campaign_id);
CREATE INDEX IF NOT EXISTS idx_ee_email_id ON email_events(email_id);
CREATE INDEX IF NOT EXISTS idx_ee_recipient ON email_events(recipient);
CREATE INDEX IF NOT EXISTS idx_ee_event_created ON email_events(event_created_at);
CREATE INDEX IF NOT EXISTS idx_ee_dedup ON email_events(email_id, event_type);

-- ============================================================
-- 7. DOMAINS
-- ============================================================
CREATE TABLE IF NOT EXISTS domains (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    domain_id       VARCHAR(255) NOT NULL UNIQUE,
    domain_name     VARCHAR(255) NOT NULL,
    sender_email    VARCHAR(255) DEFAULT 'N/A',
    pretty_name     VARCHAR(255) DEFAULT 'N/A',
    status          VARCHAR(50) NOT NULL DEFAULT 'pending',
    region          VARCHAR(50),
    dns_provider    VARCHAR(100),
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_domains_domain_id ON domains(domain_id);

-- ============================================================
-- 8. ANALYTICS (cached snapshots)
-- ============================================================
CREATE TABLE IF NOT EXISTS analytics (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    domain_id       VARCHAR(255),
    data            JSONB NOT NULL DEFAULT '[]',
    generated_at    TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ============================================================
-- 9. CAMPAIGN ANALYTICS VIEW
-- ============================================================
CREATE OR REPLACE VIEW campaign_analytics_view AS
SELECT
    ec.id AS campaign_id,
    ec.name AS campaign_name,
    ec.subject,
    ec.template_html,
    ec.template_plain_text,
    ec.domain_id,
    ec.sent_at,
    COUNT(DISTINCT ee.recipient)::int AS total_emails,
    COUNT(DISTINCT CASE WHEN ee.event_type = 'email.delivered' THEN ee.email_id END)::int AS delivered_count,
    COUNT(DISTINCT CASE WHEN ee.event_type = 'email.opened' THEN ee.email_id END)::int AS opened_count,
    COUNT(DISTINCT CASE WHEN ee.event_type = 'email.clicked' THEN ee.email_id END)::int AS clicked_count,
    CASE
        WHEN COUNT(DISTINCT ee.recipient) > 0
        THEN ROUND(COUNT(DISTINCT CASE WHEN ee.event_type = 'email.delivered' THEN ee.email_id END)::numeric
             / COUNT(DISTINCT ee.recipient) * 100, 2)
        ELSE 0
    END AS delivery_rate,
    CASE
        WHEN COUNT(DISTINCT ee.recipient) > 0
        THEN ROUND(COUNT(DISTINCT CASE WHEN ee.event_type = 'email.opened' THEN ee.email_id END)::numeric
             / COUNT(DISTINCT ee.recipient) * 100, 2)
        ELSE 0
    END AS open_rate,
    CASE
        WHEN COUNT(DISTINCT ee.recipient) > 0
        THEN ROUND(COUNT(DISTINCT CASE WHEN ee.event_type = 'email.clicked' THEN ee.email_id END)::numeric
             / COUNT(DISTINCT ee.recipient) * 100, 2)
        ELSE 0
    END AS click_rate
FROM email_campaigns ec
LEFT JOIN email_events ee ON ee.campaign_id = ec.id::text
WHERE ec.status = 'sent'
GROUP BY ec.id, ec.name, ec.subject, ec.template_html, ec.template_plain_text, ec.domain_id, ec.sent_at
ORDER BY ec.sent_at DESC;

-- ============================================================
-- 10. RPC FUNCTIONS
-- ============================================================

-- Get event counts grouped by type
CREATE OR REPLACE FUNCTION get_total_per_event_type()
RETURNS TABLE(
    "eventType" TEXT,
    count BIGINT
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        ee.event_type::TEXT AS "eventType",
        COUNT(*)::BIGINT AS count
    FROM email_events ee
    WHERE ee.event_type IN ('email.delivered', 'email.opened', 'email.clicked')
      AND ee.campaign_id IS NOT NULL
    GROUP BY ee.event_type;
END;
$$ LANGUAGE plpgsql;

-- Get unique contacts sum
CREATE OR REPLACE FUNCTION get_unique_contacts_sum()
RETURNS TABLE(
    "totalContacts" BIGINT
) AS $$
BEGIN
    RETURN QUERY
    SELECT COUNT(DISTINCT recipient)::BIGINT AS "totalContacts"
    FROM email_events;
END;
$$ LANGUAGE plpgsql;

-- Get events grouped by unique email
CREATE OR REPLACE FUNCTION get_events_grouped_by_mail()
RETURNS TABLE(
    "_id" TEXT,
    events JSONB
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        ee.recipient::TEXT AS "_id",
        jsonb_agg(
            jsonb_build_object(
                'event_type', ee.event_type,
                'event_created_at', ee.event_created_at,
                'email_id', ee.email_id,
                'subject', ee.subject,
                'campaign_id', ee.campaign_id
            ) ORDER BY ee.event_created_at ASC
        ) AS events
    FROM email_events ee
    WHERE ee.recipient IS NOT NULL
    GROUP BY ee.recipient;
END;
$$ LANGUAGE plpgsql;

-- Get analytics by domain
CREATE OR REPLACE FUNCTION get_analytics_by_domain(p_domain_id TEXT)
RETURNS TABLE(
    "campaignId" TEXT,
    "campaignName" TEXT,
    subject TEXT,
    "templateHTML" TEXT,
    "templatePlainText" TEXT,
    domain_id TEXT,
    sent_at TIMESTAMPTZ,
    "totalEmails" INT,
    "deliveryRate" NUMERIC,
    "openRate" NUMERIC,
    "clickRate" NUMERIC
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        cav.campaign_id::TEXT AS "campaignId",
        cav.campaign_name AS "campaignName",
        cav.subject,
        cav.template_html AS "templateHTML",
        cav.template_plain_text AS "templatePlainText",
        cav.domain_id::TEXT,
        cav.sent_at,
        cav.total_emails AS "totalEmails",
        cav.delivery_rate AS "deliveryRate",
        cav.open_rate AS "openRate",
        cav.click_rate AS "clickRate"
    FROM campaign_analytics_view cav
    WHERE cav.domain_id = p_domain_id OR p_domain_id IS NULL
    ORDER BY cav.sent_at DESC;
END;
$$ LANGUAGE plpgsql;

-- ============================================================
-- 11. DISABLE RLS (using service_role key bypasses anyway,
--     but disable to avoid issues)
-- ============================================================
ALTER TABLE contacts ENABLE ROW LEVEL SECURITY;
ALTER TABLE audiences ENABLE ROW LEVEL SECURITY;
ALTER TABLE audience_contacts ENABLE ROW LEVEL SECURITY;
ALTER TABLE email_campaigns ENABLE ROW LEVEL SECURITY;
ALTER TABLE campaign_audiences ENABLE ROW LEVEL SECURITY;
ALTER TABLE email_events ENABLE ROW LEVEL SECURITY;
ALTER TABLE domains ENABLE ROW LEVEL SECURITY;
ALTER TABLE analytics ENABLE ROW LEVEL SECURITY;

-- Create policies that allow service_role full access
-- (service_role key bypasses RLS by default, so these are just safety nets)
CREATE POLICY "Allow all for service role" ON contacts FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Allow all for service role" ON audiences FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Allow all for service role" ON audience_contacts FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Allow all for service role" ON email_campaigns FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Allow all for service role" ON campaign_audiences FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Allow all for service role" ON email_events FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Allow all for service role" ON domains FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Allow all for service role" ON analytics FOR ALL USING (true) WITH CHECK (true);
