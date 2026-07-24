-- Standardroller v1 (AB-0011) — idempotent seed (quality grunndata)

INSERT INTO auth_roles (role_key, name, description, scope_type, is_system, status)
SELECT 'org_owner', 'Organisasjonseier', 'Fullt ansvar for organisasjonen', 'organization', 1, 'active'
WHERE NOT EXISTS (SELECT 1 FROM auth_roles WHERE role_key = 'org_owner');

INSERT INTO auth_roles (role_key, name, description, scope_type, is_system, status)
SELECT 'org_admin', 'Administrator', 'Administrerer organisasjonen', 'organization', 1, 'active'
WHERE NOT EXISTS (SELECT 1 FROM auth_roles WHERE role_key = 'org_admin');

INSERT INTO auth_roles (role_key, name, description, scope_type, is_system, status)
SELECT 'org_member', 'Medlem', 'Medlem uten administrativ rolle', 'organization', 1, 'active'
WHERE NOT EXISTS (SELECT 1 FROM auth_roles WHERE role_key = 'org_member');

INSERT INTO auth_roles (role_key, name, description, scope_type, is_system, status)
SELECT 'org_contact', 'Kontaktperson', 'Kontaktperson for organisasjonen', 'organization', 1, 'active'
WHERE NOT EXISTS (SELECT 1 FROM auth_roles WHERE role_key = 'org_contact');

INSERT INTO auth_roles (role_key, name, description, scope_type, is_system, status)
SELECT 'org_treasurer', 'Kasserer', 'Økonomiansvar i organisasjonen', 'organization', 1, 'active'
WHERE NOT EXISTS (SELECT 1 FROM auth_roles WHERE role_key = 'org_treasurer');

INSERT INTO auth_roles (role_key, name, description, scope_type, is_system, status)
SELECT 'event_manager', 'Arrangementsleder', 'Ansvar for arrangement', 'event', 1, 'active'
WHERE NOT EXISTS (SELECT 1 FROM auth_roles WHERE role_key = 'event_manager');

INSERT INTO auth_roles (role_key, name, description, scope_type, is_system, status)
SELECT 'result_manager', 'Resultatansvarlig', 'Ansvar for resultater', 'event', 1, 'active'
WHERE NOT EXISTS (SELECT 1 FROM auth_roles WHERE role_key = 'result_manager');
