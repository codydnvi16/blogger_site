<?php
// load google icons (call this once in head)
function icons_css() {
    echo '<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">';
}

// main icon function — just pass the icon name
function icon($name, $size = 20, $color = '') {
    $style = "font-size:{$size}px; vertical-align:middle; line-height:1;";
    if ($color) $style .= " color:{$color};";
    return "<span class='material-icons' style='{$style}'>{$name}</span>";
}

// ---- PREDEFINED ICONS ----
// so you dont have to remember icon names

// navigation
function icon_home()          { return icon('home'); }
function icon_search()        { return icon('search'); }
function icon_logout()        { return icon('logout'); }
function icon_settings()      { return icon('settings'); }
function icon_menu()          { return icon('menu'); }

// user
function icon_profile()       { return icon('person'); }
function icon_users()         { return icon('group'); }
function icon_follow()        { return icon('person_add'); }
function icon_following()     { return icon('how_to_reg'); }
function icon_ban()           { return icon('block'); }

// blog actions
function icon_write()         { return icon('edit'); }
function icon_publish()       { return icon('publish'); }
function icon_draft()         { return icon('save'); }
function icon_delete()        { return icon('delete'); }
function icon_view()          { return icon('visibility'); }
function icon_views()         { return icon('visibility', 16); }
function icon_article()       { return icon('article'); }

// engagement
function icon_like()          { return icon('favorite'); }
function icon_unlike()        { return icon('favorite_border'); }
function icon_comment()       { return icon('chat_bubble_outline'); }
function icon_reply()         { return icon('reply'); }
function icon_share()         { return icon('share'); }
function icon_bookmark()      { return icon('bookmark'); }
function icon_bookmark_off()  { return icon('bookmark_border'); }
function icon_report()        { return icon('flag'); }

// notifications
function icon_bell()          { return icon('notifications'); }
function icon_bell_off()      { return icon('notifications_none'); }

// admin
function icon_dashboard()     { return icon('dashboard'); }
function icon_category()      { return icon('folder'); }
function icon_approve()       { return icon('check_circle', 20, '#27ae60'); }
function icon_reject()        { return icon('cancel',       20, '#e74c3c'); }
function icon_feature()       { return icon('star'); }
function icon_unfeature()     { return icon('star_border'); }
function icon_reports()       { return icon('report'); }
function icon_messages()      { return icon('mail'); }
function icon_trending()      { return icon('trending_up'); }

// premium
function icon_premium()       { return icon('workspace_premium', 20, '#f39c12'); }

// misc
function icon_date()          { return icon('calendar_today', 16); }
function icon_image()         { return icon('image'); }
function icon_close()         { return icon('close'); }
function icon_arrow_back()    { return icon('arrow_back'); }
function icon_arrow_right()   { return icon('arrow_forward'); }
function icon_check()         { return icon('check', 20, '#27ae60'); }
function icon_info()          { return icon('info'); }
?>