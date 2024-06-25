import {CoreRESTClient} from '../../../../../js/RESTClient.js';

const HAUPTBERUF_TIMEOUT = 5000;

const HauptberufAPI = {
	methods: {
		callGetHauptberufe: function(studiensemester_kurzbz, successCallback) {
			return CoreRESTClient.get(
				'extensions/FHC-Core-BIS/PersonalmeldungHauptberuf/getHauptberufe',
				{
					studiensemester_kurzbz: studiensemester_kurzbz
				},
				{
					timeout: HAUPTBERUF_TIMEOUT
				}
			).then(
				result => {
					if (CoreRESTClient.hasData(result.data))
					{
						successCallback(CoreRESTClient.getData(result.data));
					}
				}
			).catch(
				error => {
					this.$fhcAlert.handleSystemError(error);
				}
			);
		},
		callGetHauptberufeByUid: function(mitarbeiter_uid, successCallback) {
			return CoreRESTClient.get(
				'extensions/FHC-Core-BIS/PersonalmeldungHauptberuf/getHauptberufeByUid',
				{
					mitarbeiter_uid: mitarbeiter_uid
				},
				{
					timeout: HAUPTBERUF_TIMEOUT
				}
			).then(
				result => {
					if (CoreRESTClient.hasData(result.data))
					{
						successCallback(CoreRESTClient.getData(result.data));
					}
				}
			).catch(
				error => {
					this.$fhcAlert.handleSystemError(error);
				}
			);
		},
		callAddHauptberuf:function(data, successCallback, errorCallback) {
			return CoreRESTClient.post(
				'extensions/FHC-Core-BIS/PersonalmeldungHauptberuf/addHauptberuf',
				data,
				{
					timeout: HAUPTBERUF_TIMEOUT
				}
			).then(
				result => {
					if (CoreRESTClient.isError(result.data))
					{
						errorCallback(result.data.retval);
					}
					else if (CoreRESTClient.hasData(result.data))
					{
						successCallback(CoreRESTClient.getData(result.data));
					}
				}
			).catch(
				error => {
					this.$fhcAlert.handleSystemError(error);
				}
			);
		},
		callUpdateHauptberuf:function(data, successCallback, errorCallback) {
			return CoreRESTClient.post(
				'extensions/FHC-Core-BIS/PersonalmeldungHauptberuf/updateHauptberuf',
				data,
				{
					timeout: HAUPTBERUF_TIMEOUT
				}
			).then(
				result => {
					if (CoreRESTClient.isError(result.data))
					{
						errorCallback(result.data.retval);
					}
					else if (CoreRESTClient.hasData(result.data))
					{
						successCallback(CoreRESTClient.getData(result.data));
					}
				}
			).catch(
				error => {
					this.$fhcAlert.handleSystemError(error);
				}
			);
		},
		callDeleteHauptberuf: function(bis_hauptberuf_id, successCallback, errorCallback) {
			return CoreRESTClient.post(
				'extensions/FHC-Core-BIS/PersonalmeldungHauptberuf/deleteHauptberuf',
				{
					bis_hauptberuf_id: bis_hauptberuf_id
				},
				{
					timeout: HAUPTBERUF_TIMEOUT
				}
			).then(
				result => {
					if (CoreRESTClient.isError(result.data))
					{
						errorCallback(result.data.retval);
					}
					else if (CoreRESTClient.hasData(result.data))
					{
						successCallback(CoreRESTClient.getData(result.data));
					}
				}
			).catch(
				error => {
					this.$fhcAlert.handleSystemError(error);
				}
			);
		},
		callGetHauptberufcodeList: function(successCallback) {
			return CoreRESTClient.get(
				'extensions/FHC-Core-BIS/PersonalmeldungHauptberuf/getHauptberufcodeList',
				null,
				{
					timeout: HAUPTBERUF_TIMEOUT
				}
			).then(
				result => {
					if (CoreRESTClient.hasData(result.data))
					{
						successCallback(CoreRESTClient.getData(result.data));
					}
				}
			).catch(
				error => {
					this.$fhcAlert.handleSystemError(error);
				}
			);
		}
	}
};

export default HauptberufAPI;
