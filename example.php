<?php
/***************************************************************
* Copyright notice
*
* (c) 2010-2013 Chi Hoang (info@chihoang.de)
*  All rights reserved
*
***************************************************************/
define("MAPWIDTH",1000);
define("MAPHEIGHT",1000);
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
$filter=$s->filter($s->proj,10);

$map=new mercator(MAPWIDTH,MAPHEIGHT);
$arr=$map->loadfile("PennsylvaniaLonLatT.txt");
$arr=array_merge($arr,$filter);
$res=$map->project($arr);

$plot=new Contourplot();
$plot->main($res,$map->mapWidth,$map->mapHeight,$s->proj,8.9);
 
$pic=new Image("/tmp/",$plot);
$pic->create();


?>