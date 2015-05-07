<?php
/***************************************************************
* Copyright notice
*
* (c) 2013-2015 Chi Hoang (info@chihoang.de)
*  All rights reserved
*
***************************************************************/
define("MAPWIDTH",1000);
define("MAPHEIGHT",1000);
define("ALPHA",2.3);
define("OMEGA",1);
define("MINRAND",40);
define("MAXRAND",60);
define("STEPS",10);

require_once("mercator.php");
require_once("contour.php");

if( !ini_get('safe_mode') )
{
    ini_set("max_execution_time","10000");
    ini_set("memory_limit","800M");
}
set_time_limit(10000);

$s=new mercator(MAPWIDTH,MAPHEIGHT);
$shape=$s->loadfileZ("PAShapeFile.txt");
$mean=$s->project($shape);
$filter=$s->filter($s->proj,OMEGA);

$d=new mercator(MAPWIDTH,MAPHEIGHT);
$arr=$d->loadfileZ("PennsylvaniaLonLatT.txt");
$mean=$d->project($arr);

$f=new mercator(MAPWIDTH,MAPHEIGHT);
$arr=$f->loadfileZ("PennsylvaniaLonLatT.txt");
$arr=array_merge($arr,$filter);
$mean=$f->project($arr);

$plot=new Contourplot();
$res=$plot->main($f->proj,$f->mapWidth,$f->mapHeight,$s->proj,$d->proj,$mean,ALPHA);
 
$pic=new Image("/tmp/",$plot);
$pic->create();


?>