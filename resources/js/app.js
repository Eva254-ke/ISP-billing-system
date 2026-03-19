import './bootstrap';

// AdminLTE 3.2 Dependencies (jQuery required)
import jQuery from 'jquery';
window.$ = window.jQuery = jQuery;

import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

import 'admin-lte/dist/js/adminlte';

// Additional Libraries
import ApexCharts from 'apexcharts';
window.ApexCharts = ApexCharts;

import Swal from 'sweetalert2';
window.Swal = Swal;

import 'datatables.net-bs5';
import 'flatpickr';
import '@fortawesome/fontawesome-free/js/all';

// Alpine.js
import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();

// Custom Admin JS
import './admin';
