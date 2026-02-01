-- Migration: Add is_paid column to transportation_jobs table
-- Date: 2026-01-28
-- Description: Adds payment status field to transportation jobs

USE depopazar;

ALTER TABLE `transportation_jobs` 
ADD COLUMN `is_paid` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Ödeme alındı mı?' AFTER `job_date`;
