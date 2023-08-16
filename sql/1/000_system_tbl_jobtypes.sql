INSERT INTO system.tbl_jobtypes (type, description) VALUES
('BISUHSTAT0', 'Send UHSTAT0 data'),
('BISUHSTAT1', 'Send UHSTAT1 data')
ON CONFLICT (type) DO NOTHING;
