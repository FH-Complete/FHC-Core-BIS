export default {
	getHauptberufe(studiensemester_kurzbz) {
		return {
			method: 'get',
			url: 'extensions/FHC-Core-BIS/api/hauptberuf/getHauptberufe',
			params: { studiensemester_kurzbz }
		};
	},
	getHauptberufeByUid(mitarbeiter_uid) {
		return {
			method: 'get',
			url: 'extensions/FHC-Core-BIS/api/hauptberuf/getHauptberufeByUid',
			params: { mitarbeiter_uid }
		};
	},
	addHauptberuf(data) {
		return {
			method: 'post',
			url: 'extensions/FHC-Core-BIS/api/hauptberuf/addHauptberuf',
			params: { data }
		};
	},
	updateHauptberuf(data) {
		return {
			method: 'post',
			url: 'extensions/FHC-Core-BIS/api/hauptberuf/updateHauptberuf',
			params: { data }
		};
	},
	deleteHauptberuf(bis_hauptberuf_id) {
		return {
			method: 'post',
			url: 'extensions/FHC-Core-BIS/api/hauptberuf/deleteHauptberuf',
			params: { bis_hauptberuf_id }
		};
	},
	getHauptberufcodeList() {
		return {
			method: 'get',
			url: 'extensions/FHC-Core-BIS/api/hauptberuf/getHauptberufcodeList'
		};
	},
};
