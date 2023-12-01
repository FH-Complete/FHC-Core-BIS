/**
 * Copyright (C) 2022 fhcomplete.org
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

import {CoreRESTClient} from '../../../../../js/RESTClient.js';

/**
 *
 */
export const PersonalmeldungAPIs = {
	getMitarbeiter: function(studiensemester_kurzbz, successCallback) {
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
				alert('Fehler beim Holen der Mitarbeiter: ' + error.message);
			}
		);
	},
	runPlausichecks: function(studiensemester_kurzbz, successCallback) {
		return CoreRESTClient.get(
			'extensions/FHC-Core-BIS/Plausichecks/runChecks',
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
				alert('Fehler beim Ausführen der Plausichecks: ' + error.message);
			}
		);
	},
	saveVerwendungen: function(studiensemester_kurzbz, successCallback) {
		return CoreRESTClient.get(
			'extensions/FHC-Core-BIS/Personalmeldung/saveVerwendungen',
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
				alert('Fehler beim Aktualisieren der Verwendungen: ' + error.message);
			}
		);
	}
};
