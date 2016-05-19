$(document).ready(function(){
    
    
    //---------------------
    // SORT THE CLIENT ORDER
    //--------------------
    
    
    var client_list = $('.client-list');
    client_list_children = client_list.children('.new-client-full-div');
    client_list_children.sort(function(a,b){
        var an = a.getAttribute('data-balance'),
		bn = parseInt(b.getAttribute('data-balance'));
        if(an > bn) {
		  return -1;
        }
        if(an < bn) {
            return 1;
        }
        return 0;
    });
    client_list_children.detach().appendTo(client_list);
    
    
    //-----------------------
    // GRAB THAT HASH  
    //-----------------------
    
    
    var page_hash = window.location.hash.substring(1,window.location.hash.length);
    if(page_hash.length >0) load_hash(page_hash);
    //ADD CLASS TO CLIENT
    $('[data-id="'+page_hash+'"]').addClass('active');
    //CLICKABLE ACTIONS
    
    
    //--------------------
    // LOAD HASH FUNCTION
    //--------------------
    
    
    function load_hash(h){
        $('[data-order="'+h+'"]').slideToggle();
    }
    
    
    //--------------------
    // FANCY BOX
    //--------------------
    
    
    $('.fb').click(function(){
        //GET PAGE HASH
        var page_hash = window.location.hash.substring(1,window.location.hash.length);
        //GET URL
        url = $(this).attr('data-fb');

        fb(url+page_hash);
    });
    
    
    //--------
    //USER OPTIONS
    //--------
    
    
    $('.user').click(function(){
        $('.user-options').slideToggle();
    });
    
    //---
    //TOOLTIP
    //---
    
//    $('.new-item-counter').click(function(){
//        if($('.multi').is(':visible')){
//            return;   
//        }
//        //GET THE ITEM ID
//        var item_id = $(this).attr('data-item-count');        
//        //LAYOVER DIALOG
//        $(this).append('<div class="multi" id="multi-'+item_id+'"></div>');
//        //SLIDE THAT BABY UP
//        $('#multi-'+item_id).toggle("slide",{direction:"down"});
//    });
    
    //----
    //FANCY BOX    
    //----
    
    function fb(url){
        $.fancybox({
            type:'iframe',
            width:'90%',
            beforeLoad: function () {
                this.href = '/index.php/'+url;
            }
        });
    }
    
    
    //------
    //SEARCH
    //-------
    
    
    jQuery.expr[':'].Contains = function(a, i, m) {
          return jQuery(a).text().toUpperCase()
          .indexOf(m[3].toUpperCase()) >= 0;
        };
        jQuery.expr[':'].contains = function(a, i, m) {
          return jQuery(a).text().toUpperCase()
          .indexOf(m[3].toUpperCase()) >= 0;
        };
    $("#search").keyup(function(){
        var si = $("#search").val();
        $(".new-client").hide();
        if(si == ""){
           $(".new-client").show()
        }else{
            $(".new-client .col-md-7:contains("+si+")").parent().show();
        }
    });
    
    //---
    //CLIENT SWITCHER
    //---
    
    
    $('.new-client').click(function(){
        
        var order = $(this);
        //GET HASH
        window.location.hash = $(order).attr('data-id');
        //ACTIVATE
        $('.active').removeClass('active');
        $(order).addClass('active');
        //CLOSED OPEN INFO IF ITEM INFO IS OPEN
        $('.new-item-info:visible').slideToggle();
        //IF SAME ELEMENT IS CLICKED
        if($('[data-order="'+$(order).attr('data-id')+'"]').is(':visible')){
            $('[data-order="'+$(order).attr('data-id')+'"]').slideUp();
        }else if($('.new-client-order').is(':visible')){
            $('.new-client-order:visible').slideUp(function(){
                $('[data-order="'+$(order).attr('data-id')+'"]').slideDown();
            });
        }else{
            $('[data-order="'+$(order).attr('data-id')+'"]').slideDown();
        }
    
    });
    
    
    //---
    //NEW ITEM LOADER
    //----
    
    
    $('.new-item-action').click(function(){
        var x = item;
        x.load($(this).parent());
        //CLEAR BOARD
        $('#info_'+x.client_id).html('<div class="col-md-12 text-center"><img src="/images/loader.gif"/></div>');
        //
        $('[data-order="'+x.client_id+'"]').slideToggle();
        $('[data-item-info="'+x.client_id+'"]').slideToggle();
        $('[data-item-info="'+x.client_id+'"]').attr('data-item-id',x.id);
        //LOAD INFORMATION
        x.get_more(function(){
            $('#info_'+x.client_id).html(x.info_html);
            load_chart();
        });
    });
    $('.new-nyl').click(function(){
        var x = item;
        x.load($(this).parent());
        x.makelive();
    });
    $('.close-info').click(function(){
        $(this).parent().parent().parent().slideToggle();
        var client_id = $(this).parent().parent().parent().attr('data-item-info');
        $('[data-order="'+client_id+'"]').slideToggle();
    });
    $('.new-commit').click(function(){
        var x = item;
        x.load($(this).parent());
        x.commit();
    });
    $('.new-remove').click(function(){
        var x = item;
        x.load($(this).parent());
        x.uncommit();
    });

    
    //------------
    //info ENGINE
    //-------------
    
    
    $('.client-info').click(function(){
        var client_id = $(this).attr('data-info-client-id');
        var i = info;
        //CLEAR BOARD
        $('#info_'+client_id).html('<div class="col-md-12 text-center"><img src="/images/loader.gif"/></div>');
        //
        $('[data-order="'+client_id+'"]').slideToggle();
        $('[data-item-info="'+client_id+'"]').slideToggle();
        i.load(client_id,function(){
            $('#info_'+client_id).html(i.info);
            load_info();
        });
    });
    
    
    //------------
    //TASK ENGINE
    //-------------
    
    
    $('.task').click(function(){
        var t = task;
        t.load(this);
        t.assign();
    });
    
    $('.new-task').click(function(){
        var client_id=$(this).attr('data-task-client-id');
        //CLEAR BOARD
        $('#info_'+client_id).html('<div class="col-md-12 text-center"><img src="/images/loader.gif"/></div>');
        //
        $('[data-order="'+client_id+'"]').slideToggle();
        $('[data-item-info="'+client_id+'"]').slideToggle();
        //CREATE NOTE
        $.get('/index.php/ajax/create_task/?client_id='+client_id,function(ret){
            $('#info_'+client_id).html(ret);
            load_date_picker();
        });
    });
    
    $('.close-task').click(function(){
        $('.task-reader').slideUp();
    })
    
    $('.task-closer').click(function(){
        var id = $(this).attr('data-task-id');
        $.get('/index.php/ajax/close_task/'+id,function(ret){
            $('.task-reader').slideUp();
            $('#task_'+id).fadeOut();
        });
    })
    
    
    //-----
    //toggle engine
    //-----
    
    
    $('.toggler').click(function(){
        $('[data-toggle="'+$(this).attr('id')+'"]').slideToggle();
    });
    
    //----
    //overdue navigation
    //----
    $('.overdue').click(function(){
        client_id = $(this).attr('data-overdue');
        var order = $('[data-id="'+client_id+'"]');
        //GET HASH
        window.location.hash = $(order).attr('data-id');
        //ACTIVATE
        $('.active').removeClass('active');
        $(order).addClass('active');
        //CLOSED OPEN INFO IF ITEM INFO IS OPEN
        $('.new-item-info:visible').slideToggle();
        //IF SAME ELEMENT IS CLICKED
        if($('[data-order="'+$(order).attr('data-id')+'"]').is(':visible')){
            $('[data-order="'+$(order).attr('data-id')+'"]').slideUp();
        }else if($('.new-client-order').is(':visible')){
            $('.new-client-order:visible').slideUp(function(){
                $('[data-order="'+$(order).attr('data-id')+'"]').slideDown();
            });
        }else{
            $('[data-order="'+$(order).attr('data-id')+'"]').slideDown();
        }
    });
});


