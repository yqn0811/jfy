-- File transfer module tables for PostgreSQL ai_jf database.
-- All tables use the ft_ prefix to stay isolated from AI image generation tables.

-- ThinkPHP's PgSQL connector reads table metadata through table_msg().
-- Keep the helper in public schema so the file module can use the stock driver
-- without changing vendor code or the existing MySQL album connection.
CREATE OR REPLACE FUNCTION public.table_msg(table_name TEXT)
RETURNS TABLE (
    fields_name TEXT,
    fields_type TEXT,
    fields_not_null TEXT,
    fields_key_name TEXT,
    fields_default TEXT
)
LANGUAGE SQL
STABLE
AS $$
    SELECT
        a.attname::TEXT AS fields_name,
        format_type(a.atttypid, a.atttypmod)::TEXT AS fields_type,
        CASE WHEN a.attnotnull THEN 'not null' ELSE '' END::TEXT AS fields_not_null,
        CASE WHEN pk.attnum IS NOT NULL THEN 'pri' ELSE '' END::TEXT AS fields_key_name,
        pg_get_expr(d.adbin, d.adrelid)::TEXT AS fields_default
    FROM pg_attribute a
    JOIN pg_class c ON c.oid = a.attrelid
    JOIN pg_namespace n ON n.oid = c.relnamespace
    LEFT JOIN pg_attrdef d ON d.adrelid = a.attrelid AND d.adnum = a.attnum
    LEFT JOIN (
        SELECT i.indrelid, unnest(i.indkey) AS attnum
        FROM pg_index i
        WHERE i.indisprimary
    ) pk ON pk.indrelid = a.attrelid AND pk.attnum = a.attnum
    WHERE a.attnum > 0
      AND NOT a.attisdropped
      AND c.relkind IN ('r', 'p')
      AND (
          c.oid = to_regclass(table_name)
          OR c.oid = to_regclass('public.' || table_name)
          OR c.relname = table_name
      )
    ORDER BY a.attnum;
$$;

GRANT EXECUTE ON FUNCTION public.table_msg(TEXT) TO ai_jf_user;

