function fbLinkLogin(redirect) {
    FB.login(function(response) {
        if (response.authResponse) {
            // Get token link
            window.location.replace(redirect);
        } else {
            console.log('Login failed');
            console.log(response);
        }
    }, {scope: 'manage_pages,pages_show_list'});
}

$(document).ready(function() {

    // Login should be done client-side
    $("#fb-authorize").click(function (e) {
        e.preventDefault();

        var url = $(this).attr("href");

        FB.getLoginStatus(function(response) {
            if (response.status === 'connected') {
                // Logged into your app and Facebook
                $(this).attr("disabled", true).addClass('work').text('Fetching tokens');
                window.location.replace(url);
            } else if (response.status === 'not authorized') {
                // Logged in but not authorized.
                fbLinkLogin(url);
            } else {
                // Not logged in
                fbLinkLogin(url);
            }
        });
    });
});