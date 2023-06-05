INSERT INTO system.tbl_jobtypes (type, description) VALUES
('BISUHSTAT0', 'Send UHSTAT0 data')
ON CONFLICT (type) DO NOTHING;
