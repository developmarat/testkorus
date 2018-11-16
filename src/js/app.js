import 'bootstrap/dist/css/bootstrap.min.css';
import './../css/style.css';

import $ from 'jquery'
window.jQuery = $;
window.$ = $;
import "bootstrap";
import TimeSheetForm from './TimeSheetForm.js';

$(document).ready(function(){
    let form = $('form').first();
    let resultContainer = $('.result-container').first();
    let errorContainer = $('.error-container').first();

    new TimeSheetForm(form, resultContainer, errorContainer);
});