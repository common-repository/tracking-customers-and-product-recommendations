jQuery(document).ready(function($){
    $('input[name="created_at[from]"]').datetimepicker({
        timepicker: false,
        format: 'Y-m-d',
        onShow:function( ct ){
            this.setOptions({
             maxDate:jQuery('input[name="created_at[to]"]').val()?jQuery('input[name="created_at[to]"]').val():false
            })
        },
    });
    $('input[name="created_at[to]"]').datetimepicker({
        timepicker: false,
        format: 'Y-m-d',
        onShow:function( ct ){
            this.setOptions({
                minDate:jQuery('input[name="created_at[from]"]').val()?jQuery('input[name="created_at[from]"]').val():false
            })
        },
    });
    $("#filter-by-customer-id").select2({
        minimumInputLength: 2,
        ajax: {
            url: nl2a_tc.ajax_url,
            dataType: 'json',
            method: "POST",
            quietMillis: 50,
            data: function (s) {
                return {
                    s: s.term,
                    action:'nl2a_tcpr_search_customer',
                    nonce: nl2a_tc.nl2a_tcpr_nonce
                };
            },
            processResults: function (data) {
                return {
                    results: $.map(data, function (item) {
                        return {
                            text: item.display_name,
                            id: item.id
                        }
                    })
                };
            }
        }
    });
    $('#tcdoaction').on('click', function(e){
        e.preventDefault();
        var self = $(this);
        var pr = self.closest('.bulkactions');
        var ac = pr.find('select[name="bulkactions"]').val();
        self.prop('disabled', true);
        if(ac == 'delete'){
            var tc = $('.tracking-customer input[name="tc[]"]:checked').map(function(){
                return $(this).val();
            }).get();
            if(tc.length > 0){
                $.ajax({
                    url: nl2a_tc.ajax_url,
                    dataType: 'json',
                    method: "POST",
                    data:{
                        action: 'nl2a_tcpr_delete_tc',
                        tc: tc,
                        nonce: nl2a_tc.nl2a_tcpr_nonce
                    },
                    success:function(data){
                        self.prop('disabled', false);
                        if(data.error == 1){
                            alert('Delete error!');
                        }else{
                            window.location.href = window.location.href;
                        }
                    }
                });
            }else{
                self.prop('disabled', false);
            }
        }else{
            self.prop('disabled', false);
        }
    });
    var tcModal = $('#tcModal');
    $('.tracking-customer .view_detail').on('click', function(e){
        e.preventDefault();
        var self = $(this);
        var id = self.attr('data-id');
        if(id){
            $.ajax({
                url: nl2a_tc.ajax_url,
                dataType: 'html',
                method: "POST",
                data:{
                    action: 'nl2a_tcpr_detail',
                    id: id,
                    nonce: nl2a_tc.nl2a_tcpr_nonce
                },
                success:function(data){
                    tcModal.find('.content').html(data);
                }
            });
        }
        tcModal.show();
    });
    tcModal.find('.close').on('click', function(e){
        e.preventDefault();
        var self = $(this);
        tcModal.hide();
        tcModal.find('.content').html('<div class="center"><div class="loader"></div></div>');
    });
    $(".nl2a-tcpr-search-page").select2({
        minimumInputLength: 2,
        ajax: {
            url: nl2a_tc.ajax_url,
            dataType: 'json',
            method: "POST",
            quietMillis: 50,
            data: function (s) {
                return {
                    s: s.term,
                    action:'nl2a_tcpr_search_page',
                    nonce: nl2a_tc.nl2a_tcpr_nonce
                };
            },
            processResults: function (data) {
                return {
                    results: $.map(data, function (item) {
                        return {
                            text: item.title,
                            id: item.id
                        }
                    })
                };
            }
        }
    });
});