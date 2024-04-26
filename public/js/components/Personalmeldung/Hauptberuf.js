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
import FhcLoader from '../../../../../js/components/Loader.js';
import HauptberufAPI from '../../mixins/api/HauptberufAPI.js';
import studiensemester from './studiensemester/Studiensemester.js';
import HauptberufModal from "./Modals/HauptberufModal.js";
import PersonalmeldungDates from "../../mixins/PersonalmeldungDates.js";

export const Hauptberuf = {
	mixins: [PersonalmeldungDates, HauptberufAPI],
	components: {
		CoreFilterCmpt,
		FhcLoader,
		studiensemester,
		HauptberufModal
	},
	props: {
		modelValue: Object,
		default: null
	},
	data: function() {
		return {
			studiensemester_kurzbz: null,
			hauptberufTabulatorEvents: [{
				event: "rowClick",
				handler: (e, row) => {

					// exclude other clicked elements like buttons, icons...
					if (e.target.nodeName != 'DIV') return;

					// open modal for editing
					this.openModal(row.getData());
				}
			}],
			hauptberufTabulatorOptions: {
				index: 'bis_hauptberuf_id',
				//persistenceID: 'hauptberufTable',
				layout: 'fitColumns',
				columns: [
					{title: 'ID', field: 'bis_hauptberuf_id', headerFilter: true, visible: false, width: 100},
					{title: 'Uid', field: 'mitarbeiter_uid', headerFilter: true, width: 140},
					{title: 'Hauptberuflich lehrend', field: 'hauptberuflich', headerFilter: true, width: 140,
						formatter: (cell) => {
							return cell.getValue() ? 'Ja' : 'Nein';
						},
						headerFilterFunc: (headerValue, rowValue) => {
							if (rowValue === true) return "ja".includes(headerValue.toLowerCase());
							if (rowValue === false) return "nein".includes(headerValue.toLowerCase());
							return false;
						}
					},
					{title: 'Hauptberuf Code', field: 'hauptberufcode', headerFilter: true, width: 140},
					{title: 'Hauptberuf Bezeichnung', field: 'bezeichnung', headerFilter: true, width: 140},
					{title: 'Von', field: 'von', headerFilter: true, width: 140, formatter: (cell) => {
							return this.formatDate(cell.getValue());
						}
					},
					{title: 'Bis', field: 'bis', headerFilter: true, width: 140, formatter: (cell) => {
							return this.formatDate(cell.getValue());
						}
					},
					{title: 'Vorname', field: 'vorname', headerFilter: true, visible: false, width: 140},
					{title: 'Nachname', field: 'nachname', headerFilter: true, visible: false, width: 140},
					{
						title: 'Aktionen',
						field: 'actions',
						hozAlign: 'center',
						width: 140,
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
		this.getHauptberufe();
	},
	methods: {
		openModal(data) {
			this.$refs.hauptberufModal.openHauptberufModal(data);
		},
		getHauptberufe() {
			this.$refs.loader.show();
			let successCallback = (data) => {
				// set the employee data
				if (this.$refs.hauptberufTable && this.$refs.hauptberufTable.tabulator)
					this.$refs.hauptberufTable.tabulator.setData(data.hauptberufe);
				// hide loading
				this.$refs.loader.hide();
			};

			if (this.studiensemester_kurzbz != null)
			{
				this.getAllHauptberufe(successCallback);
			}
			else if(this.modelValue != null && this.modelValue.personUID)
			{
				this.getHauptberufeByUid(successCallback);
			}
		 },
		getAllHauptberufe(successCallback) {
			this.callGetHauptberufe(
				this.studiensemester_kurzbz,
				successCallback
			);
		},
		getHauptberufeByUid(successCallback) {
			this.callGetHauptberufeByUid(
				this.modelValue.personUID,
				successCallback
			);
		},
		deleteHauptberuf(bis_hauptberuf_id) {
			this.callDeleteHauptberuf(
				bis_hauptberuf_id,
				(data) => {
					this.getHauptberufe();
				},
				(error) => {
					this.$fhcAlert.alertError(error);
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
		<div>
			<header>
				<h1 class="h2 fhc-hr">Personalmeldung Hauptberufe</h1>
			</header>
			<!-- input fields -->
			<div class="row" v-if="modelValue == null || !modelValue.personUID">
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
						:tabulator-events="hauptberufTabulatorEvents"
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
				:mitarbeiter="modelValue"
				@hauptberuf-saved="handleHauptberufSaved">
			</hauptberuf-modal>
			<fhc-loader ref="loader"></fhc-loader>
		</div>`
};

export default Hauptberuf;
