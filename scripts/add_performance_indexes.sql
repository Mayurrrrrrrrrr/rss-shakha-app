-- migration script: add_performance_indexes.sql
-- These composite indexes are added to speed up date-range queries used by the admin dashboard,
-- such as loading daily records, events, notices, and other transactional data filtered by shakha and date.

-- Index for daily_records to optimize queries filtering by shakha and date range
CREATE INDEX IF NOT EXISTS idx_daily_records_shakha_date ON daily_records (shakha_id, record_date);

-- Index for events to optimize event queries by shakha and date
CREATE INDEX IF NOT EXISTS idx_events_shakha_date ON events (shakha_id, event_date);

-- Index for notices to optimize notice lookup by shakha and date
CREATE INDEX IF NOT EXISTS idx_notices_shakha_date ON notices (shakha_id, notice_date);

-- Index for subhashits to optimize subhashit queries by shakha and date
CREATE INDEX IF NOT EXISTS idx_subhashits_shakha_date ON subhashits (shakha_id, subhashit_date);

-- Index for geet table to optimize geet queries by shakha and date
CREATE INDEX IF NOT EXISTS idx_geet_shakha_date ON geet (shakha_id, geet_date);

-- Index for ghoshnayein to optimize ghoshnayein queries by shakha and date
CREATE INDEX IF NOT EXISTS idx_ghoshnayein_shakha_date ON ghoshnayein (shakha_id, ghoshna_date);

-- Index for amrit_vachan to optimize amrit vachan queries by shakha and date
CREATE INDEX IF NOT EXISTS idx_amrit_vachan_shakha_date ON amrit_vachan (shakha_id, vachan_date);
