import BsModal from '../../../../../../js/components/Bootstrap/Modal.js';
import {UpdateVerwendungForm} from '../Form/UpdateVerwendung.js';

export default {
	components: {
		BsModal,
		UpdateVerwendungForm
	},
	emits: [
		'verwendungUpdated'
	],
	mixins: [
		BsModal
	],
	data: function() {
		return {
			title: 'Verwendung Bearbeiten',
			verwendung: {}
		}
	},
	mounted() {
		this.modal = this.$refs.modalContainer.modal;
	},
	methods: {
		onHiddenBsModal() {
			this.$refs.verwendungFormCmpt.resetError();
		},
		onBsModalSave() {
			this.$refs.verwendungFormCmpt.update();
		},
		handleVerwendungFormUpdated() {
			this.$emit('verwendungUpdated');
		},
		openVerwendungModal(data) {
			this.verwendung = JSON.parse(JSON.stringify(data)); // deep copy;;
			// Prefill form with Verwendungen
			this.$refs.verwendungFormCmpt.prefill(data.verwendung_code);
			this.$refs.modalContainer.show();
		}
	},
	template: `
		<bs-modal ref="modalContainer" class="bootstrap-prompt" v-bind="$props" @hidden-bs-modal="onHiddenBsModal">
			<template v-slot:title>{{title}}</template>
			<template v-slot:default>
				<update-verwendung-form
					ref="verwendungFormCmpt"
					:verwendung="verwendung"
					@verwendung-updated="handleVerwendungFormUpdated">
				</update-verwendung-form>
			</template>
			<template v-slot:footer>
				<button type="button" class="btn btn-primary" @click="onBsModalSave">Speichern</button>
			</template>
		</bs-modal>
	`
}
