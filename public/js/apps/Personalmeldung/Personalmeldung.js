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
				layout: 'fitDataFill',
				columns: [
					{title: 'PersNr', field: 'personalnummer', headerFilter: true},
					{title: 'Uid', field: 'uid', headerFilter: true},
					{title: 'Vorname', field: 'vorname', headerFilter: true},
					{title: 'Nachname', field: 'nachname', headerFilter: true},
					{title: 'Geschlecht (X)', field: 'geschlechtX', headerFilter: true, visible: false},
					{title: 'Geburtsjahr', field: 'geburtsjahr', headerFilter: true, visible: false},
					{title: 'Staat', field: 'staatsangehoerigkeit', headerFilter: true, visible: false},
					{title: 'Höchste Ausb.', field: 'hoechste_abgeschlossene_ausbildung', headerFilter: true, visible: false},
					{title: 'Habilitation', field: 'habilitation', headerFilter: true, visible: false},
					{title: 'Hauptberufcode', field: 'hauptberufcode', headerFilter: true, visible: false},
					{title: 'Verwendungen', field: 'verwendungen', headerFilter: true,
						formatter: function(cell, formatterParams, onRendered) {
							let verwendungen = cell.getValue();
							let html = ''+
								'<table class="table table-bordered">'+
									'<tr>'+
										'<th>'+
											'Verwendung'+
										'</th>'+
										'<th>'+
											'Ba1Code'+
										'</th>'+
										'<th>'+
											'Ba2Code'+
										'</th>'+
										'<th>'+
											'VZÄ'+
										'</th>'+
										'<th>'+
											'JVZÄ'+
										'</th>'+
									'</tr>';
							for (let verwendung of verwendungen)
							{
								html +=
									'<tr>'+
										'<td>'+
											verwendung.verwendung_code +
										'</td>'+
										'<td>'+
											verwendung.ba1code +
										'</td>'+
										'<td>'+
											verwendung.ba2code +
										'</td>'+
										'<td>'+
											+parseFloat(verwendung.vzae).toFixed(2) +
										'</td>'+
										'<td>'+
											+parseFloat(verwendung.jvzae).toFixed(2) +
										'</td>'+
									'</tr>';
							}

							html += '</table>';

							return html;
						}
					},
					{title: 'Funktionen', field: 'funktionen', headerFilter: true,
						formatter: function(cell, formatterParams, onRendered) {
							let funktionen = cell.getValue();
							let html = ''+
								'<table class="table table-bordered">'+
									'<tr>'+
										'<th>'+
											'Funktion'+
										'</th>'+
										'<th>'+
											'Bes.Quali'+
										'</th>'+
										'<th>'+
											'StgKz'+
										'</th>'+
									'</tr>';

							for (let funktion of funktionen)
							{
								html +=
									'<tr>'+
										'<td>'+
											funktion.funktionscode +
										'</td>'+
										'<td>'+
											funktion.besondereQualifikationCode +
										'</td>'+
										'<td>';

										for (let stgKz of funktion.studiengang)
										{
											html += stgKz;
										}

								html += '</td>'+
									'</tr>';
							}

							html += '</table>';

							return html;
						}
					},
					{title: 'Lehre', field: 'lehre', headerFilter: true,
						formatter: function(cell, formatterParams, onRendered) {
							let lehre = cell.getValue();
							let html = ''+
								'<table class="table table-bordered">'+
									'<tr>'+
										'<th>'+
											'Lehre'+
										'</th>'+
										'<th>'+
											'StgKz'+
										'</th>'+
										'<th>'+
											'SommerSws'+
										'</th>'+
										'<th>'+
											'WinterSws'+
										'</th>'+
									'</tr>';
							for (let le of lehre)
							{
								html +=
									'<tr>'+
										'<td>'+
											le.StgKz +
										'</td>'+
										'<td>'+
											le.SommersemesterSWS +
										'</td>'+
										'<td>'+
											le.WintersemesterSWS +
										'</td>'+
									'</tr>';
							}

							html += '</table>';

							return html;
						}
					}
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
