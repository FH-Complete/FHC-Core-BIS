import {CoreRESTClient} from '../../../../../js/RESTClient.js';

const VERWENDUNGEN_TIMEOUT = 5000;

const VerwendungenAPI = {
	methods: {
		callGenerateVerwendungen: function(studiensemester_kurzbz, successCallback) {
			return CoreRESTClient.get(
				'extensions/FHC-Core-BIS/PersonalmeldungVerwendungen/generateVerwendungen',
				{
					studiensemester_kurzbz: studiensemester_kurzbz
				},
				{
					timeout: null
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
		callGetVerwendungen: function(studiensemester_kurzbz, successCallback) {
			return CoreRESTClient.get(
				'extensions/FHC-Core-BIS/PersonalmeldungVerwendungen/getVerwendungen',
				{
					studiensemester_kurzbz: studiensemester_kurzbz
				},
				{
					timeout: null
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
		callGetVerwendungenByUid: function(mitarbeiter_uid, successCallback) {
			return CoreRESTClient.get(
				'extensions/FHC-Core-BIS/PersonalmeldungVerwendungen/getVerwendungenByUid',
				{
					mitarbeiter_uid: mitarbeiter_uid
				},
				{
					timeout: VERWENDUNGEN_TIMEOUT
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
		callAddVerwendung:function(data, successCallback, errorCallback) {
			return CoreRESTClient.post(
				'extensions/FHC-Core-BIS/PersonalmeldungVerwendungen/addVerwendung',
				data,
				{
					timeout: VERWENDUNGEN_TIMEOUT
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
		callUpdateVerwendung:function(data, successCallback, errorCallback) {
			return CoreRESTClient.post(
				'extensions/FHC-Core-BIS/PersonalmeldungVerwendungen/updateVerwendung',
				{
					bis_verwendung_id: data.bis_verwendung_id,
					verwendung_code: data.verwendung_code
				},
				{
					timeout: VERWENDUNGEN_TIMEOUT
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
		callDeleteVerwendung: function(bis_verwendung_id, successCallback, errorCallback) {
			return CoreRESTClient.post(
				'extensions/FHC-Core-BIS/PersonalmeldungVerwendungen/deleteVerwendung',
				{
					bis_verwendung_id: bis_verwendung_id
				},
				{
					timeout: VERWENDUNGEN_TIMEOUT
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
		callGetVerwendungList: function(verwendung_code, successCallback) {
			return CoreRESTClient.get(
				'extensions/FHC-Core-BIS/PersonalmeldungVerwendungen/getVerwendungList',
				{
					verwendung_code: verwendung_code
				},
				{
					timeout: VERWENDUNGEN_TIMEOUT
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
		callGetFullVerwendungList: function(successCallback) {
			return CoreRESTClient.get(
				'extensions/FHC-Core-BIS/PersonalmeldungVerwendungen/getFullVerwendungList',
				null,
				{
					timeout: VERWENDUNGEN_TIMEOUT
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

export default VerwendungenAPI;
