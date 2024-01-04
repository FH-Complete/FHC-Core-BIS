import BsModal from '../../../../../../js/components/Bootstrap/Modal.js';
import {HauptberufForm} from '../Form/SaveHauptberuf.js';

export default {
	components: {
		BsModal,
		HauptberufForm
	},
	emits: [
		'hauptberufSaved'
	],
	mixins: [
		BsModal
	],
	props: {
		studiensemester_kurzbz: String
	},
	data: function() {
		return {
			type: 'add'
		}
	},
	computed: {
		title() {
			return this.type == 'update' ? "Hauptberuf bearbeiten" : "Hauptberuf hinzufügen";
		}
	},
	mounted() {
		this.modal = this.$refs.modalContainer.modal;
	},
	methods: {
		onHiddenBsModal() {
			this.$refs.hauptberufFormCmpt.reset();
		},
		onBsModalSave() {
			this.$refs.hauptberufFormCmpt.save();
		},
		handleHauptberufFormSaved() {
			this.$emit('hauptberufSaved');
		},
		openHauptberufModal(data) {
			this.type = data && data.hasOwnProperty('bis_hauptberuf_id') ? 'update' : 'add';
			// Prefill form
			this.$refs.hauptberufFormCmpt.prefill(data);
			this.$refs.modalContainer.show();
		}
	},
	template: `
		<bs-modal ref="modalContainer" class="bootstrap-prompt" v-bind="$props" @hidden-bs-modal="onHiddenBsModal">
			<template v-slot:title>{{title}}</template>
			<template v-slot:default>
				<hauptberuf-form
					ref="hauptberufFormCmpt"
					:studiensemester_kurzbz="studiensemester_kurzbz"
					@hauptberuf-saved="handleHauptberufFormSaved">
				</hauptberuf-form>
			</template>
			<template v-slot:footer>
				<button type="button" class="btn btn-primary" @click="onBsModalSave">Speichern</button>
			</template>
		</bs-modal>
	`
}
