<?php
// Copyright 2003 by Won-Kyu Park <wkpark at kldp.org>
// All rights reserved. Distributable under GPL see COPYING
// Gallery plugin for the MoniWiki
//
// Usage: [[Gallery]]
//
// $Id$
// vim:et:ts=2:

function get_pagelist($formatter,$pages,$action,$curpage=1,$listcount=10,$bra="[",$cat="]",$sep="|",$prev="&#171;",$next="&#187;",$first="",$last="",$ellip="...") {

  if ($curpage >=0)
    if ($curpage > $pages)
      $curpage=$pages;
  if ($curpage <= 0)
    $curpage=1;

  $startpage=intval(($curpage-1) / $listcount)*$listcount +1;

  $pnut="";
  if ($startpage > 1) {
    $prevref=$startpage-1;
    if (!$first) {
      $prev_l=$formatter->link_tag('',$action.$prevref,$prev);
      $prev_1=$formatter->link_tag('',$action."1","1");
      $pnut="$prev_l".$bra.$prev_1.$cat.$ellip.$bar;
    }
  } else {
    $pnut=$prev.$bra."";
  }

  for ($i=$startpage;$i < ($startpage + $listcount) && $i <=$pages; $i++) {
    if ($i != $startpage)
      $pnut.=$sep;
    if ($i != $curpage) {
      $link=$formatter->link_tag('',$action.$i,$i);
      $pnut.=$link;
    } else
      $pnut.="<b>$i</b>";
  }

  if ($i <= $pages) {
    if (!$last) {
      $next_l=$formatter->link_tag('',$action.$pages,$pages);
      $next_i=$formatter->link_tag('',$action.$i,$next);

      $pnut.=$cat.$ellip.$bra.$next_l.$cat.$next_i;
    }
  } else {
    $pnut.="".$cat.$next;
  }
  return $pnut;
}

