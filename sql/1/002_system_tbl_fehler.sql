INSERT INTO system.tbl_fehler (fehlercode, fehler_kurzbz, fehlercode_extern, fehlertext, fehlertyp_kurzbz, app) VALUES
/* Errors */
('BIS_UHSTAT0_0001', 'uhstatOrgformFehlt', NULL, 'Organisationsform nicht gefunden; prestudent_id %s; Studiensemester %s', 'error', 'bis'),
('BIS_UHSTAT0_0002', 'uhstatSvnrUndEkzFehlt', NULL, 'Weder Svnr noch Ersatzkennzeichen vorhanden', 'error', 'bis'),
('BIS_UHSTAT0_0003', 'uhstatStaatsbuergerschaftFehlt', NULL, 'Staatsbürgerschaft nicht vorhanden', 'error', 'bis'),
('BIS_UHSTAT0_0004', 'uhstatZgvOderZgvMasterFehlt', NULL, 'Zgv/Zgv Master nicht vorhanden; prestudent_id %s', 'error', 'bis')
/* Warnings */
ON CONFLICT (fehlercode) DO NOTHING;
