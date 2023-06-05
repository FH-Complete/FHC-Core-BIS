INSERT INTO system.tbl_app (app) VALUES
('bis')
ON CONFLICT (app) DO NOTHING;
