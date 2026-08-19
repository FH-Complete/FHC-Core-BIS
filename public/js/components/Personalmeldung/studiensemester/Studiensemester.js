import ApiPersonalmeldung from '../../../api/factory/Personalmeldung.js';

export default {
	emits: ['passSemester'],
	data: function() {
		return {
			semList: null, // all Studiensemester for dropdown
			currSem: null, // selected Studiensemester
		};
	},
	created() {
		this.getStudiensemester();
	},
	methods: {
		/**
		 * get Studiensemester
		 */
		getStudiensemester: function() {
			return this.$api
				.call(ApiPersonalmeldung.getStudiensemester())
				.then((response) => {
					// set the Studiensemester data
					this.semList = response.data.semList;
					this.currSem = response.data.currSem;
					this.onSemesterChange(this.currSem);
				})
				.catch(this.$fhcAlert.handleSystemError);
		},
		onSemesterChange: function() {
			this.$emit('passSemester', this.currSem);
		}
	},
	template: `
		<select class="form-select" name="studiensemester_kurzbz" v-model="currSem" @change="onSemesterChange">
			<option v-for="sem in semList" :value="sem.studiensemester_kurzbz">
				{{ sem.studiensemester_kurzbz }}
			</option>
		</select>`
};
