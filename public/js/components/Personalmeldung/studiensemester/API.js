/**
 * Copyright (C) 2023 fhcomplete.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

import {CoreRESTClient} from '../../../../../../js/RESTClient.js';

// timeout for ajax calls
const STUDIENSEMESTER_TIMEOUT = 4000;

/**
 *
 */
export const StudiensemesterAPIs = {
	getStudiensemester: function(successCallback) {
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
					alert('Keine Studiensemester Daten vorhanden');
				}
			}
		).catch(
			error => {
				alert('Fehler beim Holen der Studiensemester: ' + error.message);
			}
		);
	}
};
