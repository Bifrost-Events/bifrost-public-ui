-- Quality bootstrap: én admin-bruker for staging (idempotent)
-- Brukes til innlogging og UI-opprettelse av apper/domener/orgs.
-- password: QualityAdmin123!

INSERT INTO person_people (
    first_name, last_name, display_name, email, status,
    legacy_source, legacy_table, legacy_id
)
SELECT 'Quality', 'Admin', 'Quality Admin', 'quality.admin@bifrost.test', 'active',
       'quality_seed', 'bootstrap', 'quality-admin-person'
WHERE NOT EXISTS (
    SELECT 1 FROM person_people
    WHERE legacy_source = 'quality_seed'
      AND legacy_table = 'bootstrap'
      AND legacy_id = 'quality-admin-person'
);

INSERT INTO auth_users (
    person_id, username, email, password_hash, status,
    legacy_source, legacy_table, legacy_id
)
SELECT p.person_id, 'quality.admin', 'quality.admin@bifrost.test',
       '$2y$10$oBBe6QEq9CgXgfapyjXqM.cqTQXuR8N/hR4Bm6mXX0hGBSs/HZP.C',
       'active',
       'quality_seed', 'bootstrap', 'quality-admin-user'
FROM person_people p
WHERE p.legacy_source = 'quality_seed'
  AND p.legacy_table = 'bootstrap'
  AND p.legacy_id = 'quality-admin-person'
  AND NOT EXISTS (
      SELECT 1 FROM auth_users
      WHERE legacy_source = 'quality_seed'
        AND legacy_table = 'bootstrap'
        AND legacy_id = 'quality-admin-user'
  );

UPDATE auth_users
SET password_hash = '$2y$10$oBBe6QEq9CgXgfapyjXqM.cqTQXuR8N/hR4Bm6mXX0hGBSs/HZP.C'
WHERE email = 'quality.admin@bifrost.test'
  AND legacy_source = 'quality_seed'
  AND legacy_table = 'bootstrap'
  AND legacy_id = 'quality-admin-user';