function load_info(){
    $('.info-display').dblclick(function(){
        $(this).hide();
        $('.info-edit').show();
    });
}

function save_info(client_id){
    var data = 'client_id='+client_id+'&info='+$('#edit-info-textarea').val();
    $.post('/index.php/ajax/save_info/',data).done(function(ret){
        $('.info-display').html($('#edit-info-textarea').val());
        $('.info-edit').hide();
        $('.info-display').show();
    });
}

function load_date_picker(){
    $( "#datepicker" ).datepicker();    
}

function save_task(client_id){
    //VALIDATE
    if($('[name="subject"]').val() == '') {
        $('[name="subject"]').effect('shake');
        return;
    }
    if($('[name="note"]').val() == '') {
        $('[name="note"]').effect('shake');
        return;
    }
    if($('[name="due_date"]').val() == '') {
        $('[name="note"]').effect('shake');
        return;
    }
    var s = $('.form-control').serialize();
    //SAVE VIA AJAX
    $.post('/index.php/ajax/save_task',s).done(function(ret){
        $('[data-order="'+client_id+'"]').slideToggle();
        $('[data-item-info="'+client_id+'"]').slideToggle();
    });
}

//----
//INFO CLASS
//----

var info = {
    load:function(id,callback){
        $.get('/index.php/ajax/get_info/'+id,function(ret){
            info.info = ret;
            callback();
        });
    }

}


