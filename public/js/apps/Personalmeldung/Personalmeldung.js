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

import {CoreFilterCmpt} from '../../../../../js/components/filter/Filter.js';
import {CoreNavigationCmpt} from '../../../../../js/components/navigation/Navigation.js';
import {PersonalmeldungAPIs} from './API.js';

const personalmeldungApp = Vue.createApp({
	data: function() {
		return {
			personalmeldungTabulatorOptions: {
				maxHeight: "100%",
				minHeight: 50,
				layout: 'fitColumns',
				columns: [
					{title: 'Uid',field: 'uid', headerFilter: true},
					{title: 'Vorname',field: 'vorname', headerFilter: true},
					{title: 'Nachname',field: 'nachname', headerFilter: true}
				]
			},
			semList: null, // all Studiensemester for dropdown
			currSem: null, // selected Studiensemester
		};
	},
	components: {
		CoreNavigationCmpt,
		CoreFilterCmpt,
		PersonalmeldungAPIs,
	},
	created() {
		this.getStudiensemester();
	},
	methods: {
		/**
		 * get Studiensemester
		 */
		getStudiensemester: function() {
			PersonalmeldungAPIs.getStudiensemester(
				(data) => {
					// set the Studiensemester data
					this.semList = data.semList;
					this.currSem = data.currSem;
				}
			);
		},
		/**
		 * get Mitarbeiter
		 */
		getMitarbeiter: function() {
			PersonalmeldungAPIs.getMitarbeiter(
				this.currSem,
				(data) => {
					// set the employee data
					this.$refs.personalmeldungTable.tabulator.setData(data);
				}
			);
		},
		/**
		 * Download XML by changing url
		 */
		downloadPersonalmeldungXml: function() {
			window.location = 'Personalmeldung/downloadPersonalmeldungXml?studiensemester_kurzbz='+encodeURIComponent(this.currSem);
		}
	}
});

personalmeldungApp.mount('#main');
