<?php
// Copyright 2003 by Won-Kyu Park <wkpark at kldp.org>
// All rights reserved. Distributable under GPL see COPYING
// a Login plugin for the MoniWiki
//
// Usage: [[MiniLogin]]
//
// $Id$

function macro_minilogin($formatter,$value="",$options="") {
  global $DBInfo;

  $urlpage=$formatter->link_url($formatter->page->urlname);

  $user=new User(); # get from COOKIE VARS
  if ($user->id != 'Anonymous') {
    $udb=new UserDB($DBInfo);
    $udb->checkUser($user);
  }

  if ($user->id == 'Anonymous') {
    $login=_("Login or Join");
    $url=$formatter->link_tag('UserPreferences',"",$login);
    return $url;
  }

  $url=$formatter->link_tag('UserPreferences',"?action=userform&amp;logout=1",
       _("UserPreferences"));
  $logout=$formatter->link_tag('UserPreferences',"?action=userform&amp;logout=1",_("Logout"));
  return sprintf(_("%s or %s"),$url,$logout);
}

// vim:et:ts=2:
?>
