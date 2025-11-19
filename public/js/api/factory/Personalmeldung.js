export default {
	getMitarbeiter(studiensemester_kurzbz) {
		return {
			method: 'get',
			url: 'extensions/FHC-Core-BIS/api/personalmeldung/getMitarbeiter',
			params: { studiensemester_kurzbz }
		};
	},
	getStudiensemester() {
		return {
			method: 'get',
			url: 'extensions/FHC-Core-BIS/api/personalmeldung/getStudiensemester'
		};
	}
};
