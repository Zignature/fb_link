function ajaxTokens() {
    var tokens = token_link();
    $.ajax({
        url: tokens
    }).done(function () {
        location.reload();
    }).fail(function (response) {
        $("#fb-authorize").attr("disabled", false).text('Get Access Tokens');
        $("#fb-authorize").after("<p>An error occurred in the login/authorization process.  Check the console log for the error.</p>");
        console.log(response);
    });
}

function login() {
    FB.login(function(response) {
        if (response.authResponse) {
            console.log('Logged in');
            // Get token link
            ajaxTokens();
        } else {
            console.log('Login failed');
            console.log(response);
        }
    }, {scope: 'manage_pages,pages_show_list'});
}

$(document).ready(function() {

    // Login should be done client-side
    $("#fb-authorize").click(function() {
        $(this).attr("disabled", true).text('Fetching tokens');
        FB.getLoginStatus(function(response) {
            if (response.status === 'connected') {
                // Logged into your app and Facebook
                ajaxTokens();
            } else if (response.status === 'not authorized') {
                // Logged in but not authorized.
                login();
            } else {
                // Not logged in
                login();
            }
        });
    });
});