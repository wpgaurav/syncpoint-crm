/**
 * Starter CRM Import Wizard
 *
 * @package StarterCRM
 * @since 1.0.0
 */

(function ($) {
    'use strict';

    /**
     * Import Wizard object.
     */
    var ImportWizard = {
        /**
         * Current step.
         */
        currentStep: 1,

        /**
         * File data.
         */
        fileData: null,

        /**
         * Field mapping.
         */
        mapping: {},

        /**
         * Initialize.
         */
        init: function () {
            this.bindEvents();
        },

        /**
         * Bind events.
         */
        bindEvents: function () {
            // File upload.
            $(document).on('change', '#scrm-import-file', this.handleFileSelect.bind(this));

            // Step navigation.
            $(document).on('click', '.scrm-import-next', this.nextStep.bind(this));
            $(document).on('click', '.scrm-import-prev', this.prevStep.bind(this));

            // Field mapping.
            $(document).on('change', '.scrm-field-mapping select', this.updateMapping.bind(this));

            // Start import.
            $(document).on('click', '#scrm-start-import', this.startImport.bind(this));
        },

        /**
         * Handle file selection.
         *
         * @param {Event} e Change event.
         */
        handleFileSelect: function (e) {
            var file = e.target.files[0];
            if (!file) {
                return;
            }

            // Validate file type.
            if (file.type !== 'text/csv' && !file.name.endsWith('.csv')) {
                alert('Please select a CSV file.');
                return;
            }

            // Parse CSV.
            var reader = new FileReader();
            reader.onload = function (event) {
                this.parseCSV(event.target.result);
            }.bind(this);
            reader.readAsText(file);
        },

        /**
         * Parse CSV content.
         *
         * @param {string} content CSV content.
         */
        parseCSV: function (content) {
            var lines = content.split('\n');
            var headers = this.parseCSVLine(lines[0]);
            var rows = [];

            for (var i = 1; i < Math.min(lines.length, 6); i++) {
                if (lines[i].trim()) {
                    rows.push(this.parseCSVLine(lines[i]));
                }
            }

            this.fileData = {
                headers: headers,
                preview: rows,
                totalRows: lines.length - 1
            };

            this.showPreview();
        },

        /**
         * Parse a single CSV line.
         *
         * @param {string} line CSV line.
         * @return {Array} Parsed values.
         */
        parseCSVLine: function (line) {
            var result = [];
            var current = '';
            var inQuotes = false;

            for (var i = 0; i < line.length; i++) {
                var char = line[i];

                if (char === '"') {
                    inQuotes = !inQuotes;
                } else if (char === ',' && !inQuotes) {
                    result.push(current.trim());
                    current = '';
                } else {
                    current += char;
                }
            }

            result.push(current.trim());
            return result;
        },

        /**
         * Show file preview.
         */
        showPreview: function () {
            if (!this.fileData) {
                return;
            }

            var $preview = $('#scrm-import-preview');
            var html = '<table class="wp-list-table widefat fixed striped">';

            // Headers.
            html += '<thead><tr>';
            this.fileData.headers.forEach(function (header) {
                html += '<th>' + this.escapeHtml(header) + '</th>';
            }.bind(this));
            html += '</tr></thead>';

            // Preview rows.
            html += '<tbody>';
            this.fileData.preview.forEach(function (row) {
                html += '<tr>';
                row.forEach(function (cell) {
                    html += '<td>' + this.escapeHtml(cell) + '</td>';
                }.bind(this));
                html += '</tr>';
            }.bind(this));
            html += '</tbody></table>';

            html += '<p class="description">Showing ' + this.fileData.preview.length + ' of ' + this.fileData.totalRows + ' rows.</p>';

            $preview.html(html).show();
            $('.scrm-import-next').prop('disabled', false);
        },

        /**
         * Go to next step.
         */
        nextStep: function () {
            if (this.currentStep === 1 && !this.fileData) {
                alert('Please select a file first.');
                return;
            }

            this.currentStep++;
            this.renderStep();
        },

        /**
         * Go to previous step.
         */
        prevStep: function () {
            if (this.currentStep > 1) {
                this.currentStep--;
                this.renderStep();
            }
        },

        /**
         * Render current step.
         */
        renderStep: function () {
            $('.scrm-import-step').hide();
            $('#scrm-import-step-' + this.currentStep).show();

            if (this.currentStep === 2) {
                this.renderFieldMapping();
            }
        },

        /**
         * Render field mapping interface.
         */
        renderFieldMapping: function () {
            if (!this.fileData) {
                return;
            }

            var $container = $('#scrm-field-mapping');
            var scrmFields = [
                { value: '', label: '-- Skip --' },
                { value: 'first_name', label: 'First Name' },
                { value: 'last_name', label: 'Last Name' },
                { value: 'email', label: 'Email' },
                { value: 'phone', label: 'Phone' },
                { value: 'company_name', label: 'Company Name' },
                { value: 'type', label: 'Type' },
                { value: 'status', label: 'Status' },
                { value: 'address_line_1', label: 'Address Line 1' },
                { value: 'address_line_2', label: 'Address Line 2' },
                { value: 'city', label: 'City' },
                { value: 'state', label: 'State' },
                { value: 'postal_code', label: 'Postal Code' },
                { value: 'country', label: 'Country' },
                { value: 'source', label: 'Source' },
                { value: 'tags', label: 'Tags' }
            ];

            var html = '<table class="form-table scrm-field-mapping">';

            this.fileData.headers.forEach(function (header, index) {
                var autoMatch = this.autoMatchField(header, scrmFields);

                html += '<tr>';
                html += '<th>' + this.escapeHtml(header) + '</th>';
                html += '<td><select data-column="' + index + '">';

                scrmFields.forEach(function (field) {
                    var selected = field.value === autoMatch ? ' selected' : '';
                    html += '<option value="' + field.value + '"' + selected + '>' + field.label + '</option>';
                });

                html += '</select></td>';
                html += '</tr>';
            }.bind(this));

            html += '</table>';

            $container.html(html);
        },

        /**
         * Auto-match CSV header to SCRM field.
         *
         * @param {string} header CSV header.
         * @param {Array} fields Available fields.
         * @return {string} Matched field value.
         */
        autoMatchField: function (header, fields) {
            var normalized = header.toLowerCase().replace(/[^a-z0-9]/g, '_');

            var matches = {
                'email': 'email',
                'e_mail': 'email',
                'email_address': 'email',
                'first_name': 'first_name',
                'firstname': 'first_name',
                'first': 'first_name',
                'last_name': 'last_name',
                'lastname': 'last_name',
                'last': 'last_name',
                'phone': 'phone',
                'telephone': 'phone',
                'mobile': 'phone',
                'company': 'company_name',
                'company_name': 'company_name',
                'organization': 'company_name',
                'address': 'address_line_1',
                'address_1': 'address_line_1',
                'street': 'address_line_1',
                'city': 'city',
                'state': 'state',
                'province': 'state',
                'zip': 'postal_code',
                'zip_code': 'postal_code',
                'postal_code': 'postal_code',
                'postcode': 'postal_code',
                'country': 'country',
                'source': 'source',
                'tags': 'tags'
            };

            return matches[normalized] || '';
        },

        /**
         * Update field mapping.
         *
         * @param {Event} e Change event.
         */
        updateMapping: function (e) {
            var $select = $(e.target);
            var column = $select.data('column');
            var value = $select.val();

            if (value) {
                this.mapping[column] = value;
            } else {
                delete this.mapping[column];
            }
        },

        /**
         * Start import process.
         */
        startImport: function () {
            // Collect mapping.
            $('.scrm-field-mapping select').each(function () {
                var $select = $(this);
                var column = $select.data('column');
                var value = $select.val();

                if (value) {
                    this.mapping[column] = value;
                }
            }.bind(this));

            // Validate email mapping.
            var hasEmail = Object.values(this.mapping).includes('email');
            if (!hasEmail) {
                alert('Email field mapping is required.');
                return;
            }

            // Show progress.
            $('#scrm-import-progress').show();
            $('#scrm-start-import').prop('disabled', true);

            // TODO: Implement AJAX import.
            console.log('Starting import with mapping:', this.mapping);
        },

        /**
         * Escape HTML.
         *
         * @param {string} text Text to escape.
         * @return {string} Escaped text.
         */
        escapeHtml: function (text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    /**
     * Initialize on document ready.
     */
    $(document).ready(function () {
        ImportWizard.init();
    });

})(jQuery);
