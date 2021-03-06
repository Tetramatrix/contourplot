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
//Alphashape 
define("ALPHA",12.0);
//shapefile filter
define("OMEGA",0);
//Experimental shapefile z-value
//define("MINRAND",40);
//define("MAXRAND",60);
//contour plot color steps
define("STEPS",6);
//contour plot interval
define("INTERVAL",0.9);
define("SHAPEFILE","PAShapeFile.txt");
define("DATAFILE","PennsylvaniaLonLatT.txt");
//define("SHAPEFILE","txshape.txt");
//define("DATAFILE","txlonlat.txt");
//define("SHAPEFILE","cashape.txt");
//define("DATAFILE","calonlat.txt");
//define("SHAPEFILE","flshape.txt");
//define("DATAFILE","fllonlat.txt");
//Image path output folder
define("PATH","/tmp/");

if( !ini_get('safe_mode') )
{
    ini_set("max_execution_time","10000");
    ini_set("memory_limit","800M");
}
set_time_limit(10000);

$d=new mercator(MAPWIDTH,MAPHEIGHT);
$d->set=$d->loadfileZ(DATAFILE);
$mean=$d->project($d->set);

$s=new mercator(MAPWIDTH,MAPHEIGHT);
$shape=$s->loadfileZ(SHAPEFILE);
$s->set=$s->repair($shape,-1);
$m=$s->project($shape,-1);

$f=new mercator(MAPWIDTH,MAPHEIGHT);
$arr=$f->loadfileZ(DATAFILE);
if (OMEGA) {
    $arr=array_merge($arr,$s->filter($s->proj,OMEGA));
} 
$m=$f->project($arr,0,$s->mapLonLeft,$s->mapLatBottom,$s->mapLonRight,$s->mapLatTop);

$plot=new Contourplot();
$res=$plot->main($f->proj,$f->mapWidth,$f->mapHeight,$s->proj,$s->proj,$mean,$d->ZMax,$d->ZMin,ALPHA);
 
$pic=new Image(PATH,$plot);
$pic->create();

?>
