CREATE TABLE IF NOT EXISTS extension.tbl_bis_hauptberuf (
	bis_hauptberuf_id bigint NOT NULL,
	mitarbeiter_uid varchar(32) NOT NULL,
	hauptberuflich boolean NOT NULL DEFAULT TRUE,
	hauptberufcode integer,
	von date,
	bis date,
	insertamum timestamp default NOW(),
	insertvon varchar(32),
	updateamum timestamp,
	updatevon varchar(32)
);

COMMENT ON TABLE extension.tbl_bis_hauptberuf IS 'Table to save Hauptberuf for Mitarbeiter';

CREATE SEQUENCE IF NOT EXISTS extension.tbl_bis_hauptberuf_bis_hauptberuf_id_seq
	INCREMENT BY 1
	NO MAXVALUE
	NO MINVALUE
	CACHE 1;

GRANT SELECT, UPDATE ON extension.tbl_bis_hauptberuf_bis_hauptberuf_id_seq TO vilesci;

ALTER TABLE extension.tbl_bis_hauptberuf ALTER COLUMN bis_hauptberuf_id SET DEFAULT nextval('extension.tbl_bis_hauptberuf_bis_hauptberuf_id_seq');

DO $$
	BEGIN
		ALTER TABLE extension.tbl_bis_hauptberuf ADD CONSTRAINT tbl_bis_bis_hauptberuf_id_pkey PRIMARY KEY (bis_hauptberuf_id);
	EXCEPTION WHEN OTHERS THEN NULL;
	END $$;

DO $$
	BEGIN
		ALTER TABLE extension.tbl_bis_hauptberuf
			ADD CONSTRAINT tbl_mitarbeiter_fk FOREIGN KEY (mitarbeiter_uid)
				REFERENCES public.tbl_mitarbeiter(mitarbeiter_uid) ON UPDATE CASCADE ON DELETE RESTRICT;
	EXCEPTION WHEN OTHERS THEN NULL;
	END $$;

DO $$
	BEGIN
		ALTER TABLE extension.tbl_bis_hauptberuf
			ADD CONSTRAINT tbl_hauptberuf_fk FOREIGN KEY (hauptberufcode)
				REFERENCES bis.tbl_hauptberuf(hauptberufcode) ON UPDATE CASCADE ON DELETE RESTRICT;
	EXCEPTION WHEN OTHERS THEN NULL;
	END $$;

GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE extension.tbl_bis_hauptberuf TO vilesci;
GRANT SELECT ON TABLE extension.tbl_bis_hauptberuf TO web;
