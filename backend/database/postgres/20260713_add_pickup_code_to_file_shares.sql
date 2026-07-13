-- Add unique pickup code support for file transfer shares.

ALTER TABLE ft_file_shares
ADD COLUMN IF NOT EXISTS pickup_code VARCHAR(64) DEFAULT NULL;

CREATE UNIQUE INDEX IF NOT EXISTS ft_file_shares_pickup_code_uidx
ON ft_file_shares (pickup_code)
WHERE pickup_code IS NOT NULL AND pickup_code <> '';
