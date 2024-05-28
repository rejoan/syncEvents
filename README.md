# syncEvents
A WordPress plugin work with backend & frontend CPT.

- Backend: Get API data throug ajax and create CPT GRID
- Backend: Search eventd CPT by upcoming, date wise
- Frontend: Display "eventd" CPT based on shortcode with template selection facility
- Frontend: Get event info by shortcode
- Frontend: Get rows by random, recent event date or older first

## Short codes
- Example1: [event-random-list view=2 num=20 sort=new-first] get 20 record for eventd CPT with template 2 and recent event first
- If `sort` value is "old-first" then older event will display first, if sort not available then random
- Example2: [al-event-info] get event info
- you may make a child theme of twenty twenty four with single-eventd.html template and use example 2 shortcode

## Requirements
1. First clone the repo
```
git clone git@github.com:rejoan/syncEvents.git && cd syncEvents
```
2. Then install the plugin and activate

3. In syncData.php replace API endpoint with yours
```
https://api.prospectbox.co
```


### Authors

👤 **Rejoanul Alam**

- Github: [@githubhandle](https://github.com/rejoan)
