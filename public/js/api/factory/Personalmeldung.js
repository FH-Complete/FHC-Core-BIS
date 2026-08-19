export default {
	getMitarbeiter(studiensemester_kurzbz) {
		return {
			method: 'get',
			url: 'extensions/FHC-Core-BIS/api/frontend/v1/personalmeldung/getMitarbeiter',
			params: { studiensemester_kurzbz }
		};
	},
	getStudiensemester() {
		return {
			method: 'get',
			url: 'extensions/FHC-Core-BIS/api/frontend/v1/personalmeldung/getStudiensemester'
		};
	}
};
