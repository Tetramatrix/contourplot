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
define("ALPHA",2.5);
define("OMEGA",2);

require_once("mercator.php");
require_once("contour.php");

if( !ini_get('safe_mode') )
{
    ini_set("max_execution_time","10000");
    ini_set("memory_limit","800M");
}
set_time_limit(10000);

$s=new mercator(MAPWIDTH,MAPHEIGHT);
$shape=$s->loadfile("PAShapeFile.txt");
$res=$s->project($shape);
$filter=$s->filter($s->proj,OMEGA);

$d=new mercator(MAPWIDTH,MAPHEIGHT);
$arr=$d->loadfileZ("PennsylvaniaLonLatT.txt");
$res=$d->project($arr);

$f=new mercator(MAPWIDTH,MAPHEIGHT);
$arr=$f->loadfileZ("PennsylvaniaLonLatT.txt");
$arr=array_merge($arr,$filter);
$res=$f->project($arr);

$plot=new Contourplot();
$res=$plot->main($res,$f->mapWidth,$f->mapHeight,$s->proj,$d->proj,ALPHA);
 
$pic=new Image("/tmp/",$plot);
$pic->create();


?>