//-----
//TASK ENGINE CLASS
//-----
var task = {
    load:function(task){
        var data = JSON.parse($(task).attr('data-task'));
        this.subject = data.subject;
        this.id = data.id;
        this.note = data.note;
        this.due_date = $(task).attr('data-due');
        this.created_date = $(task).attr('data-created');
        this.client_name = data.client_name;
        this.get_created(data.created_by);
    },
    get_created:function(id){
        $.get('/index.php/ajax/return_field/?id='+id+'&table=user&field=first_name',function(ret){
            task.created_by = ret;
        });
    },
    assign:function(){
        if($('.task-reader').is(':visible')){
            $('.task-reader').slideUp(function(){
                $('#task-note').html(task.note);
                $('#task-subject').html(task.subject);
                $('#task-client-name').html(task.client_name);
                $('#task-due-date').html(task.due_date);
                $('#task-created-by').html(task.created_by);
                $('.task-reader').slideDown();
                $('.task-closer').attr('data-task-id',task.id);
            });
        }else{
            $('#task-note').html(this.note);
            $('#task-subject').html(this.subject);
            $('#task-due-date').html(this.due_date);
            $('#task-created-by').html(this.created_by);
            $('.task-reader').slideDown();
            $('.task-closer').attr('data-task-id',task.id);
        }
    },
    close:function(){
    
    }
}

once = true;

//----------------
//ITEM CLASS
//----------------


var item = {
    load:function(item){
        this.fixed = $(item).hasClass('fixed');        
        this.id = $(item).attr('id');
        this.current_balance = parseInt($('[data-item-count="'+this.id+'"]').html());
        this.client_id = $(item).attr('data-client-id');
        this.item = item;
        this.time_balance =parseInt($('[data-balance-counter="'+this.client_id+'"]').html());
        this.per_int = parseInt($(item).attr('data-per-int'));
        this.delivered = parseInt($(item).attr('data-delivered'));
        this.owed_to_date = parseInt($(item).attr('data-owed-to-date'));
        this.holder = $('[data-holder="'+this.id+'"]');
        this.info_html;
        this.total_balance = this.current_balance+this.delivered;
        this.progress_bar = progress_bar.load(this.client_id);        
        return this;
    },
    get_more:function(callback){
        $.post('/index.php/ajax/item_info','&total_balance='+this.total_balance+'&owed_to_date='+this.owed_to_date+'&delivered='+this.delivered+'&item_id='+this.id).done(function(ms){
            item.info_html = ms;
            callback();
        });
    },
    commit:function(){
        //CHECK IF CAN BE COMMITED
        if(!this.commit_check()){
            $('#'+this.id).effect('shake');
            return;    
        }
        //SLIDE IT TO PREVENT MULTIPLE HITS
        $(this.item).slideToggle();
        $(this.holder).slideToggle();
        //HANDLE THE PROGRESS BAR
        this.progress_bar.animate(this.per_int);
        this.time_balance = this.time_balance-this.per_int;
        //SEND AJAX REQUEST
        $.post('/index.php/ajax/commit_activity_2','dir=plus&item_id='+this.id).done(function(msg){
            var m = JSON.parse(msg);
            if(!m.success){
                console.log(m);
            }
        });
        //UPDATE THE BALANCE
        this.current_balance--;
        this.delivered++;
        //update the item
        this.update_item();
        //CHECK THE CLASSES
        this.check_class();
        //SLIDE IT BACK DOWN
        $(this.item).slideToggle();
        //GET THE INCREMENT HTML
    },
    uncommit:function(){
        //CEHCK
        if(!this.commit_check()){
            $('#'+this.id).effect('shake');
            return;    
        }
        //SLIDE IT TO PREVENT MULTIPLE HITS
        $(this.item).slideToggle();
        //HANDLE THE PROGRESS BAR
        this.progress_bar.animate(-this.per_int);
        this.time_balance = this.time_balance+this.per_int;
        //SEND AJAX REQUEST
        $.post('/index.php/ajax/commit_activity_2','dir=minus&item_id='+this.id).done(function(msg){
            var m = JSON.parse(msg);
            if(!m.success){
                console.log(m);
            }
        });
        //UPDATE THE BALANCE
        this.current_balance++;
        this.delivered--;
        //update the item
        this.update_item();
        //CHECK THE CLASSES
        this.check_class();
        //SLIDE IT BACK DOWN
        $(this.item).slideToggle();
        //GET THE INCREMENT HTML
    },
    update_item:function(){
        //INC THE COUNT HTML
        $('[data-item-count="'+this.id+'"]').html(this.current_balance);
        //UPDATE THE BALANCE
        $('[data-balance-counter="'+this.client_id+'"]').html(this.time_balance);
        $('#'+this.id).attr('data-delivered',this.delivered);
    },
    commit_check:function(){
        if(this.current_balance == 0){
            return false;
        }
        return true;
    },
    check_class:function(){
        if(this.current_balance == 0){
            $('.'+this.id).removeClass('over under').addClass('done');
            return;
        }
        if(this.delivered >= this.owed_to_date){
            $('.'+this.id).removeClass('over under done').addClass('over');
            return;
        }
        if(this.delivered < this.owed_to_date){
            $('.'+this.id).removeClass('over under done').addClass('under');
            return;
        }
    },
    makelive:function(){
        var data = 'item_id='+this.id+'&start_amount='+$('[data-item-count="'+this.id+'"]').attr('data-start-amount');
        if(this.fixed){
            data +='&fixed=true';
        }
        swal({
            title:'Make Live?',
            text:'Social Engine will set this item live and prorate delivery on rolling items.',
            showCancelButton: true,
            closeOnConfirm: false,
          },
        function(){
            if(!once){
                console.log('prevented');
                return false;
            }
            once = false;
            $('.confirm').html('<i class="fa fa-cog fa-spin"></i>');
            $.post('/index.php/ajax/make_live',data).done(function(msg){
                console.log(msg);
                swal({title:"Alright", text:"The item is now live!", type:"success"},function(){
                    window.location.reload();
                });
            });    
        });
    }
    
}

