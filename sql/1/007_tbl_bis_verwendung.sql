CREATE TABLE IF NOT EXISTS extension.tbl_bis_verwendung (
	bis_verwendung_id bigint NOT NULL,
	mitarbeiter_uid varchar(32) NOT NULL,
	verwendung_code smallint NOT NULL,
	von date,
	bis date,
	insertamum timestamp default NOW()
);

COMMENT ON TABLE extension.tbl_bis_verwendung IS 'Table to save Bis Verwendungen for Mitarbeiter';

CREATE SEQUENCE IF NOT EXISTS extension.tbl_bis_verwendung_bis_verwendung_id_seq
	INCREMENT BY 1
	NO MAXVALUE
	NO MINVALUE
	CACHE 1;

GRANT SELECT, UPDATE ON extension.tbl_bis_verwendung_bis_verwendung_id_seq TO vilesci;

ALTER TABLE extension.tbl_bis_verwendung ALTER COLUMN bis_verwendung_id SET DEFAULT nextval('extension.tbl_bis_verwendung_bis_verwendung_id_seq');

DO $$
	BEGIN
		ALTER TABLE extension.tbl_bis_verwendung ADD CONSTRAINT tbl_bis_verwendung_pk PRIMARY KEY (bis_verwendung_id);
	EXCEPTION WHEN OTHERS THEN NULL;
	END $$;

DO $$
	BEGIN
		ALTER TABLE extension.tbl_bis_verwendung
			ADD CONSTRAINT tbl_mitarbeiter_fk FOREIGN KEY (mitarbeiter_uid)
				REFERENCES public.tbl_mitarbeiter(mitarbeiter_uid) ON UPDATE CASCADE ON DELETE RESTRICT;
	EXCEPTION WHEN OTHERS THEN NULL;
	END $$;

DO $$
	BEGIN
		ALTER TABLE extension.tbl_bis_verwendung
			ADD CONSTRAINT tbl_verwendung_fk FOREIGN KEY (verwendung_code)
				REFERENCES bis.tbl_verwendung(verwendung_code) ON UPDATE CASCADE ON DELETE RESTRICT;
	EXCEPTION WHEN OTHERS THEN NULL;
	END $$;

DO $$
	BEGIN
		ALTER TABLE extension.tbl_bis_verwendung
			ADD CONSTRAINT tbl_verwendung_uk UNIQUE (mitarbeiter_uid, verwendung_code, von, bis);
	EXCEPTION WHEN OTHERS THEN NULL;
	END $$;

GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE extension.tbl_bis_verwendung TO vilesci;
GRANT SELECT ON TABLE extension.tbl_bis_verwendung TO web;
