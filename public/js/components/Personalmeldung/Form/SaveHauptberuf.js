import ApiHauptberuf from '../../../api/factory/Hauptberuf.js';
import uids from '../personalmeldunguids/Uids.js';

export const HauptberufForm = {
	emits: [
		'hauptberufSaved'
	],
	components: {
		uids,
		"datepicker": VueDatePicker
	},
	props: {
		studiensemester_kurzbz: String,
		mitarbeiter: Object
	},
	data() {
		return {
			hauptberuf: {
				hauptberuflich: true,
				hauptberufcode: 0
			}, // the edited Hauptberuf
			hauptberufcodeList: [] // Hauptberufcode list for dropdown
		}
	},
	computed: {
		fullHauptberuf() {
			if (this.hauptberuf.hauptberuflich === true) this.hauptberuf.hauptberufcode = null;
			if (this.mitarbeiter != null) this.hauptberuf.mitarbeiter_uid = this.mitarbeiter.personUID;
			return this.hauptberuf;
		}
	},
	methods: {
		prefill(hauptberuf) {
			this.$api
				.call(ApiHauptberuf.getHauptberufcodeList(this.fullHauptberuf))
				.then((response) => {this.hauptberufcodeList = response.data.hauptberufcodeList;})
				.catch(this.$fhcAlert.handleSystemError);

			if (hauptberuf.hasOwnProperty('mitarbeiter_uid'))
			{
				this.hauptberuf = JSON.parse(JSON.stringify(hauptberuf)); // deep copy
				if (this.$refs.uids) this.$refs.uids.prefill(hauptberuf);
			}
		},
		save() {
			let successCallback = () => {
				this.$emit('hauptberufSaved');
				this.reset();
			};
			let errorCallback = (error) => {
				this.$fhcAlert.alertError(error);
			};
			if (this.fullHauptberuf.hasOwnProperty('bis_hauptberuf_id')) {
				return this.$api
					.call(ApiHauptberuf.updateHauptberuf(this.fullHauptberuf))
					.then(successCallback)
					.catch(this.$fhcAlert.handleSystemError);
			}
			else
			{
				return this.$api
					.call(ApiHauptberuf.addHauptberuf(this.fullHauptberuf))
					.then(successCallback)
					.catch(this.$fhcAlert.handleSystemError);
			}
		},
		handleHauptberuflichChange() {
			// set default value when hauptberuflich set to false
			if (this.hauptberuf.hauptberuflich == false) this.hauptberuf.hauptberufcode = 0;
		},
		setMitarbeiterUid(mitarbeiter_uid) {
			this.hauptberuf.mitarbeiter_uid = mitarbeiter_uid;
		},
		reset() {
			this.hauptberuf = {
				hauptberuflich: true,
				hauptberufcode: 0
			};
			if (this.$refs.uids) this.$refs.uids.reset();
		}
	},
	template: `
	<div>
		<form ref="saveHauptberufForm" class="row gy-3">
				<div class="col-12" v-if="mitarbeiter == null">
					<uids
						ref="uids"
						:studiensemester_kurzbz="studiensemester_kurzbz"
						@passUid="setMitarbeiterUid">
					</uids>
				</div>
				<div class="col-12">
					<div class="form-check">
					<label class="form-check-label" for="hauptberuflich">Hauptberuflich lehrend</label>
					<input
						class="form-check-input"
						type="checkbox"
						name="hauptberuflich"
						id="hauptberuflich"
						v-model="hauptberuf.hauptberuflich"
						@change="handleHauptberuflichChange">
					</div>
				</div>
				<div class="col-12">
					<div v-if="hauptberuf.hauptberuflich == false">
					<label class="form-label" for="hauptberufcode">Hauptberuf Code</label>
					<select
						class="form-select"
						name="hauptberufcode"
						id="hauptberufcode"
						required
						v-model="hauptberuf.hauptberufcode">
						<option v-for="hb in hauptberufcodeList" :key="hb.hauptberufcode" :value="hb.hauptberufcode">
							{{hb.hauptberufcode}} - {{hb.bezeichnung}}
						</option>
					</select>
					</div>
				</div>
				<div class="col-6">
					<label class="form-label" for="von">Von</label>
					<datepicker
						v-model="hauptberuf.von"
						v-bind:enable-time-picker="false"
						v-bind:placeholder="'TT.MM.YYYY; leer lassen für unbeschränkt'"
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
						v-model="hauptberuf.bis"
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