function macro_Gallery($formatter,$value,$options='') {
  global $DBInfo;

  # add some actions at the bottom of the page
  if (!$value and !in_array('UploadFile',$formatter->actions)) {
    $formatter->actions[]='UploadFile';
    $formatter->actions[]='UploadedFiles';
  }

  $default_width=$DBInfo->gallery_img_width ? $DBInfo->gallery_img_width:600;

  if ($value) {
    $key=$DBInfo->pageToKeyname($value);
    if ($key != $value)
      $prefix=$formatter->link_url($value,"?action=download&amp;value=");
    $dir=$DBInfo->upload_dir."/$key";
  } else {
    $value=$formatter->page->name;
    $key=$DBInfo->pageToKeyname($formatter->page->name);
    if ($key != $formatter->page->name)
       $prefix=$formatter->link_url($formatter->page->name,"?action=download&amp;value=");
    $dir=$DBInfo->upload_dir."/$key";
  }

  if (!file_exists($dir)) {
    umask(000);
    mkdir($dir,0777);
  }

  $upfiles=array();
  $comments=array();
  if (file_exists($dir."/list.txt")) {
    $cache=file($dir."/list.txt");
    foreach ($cache as $line) {
      list($name,$mtime,$comment)=explode("\t",rtrim($line),3);
      $upfiles[$name]=$mtime;
      $comments[$name]=$comment;
    }
  }

  if ($options['value'])
    $file=urldecode($options['value']);

  if ($file and $upfiles[$file] and $options['comments']) {
    // admin: edit all comments
    $comment=stripslashes($options['comments']);
    $comment=str_replace("<","&lt;",$comment);
    $comment=str_replace("\r","",$comment);
    $comment=preg_replace("/\n----\n/","\t",$comment);
    $comment=str_replace("\n","\\n",$comment);
    $comments[$file]=$comment;
    $update=1;
  } else if ($file and $upfiles[$file] and $options['comment']) {
    // add new comment
    if ($options['id']=='Anonymous') $name=$_SERVER['REMOTE_ADDR'];
    else $name=$options['id'];
    if ($options['name']) $name=$options['name'];
    $date=date("(Y-m-d H:i:s) ");

    $comment=stripslashes($options['comment']);
    $comment=str_replace("\r","",$comment);
    $comment=str_replace("\n","\\n",$comment);
    $comment=str_replace("\t"," ",$comment);
    $comment=str_replace("<","&lt;",$comment);
    $comment.=" -- $name $date";
    $comments[$file]=$comment."\t".$comments[$file];
    $update=1;
  } else if ($file and $upfiles[$file]) {
    // show comments of the selected item
    $mtime=$upfiles[$file];
    $comment=$comments[$file];
    $upfiles=array();
    $comments=array();
    $upfiles[$file]=$mtime;
    $comments[$file]=$comment;
    $selected=1;
  }

  $mtime=file_exists($dir."/list.txt") ? filemtime($dir."/list.txt"):0;
  if ((filemtime($dir) > $mtime) or $update) {
    unset($upfiles);

    $handle= opendir($dir);
    $cache='';
    $cr='';
    while ($file= readdir($handle)) {
      if ($file[0]=='.' or $file=='list.txt' or is_dir($dir."/$file")) continue;
      $mtime=filemtime($dir."/".$file);
      $cache.=$cr.$file."\t".$mtime;
      $upfiles[$file]= $mtime;
      if ($comments[$file] != '') $cache.="\t".$comments[$file];
      $cr="\n";
    }
    closedir($handle);
    $fp=@fopen($dir."/list.txt",'w');
    if ($fp) {
      fwrite($fp,$cache);
      fclose($fp);
    }
  }

  if (!$upfiles) return "<h3>No files uploaded</h3>";
  asort($upfiles);

  $out.="<table border='0' cellpadding='2'>\n<tr>\n";
  $idx=1;

  if (!$prefix) $prefix=$DBInfo->url_prefix."/".$dir."/";

  $col=3;
  $width=$selected ? $default_width:150;
  $perpage=$col*4;

  $pages= intval(sizeof($upfiles) / $perpage);
  if (sizeof($upfiles) % $perpage)
    $pages++;

  if ($options['p'] > 1) {
    $slice_index=$perpage*(intval($options['p'] - 1));
    $upfiles=array_slice($upfiles,$slice_index);
  }

  if ($pages > 1)
    $pnut=get_pagelist($formatter,$pages,"?action=gallery&p=",$options['p'],$perpage);

  if (!file_exists($dir."/thumbnails")) mkdir($dir."/thumbnails",0777);

  while (list($file,$mtime) = each ($upfiles)) {
    $size=filesize($dir."/".$file);
    $id=rawurlencode($file);
    $linksrc=$prefix.$id;
    $link=$selected ? $prefix.$id:$formatter->link_url($formatter->page->urlname,"?action=gallery&amp;value=$id");
    $date=date("Y-m-d",$mtime);
    if (preg_match("/\.(jpg|jpeg|gif|png)$/i",$file)) {
      if ($DBInfo->use_covert_thumbs and !file_exists($dir."/thumbnails/".$file)) {
        system("convert -scale ".$width." ".$dir."/".$file." ".$dir."/thumbnails/".$file);
      }
      if (!$selected and file_exists($dir."/thumbnails/".$file)) {
        $thumb=$prefix."thumbnails/".rawurlencode($file);
        $object="<img src='$thumb' alt='$file' />";
      } else {
        $object="<img src='$linksrc' width='$width' alt='$file' />";
      }
    }
    else
      $object=$file;

    $unit=array('Bytes','KB','MB','GB','TB');
    $i=0;
    for (;$i<4;$i++) {
      if ($size <= 1024) {
        $size= round($size,2).' '.$unit[$i];
        break;
      }
      $size=$size/1024;
    }
#    $size=round($size,2).' '.$unit[$i];

    $comment='';
    $comment_btn=_("add comment");
    if ($comments[$file] != '' and $options['value']) {
      $comment=$comments[$file];
      $comment=str_replace("\\n","\n",$comment);
      $comment=str_replace("\t","\n----\n",$comment);
      $options['comments']=$comment;
      $comment=str_replace("\n","<br/>\n",$comment);
      $comment="<br/>".$comment;
    } else if ($comments[$file] != '') {
      $comment_btn=_("show comments");
      list($comment,$dum)=explode("\t",$comments[$file],2);
      $comment=str_replace("\\n","<br/>\n",$comment);
      $comment=str_replace("\t","<br/>\n----<br/>\n",$comment);
      $comment="<br/>".$comment;
    }
    $out.="<td align='center' valign='top' class='wiki'><a href='$link'>$object</a><br />".
          "$date ($size) ";
    if (!$options['value'])
      $out.='['.$formatter->link_tag($formatter->page->urlname,"?action=gallery&amp;value=$id",$comment_btn)."]<br />\n";
    if ($comment) $out.="<div align='left' class='gallery_comments'>$comment</div>";
    $out.="</td>\n";
    if ($idx % $col == 0) $out.="</tr>\n<tr>\n";
    $idx++;
    if ($idx > $perpage) break;
  }
  $idx--;
  $out.="</tr></table>\n";

  return $pnut.$out.$pnut;
}

