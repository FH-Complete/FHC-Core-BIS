export default {
	generateVerwendungen(studiensemester_kurzbz) {
		return {
			method: 'get',
			url: 'extensions/FHC-Core-BIS/api/frontend/v1/verwendungen/generateVerwendungen',
			params: { studiensemester_kurzbz }
		};
	},
	getVerwendungen(studiensemester_kurzbz) {
		return {
			method: 'get',
			url: 'extensions/FHC-Core-BIS/api/frontend/v1/verwendungen/getVerwendungen',
			params: { studiensemester_kurzbz }
		};
	},
	getVerwendungenByUid(mitarbeiter_uid) {
		return {
			method: 'get',
			url: 'extensions/FHC-Core-BIS/api/frontend/v1/verwendungen/getVerwendungenByUid',
			params: { mitarbeiter_uid }
		};
	},
	addVerwendung(data) {
		return {
			method: 'post',
			url: 'extensions/FHC-Core-BIS/api/frontend/v1/verwendungen/addVerwendung',
			params: { data }
		};
	},
	updateVerwendung(data) {
		return {
			method: 'post',
			url: 'extensions/FHC-Core-BIS/api/frontend/v1/verwendungen/updateVerwendung',
			params: {
				bis_verwendung_id: data.bis_verwendung_id,
				verwendung_code: data.verwendung_code
			},
		};
	},
	deleteVerwendung(bis_verwendung_id) {
		return {
			method: 'post',
			url: 'extensions/FHC-Core-BIS/api/frontend/v1/verwendungen/deleteVerwendung',
			params: { bis_verwendung_id }
		};
	},
	getVerwendungList(verwendung_code) {
		return {
			method: 'get',
			url: 'extensions/FHC-Core-BIS/api/frontend/v1/verwendungen/getVerwendungList',
			params: { verwendung_code }
		};
	},
	getFullVerwendungList() {
		return {
			method: 'get',
			url: 'extensions/FHC-Core-BIS/api/frontend/v1/verwendungen/getFullVerwendungList'
		};
	},
	getMitarbeiterUids(studiensemester_kurzbz, mitarbeiter_uid_searchtext) {
		return {
			method: 'get',
			url: 'extensions/FHC-Core-BIS/api/frontend/v1/verwendungen/getMitarbeiterUids',
			params: {
				studiensemester_kurzbz: studiensemester_kurzbz,
				mitarbeiter_uid_searchtext: mitarbeiter_uid_searchtext
			}
		};
	}
};
