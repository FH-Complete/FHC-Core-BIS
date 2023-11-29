INSERT INTO system.tbl_app (app) VALUES
('bis'),
('personalmeldung')
ON CONFLICT (app) DO NOTHING;
