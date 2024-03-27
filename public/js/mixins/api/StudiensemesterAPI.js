import {CoreRESTClient} from '../../../../../js/RESTClient.js';

// timeout for ajax calls
const STUDIENSEMESTER_TIMEOUT = 4000;

/**
 *
 */
const StudiensemesterAPI = {
	methods: {
		callGetStudiensemester: function(successCallback) {
			return CoreRESTClient.get(
				'extensions/FHC-Core-BIS/Personalmeldung/getStudiensemester',
				null,
				{
					timeout: STUDIENSEMESTER_TIMEOUT
				}
			).then(
				result => {
					if (CoreRESTClient.hasData(result.data))
					{
						successCallback(CoreRESTClient.getData(result.data));
					}
					else
					{
						this.$fhcAlert.alertError('Keine Studiensemester Daten vorhanden');
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

export default StudiensemesterAPI;
