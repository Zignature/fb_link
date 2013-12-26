$(document).ready(function() {

    // Login should be done client-side
    $('#fb-token').click(function() {
        var app_id = $('[name=app_id]').val();
        var app_secret = $('[name=app_secret]').val();

        $.get('https://graph.facebook.com/oauth/access_token?client_id=' + app_id + '&client_secret=' + app_secret + '&grant_type=client_credentials', function(response) {
            var res = response.split('=');
            var data = {};
            data[res[0]] = [res[1]];
            $('[name=access_token]').val(res[1]);
        });
    });
});