import {StudiensemesterAPIs} from './API.js';

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
			StudiensemesterAPIs.getStudiensemester(
				(data) => {
					// set the Studiensemester data
					this.semList = data.semList;
					this.currSem = data.currSem;
					this.onSemesterChange(this.currSem);
				}
			);
		},
		onSemesterChange: function() {
			console.log("passing");
			//console.log(this.currSem);
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