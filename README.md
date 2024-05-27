# sync
A WordPress plugin work with backend & frontend CPT.

- Backend: Get API data throug ajax and create CPT GRID
- Backend: Search eventd CPT by upcoming, date wise
- Frontend: Display "eventd" CPT based on shortcode with templete selection facility
- Frontend: Get event info by shortcode

## Short codes
- Example1: [event-random-list view=2 num=20] get 20 record for eventd CPT with template 2
- Example2: [al-event-info] get event info from API in any custom post type
- Example3: [al-map-display] get map of address for the post
- you may make a child theme of twenty twenty four with single-eventd.html template and use example 2,3 shortcode

## Requirements
1. First clone the repo
```
git clone git@github.com:rejoan/syncEvents.git && cd syncEvents
```
2. Then install the plugin and activate

3. In wp-config.php put your map key for shortcode [al-map-display]

```
define('API_KEY_GOOGLE', 'google_map_api_key');
```
4. In syncData.php replace API endpoint with yours
```
https://api.prospectbox.co
```


### Authors

👤 **Rejoanul Alam**

- Github: [@githubhandle](https://github.com/rejoan)