//------------------
// PROGRESSBAR CLASS
//------------------

var progress_bar = {
    //LOAD
    load:function(id){
        this.selector = $('#progress_bar_'+id);
        this.due = parseInt($('#progress_bar_'+id).attr('data-total-due'));
        this.done = parseInt($('#progress_bar_'+id).attr('data-total-done'));
        this.calc();
        return this;
    },
    //ANIAMTE FUNCTION
    animate:function(per_int){
        this.done = this.done+per_int;
        this.calc();
        $(this.selector).css('width',this.per+'%');
        $(this.selector).html(this.per+'%');
        this.update();
    },
    calc:function(){
        this.per = Math.round((this.done/parseInt(this.due))*100*100)/100;
        console.log(this);
    },
    update:function(){
        $(this.selector).attr('data-total-done',this.done);
        $(this.selector).attr('aria-valuenow',this.per);
    }
    
}

//------------------
//CHANGE AMOUNT
//------------------
function change_amount(id){
    //GRAB THE CURRENT AMOUNT.
    var current_amount = $('#current_amount:visible').html();
    
    var x = item;
    x.load($('#'+id));
    console.log(x);
    //SWEET ALERT
    swal({
        title: "Change Amount",
        text: "Please enter the new amount for this item",
        type: "input",
        showCancelButton: true,
        closeOnConfirm: false,
        animation: "slide-from-top",
        inputPlaceholder: "Write something"
    },
    function(inputValue){
        if (inputValue === false) return false;

        if (inputValue === "") {
            swal.showInputError("You need to write something!");
            return false
        }
        if (parseInt(inputValue) - parseInt(inputValue) != 0) {
            swal.showInputError("Needs to be a number!");
            return false
        }
        if (parseInt(inputValue) < parseInt(x.delivered) ) {
            swal.showInputError("Needs to be more than what has been delivered this month.");
            return false
        }
        var data = 'item_id='+id+'&start_amount='+parseInt(inputValue);
        $.post('/index.php/ajax/change_amount',data).done(function(m){
            console.log(m);
            swal({title:"Awesome!", text:"We've Changed this month's amount to: " + inputValue, type:"success"},function(){
                //CONFIM AND REFRESH
                window.location.reload();
            });
        });
    });   
}


//-------------------
//CLOSE ITEM
//-------------------
function close_item(id){
    swal({
        title: "Are you sure?",
        text: "Closing this Item cannot be undone",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#DD6B55",
        confirmButtonText: "Yes, close it!",
        cancelButtonText: "No, keep it live",
        closeOnConfirm: false,
        closeOnCancel: false
    },
    function(isConfirm){
        if (isConfirm) {
            $.get('/index.php/ajax/close_item/'+id,function(ret){
                console.log(ret);
                swal("Closed!", "Your Item has been closed.", "success");
            });
        } else {
            swal("Cancelled", "Your Item has NOT been closed", "error");
        }
    });          

}


function trash_note(id){ 
    $.get('/index.php/ajax/trash_note/'+id,function(){
        $('[data-sticky-id="'+id+'"]').fadeOut(); 
    });
}
