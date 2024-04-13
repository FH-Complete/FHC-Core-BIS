INSERT INTO system.tbl_fehler (fehlercode, fehler_kurzbz, fehlercode_extern, fehlertext, fehlertyp_kurzbz, app) VALUES
/* UHSTAT errors */
('BIS_UHSTAT0_0001', 'uhstatOrgformFehlt', NULL, 'Organisationsform nicht gefunden; prestudent_id %s; Studiensemester %s', 'error', 'bis'),
('BIS_UHSTAT0_0002', 'uhstatSvnrUndEkzFehlt', NULL, 'Weder Svnr noch Ersatzkennzeichen vorhanden', 'error', 'bis'),
('BIS_UHSTAT0_0003', 'uhstatStaatsbuergerschaftFehlt', NULL, 'Staatsbürgerschaft nicht vorhanden', 'error', 'bis'),
('BIS_UHSTAT0_0004', 'uhstatZgvOderZgvMasterFehlt', NULL, 'Zgv/Zgv Master nicht vorhanden; prestudent_id %s', 'error', 'bis'),
('BIS_UHSTAT0_0005', 'meldeStudiengangKzFehlt', NULL, 'Melde-Studiengangskennzahl nicht vorhanden; prestudent_id %s', 'error', 'bis'),
('BIS_UHSTAT1_0001', 'uhstatGeburtsnationFehlt', NULL, 'Geburtsnation nicht vorhanden', 'error', 'bis'),

/* Personalmeldung errors */
('BIS_PERSONALMELDUNG_0001', 'aktiveMitarbeiterOhneDienstverhaeltnis', NULL, 'Zu meldende*r (bismelden JA) Aktive*r Mitarbeiter*in ohne aktuelles Dienstverhältnis; uid %s', 'error', 'personalmeldung'),
('BIS_PERSONALMELDUNG_0002', 'aktiveFixeMitarbeiterOhneDienstverhaeltnis', NULL, 'Aktive*r fixe*r Mitarbeiter*in ohne aktuelles Dienstverhältnis; uid %s', 'error', 'personalmeldung'),
('BIS_PERSONALMELDUNG_0003', 'inaktiveMitarbeiterMitDienstverhaeltnis', NULL, 'Inaktive*r Mitarbeiter*in mit aktuellem Dienstverhältnis; uid %s; Dienstverhältnis Id %s', 'error', 'personalmeldung'),
('BIS_PERSONALMELDUNG_0004', 'hauptberufcodeOhneLehreVerwendung', NULL, 'Mitarbeiter*in mit Hauptberufcode und falscher Verwendung; uid %s', 'error', 'personalmeldung'),
('BIS_PERSONALMELDUNG_0005', 'aktiveFreieLektorenOhneLehreVerwendung', NULL, 'Aktiver freier Lektor mit falscher Verwendung; uid %s; Studiensemester %s', 'error', 'personalmeldung'),
('BIS_PERSONALMELDUNG_0006', 'lehrauftragOhneDienstverhaeltnis', NULL, 'Lehrauftrag ohne aktuelles Dienstverhältnis; uid %s; Studiensemester %s', 'error', 'personalmeldung'),
('BIS_PERSONALMELDUNG_0007', 'mitarbeiterOhneDienstverhaeltnisBismelden', NULL, 'Zu meldende*r (bismelden JA) Mitarbeiter*in ohne Dienstverhältnis, uid %s', 'error', 'personalmeldung'),
('BIS_PERSONALMELDUNG_0008', 'mitarbeiterMitDienstverhaeltnisOhneVerwendung', NULL, 'Mitarbeiter*in mit Dienstverhältnis, aber ohne Verwendung; uid %s; Dienstverhältnis Id %s', 'error', 'personalmeldung'),
('BIS_PERSONALMELDUNG_0009', 'mitarbeiterOhneStammdaten', NULL, 'Mitarbeiter*in mit fehlenden Stammdaten: %s; uid %s', 'error', 'personalmeldung'),
('BIS_PERSONALMELDUNG_0010', 'mitarbeiterUngueltigesGeburtsjahr', NULL, 'Mitarbeiter*in mit ungültigem Geburtsjahr: %s', 'error', 'personalmeldung'),
('BIS_PERSONALMELDUNG_0011', 'mitarbeiterUngueltigesVzae', NULL, 'Mitarbeiter*in mit ungültiger Vollzeitäquivalenz: %s; uid %s', 'error', 'personalmeldung'),
('BIS_PERSONALMELDUNG_0012', 'mitarbeiterUngueltigeSemesterwochenstunden', NULL, 'Mitarbeiter*in mit ungültigen Semesterwochenstunden: %s; uid %s', 'error', 'personalmeldung')
ON CONFLICT (fehlercode) DO NOTHING;
