import {CoreRESTClient} from '../../../../../js/RESTClient.js';

const PERSONALMELDUNG_TIMEOUT = 5000;

const PersonalmeldungAPI = {
	methods: {
		callGetMitarbeiter: function(studiensemester_kurzbz, successCallback) {
			return CoreRESTClient.get(
				'extensions/FHC-Core-BIS/Personalmeldung/getMitarbeiter',
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
		callRunPlausichecks: function(studiensemester_kurzbz, successCallback) {
			return CoreRESTClient.get(
				'extensions/FHC-Core-BIS/PersonalmeldungPlausichecks/runChecks',
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
		}
	}
};

export default PersonalmeldungAPI;
