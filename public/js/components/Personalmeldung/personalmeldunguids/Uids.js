import {UidAPIs} from './API.js';

export default {
	emits: ['passUid'],
	components: {
		UidAPIs,
		AutoComplete: primevue.autocomplete
	},
	props: {
		studiensemester_kurzbz: String
	},
	data: function() {
		return {
			mitarbeiterUids: [], // uids for autocomplete
			selectedUidObj: null // currently selected uid object
		};
	},
	methods: {
		prefill: function(mitarbeiter) {
			this.selectedUidObj = {
				mitarbeiter_uid: mitarbeiter.mitarbeiter_uid,
				bezeichnung: this.getBezeichnungFromMitarbeiter(mitarbeiter)
			}
		},
		getMitarbeiterUids: function(event) {
			UidAPIs.getMitarbeiterUids(
				this.studiensemester_kurzbz,
				event.query,
				(data) => {
					for (let mitarbeiter of data.mitarbeiterUids) {
						// set Bezeichnung for autocomplete option text
						mitarbeiter.bezeichnung = this.getBezeichnungFromMitarbeiter(mitarbeiter);
					}
					this.mitarbeiterUids = data.mitarbeiterUids;
				}
			);
		},
		getBezeichnungFromMitarbeiter: function(mitarbeiter) {
			if (mitarbeiter == null) return "";
			return mitarbeiter.mitarbeiter_uid + " - " + mitarbeiter.vorname + " " + mitarbeiter.nachname;
		},
		onUidChange: function() {
			if (this.selectedUidObj != null) this.$emit('passUid', this.selectedUidObj.mitarbeiter_uid);
		},
		reset: function() {
			this.selectedUidObj = null;
		}
	},
	template: `
		<label class="form-label" for="mitarbeiter_uid">Uid</label>
		<auto-complete
			class="w-100"
			v-model="selectedUidObj"
			dropdown
			dropdown-current
			forceSelection
			optionLabel="bezeichnung"
			:suggestions="mitarbeiterUids"
			@change="onUidChange"
			@complete="getMitarbeiterUids">
		</auto-complete>`
};
