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

import FhcLoader from '../../../../../js/components/Loader.js';
import {PersonalmeldungAPIs} from './API.js';
import studiensemester from './studiensemester/Studiensemester.js';

export const Plausichecks = {
	data: function() {
		return {
			studiensemester_kurzbz: null,
			issues: []
		};
	},
	components: {
		FhcLoader,
		PersonalmeldungAPIs,
		studiensemester
	},
	methods: {
		/**
		 * start checking
		 */
		startPlausichecks: function() {
			this.$refs.loader.show();
			PersonalmeldungAPIs.runPlausichecks(
				this.studiensemester_kurzbz,
				(data) => {
					// set the issue data
					this.issues = data;
					this.$refs.loader.hide();
				}
			);
		},
		getSemester: function(studiensemester_kurzbz) {
			this.studiensemester_kurzbz = studiensemester_kurzbz;
		}
	},
	template: `
		<div id="content">
			<header>
				<h1 class="h2 fhc-hr">Personalmeldung Plausichecks</h1>
			</header>
			<!-- input fields -->
			<div class="row">
				<div class="col-10">
					<studiensemester @passSemester="getSemester"></studiensemester>
				</div>
				<div class="col-2 text-right">
					<button type="button" class="btn btn-primary" @click="startPlausichecks">
						Starten
					</button>
				</div>
			</div>
			<br />
			<div class="row">
				<div class="col-10">
					<div class="well">
						<div class="card bg-light">
							<div class="card-body">
								<div v-for="(issue, fehler_kurzbz) in issues">
									<b>Prüfe {{fehler_kurzbz}}...</b>
									<span v-for="issuedata in issue.data" :class="{'text-danger': issuedata.type == 'error'}">
										<br />
										{{issuedata.fehlertext}}
									</span>
									<span v-if="issue.data.length == 0" class="text-success">
										<br />
										Keine Fehler gefunden
									</span>
									<br />
									<br />
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<fhc-loader ref="loader"></fhc-loader>
		</div>`
};
