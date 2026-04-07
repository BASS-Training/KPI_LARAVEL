-- =============================================================================
-- BASS Training Center & Consultant
-- KPI Dashboard System — Refactored Database Schema (3NF)
-- Generated: 2026-04-07
-- Engine: MySQL / MariaDB | Charset: utf8mb4_unicode_ci
-- =============================================================================
--
-- ┌─────────────────────────────────────────────────────────────────────────┐
-- │  ENTITY RELATIONSHIP OVERVIEW                                           │
-- │                                                                         │
-- │  divisions ──────────────────────────────────────────────┐             │
-- │      │                                                   │             │
-- │  departments ──────────────────────────────────────┐     │             │
-- │      │                                             │     │             │
-- │  positions                                         │     │             │
-- │      │                        ┌───────────────────►│     │             │
-- │  users ──────────── tasks ────┘                         │             │
-- │      │                  └──── sla                       │             │
-- │      │                                                   │             │
-- │      └── kpi_assignments ──── kpis ──── kpi_components ─┘             │
-- │                   │                                                     │
-- │             kpi_results                                                 │
-- │                   │                                                     │
-- │             kpi_summaries                                               │
-- │                   │                                                     │
-- │               leaderboard                                               │
-- │                                                                         │
-- │  (support) activity_logs, kpi_notifications, kpi_reports, settings     │
-- └─────────────────────────────────────────────────────────────────────────┘


-- =============================================================================
-- SECTION 1 — PHASE 1 TABLES (already existed, shown for reference)
-- =============================================================================

