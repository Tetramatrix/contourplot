<?php
/***************************************************************
* Copyright notice
*
* (c) 2013-2015 Chi Hoang (info@chihoang.de)
*  All rights reserved
*
***************************************************************/
require_once("mercator.php");
require_once("contour.php");

define("MAPWIDTH",1000);
define("MAPHEIGHT",1000);
define("ALPHA",2.3);
define("BETA",1);
define("OMEGA",10);
define("MINRAND",40);
define("MAXRAND",60);
define("STEPS",6);
define("PATH","/tmp/");
define("SHAPEFILE","PAShapeFile.txt");
define("DATAFILE","PennsylvaniaLonLatT.txt");
define("INTERVAL",0.9);
define("PHI",10);

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
$s->set=$s->repair($shape,$mean);
$m=$s->project($shape,$mean);
$filter=$s->filter($s->proj,OMEGA);

$f=new mercator(MAPWIDTH,MAPHEIGHT);
$arr=$f->loadfileZ(DATAFILE);
$arr=array_merge($arr,$filter);
//$arr=array_merge($arr,$s->set);
$m=$f->project($arr);

$plot=new Contourplot();
$res=$plot->main($f->proj,$f->mapWidth,$f->mapHeight,$s->proj,$d->proj,$mean,$d->ZMax,$d->ZMin,ALPHA);
 
$pic=new Image(PATH,$plot);
$pic->create();

?>