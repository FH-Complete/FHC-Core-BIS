export default {
	getHauptberufe(studiensemester_kurzbz) {
		return {
			method: 'get',
			url: 'extensions/FHC-Core-BIS/api/frontend/v1/hauptberuf/getHauptberufe',
			params: { studiensemester_kurzbz }
		};
	},
	getHauptberufeByUid(mitarbeiter_uid) {
		return {
			method: 'get',
			url: 'extensions/FHC-Core-BIS/api/frontend/v1/hauptberuf/getHauptberufeByUid',
			params: { mitarbeiter_uid }
		};
	},
	addHauptberuf(data) {
		return {
			method: 'post',
			url: 'extensions/FHC-Core-BIS/api/frontend/v1/hauptberuf/addHauptberuf',
			params: { data }
		};
	},
	updateHauptberuf(data) {
		return {
			method: 'post',
			url: 'extensions/FHC-Core-BIS/api/frontend/v1/hauptberuf/updateHauptberuf',
			params: { data }
		};
	},
	deleteHauptberuf(bis_hauptberuf_id) {
		return {
			method: 'post',
			url: 'extensions/FHC-Core-BIS/api/frontend/v1/hauptberuf/deleteHauptberuf',
			params: { bis_hauptberuf_id }
		};
	},
	getHauptberufcodeList() {
		return {
			method: 'get',
			url: 'extensions/FHC-Core-BIS/api/frontend/v1/hauptberuf/getHauptberufcodeList'
		};
	},
};
