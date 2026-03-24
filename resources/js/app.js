import './bootstrap';
import flatpickr from 'flatpickr';
import { Spanish } from 'flatpickr/dist/l10n/es.js';
import 'flatpickr/dist/flatpickr.min.css';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

const initBirthDatePickers = () => {
	document.querySelectorAll('[data-birth-date-picker]').forEach((input) => {
		if (input.dataset.pickerReady === '1') {
			return;
		}

		flatpickr(input, {
			locale: Spanish,
			dateFormat: 'Y-m-d',
			altInput: true,
			altFormat: 'd/m/Y',
			maxDate: 'today',
			disableMobile: true,
		});

		input.dataset.pickerReady = '1';
	});
};

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initBirthDatePickers);
} else {
	initBirthDatePickers();
}