CREATE TABLE IF NOT EXISTS ft_files (
    id BIGSERIAL PRIMARY KEY,
    owner_user_id BIGINT NOT NULL,
    sso_subject VARCHAR(128) DEFAULT NULL,
    original_name VARCHAR(255) NOT NULL,
    object_key VARCHAR(512) NOT NULL,
    storage_provider VARCHAR(32) NOT NULL DEFAULT 'pending',
    mime_type VARCHAR(128) NOT NULL DEFAULT '',
    extension VARCHAR(32) NOT NULL DEFAULT '',
    size_bytes BIGINT NOT NULL DEFAULT 0,
    sha256 CHAR(64) DEFAULT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'pending',
    preview_url TEXT DEFAULT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    deleted_at TIMESTAMPTZ DEFAULT NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS ft_files_object_key_uidx ON ft_files (object_key);
CREATE INDEX IF NOT EXISTS ft_files_owner_status_idx ON ft_files (owner_user_id, status, created_at DESC);
CREATE INDEX IF NOT EXISTS ft_files_sha256_idx ON ft_files (sha256);

CREATE TABLE IF NOT EXISTS ft_file_shares (
    id BIGSERIAL PRIMARY KEY,
    owner_user_id BIGINT NOT NULL,
    sso_subject VARCHAR(128) DEFAULT NULL,
    title VARCHAR(255) NOT NULL,
    share_code VARCHAR(64) NOT NULL,
    password_hash VARCHAR(255) DEFAULT NULL,
    expires_at TIMESTAMPTZ DEFAULT NULL,
    max_downloads INTEGER NOT NULL DEFAULT 0,
    download_count INTEGER NOT NULL DEFAULT 0,
    allow_preview BOOLEAN NOT NULL DEFAULT TRUE,
    notify_on_download BOOLEAN NOT NULL DEFAULT FALSE,
    status VARCHAR(32) NOT NULL DEFAULT 'active',
    file_count INTEGER NOT NULL DEFAULT 0,
    total_size_bytes BIGINT NOT NULL DEFAULT 0,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    deleted_at TIMESTAMPTZ DEFAULT NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS ft_file_shares_share_code_uidx ON ft_file_shares (share_code);
CREATE INDEX IF NOT EXISTS ft_file_shares_owner_status_idx ON ft_file_shares (owner_user_id, status, created_at DESC);
CREATE INDEX IF NOT EXISTS ft_file_shares_expires_idx ON ft_file_shares (expires_at);

CREATE TABLE IF NOT EXISTS ft_file_share_items (
    id BIGSERIAL PRIMARY KEY,
    share_id BIGINT NOT NULL REFERENCES ft_file_shares(id) ON DELETE CASCADE,
    file_id BIGINT NOT NULL REFERENCES ft_files(id) ON DELETE RESTRICT,
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE UNIQUE INDEX IF NOT EXISTS ft_file_share_items_share_file_uidx ON ft_file_share_items (share_id, file_id);
CREATE INDEX IF NOT EXISTS ft_file_share_items_file_idx ON ft_file_share_items (file_id);

CREATE TABLE IF NOT EXISTS ft_share_access_logs (
    id BIGSERIAL PRIMARY KEY,
    share_id BIGINT NOT NULL REFERENCES ft_file_shares(id) ON DELETE CASCADE,
    visitor_user_id BIGINT DEFAULT NULL,
    action VARCHAR(32) NOT NULL,
    ip_label VARCHAR(64) DEFAULT NULL,
    ip_address INET DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    extra JSONB NOT NULL DEFAULT '{}'::jsonb,
    occurred_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS ft_share_access_logs_share_time_idx ON ft_share_access_logs (share_id, occurred_at DESC);
CREATE INDEX IF NOT EXISTS ft_share_access_logs_action_idx ON ft_share_access_logs (action, occurred_at DESC);

CREATE TABLE IF NOT EXISTS ft_collection_tasks (
    id BIGSERIAL PRIMARY KEY,
    owner_user_id BIGINT NOT NULL,
    sso_subject VARCHAR(128) DEFAULT NULL,
    template_id VARCHAR(128) DEFAULT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL DEFAULT '',
    due_at TIMESTAMPTZ DEFAULT NULL,
    submit_target_description TEXT NOT NULL DEFAULT '',
    access_code_hash VARCHAR(255) DEFAULT NULL,
    allow_resubmission BOOLEAN NOT NULL DEFAULT TRUE,
    enable_ai_check BOOLEAN NOT NULL DEFAULT FALSE,
    anonymous_submit BOOLEAN NOT NULL DEFAULT FALSE,
    allow_preview BOOLEAN NOT NULL DEFAULT FALSE,
    naming_rule VARCHAR(255) NOT NULL DEFAULT '',
    reminder_before_due_hours INTEGER NOT NULL DEFAULT 24,
    status VARCHAR(32) NOT NULL DEFAULT 'collecting',
    submit_count INTEGER NOT NULL DEFAULT 0,
    file_count INTEGER NOT NULL DEFAULT 0,
    total_size_bytes BIGINT NOT NULL DEFAULT 0,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    archived_at TIMESTAMPTZ DEFAULT NULL,
    deleted_at TIMESTAMPTZ DEFAULT NULL
);

CREATE INDEX IF NOT EXISTS ft_collection_tasks_owner_status_idx ON ft_collection_tasks (owner_user_id, status, created_at DESC);
CREATE INDEX IF NOT EXISTS ft_collection_tasks_due_idx ON ft_collection_tasks (due_at);

CREATE TABLE IF NOT EXISTS ft_collection_fields (
    id BIGSERIAL PRIMARY KEY,
    task_id BIGINT NOT NULL REFERENCES ft_collection_tasks(id) ON DELETE CASCADE,
    field_key VARCHAR(64) NOT NULL,
    field_label VARCHAR(128) NOT NULL,
    field_type VARCHAR(32) NOT NULL DEFAULT 'text',
    required BOOLEAN NOT NULL DEFAULT FALSE,
    placeholder VARCHAR(255) NOT NULL DEFAULT '',
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE UNIQUE INDEX IF NOT EXISTS ft_collection_fields_task_key_uidx ON ft_collection_fields (task_id, field_key);

CREATE TABLE IF NOT EXISTS ft_collection_materials (
    id BIGSERIAL PRIMARY KEY,
    task_id BIGINT NOT NULL REFERENCES ft_collection_tasks(id) ON DELETE CASCADE,
    material_name VARCHAR(128) NOT NULL,
    file_types JSONB NOT NULL DEFAULT '[]'::jsonb,
    required BOOLEAN NOT NULL DEFAULT FALSE,
    max_size_mb INTEGER NOT NULL DEFAULT 100,
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS ft_collection_materials_task_idx ON ft_collection_materials (task_id, sort_order);

CREATE TABLE IF NOT EXISTS ft_submissions (
    id BIGSERIAL PRIMARY KEY,
    task_id BIGINT NOT NULL REFERENCES ft_collection_tasks(id) ON DELETE CASCADE,
    submitter_user_id BIGINT DEFAULT NULL,
    submitter_snapshot JSONB NOT NULL DEFAULT '{}'::jsonb,
    status VARCHAR(32) NOT NULL DEFAULT 'submitted',
    review_state VARCHAR(32) NOT NULL DEFAULT 'waiting',
    has_missing BOOLEAN NOT NULL DEFAULT FALSE,
    file_count INTEGER NOT NULL DEFAULT 0,
    total_size_bytes BIGINT NOT NULL DEFAULT 0,
    submitted_at TIMESTAMPTZ DEFAULT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    deleted_at TIMESTAMPTZ DEFAULT NULL
);

CREATE INDEX IF NOT EXISTS ft_submissions_task_status_idx ON ft_submissions (task_id, status, created_at DESC);
CREATE INDEX IF NOT EXISTS ft_submissions_submitter_idx ON ft_submissions (submitter_user_id, created_at DESC);

CREATE TABLE IF NOT EXISTS ft_submission_files (
    id BIGSERIAL PRIMARY KEY,
    submission_id BIGINT NOT NULL REFERENCES ft_submissions(id) ON DELETE CASCADE,
    material_id BIGINT DEFAULT NULL REFERENCES ft_collection_materials(id) ON DELETE SET NULL,
    file_id BIGINT NOT NULL REFERENCES ft_files(id) ON DELETE RESTRICT,
    status VARCHAR(32) NOT NULL DEFAULT 'uploaded',
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE UNIQUE INDEX IF NOT EXISTS ft_submission_files_submission_file_uidx ON ft_submission_files (submission_id, file_id);
CREATE INDEX IF NOT EXISTS ft_submission_files_material_idx ON ft_submission_files (material_id);

CREATE TABLE IF NOT EXISTS ft_review_logs (
    id BIGSERIAL PRIMARY KEY,
    submission_id BIGINT NOT NULL REFERENCES ft_submissions(id) ON DELETE CASCADE,
    reviewer_user_id BIGINT DEFAULT NULL,
    action VARCHAR(32) NOT NULL,
    result VARCHAR(32) NOT NULL,
    remark TEXT NOT NULL DEFAULT '',
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS ft_review_logs_submission_time_idx ON ft_review_logs (submission_id, created_at DESC);

CREATE TABLE IF NOT EXISTS ft_spaces (
    id BIGSERIAL PRIMARY KEY,
    owner_user_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    storage_limit_bytes BIGINT NOT NULL DEFAULT 0,
    storage_used_bytes BIGINT NOT NULL DEFAULT 0,
    member_count INTEGER NOT NULL DEFAULT 1,
    archive_rule TEXT NOT NULL DEFAULT '',
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE UNIQUE INDEX IF NOT EXISTS ft_spaces_owner_uidx ON ft_spaces (owner_user_id);

CREATE TABLE IF NOT EXISTS ft_archive_items (
    id BIGSERIAL PRIMARY KEY,
    space_id BIGINT NOT NULL REFERENCES ft_spaces(id) ON DELETE CASCADE,
    source_type VARCHAR(32) NOT NULL,
    source_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    file_count INTEGER NOT NULL DEFAULT 0,
    storage_size_bytes BIGINT NOT NULL DEFAULT 0,
    status VARCHAR(32) NOT NULL DEFAULT 'ready',
    archived_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS ft_archive_items_space_time_idx ON ft_archive_items (space_id, archived_at DESC);
