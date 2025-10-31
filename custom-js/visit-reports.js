var visitReports = {
    searchparams: [],
    sortparams: [],
    default_sort: {sorton: 'visit_date', sortorder: 'DESC'},
    paginationdata: {},
    defaultleadtabtext: 'Visit Reports',
    filtersapplied: [],
    ajax_data_script: 'visit-reports.php',
    curr_page_hash: '',
    prev_page_hash: '',
    list_page_hash: '',
    current_customer_contacts: [],
    detailed_notes_editor: null, // CKEditor instance for detailed notes
    admin_notes_editor: null, // CKEditor instance for admin notes
    visit_report_attach_url:'',

    init: function() {
        $('.main-content').on('click', 'td.clickable-cell', {self: visitReports}, common_js_funcs.changeLocationWithDataProperty);
        $('.main-content').on('click', '.page-link', {self: visitReports}, visitReports.changePage);
        $('.main-content').on('click', '.toggle-search', {self: visitReports}, visitReports.toggleSearch);
        $('#recs-list>thead>tr>th.sortable').bind('click', {self: visitReports}, visitReports.sortTable);
        $('#rec_list_container').on('click', '.searched_elem .remove_filter', visitReports.clearSearch);
        $('.main-content').on('click', '#add-new-contact-btn', visitReports.addContactRow);
        $('.main-content').on('click', '#add-selected-contacts-btn', visitReports.addSelectedContacts);
        $('.main-content').on('click', '.remove-contact', visitReports.removeContactRow);
        $('.main-content').on('click', '.delete-record-btn', visitReports.confirmDelete);
        $(window).hashchange(visitReports.onHashChange);
        $(window).hashchange();

        // Initialize datepickers
        visitReports.initDatepickers();
        visitReports.initCKEditor();
    },

    // Centralized CKEditor initialization - called once on page load
    initCKEditor: function() {
        // Initialize detailed notes editor (add form)
        if (document.querySelector('#add_detailed_notes')) {
            ClassicEditor.create(document.querySelector('#add_detailed_notes'), {
                toolbar: {
                    removeItems: ['link', 'blockQuote', 'codeBlock', 'code', 'uploadImage', 'insertImage', 'insertTable', 'mediaEmbed'],
                    shouldNotGroupWhenFull: true
                },
            })
                .then(editor => {
                    editor.config.height = '200';
                    visitReports.detailed_notes_editor = editor;
                })
                .catch(error => {
                    console.error('Detailed Notes CKEditor initialization error:', error);
                });
        }

        // Initialize admin notes editor (view form) - if element exists
        if (document.querySelector('#admin_notes_textarea')) {
            ClassicEditor.create(document.querySelector('#admin_notes_textarea'), {
                toolbar: {
                    removeItems: ['link', 'blockQuote', 'codeBlock', 'code', 'uploadImage', 'insertImage', 'insertTable', 'mediaEmbed'],
                    shouldNotGroupWhenFull: true
                }
            })
                .then(editor => {
                    visitReports.admin_notes_editor = editor;
                    // Initially hide the editor container
                    $('#admin-notes-edit').hide();
                })
                .catch(error => {
                    console.error('Admin Notes CKEditor initialization error:', error);
                });
        }
    },

    initDatepickers: function() {
        // Initialize all datepicker fields
        $('.datepicker').datepicker({
            dateFormat: 'dd-mm-yy',
            showOn: "both",
            buttonImage: 'images/calendar.png',
            changeMonth: true,
            changeYear: true,
            showButtonPanel: true,
            maxDate: '+0d', // Don't allow future dates for visit reports
        });

        // Set default date to today for add form
        $('#add_visit_date').datepicker('setDate', new Date());
    },

    loadCustomerContacts: function(customer_id) {
        if (!customer_id) {
            $('#existing-contacts-section').hide();
            $('#existing-contacts-select').empty();
            visitReports.current_customer_contacts = [];
            return;
        }

        var params = {
            mode: 'getCustomerContacts',
            customer_id: customer_id
        };

        var ajaxOptions = {
            cache: 'no-cache',
            async: true,
            type: 'post',
            dataType: 'json',
            url: visitReports.ajax_data_script,
            params: params,
            successResponseHandler: function(resp) {
                if (resp.error_code === 0) {
                    visitReports.current_customer_contacts = resp.contacts || [];
                    var select = $('#existing-contacts-select');
                    select.empty();

                    if (visitReports.current_customer_contacts.length > 0) {
                        visitReports.current_customer_contacts.forEach(function(contact) {
                            var option = $('<option></option>')
                                .attr('value', contact.id)
                                .text(contact.name + (contact.designation ? ' (' + contact.designation + ')' : ''));
                            select.append(option);
                        });
                        $('#existing-contacts-section').show();
                    } else {
                        $('#existing-contacts-section').hide();
                    }
                }
            }
        };

        common_js_funcs.callServer(ajaxOptions);
    },

    addSelectedContacts: function() {
        var selectedOptions = $('#existing-contacts-select option:selected');
        selectedOptions.each(function() {
            var contactId = $(this).val();
            var contact = visitReports.current_customer_contacts.find(c => c.id == contactId);
            if (contact) {
                visitReports.addContactRow(contact);
            }
        });
        selectedOptions.prop('selected', false);
    },

    addContactRow: function(contactData) {
        const contact = contactData || {
            id: '',
            name: '',
            department: '',
            designation: '',
            email: '',
            phone: '',
            is_new_contact: contactData ? 0 : 1
        };

        const contactTemplate = `
            <tr class="contact-row">
                <td data-label="Name *">
                    <input type="hidden" name="contact_id[]" class="contact-id" value="${contact.id || ''}">
                    <input type="text" class="form-control contact-name" name="contact_name[]" value="${contact.name || ''}" placeholder="Name *" required>
                </td>
                <td data-label="Department">
                    <input type="text" class="form-control contact-department" name="contact_department[]" value="${contact.department || ''}" placeholder="Department">
                </td>
                <td data-label="Designation">
                    <input type="text" class="form-control contact-designation" name="contact_designation[]" value="${contact.designation || ''}" placeholder="Designation">
                </td>
                <td data-label="Email">
                    <input type="email" class="form-control contact-email" name="contact_email[]" value="${contact.email || ''}" placeholder="Email">
                </td>
                <td data-label="Phone">
                    <input type="tel" class="form-control contact-phone" name="contact_phone[]" value="${contact.phone || ''}" placeholder="Phone">
                </td>
                <td data-label="Actions">
                    <button type="button" class="btn btn-danger btn-sm remove-contact rounded">Remove</button>
                </td>
            </tr>
        `;
        $('#contacts-table tbody').append(contactTemplate);
    },

    removeContactRow: function(event) {
        $(event.target).closest('.contact-row').remove();
    },

    toggleSearch: function(ev) {
        visitReports.setPanelVisibilityStatus('visit_reports_search_toggle',
            $(ev.currentTarget).hasClass('search-form-visible') ? '' : 'visible');
        visitReports.showHidePanel('visit_reports_search_toggle');
    },

    setPanelVisibilityStatus: function(panel, status) {
        if (typeof(Storage) !== "undefined") {
            localStorage[panel] = status;
        } else {
            Cookies.set(panel, status, {path : '/'});
        }
    },

    showHidePanel: function(panel) {
        if(panel === 'visit_reports_search_toggle') {
            let show_srch_form = false;
            if (typeof(Storage) !== "undefined") {
                srch_frm_visible = localStorage.visit_reports_search_toggle;
            } else {
                srch_frm_visible = Cookies.get('visit_reports_search_toggle');
            }

            if(srch_frm_visible && srch_frm_visible == 'visible')
                show_srch_form = true;

            $('.toggle-search').toggleClass('search-form-visible', show_srch_form);
            $('#search_records').closest('.panel-search').toggleClass('d-none', !show_srch_form);

            let search_form_cont = $('#search_records').closest('.panel-search');
            if(search_form_cont.hasClass('d-none'))
                $('.toggle-search').prop('title','Open search panel');
            else {
                $('.toggle-search').prop('title','Close search panel');
                $("#search-field_customer_name").focus();
            }
        }
    },

    getList: function(options) {
        var self = visitReports;
        var pno = 1;
        var params = [];

        if ('pno' in options) {
            params.push('pno=' + encodeURIComponent(options.pno));
        } else {
            params.push('pno=1');
        }

        params.push('searchdata=' + encodeURIComponent(JSON.stringify(self.searchparams)));
        params.push('sortdata=' + encodeURIComponent(JSON.stringify(self.sortparams)));
        params.push('ref=' + Math.random());

        $("#common-processing-overlay").removeClass('d-none');
        location.hash = params.join('&');
    },

    showList: function(resp, otherparams) {
        var self = visitReports;
        var listhtml = resp[1].list;

        $("#rec_list_container").removeClass('d-none');
        $("#rec_detail_add_edit_container").addClass('d-none');
        $("#rec_detail_view_container").addClass('d-none');  // Add this line to hide view container
        $("#common-processing-overlay").addClass('d-none');

        $("#userlistbox").html(listhtml);

        if (resp[1].reccount > 0) {
            $('#heading_rec_cnt').text(`(${resp[1]['reccount']})`);
            visitReports.setExportLink(false); // do not show the export option, if required it will be activated later
        } else {
            $('#heading_rec_cnt').text('(0)');
            visitReports.setExportLink(false);
        }

        $("#add-record-button").removeClass('d-none');
        $(".back-to-list-button").addClass('d-none').attr('href', "visit-reports.php#"+visitReports.curr_page_hash);

        self.paginationdata = resp[1].paginationdata;
        self.setSortOrderIcon();
    },

    setExportLink: function(show) {
        const dnld_elem = $('#export_visit_reports');
        if (dnld_elem.length <= 0)
            return;

        let url = '#';
        if (show === true) {
            let params = [];
            params.push('mode=export');
            params.push('searchdata=' + encodeURIComponent(JSON.stringify(visitReports.searchparams)));
            params.push('sortdata=' + encodeURIComponent(JSON.stringify(visitReports.sortparams)));
            params.push('ref=' + Math.random());
            url = `${window.location.origin}${window.location.pathname}?${params.join('&')}`;
        }

        dnld_elem.attr('href', url).toggleClass('d-none', show !== true);
    },

    changePage: function(ev) {
        ev.preventDefault();
        if (!$(ev.currentTarget).parent().hasClass('disabled')) {
            var self = visitReports;
            var pno = $(ev.currentTarget).data('page');
            self.getList({pno: pno});
        }
    },

    sortTable: function(e) {
        var self = e.data.self;
        var elemid = e.currentTarget.id;
        var elemidparts = elemid.split('_');
        var sorton = elemidparts[1].replaceAll('-', '_');
        var sortorder = 'ASC';

        if ($(e.currentTarget).find("i:eq(0)").hasClass('fa-sort-up'))
            sortorder = 'DESC';

        visitReports.sortparams = [];
        visitReports.sortparams.push({sorton: sorton, sortorder: sortorder});

        var options = {pno: 1};
        visitReports.getList(options);
    },

    setSortOrderIcon: function() {
        var self = visitReports;
        if (self.sortparams.length > 0) {
            var sorton = self.sortparams[0].sorton.replaceAll('_', '-');
            var colheaderelemid = 'colheader_' + sorton;

            if (self.sortparams[0].sortorder == 'DESC') {
                var sort_order_class = 'fa-sort-down';
            } else {
                var sort_order_class = 'fa-sort-up';
            }

            $("#" + colheaderelemid)
                .siblings('th.sortable')
                .removeClass('sorted-col')
                .find('i:eq(0)')
                .removeClass('fa-sort-down fa-sort-up')
                .addClass('fa-sort')
                .end()
                .end()
                .addClass('sorted-col')
                .find('i:eq(0)')
                .removeClass('fa-sort-down fa-sort-up')
                .addClass(sort_order_class);
        }
    },

    resetSearchParamsObj: function() {
        visitReports.searchparams = [];
    },

    setSearchParams: function(obj) {
        visitReports.searchparams.push(obj);
    },

    clearSearch: function(e) {
        let remove_all = true;
        if (e) {
            e.stopPropagation();
            elem = e.currentTarget;
            if ($(elem).hasClass('remove_filter')) {
                remove_all = $(elem).data('fld');
                $(elem).parent('.searched_elem').remove();

                if (remove_all === 'visit_date_range') {
                    $('#search-field_visit_date_start, #search-field_visit_date_end').val('');
                } else if (remove_all === 'customer_id') {
                    $('#search-field_customer_id').val('');
                } else if (remove_all === 'department') {
                    $('#search-field_department').val('');
                } else if (remove_all === 'type') {
                    $('#search-field_type').val('');
                } else if (remove_all === 'created_by') {
                    $('#search-field_created_by').val('');
                } else if (remove_all === 'customer_group_id') {
                    $('#search-field_customer_group_id').val('');
                } else {
                    $('.panel-search .srchfld[data-fld=' + remove_all + ']').val('');
                }
            }
        }

        if (remove_all === true) {
            visitReports.resetSearchParamsObj();
            document.search_form.reset();
        } else {
            visitReports.searchparams = visitReports.searchparams.filter(fltr => {
                return fltr.searchon !== remove_all;
            });
        }

        var options = {pno: 1};
        visitReports.getList(options);
        return false;
    },

    doSearch: function() {
        visitReports.resetSearchParamsObj();
        let fld = '';

        // Handle regular search fields
        $('.panel-search .srchfld').each(function(i, el) {
            let val = $.trim($(el).val());
            let field_type = $(el).data('type');
            let disp_text = '';

            if (field_type === 'DATE_RANGE') {
                // Skip individual date fields, handle them separately
                return;
            }

            if (val != '') {
                fld = $(el).data('fld');
                if(fld==='customer_group_id' || fld==='customer_id' || fld==='created_by' || fld==='department' || fld==='type') {
                    disp_text = $(el).find('option:selected').text().replace(/\r?\n/g, '');
                }
                visitReports.setSearchParams({
                    searchon: $(el).data('fld'),
                    searchtype: $(el).data('type'),
                    searchtext: val,
                    disptext: disp_text,
                });
            }
        });

        // Handle date range search - convert dates to server format
        let startDate = $('#search-field_visit_date_start').val();
        let endDate = $('#search-field_visit_date_end').val();

        if (startDate && endDate) {
            let convertedStartDate = visitReports.convertDateForServer(startDate);
            let convertedEndDate = visitReports.convertDateForServer(endDate);

            visitReports.setSearchParams({
                searchon: 'visit_date_range',
                searchtype: 'DATE_RANGE',
                searchtext: `${startDate} to ${endDate}`, // Display format for filter text
                start_date: convertedStartDate, // Server format
                end_date: convertedEndDate // Server format
            });
        }

        if (visitReports.searchparams.length <= 0)
            return false;

        var options = {pno: 1};
        visitReports.getList(options);
        return false;
    },

    openRecordForViewing: function(recordid) {
        if (recordid == '')
            return false;

        $("#common-processing-overlay").removeClass('d-none');

        var options = {
            mode: 'viewrecord',
            recordid: recordid,
            loadingmsg: "Opening the visit report '" + recordid + "' for viewing...",
            leadtabtext: 'View Visit Report Details'
        };

        visitReports.openRecord(options);
        return false;
    },

    openRecord: function(options) {
        var opts = {leadtabtext: 'Visit Report Details'};
        $.extend(true, opts, options);

        var params = {
            mode: "getRecordDetails",
            recordid: opts.recordid
        };

        var ajaxOptions = {
            cache: 'no-cache',
            async: true,
            type: 'post',
            dataType: 'json',
            url: visitReports.ajax_data_script,
            params: params,
            successResponseHandler: visitReports.showVisitReportDetailsWindow,
            successResponseHandlerParams: {
                self: this,
                mode: opts.mode,
                recordid: opts.recordid,
                header_bar_text: opts.leadtabtext
            }
        };

        common_js_funcs.callServer(ajaxOptions);
    },

    showVisitReportDetailsWindow: function(resp, otherparams) {
        if (resp[0] != 0) {
            alert('Error loading visit report details. Please try again.');
            $("#common-processing-overlay").addClass('d-none');
            window.location.hash = this.prev_page_hash??'';
            return false;
        }

        var recorddetails = resp[1].record_details;
        var can_edit_admin_notes = resp[1].can_edit_admin_notes;

        // Populate the view with data
        visitReports.populateViewTemplate(recorddetails, can_edit_admin_notes);

        $("#add-record-button").addClass('d-none');
        $("#rec_list_container").addClass('d-none');
        $("#rec_detail_add_edit_container").addClass('d-none');
        $("#rec_detail_view_container").removeClass('d-none');

        $(".back-to-list-button").removeClass('d-none');

        $("#common-processing-overlay").addClass('d-none');

        document.querySelector('.main-content').scrollIntoView(true);
    },

    populateViewTemplate: function(recorddetails, can_edit_admin_notes) {
        // Populate the view with data
        $('#view_customer_name').text(recorddetails.customer_name || '');
        
        // Add department display
        let departmentText = '';
        switch(parseInt(recorddetails.department)) {
            case 1: departmentText = 'Supply Chain'; break;
            case 2: departmentText = 'R & D'; break;
            case 3: departmentText = 'Others'; break;
            default: departmentText = 'Not specified';
        }
        $('#view_department').text(departmentText);
        
        // Add type display
        let typeText = '';
        switch(parseInt(recorddetails.type)) {
            case 1: typeText = 'New'; break;
            case 2: typeText = 'Existing'; break;
            default: typeText = 'Not specified';
        }
        $('#view_type').text(typeText);

        $('#view_visit_date').text(recorddetails.visit_date ? recorddetails.visit_date_disp: '');
        $('#view_meeting_title').text(recorddetails.meeting_title || '');

        // Display detailed notes as HTML content
        let detailedNotes = recorddetails.detailed_notes || '';
        if (detailedNotes && detailedNotes.trim() !== '') {
            $('#view_detailed_notes').html(detailedNotes);
        } else {
            $('#view_detailed_notes').html('<em>No detailed notes provided.</em>');
        }

        $('#view_created_by').text(recorddetails.created_by_name || '');
        $('#view_created_on').text(recorddetails.created_on ? recorddetails.created_on_disp: '');

        // Handle attachment
        if (recorddetails.attachment_file_name) {
            $('#attachment-filename').text(recorddetails.attachment_file_name);
            $('#attachment-download-link').attr('href', visitReports.visit_report_attach_url + recorddetails.attachment_file_path);
            $('#attachment-row').show();
        } else {
            $('#attachment-row').hide();
        }

        // Handle contacts
        var contactsList = $('#view_contacts_list');
        if (recorddetails.contacts && recorddetails.contacts.length > 0) {
            var contactsHtml = '';
            recorddetails.contacts.forEach(function(contact) {
                contactsHtml += '<div class="mb-2 p-2 view_contact_block_border">';
                contactsHtml += '<strong>' + (contact.name || '') + '</strong>';
                if (contact.designation) contactsHtml += ' - ' + contact.designation;
                if (contact.department) contactsHtml += ' (' + contact.department + ')';
                contactsHtml += '<br>';
                if (contact.email) contactsHtml += '<small>Email: ' + contact.email + '</small><br>';
                if (contact.phone) contactsHtml += '<small>Phone: ' + contact.phone + '</small>';
                contactsHtml += '</div>';
            });
            contactsList.html(contactsHtml);
        } else {
            contactsList.html('<em>No contacts recorded for this visit.</em>');
        }

        // Handle admin notes as HTML content
        $('#admin_notes_recordid').val(recorddetails.id);
        let adminNotesHtml = recorddetails.admin_notes || '<em>No admin notes yet.</em>';
        $('#admin-notes-view').html(adminNotesHtml); // Use .html() instead of .text()

        // Set CKEditor content if editor is available
        if (visitReports.admin_notes_editor) {
            visitReports.admin_notes_editor.setData(recorddetails.admin_notes || '');
        } else {
            $('#admin_notes_textarea').val(recorddetails.admin_notes || '');
        }

        if (recorddetails.admin_notes_updated_on) {
            var updatedInfo = 'Last updated by ' + (recorddetails.admin_notes_updated_by_name || 'Unknown') +
                ' on ' + recorddetails.admin_notes_updated_on_disp;
            $('#view_admin_notes_updated').text(updatedInfo);
            $('#admin-notes-updated-info').show();
        } else {
            $('#admin-notes-updated-info').hide();
        }

        // Show edit button for admin notes if user has permission
        if (can_edit_admin_notes) {
            $('#admin-notes-buttons').html('<button type="button" class="btn btn-primary btn-sm rounded" onclick="visitReports.editAdminNotes();"><i class="fa fa-edit"></i> Edit Notes</button>');
        } else {
            $('#admin-notes-buttons').empty();
        }
    },

    openAddForm: function(e) {
        document.addrecform && document.addrecform.reset();

        $("#add-record-button").addClass('d-none');
        $("#rec_list_container").addClass('d-none');
        $("#rec_detail_view_container").addClass('d-none');
        $("#rec_detail_add_edit_container").removeClass('d-none');

        // Clear any existing contacts and reset form
        $('#contacts-table tbody').empty();
        $('#existing-contacts-section').hide();
        $('#add_customer_id').val('').trigger('change');

        // Add one default contact row to ensure people met is not empty
        visitReports.addContactRow();

        $('#msgFrm').removeClass('d-none');
        $(".back-to-list-button").removeClass('d-none');

        // Set default date to today using the correct format
        var today = new Date();
        var todayFormatted = ('0' + today.getDate()).slice(-2) + '-' +
            ('0' + (today.getMonth() + 1)).slice(-2) + '-' +
            today.getFullYear();
        $('#add_visit_date').val(todayFormatted);

        // Re-initialize datepickers for the form
        visitReports.initDatepickers();

        // Clear CKEditor content
        if (visitReports.detailed_notes_editor) {
            visitReports.detailed_notes_editor.setData('');
        }

        $("#common-processing-overlay").addClass('d-none');

        // Focus on first field
        setTimeout(function() {
            $("#add_customer_id").focus();
        }, 100);

        document.querySelector('.main-content').scrollIntoView(true);

        return false;
    },

    validateRecDetails: function(opts) {
        $(".form-control").removeClass("error-field");

        let customer_id = $('#add_customer_id').val();
        let department = $('#add_department').val();
        let type = $('input[name="type"]:checked').val();
        let visit_date = $('#add_visit_date').val();
        let meeting_title = $('#add_meeting_title').val().trim();

        if (!customer_id) {
            $('#add_customer_id').addClass("error-field");
            return {'error_msg': 'Customer is required.', 'error_field': $('#add_customer_id')};
        }

        if (!department) {
            $('#add_department').addClass("error-field");
            return {'error_msg': 'Department is required.', 'error_field': $('#add_department')};
        }

        if (!type) {
            $('input[name="type"]').addClass("error-field");
            return {'error_msg': 'Type is required.', 'error_field': $('input[name="type"]:first')};
        }

        if (!visit_date) {
            $('#add_visit_date').addClass("error-field");
            return {'error_msg': 'Visit date is required.', 'error_field': $('#add_visit_date')};
        }

        // Validate date format (dd-mm-yyyy)
        if (!/^\d{2}-\d{2}-\d{4}$/.test(visit_date)) {
            $('#add_visit_date').addClass("error-field");
            return {'error_msg': 'Please enter visit date in DD-MM-YYYY format.', 'error_field': $('#add_visit_date')};
        }

        if (!meeting_title) {
            $('#add_meeting_title').addClass("error-field");
            return {'error_msg': 'Meeting title is required.', 'error_field': $('#add_meeting_title')};
        }

        // NEW: Validate detailed notes (check for meaningful content)
        let detailedNotesContent = '';
        if (visitReports.detailed_notes_editor) {
            detailedNotesContent = visitReports.detailed_notes_editor.getData().trim();
        } else {
            detailedNotesContent = $('#add_detailed_notes').val().trim();
        }

        // Check if content is empty or contains only HTML tags with no text
        let textContent = detailedNotesContent.replace(/<[^>]*>/g, '').trim();
        if (!textContent) {
            $('#add_detailed_notes').addClass("error-field");
            if (visitReports.detailed_notes_editor) {
                visitReports.detailed_notes_editor.focus();
            }
            return {'error_msg': 'Detailed notes are required.', 'error_field': $('#add_detailed_notes')};
        }

        // NEW: Validate people met (ensure at least one contact)
        let hasContacts = false;
        $('.contact-row').each(function() {
            let name = $(this).find('.contact-name').val().trim();
            if (name !== '') {
                hasContacts = true;
                return false; // Break the loop
            }
        });

        if (!hasContacts) {
            $('#contacts-table').addClass("error-field");
            return {'error_msg': 'At least one person met is required.', 'error_field': $('#contacts-table')};
        }

        // Validate attachment if present
        var fileInput = $('#add_attachment')[0];
        if (fileInput && fileInput.files.length > 0) {
            var file = fileInput.files[0];
            var allowedTypes = ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/pdf'];
            var maxSize = 10 * 1024 * 1024; // 10MB

            if (!allowedTypes.includes(file.type)) {
                $('#add_attachment').addClass("error-field");
                return {'error_msg': 'Invalid file type. Only DOC, DOCX, XLS, XLSX, PDF files are allowed.', 'error_field': $('#add_attachment')};
            }

            if (file.size > maxSize) {
                $('#add_attachment').addClass("error-field");
                return {'error_msg': 'File size exceeds 10MB limit.', 'error_field': $('#add_attachment')};
            }
        }

        // Validate existing contacts
        let contactsValid = true;
        let contactErrors = [];

        $('.contact-row').each(function(index) {
            let name = $(this).find('.contact-name').val().trim();
            let email = $(this).find('.contact-email').val().trim();
            let phone = $(this).find('.contact-phone').val().trim();

            if (name === '') {
                $(this).find('.contact-name').addClass('error-field');
                contactErrors.push('Contact name is required.');
                contactsValid = false;
                return false;
            }

            if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                $(this).find('.contact-email').addClass('error-field');
                contactErrors.push('Invalid email format.');
                contactsValid = false;
                return false;
            }

            if (phone && !/^[0-9+\-\s()]{10,}$/.test(phone)) {
                $(this).find('.contact-phone').addClass('error-field');
                contactErrors.push('Invalid phone number format.');
                contactsValid = false;
                return false;
            }
        });

        return contactsValid ?
            {'error_msg': null, 'error_field': null} :
            {'error_msg': contactErrors[0], 'error_field': $('.contact-name.error-field:first')};
    },

    // Helper function to convert date from dd-mm-yyyy to yyyy-mm-dd for server
    convertDateForServer: function(dateStr) {
        if (!dateStr) return '';
        var parts = dateStr.split('-');
        if (parts.length === 3) {
            return parts[2] + '-' + parts[1] + '-' + parts[0]; // yyyy-mm-dd
        }
        return dateStr;
    },

    // Helper function to convert date from yyyy-mm-dd to dd-mm-yyyy for display
    convertDateForDisplay: function(dateStr) {
        if (!dateStr) return '';
        var parts = dateStr.split('-');
        if (parts.length === 3) {
            return parts[2] + '-' + parts[1] + '-' + parts[0]; // dd-mm-yyyy
        }
        return dateStr;
    },

    saveRecDetails: function(formelem) {
        var self = this;
        var data_mode = $(formelem).data('mode');

        var res = self.validateRecDetails({mode: data_mode});
        if (res.error_field) {
            alert(res.error_msg);
            setTimeout(function(){
                if (res.error_field.attr('id') === 'add_detailed_notes' && visitReports.detailed_notes_editor) {
                    visitReports.detailed_notes_editor.focus();
                } else if (res.error_field.attr('id') === 'contacts-table') {
                    // Focus on first contact name field
                    $('.contact-name:first').focus();
                } else {
                    $(res.error_field).focus();
                }
            }, 0);
            return false;
        }

        // Sync CKEditor content to textarea before submission
        try {
            if (visitReports.detailed_notes_editor) {
                visitReports.detailed_notes_editor.updateSourceElement();
            }
        } catch (error) {
            console.warn('Could not sync CKEditor content:', error);
            // Continue with form submission as textarea might have content
        }

        // Convert date format from dd-mm-yyyy to yyyy-mm-dd before submission
        var visitDateField = $('#add_visit_date');
        var originalDate = visitDateField.val();
        var convertedDate = visitReports.convertDateForServer(originalDate);
        visitDateField.val(convertedDate);

        $("#common-processing-overlay").removeClass('d-none');
        $('#record-save-button').addClass('disabled').attr('disabled', true);

        $('#rec_detail_add_edit_container .error-field').removeClass('error-field');

        return true;
    },

    handleAddRecResponse: function(resp) {
        $(".form-control").removeClass("error-field");

        if (resp.error_code == 0) {
            var message_container = '.alert-success';
            $("form[name=addrecform]").find(".error-field").removeClass('error-field').end().get(0).reset();
            $('#contacts-table > tbody').empty();
            $('#existing-contacts-section').hide();

            // Clear CKEditor content safely
            if (visitReports.detailed_notes_editor) {
                try {
                    visitReports.detailed_notes_editor.setData('');
                } catch (error) {
                    console.warn('Could not clear CKEditor content:', error);
                    // Fallback to textarea clear
                    $('#add_detailed_notes').val('');
                }
            }

            // Add default contact row for next entry
            visitReports.addContactRow();

            $("#add_customer_id").focus();
            document.querySelector('.main-content').scrollIntoView(true);
        } else if (resp.error_code == 2) {
            var message_container = '';
            if (resp.error_fields && resp.error_fields.length > 0) {
                var msg = Array.isArray(resp.message) ? resp.message.join('<br>') : resp.message;
                alert(msg);

                // Handle focus for different field types
                var errorField = $(resp.error_fields[0]);
                if (errorField.attr('id') === 'add_detailed_notes' && visitReports.detailed_notes_editor) {
                    visitReports.detailed_notes_editor.focus();
                } else if (errorField.attr('id') === 'contacts-table') {
                    // Focus on first contact name field
                    $('.contact-name:first').focus();
                } else {
                    errorField.focus();
                }

                errorField.addClass("error-field");
            }
        } else {
            var message_container = '.alert-danger';
        }

        $('#record-save-button').removeClass('disabled').attr('disabled', false);
        $("#common-processing-overlay").addClass('d-none');

        if (message_container != '') {
            $(message_container)
                .removeClass('d-none')
                .siblings('.alert')
                .addClass('d-none')
                .end()
                .find('.alert-message')
                .html(Array.isArray(resp.message) ? resp.message.join('<br>') : resp.message);

            var page_scroll = '.main-container-inner';
            common_js_funcs.scrollTo($(page_scroll));
            $('#msgFrm').addClass('d-none');
        }
    },

    editAdminNotes: function() {
        $('#admin-notes-view').hide();
        $('#admin-notes-buttons').hide();
        $('#admin-notes-edit').show();

        // Set focus to CKEditor if available
        if (visitReports.admin_notes_editor) {
            visitReports.admin_notes_editor.focus();
        }
    },

    cancelEditAdminNotes: function() {
        // No editor destruction needed - just hide/show containers
        $('#admin-notes-edit').hide();
        $('#admin-notes-view').show();
        $('#admin-notes-buttons').show();
    },

    updateAdminNotes: function(formelem) {
        // Sync CKEditor content before submission
        if (visitReports.admin_notes_editor) {
            visitReports.admin_notes_editor.updateSourceElement();
        }

        $("#common-processing-overlay").removeClass('d-none');
        $('#save-admin-notes-btn').addClass('disabled').attr('disabled', true);
        return true;
    },

    handleUpdateAdminNotesResponse: function(resp) {
        if (resp.error_code == 0) {
            var message_container = '.alert-success';
            visitReports.cancelEditAdminNotes();

            // Update the view directly with the returned data
            if (resp.updated_data) {
                $('#admin-notes-view').html(resp.updated_data.admin_notes || '<em>No admin notes yet.</em>');

                // Update CKEditor content if available
                if (visitReports.admin_notes_editor) {
                    visitReports.admin_notes_editor.setData(resp.updated_data.admin_notes || '');
                } else {
                    $('#admin_notes_textarea').val(resp.updated_data.admin_notes || '');
                }

                var updatedInfo = 'Last updated by ' + resp.updated_data.admin_notes_updated_by_name +
                    ' on ' + resp.updated_data.admin_notes_updated_on_disp;
                $('#view_admin_notes_updated').text(updatedInfo);
                $('#admin-notes-updated-info').show();
            }
        } else {
            var message_container = '.alert-danger';
        }

        $('#save-admin-notes-btn').removeClass('disabled').attr('disabled', false);
        $("#common-processing-overlay").addClass('d-none');

        if (message_container != '') {
            $(message_container)
                .removeClass('d-none')
                .siblings('.alert')
                .addClass('d-none')
                .end()
                .find('.alert-message')
                .html(resp.message);

            var page_scroll = '.main-container-inner';
            common_js_funcs.scrollTo($(page_scroll));
        }
    },

    confirmDelete: function(e) {
        e.preventDefault();
        var recordId = $(e.currentTarget).data('recid');

        if (confirm('Are you sure you want to delete this visit report? This action cannot be undone.')) {
            visitReports.deleteRecord(recordId);
        }
    },

    deleteRecord: function(recordId) {
        var params = {
            mode: 'deleteRecord',
            recordid: recordId
        };

        $("#common-processing-overlay").removeClass('d-none');

        var ajaxOptions = {
            cache: 'no-cache',
            async: true,
            type: 'post',
            dataType: 'json',
            url: visitReports.ajax_data_script,
            params: params,
            successResponseHandler: visitReports.handleDeleteResponse,
            successResponseHandlerParams: {
                recordId: recordId
            }
        };

        common_js_funcs.callServer(ajaxOptions);
    },

    handleDeleteResponse: function(resp, otherparams) {
        $("#common-processing-overlay").addClass('d-none');
        // Show success message
        alert(resp.message);
        if (resp.error_code === 0) {
            // Refresh the list - stay on current page or go to previous if this was the last record
            var currentPage = visitReports.paginationdata.current_page || 1;
            var totalPages = visitReports.paginationdata.total_pages || 1;
            var recordsOnPage = $('#userlistbox tr').length - 1; // Subtract 1 for potential filter row

            // If this was the last record on the last page, go to previous page
            if (recordsOnPage === 1 && currentPage === totalPages && currentPage > 1) {
                currentPage = currentPage - 1;
            }

            visitReports.getList({pno: currentPage});
        }
        var page_scroll = '.main-container-inner';
        common_js_funcs.scrollTo($(page_scroll));
    },

    onHashChange: function(e) {
        var hash = location.hash.replace(/^#/, '');

        if (visitReports.curr_page_hash != visitReports.prev_page_hash) {
            visitReports.prev_page_hash = visitReports.curr_page_hash;
        }
        visitReports.curr_page_hash = hash;

        var hash_params = {mode: ''};
        if (hash != '') {
            var hash_params_temp = hash.split('&');
            var hash_params_count = hash_params_temp.length;

            for (var i = 0; i < hash_params_count; i++) {
                var temp = hash_params_temp[i].split('=');
                hash_params[temp[0]] = decodeURIComponent(temp[1]);
            }
        }

        switch (hash_params.mode.toLowerCase()) {
            case 'addrec':
                $('.alert-success, .alert-danger').addClass('d-none');
                $('#msgFrm').removeClass('d-none');
                visitReports.openAddForm();
                break;

            case 'view':
                $('.alert-success, .alert-danger').addClass('d-none');
                $('#msgFrm').removeClass('d-none');

                if (hash_params.hasOwnProperty('recid') && hash_params.recid != '') {
                    visitReports.openRecordForViewing(hash_params.recid);
                } else {
                    location.hash = visitReports.prev_page_hash;
                }
                break;

            default:
                if(hash_params.mode==='')
                    visitReports.list_page_hash = hash;
                $('.alert-success, .alert-danger').addClass('d-none');
                $('#msgFrm').removeClass('d-none');

                var params = {
                    mode: 'getList',
                    pno: 1,
                    searchdata: "[]",
                    sortdata: JSON.stringify(visitReports.sortparams),
                    listformat: 'html'
                };

                if (hash_params.hasOwnProperty('pno')) {
                    params['pno'] = hash_params.pno;
                }

                if (hash_params.hasOwnProperty('searchdata')) {
                    params['searchdata'] = hash_params.searchdata;
                }

                if (hash_params.hasOwnProperty('sortdata')) {
                    params['sortdata'] = hash_params.sortdata;
                }

                visitReports.searchparams = JSON.parse(params['searchdata']);
                visitReports.sortparams = JSON.parse(params['sortdata']);

                if (visitReports.sortparams.length == 0) {
                    visitReports.sortparams.push(visitReports.default_sort);
                    params['sortdata'] = JSON.stringify(visitReports.sortparams);
                }

                if (visitReports.searchparams.length > 0) {
                    $.each(visitReports.searchparams, function(idx, data) {
                        switch (data.searchon) {
                            case 'customer_group_id':
                                $("#search-field_customer_group_id").val(data.searchtext);
                                break;
                            case 'customer_id':
                                $("#search-field_customer_id").val(data.searchtext);
                                break;
                            case 'department':
                                $("#search-field_department").val(data.searchtext);
                                break;
                            case 'type':
                                $("#search-field_type").val(data.searchtext);
                                break;
                            case 'meeting_title':
                                $("#search-field_meeting_title").val(data.searchtext);
                                break;
                            case 'created_by':
                                $("#search-field_created_by").val(data.searchtext);
                                break;
                            case 'visit_date_range':
                                // Convert server format dates back to display format
                                if (data.start_date) {
                                    $("#search-field_visit_date_start").val(visitReports.convertDateForDisplay(data.start_date));
                                }
                                if (data.end_date) {
                                    $("#search-field_visit_date_end").val(visitReports.convertDateForDisplay(data.end_date));
                                }
                                break;
                        }
                    });
                }

                $("#common-processing-overlay").removeClass('d-none');
                common_js_funcs.callServer({
                    cache: 'no-cache',
                    async: true,
                    dataType: 'json',
                    type: 'post',
                    url: visitReports.ajax_data_script,
                    params: params,
                    successResponseHandler: visitReports.showList,
                    successResponseHandlerParams: {self: this}
                });

                visitReports.showHidePanel('visit_reports_search_toggle');
        }
    }
};