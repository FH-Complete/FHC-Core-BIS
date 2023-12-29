import {PersonalmeldungAPIs} from '../API.js';

export const NewVerwendungForm = {
	emits: [
		'verwendungAdded'
	],
	components: {
		PersonalmeldungAPIs,
		AutoComplete: primevue.autocomplete,
		"datepicker": VueDatePicker
	},
	props: {
		studiensemester_kurzbz: String
	},
	data() {
		return {
			verwendung: {}, // the added Verwendung
			mitarbeiterUids: [], // uids for autocomplete
			mitarbeiterUidObj: null, // currently selected uid object
			verwendungList: [], // Verwendung list for dropdown
			errorText: null
		}
	},
	computed: {
		fullVerwendung() {
			// add mitarbeiter uid to Verwendung, uid is provided by autocomplete
			this.verwendung.mitarbeiter_uid = this.mitarbeiterUidObj.mitarbeiter_uid;
			return this.verwendung;
		}
	},
	methods: {
		prefill(verwendung_code) {
			PersonalmeldungAPIs.getFullVerwendungList(
				(data) => {
					this.verwendungList = data.verwendungList;
				}
			);
		},
		getMitarbeiterUids(event) {
			PersonalmeldungAPIs.getMitarbeiterUids(
				this.studiensemester_kurzbz,
				event.query,
				(data) => {
					for (let mitarbeiter of data.mitarbeiterUids) {
						// set Bezeichnung for autocomplete option text
						mitarbeiter.bezeichnung = mitarbeiter.mitarbeiter_uid + " - " + mitarbeiter.vorname + " " + mitarbeiter.nachname;
					}
					this.mitarbeiterUids = data.mitarbeiterUids;
				}
			);
		},
		add() {
			PersonalmeldungAPIs.addVerwendung(
				this.fullVerwendung,
				(data) => {
					this.$emit('verwendungAdded');
					this.reset();
				},
				(error) => {
					this.errorText = error;
				}
			);
		},
		reset() {
			this.mitarbeiterUidObj = null;
			this.verwendung = {};
			this.resetError();
		},
		resetError() {
			this.errorText = null;
		}
	},
	template: `
	<div>
		<div class="alert alert-danger" role="alert" v-show="errorText != null">
			{{ errorText }}
		<br />
		</div>
		<br />
		<form ref="newVerwendungForm" class="row gy-3">
			<div class="form-group row">
				<div class="col-6">
					<label class="form-label" for="mitarbeiter_uid">Uid</label>
					<auto-complete
						class="w-100"
						v-model="mitarbeiterUidObj"
						dropdown
						dropdown-current
						forceSelection
						optionLabel="bezeichnung"
						:suggestions="mitarbeiterUids"
						@complete="getMitarbeiterUids">
					</auto-complete>
				</div>
				<div class="col-6">
					<label class="form-label" for="verwendung_code">Verwendung</label>
					<select
						class="form-select"
						name="verwendung_code"
						id="verwendung_code"
						required
						v-model="verwendung.verwendung_code">
						<option v-for="verw in verwendungList" :key="index" :value="verw.verwendung_code">
							{{verw.verwendung_code}} - {{verw.verwendungbez}}
						</option>
					</select>
				</div>
			</div>
			<div class="form-group row">
				<div class="col-6">
					<label class="form-label" for="von">Von</label>
					<datepicker
						v-model="verwendung.von"
						v-bind:enable-time-picker="false"
						v-bind:placeholder="'TT.MM.YY'"
						v-bind:text-input="true"
						v-bind:auto-apply="true"
						name="von"
						id="von"
						locale="de"
						format="dd.MM.yyyy"
						model-type="yyyy-MM-dd">
					</datepicker>
				</div>
				<div class="col-6">
					<label class="form-label" for="bis">Bis</label>
					<datepicker
						v-model="verwendung.bis"
						v-bind:enable-time-picker="false"
						v-bind:placeholder="'TT.MM.YY; leer lassen für unbeschränkt'"
						v-bind:text-input="true"
						v-bind:auto-apply="true"
						name="bis"
						id="bis"
						locale="de"
						format="dd.MM.yyyy"
						model-type="yyyy-MM-dd">
					</datepicker>
				</div>
			</div>
		</form>
	</div>
	`
}
