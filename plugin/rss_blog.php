<?php
// Copyright 2003 by Won-Kyu Park <wkpark at kldp.org>
// All rights reserved. Distributable under GPL see COPYING
// a rss_blog action plugin for the MoniWiki
//
// $Id$

if (!function_exists('macro_BlogChanges'))
  if ($plugin=getPlugin('BlogChanges')) include_once("plugin/$plugin.php");

function do_rss_blog($formatter,$options) {
  global $DBInfo;

  if (!$options['date'] or !preg_match('/^\d+$/',$date)) $date=date('Ym');
  else $date=$options['date'];

  if ($options['all']) {
    # check error and set default value
    $blog_rss=new Cache_text('blogrss');

#    $blog_mtime=filemtime($DBInfo->cache_dir."/blog");
#    if ($blog_rss->exists($date'.xml') and ($blog_rss->mtime($date.'.xml') > $blog_mtime)) {
#      print $blog_rss->fetch($date.'.xml');
#      return;
#    }

    $blogs=Blog_cache::get_rc_blogs($date);
    $logs=Blog_cache::get_simple($blogs,$date);
  } else {
    $blogs=array($DBInfo->pageToKeyname($formatter->page->name));
    $logs=Blog_cache::get_summary($blogs,$date);
  }
    
  $time_current= time();

  $URL=qualifiedURL($formatter->prefix);
  $img_url=qualifiedURL($DBInfo->logo_img);

  $head=<<<HEAD
<?xml version="1.0" encoding="$DBInfo->charset"?>
<rdf:RDF xmlns:wiki="http://purl.org/rss/1.0/modules/wiki/"
         xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
         xmlns:xlink="http://www.w3.org/1999/xlink"
         xmlns:dc="http://purl.org/dc/elements/1.1/"
         xmlns="http://purl.org/rss/1.0/">\n
HEAD;
  $url=qualifiedUrl($formatter->link_url("RecentChanges"));
  $channel=<<<CHANNEL
<channel rdf:about="$URL">
  <title>$DBInfo->sitename</title>
  <link>$url</link>
  <description>
    BlogChanges at $DBInfo->sitename
  </description>
  <image rdf:resource="$img_url"/>
  <items>
  <rdf:Seq>
CHANNEL;
  $items="";

#          print('<description>'."[$data] :".$chg["action"]." ".$chg["pageName"].$comment.'</description>'."\n");
#          print('</rdf:li>'."\n");
#        }

  $ratchet_day= FALSE;
  if (!$logs) $logs=array();

  foreach ($logs as $log) {
    #print_r($log);
    list($page, $user,$date,$title,$summary)= $log;
    $url=qualifiedUrl($formatter->link_url(_urlencode($page)));

    if (!$title) continue;
    #$tag=md5("#!blog ".$line);
    $tag=md5($line);
    #$tag=_rawurlencode(normalize($title));

    $channel.="    <rdf:li rdf:resource=\"$url#$tag\"/>\n";
    $items.="     <item rdf:about=\"$url#$tag\">\n";
    $items.="     <title>$title</title>\n";
    $items.="     <link>$url#$tag</link>\n";
    if ($summary)
      $items.="     <description>$summary</description>\n";
    $items.="     <dc:date>$date</dc:date>\n";
    $items.="     <dc:contributor>\n<rdf:Description>\n"
          ."<rdf:value>$user</rdf:value>\n"
          ."</rdf:Description>\n</dc:contributor>\n";
    $items.="     </item>\n";

  }
  $url=qualifiedUrl($formatter->link_url($DBInfo->frontpage));
  $channel.= <<<FOOT
    </rdf:Seq>
  </items>
</channel>
<image rdf:about="$img_url">
<title>$DBInfo->sitename</title>
<link>$url</link>
<url>$img_url</url>
</image>
FOOT;

  $url=qualifiedUrl($formatter->link_url("FindPage"));
  $form=<<<FORM
<textinput>
<title>Search</title>
<link>$url</link>
<name>goto</name>
</textinput>
FORM;
  header("Content-Type: text/xml");
  print $head;
  print $channel;
  print $items;
  print $form;
  print "</rdf:RDF>";
}
?>
