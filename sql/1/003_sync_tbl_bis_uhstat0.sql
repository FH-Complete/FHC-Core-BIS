CREATE TABLE IF NOT EXISTS sync.tbl_bis_uhstat0 (
	uhstat0_id bigint NOT NULL,
	prestudent_id integer NOT NULL,
	studiensemester_kurzbz varchar(16) NOT NULL,
	meldedatum date NOT NULL,
	insertamum timestamp DEFAULT now()
);

COMMENT ON TABLE sync.tbl_bis_uhstat0 IS 'Table to save information about sent UHSTAT0 data';
COMMENT ON COLUMN sync.tbl_bis_uhstat0.prestudent_id IS 'id of prestudent sent';
COMMENT ON COLUMN sync.tbl_bis_uhstat0.studiensemester_kurzbz IS 'semester for which the uhstat data was sent';
COMMENT ON COLUMN sync.tbl_bis_uhstat0.meldedatum IS 'day on which uhstat data was sent';

CREATE SEQUENCE IF NOT EXISTS sync.tbl_bis_uhstat0_uhstat0_id_seq
	INCREMENT BY 1
	NO MAXVALUE
	NO MINVALUE
	CACHE 1;

GRANT SELECT, UPDATE ON sync.tbl_bis_uhstat0_uhstat0_id_seq TO vilesci;

ALTER TABLE sync.tbl_bis_uhstat0 ALTER COLUMN uhstat0_id SET DEFAULT nextval('sync.tbl_bis_uhstat0_uhstat0_id_seq');

DO $$
	BEGIN
		ALTER TABLE sync.tbl_bis_uhstat0 ADD CONSTRAINT tbl_bis_uhstat0_pkey PRIMARY KEY (uhstat0_id);
	EXCEPTION WHEN OTHERS THEN NULL;
	END $$;

DO $$
	BEGIN
		ALTER TABLE sync.tbl_bis_uhstat0
			ADD CONSTRAINT tbl_bis_uhstat0_prestudent_id_fkey FOREIGN KEY (prestudent_id)
				REFERENCES public.tbl_prestudent(prestudent_id) ON UPDATE CASCADE ON DELETE RESTRICT;
	EXCEPTION WHEN OTHERS THEN NULL;
	END $$;

DO $$
	BEGIN
		ALTER TABLE sync.tbl_bis_uhstat0
			ADD CONSTRAINT tbl_bis_uhstat0_studiensemester__kurzbz_fkey FOREIGN KEY (studiensemester_kurzbz)
				REFERENCES public.tbl_studiensemester(studiensemester_kurzbz) ON UPDATE CASCADE ON DELETE RESTRICT;
	EXCEPTION WHEN OTHERS THEN NULL;
	END $$;

GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE sync.tbl_bis_uhstat0 TO vilesci;
GRANT SELECT ON TABLE sync.tbl_bis_uhstat0 TO web;
