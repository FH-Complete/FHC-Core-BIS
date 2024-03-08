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
import {PersonalmeldungAPIs} from './API.js';
import studiensemester from './studiensemester/Studiensemester.js';
import NewVerwendungModal from "./Modals/NewVerwendungModal.js";
import UpdateVerwendungModal from "./Modals/UpdateVerwendungModal.js";
import PersonalmeldungDates from "../../mixins/PersonalmeldungDates.js";

export const Verwendungen = {
	components: {
		CoreFilterCmpt,
		FhcLoader,
		PersonalmeldungAPIs,
		studiensemester,
		NewVerwendungModal,
		UpdateVerwendungModal
	},
	mixins: [PersonalmeldungDates],
        props: {
            modelValue: Object
        },
	data: function() {
		return {
			studiensemester_kurzbz: null,
                        verwendungenTabulatorEvents: [{
                            event: "rowClick",
                            handler: (e, row) => {

				// exclude other clicked elements like buttons, icons...
				if (e.target.nodeName != 'DIV') return;

				// open modal for editing
				this.openUpdateModal(row.getData());
                            }
                        }],
			verwendungenTabulatorOptions: {
				index: 'bis_verwendung_id',
				maxHeight: "100%",
				minHeight: 50,
				layout: 'fitColumns',
				columns: [
					{title: 'ID', field: 'bis_verwendung_id', headerFilter: true, visible: false},
					{title: 'Uid', field: 'mitarbeiter_uid', headerFilter: true},
					{title: 'Verwendung Code', field: 'verwendung_code', headerFilter: true},
					{title: 'Verwendung Bezeichnung', field: 'verwendungbez', headerFilter: true},
					{title: 'Von', field: 'von', headerFilter: true, formatter: (cell) => {
							return this.formatDate(cell.getValue());
						}
					},
					{title: 'Bis', field: 'bis', headerFilter: true, formatter: (cell) => {
							return this.formatDate(cell.getValue());
						}
					},
					{title: 'Manuell', field: 'manuell', headerFilter: true, mutator: (value) => {
							return value ? 'Ja' : 'Nein';
						}}
					,
					{title: 'Vorname', field: 'vorname', headerFilter: true, visible: false},
					{title: 'Nachname', field: 'nachname', headerFilter: true, visible: false},
					{
						title: 'Aktionen',
						field: 'actions',
						hozAlign: 'center',
						formatter: (cell) => {
							let manuell = cell.getRow().getData().manuell;

							let container = document.createElement('div');
							container.className = "d-flex gap-2";

							// add edit button
							let button = document.createElement('button');
							button.className = 'btn btn-outline-secondary';
							button.innerHTML = '<i class="fa fa-edit"></i>';
							button.addEventListener('click', (event) => this.openUpdateModal(cell.getRow().getData()));
							container.append(button);

							// add delete button for manually added entries
							if (manuell == "Ja")
							{
								button = document.createElement('button');
								button.className = 'btn btn-outline-secondary';
								button.innerHTML = '<i class="fa fa-xmark"></i>';
								button.addEventListener('click', () => this.deleteVerwendung(cell.getRow().getIndex()));
								container.append(button);
							}

							return container;
						}
					}
				]
			}
		};
	},
	methods: {
		openNewModal() {
			this.$refs.newVerwendungModal.openVerwendungModal();
		},
		openUpdateModal(data) {
			this.$refs.updateVerwendungModal.openVerwendungModal(data);
		},
		/**
		 * get Verwendungen
		 */
		getVerwendungen() {
			// show loading
			this.$refs.loader.show();
			PersonalmeldungAPIs.getVerwendungen(
				this.studiensemester_kurzbz,
				(data) => {
					// set the employee data
					this.$refs.verwendungTable.tabulator.setData(data.verwendungen);
					// hide loading
					this.$refs.loader.hide();
				}
			);
		},
		deleteVerwendung(bis_verwendung_id) {
			PersonalmeldungAPIs.deleteVerwendung(
				bis_verwendung_id,
				(data) => {
					this.getVerwendungen();
				},
				(error) => {
					alert(error);
				}
			);
		},
		/**
		 * generate ("refresh") Verwendungen
		 */
		generateVerwendungen: function() {
			// show loading
			this.$refs.loader.show();
			PersonalmeldungAPIs.generateVerwendungen(
				this.studiensemester_kurzbz,
				(data) => {
					this.getVerwendungen();
				}
			);
		},
		/**
		 * Set Studiensemester
		 */
		setSemester(studiensemester_kurzbz) {
			this.studiensemester_kurzbz = studiensemester_kurzbz;
			// get Verwendungen after semester has been set
			//this.getVerwendungen();
		},
		handleVerwendungAdded() {
			this.$refs.newVerwendungModal.hide();
			this.getVerwendungen();
		},
		handleVerwendungUpdated() {
			this.$refs.updateVerwendungModal.hide();
			this.getVerwendungen();
		}
	},
	template: `
		<div>
			<header>
				<h1 class="h2 fhc-hr">Personalmeldung Verwendungen</h1>
			</header>
			<!-- input fields -->
			<div class="row">
				<div class="col-7">
					<studiensemester @passSemester="setSemester"></studiensemester>
				</div>
				<div class="col-5">
					<span class="text-left">
						<button type="button" class="btn btn-primary me-2" @click="getVerwendungen">
							Anzeigen
						</button>
					</span>
					<span class="text-end">
						<button type="button" class="btn btn-outline-secondary me-2 float-end" @click="generateVerwendungen">
							Verwendungen neu generieren
						</button>
					</span>
				</div>
			</div>
			<br />
			<!-- Filter component -->
			<div class="row">
				<div class="col">
					<core-filter-cmpt
						ref="verwendungTable"
						:side-menu="false"
						:tabulator-options="verwendungenTabulatorOptions"
                                                :tabulator-events="verwendungenTabulatorEvents"
						:table-only="true"
						:new-btn-label="'Verwendung'"
						:new-btn-show="true"
						@click:new="openNewModal">
					</core-filter-cmpt>
				</div>
			</div>
			<!-- Verwendung modal component -->
			<new-verwendung-modal
				class="fade"
				ref="newVerwendungModal"
				dialog-class="modal-xl"
				:studiensemester_kurzbz="studiensemester_kurzbz"
				@verwendung-added="handleVerwendungAdded">
			</new-verwendung-modal>
			<update-verwendung-modal
				class="fade"
				ref="updateVerwendungModal"
				dialog-class="modal-xl"
				@verwendung-updated="handleVerwendungUpdated">
			</update-verwendung-modal>
			<fhc-loader ref="loader"></fhc-loader>
		</div>`
};

export default Verwendungen;