function do_gallery($formatter,$options='') {
  global $DBInfo;
  $cols = preg_match('/MSIE/', $HTTP_USER_AGENT) ? $COLS_MSIE : $COLS_OTHER;
                                                                                
  $rows=$options['rows'] > 5 ? $options['rows']: 4;
  $cols=$options['cols'] > 60 ? $options['cols']: $cols;

  $formatter->send_header("",$options);

  if ($options['admin'] and $options['comments'] and !$DBInfo->security->is_valid_password($options['passwd'],$options)) {
    $title= sprintf('Invalid password !');
    $formatter->send_title($title);
    $formatter->send_footer();
    return;
  }

  $ret=macro_Gallery($formatter,'',&$options);

  if ($options['passwd'] and $options['comments']) {
    $options['msg']=sprintf(_("Go back or return to %s"),$formatter->link_tag($formatter->page->urlname,"",$options['page']));
    $options['title']=_("Comments are edited");
  } else if ($options['comment']) {
    $options['msg']=sprintf(_("Go back or return to %s"),$formatter->link_tag($formatter->page->urlname,"",$options['page']));
    $options['title']=_("Comments is added");
  }

  if (!$options['value']) {
    $formatter->send_title("","",$options);
    print $ret;
  } else
  if ($options['comment'] or ($options['comments'] and $options['passwd'])) {
    $formatter->send_title("","",$options);
  } else
  if ($options['comments'] and $options['admin'] and !$options['passwd']) {
    // admin form
    $rows+=5;
    $formatter->send_title("","",$options);
    print $ret;
    $url=$formatter->link_url($formatter->page->urlname);
    $form = "<form method='post' action='$url'>\n";
    $form.= <<<FORM
<textarea class="wiki" id="content" wrap="virtual" name="comments"
 rows="$rows" cols="$cols" class="wiki">
FORM;
    $form.=$options['comments'];
    $form.='</textarea><br />';
    $form.= <<<FORM2
<input type="hidden" name="action" value="gallery" />
<input type="hidden" name="value" value="$options[value]" />
password: <input type='password' name='passwd' />
<input type="submit" value="Save" />&nbsp;
<input type="reset" value="Reset" />&nbsp;
</form>
FORM2;
    print $form;
  } else if (!$options['comment']) {
    // add comment form
    $formatter->send_title("","",$options);
    print $ret;
    $url=$formatter->link_url($formatter->page->urlname);
                                                                                
    $form = "<form method='post' action='$url'>\n";
    $form.= "<input name='admin' type='submit' value='Admin' /><br />\n";
    $form.= "<b>Name or Email</b>: <input name='name' size='30' maxlength='30' style='width:200' /><br />\n";
    $form.= <<<FORM
<textarea class="wiki" id="content" wrap="virtual" name="comment"
 rows="$rows" cols="$cols" class="wiki"></textarea><br />
FORM;
    $form.= <<<FORM2
<input type="hidden" name="action" value="gallery" />
<input type="hidden" name="value" value="$options[value]" />
<input type="submit" value="Save" />&nbsp;
<input type="reset" value="Reset" />&nbsp;
</form>
FORM2;
    print $form;
  }

  $formatter->send_footer("",$options);
  return;
}

?>
