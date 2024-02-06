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

import {CoreFilterCmpt} from '../../../../../js/components/filter/Filter.js';
import {CoreNavigationCmpt} from '../../../../../js/components/navigation/Navigation.js';
import FhcLoader from '../../../../../js/components/Loader.js';
import {PersonalmeldungAPIs} from './API.js';
import studiensemester from './studiensemester/Studiensemester.js';
import personalmeldungSums from './personalmeldungsums/PersonalmeldungSums.js';

export const Personalmeldung = {
	components: {
		CoreFilterCmpt,
		CoreNavigationCmpt,
		FhcLoader,
		PersonalmeldungAPIs,
		studiensemester,
		personalmeldungSums
	},
	data: function() {
		return {
			studiensemester_kurzbz: null,
			personalmeldungSums: null,
			verwendungenSaved: false,
			personalmeldungTabulatorOptions: {
				layout: 'fitDataTable',
				columns: [
					{title: 'PersNr', field: 'personalnummer', headerFilter: true,
						formatter: function(cell) {return cell.getValue().replace(/^0+/, '');}
					},
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
								'<table class="table table-bordered table-sm">'+
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
								'<table class="table table-bordered table-sm">'+
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
								'<table class="table table-bordered table-sm">'+
									'<tr>'+
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
			}
		};
	},
	methods: {
		/**
		 * get Mitarbeiter
		 */
		getMitarbeiter: function() {
			// show loading
			this.$refs.loader.show();
			PersonalmeldungAPIs.getMitarbeiter(
				this.studiensemester_kurzbz,
				(data) => {
					// set the employee data
					this.$refs.personalmeldungTable.tabulator.setData(data.mitarbeiter);
					// set employee sum data
					this.personalmeldungSums = data.personalmeldungSums;
					// hide loading
					this.$refs.loader.hide();
				}
			);
		},
		/**
		 * save ("refresh") Verwendungen
		 */
		saveVerwendungen: function() {
			// show loading
			this.$refs.loader.show();
			PersonalmeldungAPIs.saveVerwendungen(
				this.studiensemester_kurzbz,
				(data) => {
					// display success alert
					this.verwendungenSaved = true;
					// remove success alert after short time
					setTimeout(() => this.verwendungenSaved = false, 2000)
					// hide loading
					this.$refs.loader.hide();
				}
			);
		},
		/**
		 * Download XML by changing url
		 */
		downloadPersonalmeldungXml: function() {
			window.location = 'Personalmeldung/downloadPersonalmeldungXml?studiensemester_kurzbz='+encodeURIComponent(this.studiensemester_kurzbz);
		},
		/**
		 * Set Studiensemester
		 */
		setSemester: function(studiensemester_kurzbz) {
			this.studiensemester_kurzbz = studiensemester_kurzbz;
		}
	},
	template: `
		<!-- Navigation component -->
		<core-navigation-cmpt></core-navigation-cmpt>

		<div id="content">
			<header>
				<h1 class="h2 fhc-hr">Personalmeldung</h1>
			</header>
			<!-- input fields -->
			<div class="row">
				<div class="col-4">
					<studiensemester @passSemester="setSemester"></studiensemester>
				</div>
				<div class="col-8">
					<span class="text-left">
						<button type="button" class="btn btn-primary me-2" @click="getMitarbeiter">
							Mitarbeiterdaten anzeigen
						</button>
					</span>
					<span class="text-end">
						<button type="button" class="btn btn-primary me-2" @click="downloadPersonalmeldungXml">
							XML exportieren
						</button>
						<button type="button" class="btn btn-outline-secondary me-2 float-end" @click="saveVerwendungen">
							Verwendungen neu generieren
						</button>
					</span>
				</div>
			</div>
			<br />
			<div class="alert alert-success" v-show="verwendungenSaved">
				Verwendungen erfolgreich aktualisiert
			</div>
			<br />
			<personalmeldungSums :personalmeldungSums="personalmeldungSums"></personalmeldungSums>
			<!-- Filter component -->
			<div class="row">
				<div class="col">
					<core-filter-cmpt
						ref="personalmeldungTable"
						:side-menu="false"
						:tabulator-options="personalmeldungTabulatorOptions"
						:table-only="true">
					</core-filter-cmpt>
				</div>
			</div>
			<fhc-loader ref="loader"></fhc-loader>
		</div>`
};
