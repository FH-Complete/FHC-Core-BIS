import VerwendungenAPI from '../../../mixins/api/VerwendungenAPI.js';
import PersonalmeldungDates from '../../../mixins/PersonalmeldungDates.js';

export const UpdateVerwendungForm = {
	emits: [
		'verwendungUpdated'
	],
	mixins: [PersonalmeldungDates, VerwendungenAPI],
	props: {
		verwendung: Object
	},
	data() {
		return {
			verwendungList: [],
			errorText: null
		}
	},
	computed: {
		fullName() {
			return this.verwendung.vorname + " " + this.verwendung.nachname;
		},
		vonDateFormatted() {
			return this.formatDate(this.verwendung.von);
		},
		bisDateFormatted() {
			return this.formatDate(this.verwendung.bis);
		}
	},
	methods: {
		prefill(verwendung_code) {
			this.callGetVerwendungList(
				verwendung_code,
				(data) => {
					this.verwendungList = data.verwendungList;
				}
			);
		},
		update() {
			this.callUpdateVerwendung(
				this.verwendung,
				(data) => {
					this.$emit('verwendungUpdated');
					this.resetError();
				},
				(error) => {
					this.errorText = error;
				}
			);
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
		<form ref="editVerwendungForm" class="row gy-3">
			<div class="col-12">
				<div class="form-group row">
					<label class="col-2 col-form-label" for="mitarbeiter_uid">Uid</label>
					<div class="col-10">
						<input type="text" readonly class="form-control-plaintext" id="mitarbeiter_uid" :value="verwendung.mitarbeiter_uid">
					</div>
				</div>
				<div class="form-group row">
					<label class="col-2 col-form-label" for="name">Name</label>
					<div class="col-10">
						<input type="text" readonly class="form-control-plaintext" id="name" :value="fullName">
					</div>
				</div>
				<div class="form-group row">
					<label class="col-2 col-form-label" for="von">Von</label>
					<div class="col-10">
						<input type="text" readonly class="form-control-plaintext" id="von" :value="vonDateFormatted">
					</div>
				</div>
				<div class="form-group row">
					<label class="col-2 col-form-label" for="bis">Bis</label>
					<div class="col-10">
						<input type="text" readonly class="form-control-plaintext" id="bis" :value="bisDateFormatted">
					</div>
				</div>
				<div class="form-group row">
					<label class="col-2 col-form-label" for="verwendung_code">Verwendung</label>
					<div class="col-10">
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
			</div>
		</form>
	</div>
	`
}
