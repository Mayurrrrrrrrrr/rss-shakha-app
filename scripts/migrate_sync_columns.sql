-- Migration: Add updated_at columns to notices and personalities for sync support
-- Run this on your MySQL server before deploying the new app version

-- 1. Add updated_at to notices table (if not exists)
ALTER TABLE notices ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Backfill notices updated_at from created_at where NULL
UPDATE notices SET updated_at = COALESCE(created_at, NOW()) WHERE updated_at IS NULL;

-- 2. Add updated_at to personalities table (if not exists)
ALTER TABLE personalities ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Backfill personalities updated_at
UPDATE personalities SET updated_at = NOW() WHERE updated_at IS NULL;

-- 3. Add created_at to personalities table (if not exists)
ALTER TABLE personalities ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- 4. Ensure events have updated_at (should already exist, but safe to run)
-- ALTER TABLE events ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- 5. Backfill any events missing updated_at
UPDATE events SET updated_at = COALESCE(created_at, NOW()) WHERE updated_at IS NULL;
