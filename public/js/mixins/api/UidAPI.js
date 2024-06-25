import {CoreRESTClient} from '../../../../../js/RESTClient.js';
/**
 *
 */
const UidAPI = {
	methods: {
		callGetMitarbeiterUids: function(studiensemester_kurzbz, mitarbeiter_uid_searchtext, successCallback) {
			return CoreRESTClient.get(
				'extensions/FHC-Core-BIS/PersonalmeldungVerwendungen/getMitarbeiterUids',
				{
					studiensemester_kurzbz: studiensemester_kurzbz,
					mitarbeiter_uid_searchtext: mitarbeiter_uid_searchtext
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

export default UidAPI;