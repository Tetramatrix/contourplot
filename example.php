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
define("STEPS",5);
define("PATH","/tmp/");
define("SHAPEFILE","PAShapeFile.txt");
define("DATAFILE","PennsylvaniaLonLatT.txt");

require_once("mercator.php");
require_once("contour.php");

if( !ini_get('safe_mode') )
{
    ini_set("max_execution_time","10000");
    ini_set("memory_limit","800M");
}
set_time_limit(10000);

$d=new mercator(MAPWIDTH,MAPHEIGHT);
$arr=$d->loadfileZ(DATAFILE);
$mean=$d->project($arr);

$s=new mercator(MAPWIDTH,MAPHEIGHT);
$shape=$s->loadfileZ(SHAPEFILE);
$m=$s->project($shape,$mean);
$filter=$s->filter($s->proj,OMEGA);

$f=new mercator(MAPWIDTH,MAPHEIGHT);
$arr=$f->loadfileZ(DATAFILE);
$arr=array_merge($arr,$filter);
$m=$f->project($arr);

$plot=new Contourplot();
$res=$plot->main($f->proj,$f->mapWidth,$f->mapHeight,$s->proj,$d->proj,$mean,ALPHA);
 
$pic=new Image(PATH,$plot);
$pic->create();

?>