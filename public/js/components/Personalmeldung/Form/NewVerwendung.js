import {PersonalmeldungAPIs} from '../API.js';
import uids from '../personalmeldunguids/Uids.js';

export const NewVerwendungForm = {
	emits: [
		'verwendungAdded'
	],
	components: {
		PersonalmeldungAPIs,
		uids,
		"datepicker": VueDatePicker
	},
	props: {
		studiensemester_kurzbz: String,
		mitarbeiter: Object
	},
	data() {
		return {
			verwendung: {
				verwendung_code: 1
			}, // the added Verwendung
			verwendungList: [], // Verwendung list for dropdown
			errorText: null
		}
	},
	computed: {
		fullVerwendung() { // the Verwendung to add
			if (this.mitarbeiter != null) this.verwendung.mitarbeiter_uid = this.mitarbeiter.personUID;
			return this.verwendung;
		}
	},
	methods: {
		prefill() {
			PersonalmeldungAPIs.getFullVerwendungList(
				(data) => {
					this.verwendungList = data.verwendungList;
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
		setMitarbeiterUid(mitarbeiter_uid) {
			this.verwendung.mitarbeiter_uid = mitarbeiter_uid;
		},
		reset() {
			this.verwendung = {
				verwendung_code: 1
			};
			if (this.$refs.uids) this.$refs.uids.reset();
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
			<div class="col-6" v-if="mitarbeiter == null">
				<uids
					ref="uids"
					:studiensemester_kurzbz="studiensemester_kurzbz"
					@passUid="setMitarbeiterUid">
				</uids>
			</div>
			<div v-bind:class="mitarbeiter == null ? 'col-6' : 'col-12'">
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
			<div class="col-6">
				<label class="form-label" for="von">Von</label>
				<datepicker
					v-model="verwendung.von"
					v-bind:enable-time-picker="false"
					v-bind:placeholder="'TT.MM.YYYY'"
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
					v-bind:placeholder="'TT.MM.YYYY; leer lassen für unbeschränkt'"
					v-bind:text-input="true"
					v-bind:auto-apply="true"
					name="bis"
					id="bis"
					locale="de"
					format="dd.MM.yyyy"
					model-type="yyyy-MM-dd">
				</datepicker>
			</div>
		</form>
	</div>
	`
}
