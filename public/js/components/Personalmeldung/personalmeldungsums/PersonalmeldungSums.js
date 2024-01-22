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


export default {
	props: {
		personalmeldungSums: Object
	},
	computed: {
		verwendungSums() {
			return this.personalmeldungSums == null ? {} : this.personalmeldungSums.verwendungSums;
		},
		funktionSums() {
			return this.personalmeldungSums == null ? {} : this.personalmeldungSums.funktionSums;
		},
		lehreSums() {
			return this.personalmeldungSums == null ? {} : this.personalmeldungSums.lehreSums;
		},
		totalSums() {
			if (this.personalmeldungSums == null) return {};

			let verwendungCountTotal = 0;
			let vzaeSumTotal = 0;
			let jvzaeSumTotal = 0;
			let funktionCountTotal = 0;
			let lehreSumTotal = 0;
			for (let idx in this.personalmeldungSums.verwendungSums) {
				let verwendungSum = this.personalmeldungSums.verwendungSums[idx];
				let count = parseFloat(verwendungSum.count);
				let vzae = parseFloat(verwendungSum.vzae);
				let jvzae = parseFloat(verwendungSum.jvzae);
				verwendungCountTotal += Number.isNaN(count) ? 0 : count;
				vzaeSumTotal += Number.isNaN(vzae) ? 0 : vzae;
				jvzaeSumTotal += Number.isNaN(jvzae) ? 0 : jvzae;
			}
			for (let idx in this.personalmeldungSums.funktionSums) {
				let funktionSum = this.personalmeldungSums.funktionSums[idx];
				let count = parseFloat(funktionSum.count);
				funktionCountTotal += Number.isNaN(count) ? 0 : count;
			}
			for (let idx in this.personalmeldungSums.lehreSums) {
				let lehreSum = this.personalmeldungSums.lehreSums[idx];
				let sum = parseFloat(lehreSum);
				lehreSumTotal += Number.isNaN(sum) ? 0 : sum;
			}
			return {
				verwendungen: verwendungCountTotal,
				vzae: vzaeSumTotal.toFixed(2),
				jvzae: jvzaeSumTotal.toFixed(2),
				funktionen: funktionCountTotal,
				lehre: lehreSumTotal.toFixed(2),
			}
		}
	},
	template: `
			<div class="row" v-if="this.personalmeldungSums != null">
				<div class="col">
					<table class="table table-bordered table-sm">
						<thead>
							<tr>
								<th colspan="4" class="text-center">Verwendungen</th>
							</tr>
							<tr>
								<th>&nbsp;</th>
								<th class="text-center">Anzahl</th>
								<th class="text-center">Vzae</th>
								<th class="text-center">Jvzae</th>
							</tr>
						</thead>
						<tbody>
							<tr v-for = "(verwendungSum, verwendungCode) in verwendungSums">
								<td>{{ verwendungCode }} - {{ verwendungSum.name.charAt(0).toUpperCase() + verwendungSum.name.slice(1) }}</td>
								<td class="text-end">{{ verwendungSum.count }}</td>
								<td class="text-end">{{ verwendungSum.vzae }}</td>
								<td class="text-end">{{ verwendungSum.jvzae }}</td>
							</tr>
						</tbody>
						<tfoot>
							<tr>
								<th>Summen</th>
								<td class="text-end">{{ totalSums.verwendungen }}</td>
								<td class="text-end">{{ totalSums.vzae }}</td>
								<td class="text-end">{{ totalSums.jvzae }}</td>
							</tr>
						</tfoot>
					</table>
				</div>
				<div class="col">
					<table class="table table-bordered table-sm">
						<thead>
							<tr>
								<th colspan="2" class="text-center">Funktionen</th>
							</tr>
							<tr>
								<th>&nbsp;</th>
								<th class="text-center">Anzahl</th>
							</tr>
						</thead>
						<tbody>
							<tr v-for = "(funktionCount, funktionCode) in funktionSums">
								<td>{{ funktionCode }} - {{ funktionCount.name.charAt(0).toUpperCase() + funktionCount.name.slice(1) }}</td>
								<td class="text-end">{{ funktionCount.count }}</td>
							</tr>
						</tbody>
						<tfoot>
							<tr>
								<th>Summen</th>
								<td class="text-end">{{ totalSums.funktionen }}</td>
							</tr>
						</tfoot>
					</table>
				</div>
				<div class="col">
					<table class="table table-bordered table-sm">
						<thead>
							<tr>
								<th colspan="2" class="text-center">Lehre</th>
							</tr>
							<tr>
								<th>&nbsp;</th>
								<th class="text-center">Summe</th>
							</tr>
						</thead>
						<tbody>
							<tr v-for = "(lehreSum, semester) in lehreSums">
								<td>{{ semester }}</td>
								<td class="text-end">{{ lehreSum }}</td>
							</tr>
						</tbody>
						<tfoot>
							<tr>
								<th>Summen</th>
								<td class="text-end">{{ totalSums.lehre }}</td>
							</tr>
						</tfoot>
					</table>
				</div>
			</div>`
};
