import BsModal from '../../../../../../js/components/Bootstrap/Modal.js';
import {NewVerwendungForm} from '../Form/NewVerwendung.js';

export default {
	components: {
		BsModal,
		NewVerwendungForm
	},
	emits: [
		'verwendungAdded'
	],
	mixins: [
		BsModal
	],
	props: {
		studiensemester_kurzbz: String,
		mitarbeiter: Object
	},
	data: function() {
		return {
			title: 'Verwendung Hinzufügen'
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
			this.$refs.verwendungFormCmpt.add();
		},
		handleVerwendungFormAdded() {
			this.$emit('verwendungAdded');
		},
		openVerwendungModal() {
			// Prefill form with Verwendungen
			this.$refs.verwendungFormCmpt.prefill();
			this.$refs.modalContainer.show();
		}
	},
	template: `
		<bs-modal ref="modalContainer" class="bootstrap-prompt" v-bind="$props" @hidden-bs-modal="onHiddenBsModal">
			<template v-slot:title>{{title}}</template>
			<template v-slot:default>
				<new-verwendung-form
					ref="verwendungFormCmpt"
					:studiensemester_kurzbz="studiensemester_kurzbz"
					:mitarbeiter="mitarbeiter"
					@verwendung-added="handleVerwendungFormAdded">
				</new-verwendung-form>
			</template>
			<template v-slot:footer>
				<button type="button" class="btn btn-primary" @click="onBsModalSave">Speichern</button>
			</template>
		</bs-modal>
	`
}
