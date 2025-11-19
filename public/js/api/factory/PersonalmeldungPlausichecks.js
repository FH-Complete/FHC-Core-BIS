export default {
	runPlausichecks(studiensemester_kurzbz) {
		return {
			method: 'get',
			url: 'extensions/FHC-Core-BIS/api/plausichecks/runChecks',
			params: { studiensemester_kurzbz }
		};
	}
};
