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

import {CoreRESTClient} from '../../../../../js/RESTClient.js';

const PERSONALMELDUNG_TIMEOUT = 4000;

/**
 *
 */
export const PersonalmeldungAPIs = {

	/**------------------------------------------- PERSONALMELDUNG -------------------------------------------**/

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
				alert('Fehler beim Ausführen der Plausichecks: ' + error.message);
			}
		);
	},

	/**------------------------------------------- VERWENDUNGEN -------------------------------------------**/

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
	},
	getVerwendungen: function(studiensemester_kurzbz, successCallback) {
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
				alert('Fehler beim Holen der Verwendungen: ' + error.message);
			}
		);
	},
	addVerwendung: function(data, successCallback, errorCallback) {
		return CoreRESTClient.post(
			'extensions/FHC-Core-BIS/PersonalmeldungVerwendungen/addVerwendung',
			data,
			{
				timeout: PERSONALMELDUNG_TIMEOUT
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
				alert('Fehler beim Aktualisieren der Verwendung: ' + error.message);
			}
		);
	},
	updateVerwendung: function(data, successCallback, errorCallback) {
		return CoreRESTClient.post(
			'extensions/FHC-Core-BIS/PersonalmeldungVerwendungen/updateVerwendung',
			{
				bis_verwendung_id: data.bis_verwendung_id,
				verwendung_code: data.verwendung_code
			},
			{
				timeout: PERSONALMELDUNG_TIMEOUT
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
				alert('Fehler beim Aktualisieren der Verwendung: ' + error.message);
			}
		);
	},
	deleteVerwendung: function(bis_verwendung_id, successCallback, errorCallback) {
		return CoreRESTClient.post(
			'extensions/FHC-Core-BIS/PersonalmeldungVerwendungen/deleteVerwendung',
			{
				bis_verwendung_id: bis_verwendung_id
			},
			{
				timeout: PERSONALMELDUNG_TIMEOUT
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
				alert('Fehler beim Löschen der Verwendung: ' + error.message);
			}
		);
	},
	getMitarbeiterUids: function(studiensemester_kurzbz, mitarbeiter_uid_searchtext, successCallback) {
		return CoreRESTClient.get(
			'extensions/FHC-Core-BIS/PersonalmeldungVerwendungen/getMitarbeiterUids',
			{
				studiensemester_kurzbz: studiensemester_kurzbz,
				mitarbeiter_uid_searchtext: mitarbeiter_uid_searchtext
			},
			{
				timeout: PERSONALMELDUNG_TIMEOUT
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
				alert('Fehler beim Holen der Mitarbeiter Uids: ' + error.message);
			}
		);
	},
	getVerwendungList: function(verwendung_code, successCallback) {
		return CoreRESTClient.get(
			'extensions/FHC-Core-BIS/PersonalmeldungVerwendungen/getVerwendungList',
			{
				verwendung_code: verwendung_code
			},
			{
				timeout: PERSONALMELDUNG_TIMEOUT
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
				alert('Fehler beim Holen der Verwendung Liste: ' + error.message);
			}
		);
	},
	getFullVerwendungList: function(successCallback) {
		return CoreRESTClient.get(
			'extensions/FHC-Core-BIS/PersonalmeldungVerwendungen/getFullVerwendungList',
			null,
			{
				timeout: PERSONALMELDUNG_TIMEOUT
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
				alert('Fehler beim Holen der Verwendung Liste: ' + error.message);
			}
		);
	},

/**------------------------------------------- HAUPTBERUF -------------------------------------------**/

	getHauptberufe: function(studiensemester_kurzbz, successCallback) {
		return CoreRESTClient.get(
			'extensions/FHC-Core-BIS/PersonalmeldungHauptberuf/getHauptberufe',
			{
				studiensemester_kurzbz: studiensemester_kurzbz
			},
			{
				timeout: PERSONALMELDUNG_TIMEOUT
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
				alert('Fehler beim Holen der Hauptberufe: ' + error.message);
			}
		);
	},
	addHauptberuf: function(data, successCallback, errorCallback) {
		return CoreRESTClient.post(
			'extensions/FHC-Core-BIS/PersonalmeldungHauptberuf/addHauptberuf',
			data,
			{
				timeout: PERSONALMELDUNG_TIMEOUT
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
				alert('Fehler beim Aktualisieren der Verwendung: ' + error.message);
			}
		);
	},
	updateHauptberuf: function(data, successCallback, errorCallback) {
		return CoreRESTClient.post(
			'extensions/FHC-Core-BIS/PersonalmeldungHauptberuf/updateHauptberuf',
			data,
			{
				timeout: PERSONALMELDUNG_TIMEOUT
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
				alert('Fehler beim Aktualisieren des Hauptberufs: ' + error.message);
			}
		);
	},
	deleteHauptberuf: function(bis_hauptberuf_id, successCallback, errorCallback) {
		return CoreRESTClient.post(
			'extensions/FHC-Core-BIS/PersonalmeldungHauptberuf/deleteHauptberuf',
			{
				bis_hauptberuf_id: bis_hauptberuf_id
			},
			{
				timeout: PERSONALMELDUNG_TIMEOUT
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
				alert('Fehler beim Löschen der Verwendung: ' + error.message);
			}
		);
	},
	getHauptberufcodeList: function(successCallback) {
		return CoreRESTClient.get(
			'extensions/FHC-Core-BIS/PersonalmeldungHauptberuf/getHauptberufcodeList',
			null,
			{
				timeout: PERSONALMELDUNG_TIMEOUT
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
				alert('Fehler beim Holen der Hauptberufcode Liste: ' + error.message);
			}
		);
	}
};
