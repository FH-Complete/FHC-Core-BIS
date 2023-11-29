CREATE TABLE IF NOT EXISTS extension.tbl_bis_vertragsart_beschaeftigungsart1 (
	vertragsart_kurzbz varchar(32) NOT NULL,
	ba1code smallint NOT NULL
);

COMMENT ON TABLE extension.tbl_bis_vertragsart_beschaeftigungsart1 IS 'Table to map Vertragsart to Beschaeftigungsart';

DO $$
	BEGIN
		ALTER TABLE extension.tbl_bis_vertragsart_beschaeftigungsart1 ADD CONSTRAINT tbl_bis_vertragsart_beschaeftigungsart1_pk PRIMARY KEY (vertragsart_kurzbz);
	EXCEPTION WHEN OTHERS THEN NULL;
	END $$;

DO $$
	BEGIN
		ALTER TABLE extension.tbl_bis_vertragsart_beschaeftigungsart1
			ADD CONSTRAINT tbl_vertragsart_fk FOREIGN KEY (vertragsart_kurzbz)
				REFERENCES hr.tbl_vertragsart(vertragsart_kurzbz) ON UPDATE CASCADE ON DELETE RESTRICT;
	EXCEPTION WHEN OTHERS THEN NULL;
	END $$;

GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE extension.tbl_bis_vertragsart_beschaeftigungsart1 TO vilesci;
GRANT SELECT ON TABLE extension.tbl_bis_vertragsart_beschaeftigungsart1 TO web;

INSERT INTO extension.tbl_bis_vertragsart_beschaeftigungsart1 (vertragsart_kurzbz, ba1code) VALUES
('echterdv', 3),
('freierdv', 7),
('werkvertrag', 5)
ON CONFLICT (vertragsart_kurzbz) DO NOTHING;
