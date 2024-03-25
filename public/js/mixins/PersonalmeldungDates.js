const PersonalmeldungDates = {
	methods: {
		formatDate(date) {
			if (date == null) return 'unbeschränkt';
			return date.replace(/(.*)-(.*)-(.*)/, '$3.$2.$1');
		}
	}
};

export default PersonalmeldungDates;
