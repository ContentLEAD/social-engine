
function sego(network,sfid){
    $('#info-chart').slideToggle(function(){
        $.get('/index.php/sego/twitter/'+sfid,function(ret){
            ret = JSON.parse(ret);
            if(!ret.auth){
                $('#info-message').html('<div class="col-md-12 text-center" style="margin-top:100px;">'+ret.signin+'</div>');
            }else{
                p = post;
                p.load(network,sfid);
                $('#info-message').html(p.interface());
            }
            $('#info-message').slideToggle();
        });
    });
}




post = {
    load:function(network,sfid){
        this.network = network;
        this.sfid = sfid;
    },
    interface:function(){
        if(this.network == 'TWITTER'){
            var h = '<div class="col-md-12" style="margin-top:10px;"><strong>TWEET</strong>';
            h += '<textarea id="sego_tweet" class="form-control"></textarea>';
            h += '<button class="btn btn-primary" onclick="p.tweet()">Tweet</button>';
            h += '</div>';
            return h;
        }
    },
    tweet:function(){
        this.post = $('#sego_tweet').val();
        $('#info-message').html('<div class="col-md-12 text-center"><img src="/images/loader.gif"/></div>');
        var data = this.build_post();
        $.post('/index.php/sego/tweet/',data).done(function(ret){
            $('#info-message').html(ret);
        });
        
    },
    build_post:function(){
        return 'network='+p.network+'&sfid='+p.sfid+'&post='+p.post;
    }
}