CREATE TABLE divisions (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama            VARCHAR(100)    NOT NULL,
    kode            VARCHAR(20)     NOT NULL,
    deskripsi       TEXT,
    is_active       TINYINT(1)      NOT NULL DEFAULT 1,
    created_at      TIMESTAMP       NULL,
    updated_at      TIMESTAMP       NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_div_kode (kode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Top-level organisational divisions: IT, Sales, Marketing, etc.';


-- =============================================================================
-- SECTION 2 — NORMALIZATION TABLES (NEW)
-- =============================================================================

-- 2a. departments
-- Sub-units within a division. Replaces free-text 'departemen' on users.
CREATE TABLE departments (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama            VARCHAR(100)    NOT NULL,
    kode            VARCHAR(20)     NOT NULL  COMMENT 'Short code: BOD, HRGA_DEP, MKT_DEP, FIN_DEP...',
    division_id     BIGINT UNSIGNED           COMMENT 'Parent division; NULL for top-level depts',
    deskripsi       TEXT,
    is_active       TINYINT(1)      NOT NULL DEFAULT 1,
    created_at      TIMESTAMP       NULL,
    updated_at      TIMESTAMP       NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_dept_kode (kode),
    KEY idx_dept_division_active (division_id, is_active),
    CONSTRAINT fk_dept_division
        FOREIGN KEY (division_id) REFERENCES divisions (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Departments (normalized from users.departemen string)';


-- 2b. positions
-- Jabatan / job titles. Replaces free-text 'jabatan' on users, kpi_components, sla.
CREATE TABLE positions (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama            VARCHAR(100)    NOT NULL  COMMENT 'Full title: HR & GA Manager, IT Staff...',
    kode            VARCHAR(40)     NOT NULL  COMMENT 'Short code: HR_MGR, IT_STAFF, MKT_SALES...',
    department_id   BIGINT UNSIGNED           COMMENT 'Home department; NULL for executives',
    level           ENUM('staff','supervisor','manager','director','executive')
                                    NOT NULL DEFAULT 'staff',
    is_active       TINYINT(1)      NOT NULL DEFAULT 1,
    created_at      TIMESTAMP       NULL,
    updated_at      TIMESTAMP       NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_pos_kode (kode),
    KEY idx_pos_dept_active (department_id, is_active),
    KEY idx_pos_level (level),
    CONSTRAINT fk_pos_department
        FOREIGN KEY (department_id) REFERENCES departments (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Positions / jabatan (normalized from free-text columns)';


-- =============================================================================
-- SECTION 3 — ALTER TABLES (additive — old string columns preserved)
-- =============================================================================

-- users: add department_id, position_id (keep jabatan & departemen for now)
ALTER TABLE users
    ADD COLUMN department_id BIGINT UNSIGNED NULL
        AFTER division_id
        COMMENT 'Normalized FK replacing free-text departemen',
    ADD COLUMN position_id   BIGINT UNSIGNED NULL
        AFTER department_id
        COMMENT 'Normalized FK replacing free-text jabatan',
    ADD KEY idx_users_org_role   (division_id, department_id, role),
    ADD KEY idx_users_pos_status (position_id, status_karyawan),
    ADD CONSTRAINT fk_users_department
        FOREIGN KEY (department_id) REFERENCES departments (id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_users_position
        FOREIGN KEY (position_id) REFERENCES positions (id) ON DELETE SET NULL;


-- kpi_components: add position_id (keep jabatan string)
ALTER TABLE kpi_components
    ADD COLUMN position_id BIGINT UNSIGNED NULL
        AFTER division_id,
    ADD KEY idx_kpi_comp_pos_active (position_id, is_active),
    ADD KEY idx_kpi_comp_div_active (division_id, is_active),
    ADD CONSTRAINT fk_kpi_comp_position
        FOREIGN KEY (position_id) REFERENCES positions (id) ON DELETE SET NULL;


-- sla: add position_id (keep jabatan string)
ALTER TABLE sla
    ADD COLUMN position_id BIGINT UNSIGNED NULL
        AFTER jabatan,
    ADD CONSTRAINT fk_sla_position
        FOREIGN KEY (position_id) REFERENCES positions (id) ON DELETE SET NULL;


-- tasks: add computed duration, SLA tracking, score flag
ALTER TABLE tasks
    ADD COLUMN duration_minutes  SMALLINT UNSIGNED NULL
        AFTER waktu_selesai
        COMMENT 'waktu_selesai - waktu_mulai in minutes',
    ADD COLUMN sla_id            BIGINT UNSIGNED   NULL
        AFTER kpi_component_id
        COMMENT 'Which SLA rule was this task evaluated against',
    ADD COLUMN sla_status        ENUM('on_time','late','not_applicable')
                                 NOT NULL DEFAULT 'not_applicable'
        AFTER sla_id,
    ADD COLUMN score_generated   TINYINT(1) NOT NULL DEFAULT 0
        AFTER sla_status
        COMMENT 'TRUE if already included in a kpi_result',
    ADD KEY idx_tasks_user_date    (user_id, tanggal),
    ADD KEY idx_tasks_kpi_date     (kpi_component_id, tanggal),
    ADD KEY idx_tasks_date_status  (tanggal, status),
    ADD KEY idx_tasks_sla_status   (sla_status),
    ADD CONSTRAINT fk_tasks_sla
        FOREIGN KEY (sla_id) REFERENCES sla (id) ON DELETE SET NULL;


-- =============================================================================
-- SECTION 4 — NEW KPI SYSTEM TABLES
-- =============================================================================

-- 4a. kpis — master KPI definitions (reusable, assignable to any user)
CREATE TABLE kpis (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    kpi_component_id    BIGINT UNSIGNED NULL
        COMMENT 'Bridge to legacy kpi_components during migration',
    title               VARCHAR(255)    NOT NULL,
    description         TEXT,
    type                VARCHAR(30)     NOT NULL DEFAULT 'achievement'
        COMMENT 'achievement | percentage | boolean | csi | zero_delay | zero_error | zero_complaint | number',
    period              ENUM('daily','weekly','monthly','quarterly','yearly')
                                        NOT NULL DEFAULT 'monthly',
    unit                VARCHAR(50)     NULL     COMMENT 'Rp | % | leads | laporan | unit',
    division_id         BIGINT UNSIGNED NULL     COMMENT 'Primary division (can still be cross-assigned)',
    default_target      DECIMAL(20,4)   NULL,
    default_weight      DECIMAL(5,2)    NOT NULL DEFAULT 0.00
        COMMENT '0.00–100.00 (percentage weight in final score)',
    is_active           TINYINT(1)      NOT NULL DEFAULT 1,
    created_at          TIMESTAMP       NULL,
    updated_at          TIMESTAMP       NULL,
    deleted_at          TIMESTAMP       NULL,
    PRIMARY KEY (id),
    KEY idx_kpis_div_active    (division_id, is_active),
    KEY idx_kpis_type_period   (type, period),
    CONSTRAINT fk_kpis_component
        FOREIGN KEY (kpi_component_id) REFERENCES kpi_components (id) ON DELETE SET NULL,
    CONSTRAINT fk_kpis_division
        FOREIGN KEY (division_id) REFERENCES divisions (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Master KPI definitions — decoupled from positions/users';


-- 4b. kpi_assignments — links KPIs to individual users
CREATE TABLE kpi_assignments (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    kpi_id          BIGINT UNSIGNED NOT NULL,
    user_id         BIGINT UNSIGNED NOT NULL,
    target          DECIMAL(20,4)   NULL    COMMENT 'Per-user target; overrides kpis.default_target',
    weight          DECIMAL(5,2)    NOT NULL DEFAULT 0.00
        COMMENT 'Per-user weight; overrides kpis.default_weight',
    start_date      DATE            NOT NULL,
    end_date        DATE            NULL    COMMENT 'NULL = open-ended',
    assigned_by     BIGINT UNSIGNED NULL    COMMENT 'HR who created assignment',
    notes           TEXT            NULL,
    is_active       TINYINT(1)      NOT NULL DEFAULT 1,
    created_at      TIMESTAMP       NULL,
    updated_at      TIMESTAMP       NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_kpi_user_start     (kpi_id, user_id, start_date),
    KEY   idx_assign_user_active       (user_id, is_active, start_date),
    KEY   idx_assign_kpi_active        (kpi_id, is_active),
    KEY   idx_assign_period            (start_date, end_date),
    CONSTRAINT fk_assign_kpi
        FOREIGN KEY (kpi_id)      REFERENCES kpis (id)  ON DELETE CASCADE,
    CONSTRAINT fk_assign_user
        FOREIGN KEY (user_id)     REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_assign_by
        FOREIGN KEY (assigned_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='KPI assignments — maps KPIs to users with individual target/weight';


-- 4c. kpi_results — computed KPI results per assignment per period
CREATE TABLE kpi_results (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    assignment_id       BIGINT UNSIGNED NOT NULL,
    user_id             BIGINT UNSIGNED NOT NULL  COMMENT 'Denormalized for fast reads',
    kpi_id              BIGINT UNSIGNED NOT NULL  COMMENT 'Denormalized for fast reads',
    period_label        VARCHAR(20)     NOT NULL
        COMMENT 'ISO label: 2026-04 | 2026-W15 | 2026-04-07',
    period_type         ENUM('daily','weekly','monthly','quarterly','yearly')
                                        NOT NULL DEFAULT 'monthly',
    actual_value        DECIMAL(20,4)   NULL,
    target_value        DECIMAL(20,4)   NULL      COMMENT 'Snapshot of target at calculation time',
    achievement_rate    DECIMAL(8,2)    NULL
        COMMENT 'actual_value / target_value * 100',
    score               DECIMAL(5,2)    NULL      COMMENT 'Legacy 0–5 scale',
    final_score         DECIMAL(8,4)    NULL      COMMENT 'score * weight / 100',
    score_label         ENUM('excellent','good','average','bad') NULL,
    notes               TEXT            NULL,
    evidence_path       VARCHAR(500)    NULL,
    status              ENUM('draft','submitted','approved','rejected')
                                        NOT NULL DEFAULT 'draft',
    submitted_by        BIGINT UNSIGNED NULL,
    submitted_at        TIMESTAMP       NULL,
    calculated_at       TIMESTAMP       NULL,
    created_at          TIMESTAMP       NULL,
    updated_at          TIMESTAMP       NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_result_assignment_period (assignment_id, period_label),
    KEY   idx_results_user_period    (user_id, period_label, period_type),
    KEY   idx_results_kpi_period     (kpi_id, period_label),
    KEY   idx_results_label_period   (score_label, period_label),
    CONSTRAINT fk_results_assignment
        FOREIGN KEY (assignment_id) REFERENCES kpi_assignments (id) ON DELETE CASCADE,
    CONSTRAINT fk_results_user
        FOREIGN KEY (user_id)       REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_results_kpi
        FOREIGN KEY (kpi_id)        REFERENCES kpis (id) ON DELETE CASCADE,
    CONSTRAINT fk_results_submitted
        FOREIGN KEY (submitted_by)  REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Computed KPI results per assignment per period';


-- =============================================================================
-- SECTION 5 — AGGREGATION / ANALYTICS TABLES
-- =============================================================================

-- 5a. kpi_summaries — pre-aggregated totals per user per period
CREATE TABLE kpi_summaries (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id             BIGINT UNSIGNED NOT NULL,
    period_label        VARCHAR(20)     NOT NULL,
    period_type         ENUM('daily','weekly','monthly','quarterly','yearly')
                                        NOT NULL DEFAULT 'monthly',
    total_score         DECIMAL(8,4)    NOT NULL DEFAULT 0
        COMMENT 'SUM(final_score) from kpi_results (0–5 scale)',
    achievement_rate    DECIMAL(8,2)    NOT NULL DEFAULT 0
        COMMENT 'Weighted average of achievement_rate from kpi_results',
    score_label         ENUM('excellent','good','average','bad') NULL,
    kpi_count           SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    achieved_count      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    recalculated_at     TIMESTAMP       NULL,
    created_at          TIMESTAMP       NULL,
    updated_at          TIMESTAMP       NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_summary_user_period (user_id, period_label, period_type),
    KEY idx_summary_period_rate   (period_label, achievement_rate),
    KEY idx_summary_user_trend    (user_id, period_type, period_label),
    CONSTRAINT fk_summary_user
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Pre-aggregated KPI totals — refreshed by background job';


-- 5b. leaderboard — materialized ranking table
CREATE TABLE leaderboard (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id             BIGINT UNSIGNED NOT NULL,
    period_label        VARCHAR(20)     NOT NULL,
    period_type         ENUM('daily','weekly','monthly','quarterly','yearly')
                                        NOT NULL DEFAULT 'monthly',
    total_score         DECIMAL(8,4)    NOT NULL DEFAULT 0,
    achievement_rate    DECIMAL(8,2)    NOT NULL DEFAULT 0,
    score_label         ENUM('excellent','good','average','bad') NULL,
    rank_overall        SMALLINT UNSIGNED NULL,
    rank_in_division    SMALLINT UNSIGNED NULL,
    rank_in_dept        SMALLINT UNSIGNED NULL,
    division_id         BIGINT UNSIGNED NULL   COMMENT 'Denormalized from user for fast filter',
    department_id       BIGINT UNSIGNED NULL   COMMENT 'Denormalized from user for fast filter',
    generated_at        TIMESTAMP       NULL,
    created_at          TIMESTAMP       NULL,
    updated_at          TIMESTAMP       NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_leader_user_period  (user_id, period_label, period_type),
    KEY idx_leader_period_overall  (period_label, rank_overall),
    KEY idx_leader_period_div      (period_label, division_id, rank_in_division),
    KEY idx_leader_period_dept     (period_label, department_id, rank_in_dept),
    CONSTRAINT fk_leader_user
        FOREIGN KEY (user_id)       REFERENCES users (id)       ON DELETE CASCADE,
    CONSTRAINT fk_leader_division
        FOREIGN KEY (division_id)   REFERENCES divisions (id)   ON DELETE SET NULL,
    CONSTRAINT fk_leader_department
        FOREIGN KEY (department_id) REFERENCES departments (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Materialized leaderboard — refreshed by KpiLeaderboardJob';


-- =============================================================================
-- SECTION 6 — EXAMPLE QUERIES
-- =============================================================================

-- ─────────────────────────────────────────────────────────────
-- Q1. Hitung KPI score seorang user untuk periode tertentu
--     (menggunakan sistem baru: kpi_results)
-- ─────────────────────────────────────────────────────────────
SELECT
    u.id,
    u.nama,
    u.nip,
    p.nama                          AS jabatan,
    d.nama                          AS departemen,
    dv.nama                         AS divisi,
    SUM(r.final_score)              AS total_score,
    AVG(r.achievement_rate)         AS avg_achievement_rate,
    SUM(r.final_score) / COUNT(r.id) * 100 AS completion_pct,
    CASE
        WHEN AVG(r.achievement_rate) > 100 THEN 'Excellent'
        WHEN AVG(r.achievement_rate) >= 80  THEN 'Good'
        WHEN AVG(r.achievement_rate) >= 50  THEN 'Average'
        ELSE 'Bad'
    END                             AS predikat
FROM users u
LEFT JOIN positions   p  ON p.id = u.position_id
LEFT JOIN departments d  ON d.id = u.department_id
LEFT JOIN divisions   dv ON dv.id = u.division_id
LEFT JOIN kpi_assignments a ON a.user_id = u.id AND a.is_active = 1
LEFT JOIN kpi_results r ON r.assignment_id = a.id
                       AND r.period_label = '2026-04'
                       AND r.period_type  = 'monthly'
WHERE u.id = 5          -- target user
GROUP BY u.id, u.nama, u.nip, p.nama, d.nama, dv.nama;


-- ─────────────────────────────────────────────────────────────
-- Q2. Dashboard Direktur — top 10 & bottom 3 bulan ini
-- ─────────────────────────────────────────────────────────────
-- Top 10:
SELECT
    lb.rank_overall,
    u.nama,
    u.nip,
    p.nama              AS jabatan,
    dv.nama             AS divisi,
    lb.achievement_rate,
    lb.total_score,
    lb.score_label
FROM leaderboard lb
JOIN users     u  ON u.id  = lb.user_id
JOIN positions p  ON p.id  = u.position_id
JOIN divisions dv ON dv.id = lb.division_id
WHERE lb.period_label = '2026-04'
  AND lb.period_type  = 'monthly'
ORDER BY lb.rank_overall ASC
LIMIT 10;

-- Bottom 3 (perlu perhatian):
SELECT
    lb.rank_overall,
    u.nama,
    dv.nama             AS divisi,
    lb.achievement_rate,
    lb.score_label
FROM leaderboard lb
JOIN users     u  ON u.id  = lb.user_id
JOIN divisions dv ON dv.id = lb.division_id
WHERE lb.period_label = '2026-04'
  AND lb.period_type  = 'monthly'
ORDER BY lb.rank_overall DESC
LIMIT 3;


-- ─────────────────────────────────────────────────────────────
-- Q3. Performa per divisi bulan ini (untuk bar chart)
-- ─────────────────────────────────────────────────────────────
SELECT
    dv.nama                         AS divisi,
    dv.kode,
    COUNT(DISTINCT lb.user_id)      AS jumlah_pegawai,
    AVG(lb.achievement_rate)        AS avg_achievement,
    AVG(lb.total_score)             AS avg_score,
    SUM(CASE WHEN lb.score_label = 'excellent' THEN 1 ELSE 0 END) AS excellent,
    SUM(CASE WHEN lb.score_label = 'good'      THEN 1 ELSE 0 END) AS good,
    SUM(CASE WHEN lb.score_label = 'average'   THEN 1 ELSE 0 END) AS average_cnt,
    SUM(CASE WHEN lb.score_label = 'bad'       THEN 1 ELSE 0 END) AS bad
FROM divisions dv
LEFT JOIN leaderboard lb ON lb.division_id = dv.id
                        AND lb.period_label = '2026-04'
                        AND lb.period_type  = 'monthly'
WHERE dv.is_active = 1
GROUP BY dv.id, dv.nama, dv.kode
ORDER BY avg_achievement DESC;


-- ─────────────────────────────────────────────────────────────
-- Q4. Trend KPI seorang user selama setahun (untuk line chart)
-- ─────────────────────────────────────────────────────────────
SELECT
    ks.period_label,
    ks.total_score,
    ks.achievement_rate,
    ks.score_label,
    ks.kpi_count,
    ks.achieved_count
FROM kpi_summaries ks
WHERE ks.user_id     = 5
  AND ks.period_type = 'monthly'
  AND ks.period_label BETWEEN '2026-01' AND '2026-12'
ORDER BY ks.period_label ASC;


-- ─────────────────────────────────────────────────────────────
-- Q5. Distribusi predikat keseluruhan bulan ini (untuk pie chart)
-- ─────────────────────────────────────────────────────────────
SELECT
    score_label,
    COUNT(*)                               AS jumlah,
    ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER (), 1) AS persen
FROM kpi_summaries
WHERE period_label = '2026-04'
  AND period_type  = 'monthly'
GROUP BY score_label
ORDER BY FIELD(score_label, 'excellent', 'good', 'average', 'bad');


-- ─────────────────────────────────────────────────────────────
-- Q6. KPI achievement per komponen untuk seorang user (detail)
-- ─────────────────────────────────────────────────────────────
SELECT
    k.title                             AS kpi_name,
    k.type,
    k.period,
    k.unit,
    a.target,
    a.weight,
    r.actual_value,
    r.achievement_rate,
    r.score,
    r.final_score,
    r.score_label,
    r.status
FROM kpi_assignments a
JOIN kpis        k ON k.id = a.kpi_id
LEFT JOIN kpi_results r ON r.assignment_id = a.id
                       AND r.period_label  = '2026-04'
WHERE a.user_id   = 5
  AND a.is_active = 1
ORDER BY a.weight DESC;


-- ─────────────────────────────────────────────────────────────
-- Q7. Tasks dengan SLA late untuk monitoring HR
-- ─────────────────────────────────────────────────────────────
SELECT
    t.id,
    u.nama                  AS pegawai,
    t.judul,
    t.tanggal,
    t.duration_minutes,
    s.durasi_jam * 60       AS sla_menit,
    t.sla_status,
    kc.objectives           AS kpi_component
FROM tasks t
JOIN users          u  ON u.id  = t.user_id
LEFT JOIN sla       s  ON s.id  = t.sla_id
LEFT JOIN kpi_components kc ON kc.id = t.kpi_component_id
WHERE t.sla_status    = 'late'
  AND t.tanggal       BETWEEN '2026-04-01' AND '2026-04-30'
ORDER BY t.tanggal DESC, u.nama;


-- ─────────────────────────────────────────────────────────────
-- Q8. Assign KPI baru ke user (INSERT)
-- ─────────────────────────────────────────────────────────────
INSERT INTO kpi_assignments
    (kpi_id, user_id, target, weight, start_date, end_date, assigned_by, is_active)
VALUES
    (1,   -- kpi_id: "Sales Revenue"
     4,   -- user_id: Nadia Permatasari
     5000000000,
     60.00,
     '2026-04-01',
     '2026-12-31',
     3,   -- assigned_by: HR Manager
     1);


-- ─────────────────────────────────────────────────────────────
-- Q9. Simpan hasil KPI setelah kalkulasi (INSERT kpi_results)
-- ─────────────────────────────────────────────────────────────
INSERT INTO kpi_results
    (assignment_id, user_id, kpi_id, period_label, period_type,
     actual_value, target_value, achievement_rate,
     score, final_score, score_label, status, calculated_at)
VALUES
    (1, 4, 1, '2026-04', 'monthly',
     4750000000,    -- actual revenue
     5000000000,    -- target
     95.00,         -- achievement_rate = 4750/5000 * 100
     4,             -- legacy 0-5 score
     2.4000,        -- final_score = 4 * 60/100
     'good',        -- 80% ≤ 95% ≤ 100%
     'submitted',
     NOW());


-- ─────────────────────────────────────────────────────────────
-- Q10. Refresh kpi_summaries setelah kpi_results diperbarui
--      (dijalankan oleh KpiSummaryJob)
-- ─────────────────────────────────────────────────────────────
INSERT INTO kpi_summaries
    (user_id, period_label, period_type,
     total_score, achievement_rate, score_label,
     kpi_count, achieved_count, recalculated_at)
SELECT
    r.user_id,
    r.period_label,
    r.period_type,
    SUM(r.final_score)                              AS total_score,
    AVG(r.achievement_rate)                         AS achievement_rate,
    CASE
        WHEN AVG(r.achievement_rate) > 100 THEN 'excellent'
        WHEN AVG(r.achievement_rate) >= 80  THEN 'good'
        WHEN AVG(r.achievement_rate) >= 50  THEN 'average'
        ELSE 'bad'
    END                                             AS score_label,
    COUNT(r.id)                                     AS kpi_count,
    SUM(CASE WHEN r.achievement_rate >= 80 THEN 1 ELSE 0 END) AS achieved_count,
    NOW()                                           AS recalculated_at
FROM kpi_results r
WHERE r.period_label = '2026-04'
  AND r.user_id      = 5
GROUP BY r.user_id, r.period_label, r.period_type
ON DUPLICATE KEY UPDATE
    total_score      = VALUES(total_score),
    achievement_rate = VALUES(achievement_rate),
    score_label      = VALUES(score_label),
    kpi_count        = VALUES(kpi_count),
    achieved_count   = VALUES(achieved_count),
    recalculated_at  = VALUES(recalculated_at);


-- ─────────────────────────────────────────────────────────────
-- Q11. Generate leaderboard untuk bulan tertentu
--      (dijalankan oleh KpiLeaderboardJob)
-- ─────────────────────────────────────────────────────────────
INSERT INTO leaderboard
    (user_id, period_label, period_type, total_score, achievement_rate,
     score_label, rank_overall, rank_in_division, rank_in_dept,
     division_id, department_id, generated_at)
SELECT
    ks.user_id,
    ks.period_label,
    ks.period_type,
    ks.total_score,
    ks.achievement_rate,
    ks.score_label,
    RANK() OVER (
        PARTITION BY ks.period_label, ks.period_type
        ORDER BY ks.achievement_rate DESC, ks.total_score DESC
    )                                                   AS rank_overall,
    RANK() OVER (
        PARTITION BY ks.period_label, ks.period_type, u.division_id
        ORDER BY ks.achievement_rate DESC, ks.total_score DESC
    )                                                   AS rank_in_division,
    RANK() OVER (
        PARTITION BY ks.period_label, ks.period_type, u.department_id
        ORDER BY ks.achievement_rate DESC, ks.total_score DESC
    )                                                   AS rank_in_dept,
    u.division_id,
    u.department_id,
    NOW()                                               AS generated_at
FROM kpi_summaries ks
JOIN users u ON u.id = ks.user_id
WHERE ks.period_label = '2026-04'
  AND ks.period_type  = 'monthly'
ON DUPLICATE KEY UPDATE
    total_score      = VALUES(total_score),
    achievement_rate = VALUES(achievement_rate),
    score_label      = VALUES(score_label),
    rank_overall     = VALUES(rank_overall),
    rank_in_division = VALUES(rank_in_division),
    rank_in_dept     = VALUES(rank_in_dept),
    generated_at     = VALUES(generated_at);
