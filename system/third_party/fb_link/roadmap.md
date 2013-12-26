Development Roadmap
===================

Feature List
------------

1. Utilize App Token, Page Token, and User Token (if logged in) for data retrieval
2. Pull Graph API connections using short-tags (i.e. exp:fb_link:feed) and full-query-style (exp:fb_link:graph)
3. Integrate an "available connections" feature in the CP utilizing the ?metadata=1 functionality of the Graph
4. Fieldtype for displaying specific FB posts. Must be SafeCracker and Matrix compatible
5. Add a way to get a list of page fans
6. Add a caching feature
7. Paging feature (will require AJAX)
8. User login.  Built to leverage the {if loggedin} tags and allows Facebook logins to map to a certain group (enabling the {if group_id == ''} tags to be useful).
9. Like/comment tags
10. Enable Facebook Insights in the CP
11. Open Graph interaction-capability

Usage considerations
--------------------

An 'app token' is now used for API calls.  The benefit is that app tokens do not expire so things work more dependably.  The following cases are known where app tokens do not work:
1. Age restrictions are in place
2. Country/location restrictions are in place
3. The page is alcohol related

In these cases a long-lived 'user token' should be retrieved manually and entered into the EE control panel.  This token wil need to be manually refreshed every 60 days.
