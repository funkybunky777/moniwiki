<?php
// Copyright 2003 by Won-Kyu Park <wkpark at kldp.org>
// All rights reserved. Distributable under GPL see COPYING
// a bookmark action plugin for the MoniWiki
// vim:et:ts=2:
//
// $Id$

function do_bookmark($formatter,$options) {
  global $DBInfo;
  global $_COOKIE;

  $user=new User(); # get cookie
  if ($user->id != 'Anonymous') {
    $udb=new UserDB($DBInfo);
    $udb->checkUser($user);
  }

  if (!$options['time']) {
     $bookmark=time();
  } else {
     $bookmark=$options['time'];
  }
  if (0 === strcmp($bookmark , (int)$bookmark)) {
    if ($user->id == "Anonymous") {
      setcookie("MONI_BOOKMARK",$bookmark,time()+60*60*24*30,get_scriptname());
      # set the fake cookie
      $_COOKIE['MONI_BOOKMARK']=$bookmark;
      $options['msg'] = 'Bookmark Changed';
    } else {
      $user->info['bookmark']=$bookmark;
      $udb->saveUser($user);
      $options['msg'] = 'Bookmark Changed';
    }
  } else
    $options['msg']="Invalid bookmark!";
  $formatter->send_header("",$options);
  $formatter->send_title($title,"",$options);
  $formatter->send_page();
  $formatter->send_footer("",$options);
}

?>
