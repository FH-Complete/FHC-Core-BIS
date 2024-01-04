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
import HauptberufModal from "./Modals/HauptberufModal.js";
import PersonalmeldungDates from "../../mixins/PersonalmeldungDates.js";

export const Hauptberuf = {
	components: {
		CoreFilterCmpt,
		CoreNavigationCmpt,
		FhcLoader,
		PersonalmeldungAPIs,
		studiensemester,
		HauptberufModal
	},
	mixins: [PersonalmeldungDates],
	data: function() {
		return {
			studiensemester_kurzbz: null,
			hauptberufTabulatorOptions: {
				index: 'bis_hauptberuf_id',
				maxHeight: "100%",
				minHeight: 50,
				layout: 'fitColumns',
				columns: [
					{title: 'ID', field: 'bis_hauptberuf_id', headerFilter: true, visible: false},
					{title: 'Uid', field: 'mitarbeiter_uid', headerFilter: true},
					{title: 'Hauptberuflich', field: 'hauptberuflich', headerFilter: true,
						formatter: (cell) => {
							return cell.getValue() ? 'Ja' : 'Nein';
						},
						headerFilterFunc: (headerValue, rowValue) => {
							if (rowValue === true) return "ja".includes(headerValue.toLowerCase());
							if (rowValue === false) return "nein".includes(headerValue.toLowerCase());
							return false;
						}
					},
					{title: 'Hauptberuf Code', field: 'hauptberufcode', headerFilter: true},
					{title: 'Hauptberuf Bezeichnung', field: 'bezeichnung', headerFilter: true},
					{title: 'Von', field: 'von', headerFilter: true, formatter: (cell) => {
							return this.formatDate(cell.getValue());
						}
					},
					{title: 'Bis', field: 'bis', headerFilter: true, formatter: (cell) => {
							return this.formatDate(cell.getValue());
						}
					},
					{title: 'Vorname', field: 'vorname', headerFilter: true, visible: false},
					{title: 'Nachname', field: 'nachname', headerFilter: true, visible: false},
					{
						title: 'Aktionen',
						field: 'actions',
						hozAlign: 'center',
						formatter: (cell) => {
							let container = document.createElement('div');
							container.className = "d-flex gap-2";

							// add edit button
							let button = document.createElement('button');
							button.className = 'btn btn-outline-secondary';
							button.innerHTML = '<i class="fa fa-edit"></i>';
							button.addEventListener('click', (event) => this.openModal(cell.getRow().getData()));
							container.append(button);

							// add delete button for manually added entries
							button = document.createElement('button');
							button.className = 'btn btn-outline-secondary';
							button.innerHTML = '<i class="fa fa-xmark"></i>';
							button.addEventListener('click', () => this.deleteHauptberuf(cell.getRow().getIndex()));
							container.append(button);

							return container;
						}
					}
				]
			}
		};
	},
	mounted() {
		this.setTabulatorEvents();
	},
	methods: {
		setTabulatorEvents() {
			// row click event (showing Hauptberuf details)
			this.$refs.hauptberufTable.tabulator.on("rowClick", (e, row) => {

				// exclude other clicked elements like buttons, icons...
				if (e.target.nodeName != 'DIV') return;

				// open modal for editing
				this.openModal(row.getData());
			});
		},
		openModal(data) {
			this.$refs.hauptberufModal.openHauptberufModal(data);
		},
		getHauptberufe() {
			// show loading
			this.$refs.loader.show();
			PersonalmeldungAPIs.getHauptberufe(
				this.studiensemester_kurzbz,
				(data) => {
					// set the employee data
					this.$refs.hauptberufTable.tabulator.setData(data.hauptberufe);
					// hide loading
					this.$refs.loader.hide();
				}
			);
		},
		deleteHauptberuf(bis_hauptberuf_id) {
			PersonalmeldungAPIs.deleteHauptberuf(
				bis_hauptberuf_id,
				(data) => {
					this.getHauptberufe();
				},
				(error) => {
					alert(error);
				}
			);
		},
		/**
		 * Set Studiensemester
		 */
		setSemester(studiensemester_kurzbz) {
			this.studiensemester_kurzbz = studiensemester_kurzbz;
			// get Hauptberufe after semester has been set
			this.getHauptberufe();
		},
		handleHauptberufSaved() {
			this.$refs.hauptberufModal.hide();
			this.getHauptberufe();
		}
	},
	template: `
		<!-- Navigation component -->
		<core-navigation-cmpt></core-navigation-cmpt>

		<div id="content">
			<header>
				<h1 class="h2 fhc-hr">Personalmeldung Hauptberufe</h1>
			</header>
			<!-- input fields -->
			<div class="row">
				<div class="col-12">
					<studiensemester @passSemester="setSemester"></studiensemester>
				</div>
			</div>
			<br />
			<!-- Filter component -->
			<div class="row">
				<div class="col">
					<core-filter-cmpt
						ref="hauptberufTable"
						:side-menu="false"
						:tabulator-options="hauptberufTabulatorOptions"
						:table-only="true"
						:new-btn-label="'Hauptberuf'"
						:new-btn-show="true"
						@click:new="openModal">
					</core-filter-cmpt>
				</div>
			</div>
			<!-- Hauptberuf modal component -->
			<hauptberuf-modal
				class="fade"
				ref="hauptberufModal"
				dialog-class="modal-xl"
				:studiensemester_kurzbz="studiensemester_kurzbz"
				@hauptberuf-saved="handleHauptberufSaved">
			</hauptberuf-modal>
			<fhc-loader ref="loader"></fhc-loader>
		</div>`
};
