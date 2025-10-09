-- Template for database structure without sensitive data
-- Replace [YOUR_TOKEN] with your actual token in a local copy
-- DO NOT commit the file with real credentials

-- Database structure
CREATE DATABASE IF NOT EXISTS `angelow`;
USE `angelow`;

-- Add your database structure here
-- but remove any sensitive data like:
-- - API keys
-- - OAuth tokens
-- - Passwords
-- - Private keys
-- - Personal data

-- Example of how to handle sensitive data:
-- INSERT INTO `oauth_config` (`key`, `value`) VALUES
-- ('google_oauth_token', '[YOUR_TOKEN]');