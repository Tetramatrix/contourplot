<?php

/* * *************************************************************
 * Copyright notice
 *
 * (c) 2013-2015 Chi Hoang (info@chihoang.de)
 *  All rights reserved
 *
 * **************************************************************/

define("EPSILON",0.000001);
define("SUPER_TRIANGLE",(float)1000000000);

  // circum circle
class Circle
{
   var $x, $y, $r, $r2;
   function Circle($x, $y, $r)
   {
      $this->x = $x;
      $this->y = $y;
      $this->r = $r;
   }
}

class db
{
   var $connid;
   var $erg;

   function db ($host,$user,$passwort)
   {
      if(!$this->connid = mysql_connect($host, $user, $passwort))
      {
	 echo "Fehler beim Verbinden...";
      }
      return $this->connid;
   }

   function select_db($db)
   {
      if (!mysql_select_db($db, $this->connid))
      {
	 echo "Fehler beim Auswaehlen der DB...";
      }
   }

   function sql ($sql)
   {
      if (!$this->erg = mysql_query($sql, $this->connid))
      {
	 echo "Fehler beim Senden der Abfrage...";
      }
      return $this->erg;
   }
}

class Image
{
   var $path;
   var $stageWidth;
   var $stageHeight;
   var $padding;
   var $delaunay;
   var $average;
   var $shape;
   var $hull;
   var $nvertx;
   var $nverty;
   
   function __construct($path,$pObj)
   {
      $this->path=$path;
      $this->stageWidth=$pObj->stageWidth;
      $this->stageHeight=$pObj->stageHeight;
      $this->padding=20;
      $this->delaunay=$pObj->delaunay;
      $this->average=$pObj->average;
      $this->shape=$pObj->shape;
      $this->hull=$pObj->hull;
      $this->nvertx=$pObj->nvertx;
      $this->nverty=$pObj->nverty;
   }
   
   function erropen()
   {
      print "Cannot open file";
      exit;
   }
   
   function errwrite()
   {
      print "Cannot write file";
      exit;
   }
   
   function pnpoly($nvert, $vertx, $verty, $testx, $testy)
   {
      $i=$j=$c=0;
      for ($i=0, $j=$nvert-1; $i<$nvert; $j=$i++)
      {
	if ( (($verty[$i]>$testy) != ($verty[$j]>$testy)) &&
	 ($testx < ($vertx[$j]-$vertx[$i]) * ($testy-$verty[$i]) / ($verty[$j]-$verty[$i]) + $vertx[$i]) )
	 {
	    $c=1;
	    break;
	 }
      }
      return $c;
   }
   
   function create2($set,$stageWidth,$stageHeight)
   {
         // Generate the image variables
      $im = imagecreate($stageWidth,$stageHeight);
      $white = imagecolorallocate ($im,0xff,0xff,0xff);
      $blue = imagecolorallocate ($im,0x00,0x00,0xff);
      $white = imagecolorallocate ($im,0xff,0xff,0xff);
      $black = imagecolorallocate($im,0x00,0x00,0x00);
      $gray_lite = imagecolorallocate ($im,0xee,0xee,0xee);
      $gray_dark = imagecolorallocate ($im,0x7f,0x7f,0x7f);
      $firebrick = imagecolorallocate ($im,0xb2,0x22,0x22);
      $blue = imagecolorallocate ($im,0x00,0x00,0xff);
      $darkorange = imagecolorallocate ($im,0xff,0x8c,0x00);
      $green = imagecolorallocate ($im,0x00,0xff,0x00);
      $darkviolet = imagecolorallocate ($im,0x94,0x00,0xd3);
      $purple = imagecolorallocate ($im,0x80,0x00,0x80);
      $red  = imagecolorallocate ($im,0xff,0x00,0x00);
      
      // Fill in the background of the image
      imagefilledrectangle($im, 0, 0, $stageWidth+200, $stageHeight+200, $white);

      foreach ($set as $key => $arr)
      {
	 list($x1,$y1)=$arr;
	 imagefilledellipse($im, $x1, $y1, 8, 8, $blue);
      }
      
      flush();
      ob_start();
      imagepng($im);
      $imagevariable = ob_get_contents();
      ob_end_clean();

         // write to file
      $filename = $this->path."tri_". rand(0,1000).".png";
      $fp = fopen($filename, "w");
      fwrite($fp, $imagevariable);
      if(!$fp)
      {
         $this->errwrite();   
      }
      fclose($fp);
   }

   function create()
   {
         // Generate the image variables
      $im = imagecreate($this->stageWidth+200,$this->stageHeight+200);
      $white = imagecolorallocate ($im,0xff,0xff,0xff);
      $black = imagecolorallocate($im,0x00,0x00,0x00);
      $gray_lite = imagecolorallocate ($im,0xee,0xee,0xee);
      $gray_dark = imagecolorallocate ($im,0x7f,0x7f,0x7f);
      $firebrick = imagecolorallocate ($im,0xb2,0x22,0x22);
      $blue = imagecolorallocate ($im,0x00,0x00,0xff);
      $darkorange = imagecolorallocate ($im,0xff,0x8c,0x00);
      $red = imagecolorallocate ($im,0xff,0x00,0x00);
      $purple = imagecolorallocate ($im,0x80,0x00,0x80);
      
      $points=array();
      // Fill in the background of the image
      imagefilledrectangle($im, 0, 0, $this->stageWidth+200, $this->stageHeight+200, $white);
      foreach ($this->delaunay as $key => $arr)
      {
	 foreach ($arr as $ikey => $iarr)
	 {
	    list($x1,$y1,$x2,$y2) = $iarr;
	    $dx = $x2-$x1;
	    $dy = $y2-$y1;
	    $d = $dx*$dx+$dy*$dy;
	    if ($d<$this->average && abs($x1) != SUPER_TRIANGLE && abs($y1) != SUPER_TRIANGLE && abs($x2) != SUPER_TRIANGLE && abs($y2) != SUPER_TRIANGLE)
	    {
	       $ok=0;
	//       if (!$this->pnpoly(count($this->nvertx),$this->nvertx,$this->nverty,$x1,$y1)) {
	//	  $ok=1;
	//       }
	//       if (!$this->pnpoly(count($this->nvertx),$this->nvertx,$this->nverty,$x2,$y2)) {
	//	  $ok=1;
	//       }
	       foreach ($this->shape as $iikey => $iiarr)
	       {
		  list($x,$y)=$iiarr;
		  if (array($x,$y)==array($x1,$y1) || array($x,$y)==array($x2,$y2))
		  {
		     $ok=1;
		  }
	       }
	       if ($ok==0)
	       {
		  $points[$key][]=$x1+$this->padding;
		  $points[$key][]=$y1+$this->padding;
		  $points[$key][]=$x2+$this->padding;
		  $points[$key][]=$y2+$this->padding;
	       }
	    }
	 }	 
      }
      
      foreach ($points as $key=>$arr) {
	 $num=count($arr)/2;
	 if ($num >=3) {
	    imagefilledpolygon($im,$arr,$num,$gray_lite);
	 }
      }
       
      foreach ($points as $key=>$arr) {
	 if (count($arr)/2 >=3) {
	    for ($i=0,$num=count($arr);$i<$num;$i+=4) {
	       imagefilledellipse($im,$arr[$i],$arr[$i+1], 4, 4, $blue);
	       imageline($im,$arr[$i],$arr[$i+1],$arr[$i+2],$arr[$i+3],$gray_dark);
	    }
	 }
      }
      
      foreach ($this->hull as $key => $arr)
      {
	 foreach ($arr as $ikey => $iarr)
	 {
	    list($x1,$y1,$x2,$y2) = $iarr;
	    $dx = $x2-$x1;
	    $dy = $y2-$y1;
	    $d = $dx*$dx+$dy*$dy;
	    if ($d<$this->average && abs($x1)!= SUPER_TRIANGLE && abs($y1)!= SUPER_TRIANGLE && abs($x2) != SUPER_TRIANGLE && abs($y2) != SUPER_TRIANGLE)
	    {
	       imageline($im,$x1+$this->padding,$y1+$this->padding,$x2+$this->padding,$y2+$this->padding,$red); 	       
	    }
	 }
      }
      
      flush();
      ob_start();
      imagepng($im);
      $imagevariable = ob_get_contents();
      ob_end_clean();

         // write to file
      $filename = $this->path."tri_". rand(0,1000).".png";
      $fp = fopen($filename, "w");
      fwrite($fp, $imagevariable);
      if(!$fp)
      {
         $this->errwrite();   
      }
      fclose($fp);
   }
   
   function tri()
   {
      if (!$handle = fopen($this->path."tri.csv", "w"))
      {
         $this->erropen();  
      }
      rewind($handle);	
      $c=0;
      foreach ($this->pObj->delaunay as $key => $arr)
      {
         foreach ($arr as $ikey => $iarr)
         {
            if (!fwrite(handle, $iarr[0].",".$iarr[1]."\n"))
            {
               $this->errwrite();  
            }
         }
      }
      fclose($handle);   
   }
   
   function pset($path)
   {
      if (!$handle = fopen($this->path."pset.csv", "w"))
      {
         $this->erropen();  
      }
      rewind($handle);	
      $c=0;
      foreach ($this->pObj->pointset as $key => $arr)
      {
         if ( !fwrite ($handle, $arr[0].",".$arr[1]."\n" ) )
         {
            $this->errwrite(); 
         }
      }
      fclose($handle);   
   }
}

class Contourplot
{
   var $stageWidth = 400;
   var $stageHeight = 400;
   var $delaunay = array();
   var $pointset = array();
   var $indices = array();
   var $cc = array();

   //LEFT_SIDE = true, RIGHT_SIDE = false, 2 = COLINEAR
   function side($x1,$y1,$x2,$y2,$px,$py)
   {
      $dx1 = $x2 - $x1;
      $dy1 = $y2 - $y1;
      $dx2 = $px - $x1;
      $dy2 = $py - $y1;
      $o = ($dx1*$dy2)-($dy1*$dx2);
      if ($o > 0.0) return(0);
      if ($o < 0.0) return(1);
      return(-1);
   }

   function GetCircumCenter($Ax, $Ay, $Bx, $By, $Cx, $Cy)
   {  
      //$Ax = 5;
      //$Ay = 7;
      //$Bx = 6;
      //$By = 6;
      //$Cx = 2;
      //$Cy = -2;
      
      //$Ax = 5;
      //$Ay = 1;
      //$Bx = -2;
      //$By = 0;
      //$Cx = 4;
      //$Cy = 8;

      $MidSideAx = (($Bx + $Ax)/2.0);
      $MidSideAy = (($By + $Ay)/2.0);
      
      $MidSideBx = (($Bx + $Cx)/2.0);
      $MidSideBy = (($By + $Cy)/2.0);
     
      $MidSideCx = (($Cx + $Ax)/2.0);
      $MidSideCy = (($Cy + $Ay)/2.0);
      
      //Inverted Slopes of two Perpendicular lines of the Triangle y = mx + c
      $SlopeAB = (-(($Bx - $Ax)/($By - $Ay)));
      $SlopeBC = (-(($Cx - $Bx)/($Cy - $By)));
      $SlopeCA = (-(($Cx - $Ax)/($Cy - $Ay)));
      
      //Cab
      $Cab = -1 * ($SlopeAB * $MidSideAx - $MidSideAy);
      
      //Cba
      $Cbc = -1 * ($SlopeBC * $MidSideBx - $MidSideBy);
      
      //Cac
      $Cac = -1 * ($SlopeCA * $MidSideCx - $MidSideCy);
      
      //intersection
      //CircumCenterX = ((SlopeBC * MidSideAx) + MidSideAy - (SlopeAB * MidSideCx) + MidSideCy) / (SlopeBC - SlopeAB);
      //CircumCenterY = SlopeAB * (CircumCenterX - MidSideCx) + MidSideCy;
      
      $xc = ($Cab - $Cbc) / ($SlopeBC - $SlopeAB);
      $yc = $SlopeCA * $CircumCenterX + $Cac;
      
      $dx = $x2 - $xc;
      $dy = $y2 - $yc;
      $rsqr = $dx*$dx + $dy*$dy;
      
      return new Circle($xc, $yc,$rsqr);
   }
   
   function CircumCircle($x1,$y1,$x2,$y2,$x3,$y3)
   {
      //list($x1,$y1)=array(1,3);
      //list($x2,$y2)=array(6,5);
      //list($x3,$y3)=array(4,7);
      
      $absy1y2 = abs($y1-$y2);
      $absy2y3 = abs($y2-$y3);

      if ($absy1y2 < EPSILON)
      {
         $m2 = -($x3-$x2) / ($y3-$y2);
         $mx2 = ($x2 + $x3) / 2.0;
         $my2 = ($y2 + $y3) / 2.0;
         $xc = ($x2 + $x1) / 2.0;
         $yc = $m2 * ($xc - $mx2) + $my2;
      }
      else if ($absy2y3 < EPSILON)
      {
         $m1 = -($x2-$x1) / ($y2-$y1);
         $mx1 = ($x1 + $x2) / 2.0;
         $my1 = ($y1 + $y2) / 2.0;
         $xc = ($x3 + $x2) / 2.0;
         $yc = $m1*($xc - $mx1) + $my1;	
      }
      else
      {
         $m1 = -($x2-$x1) / ($y2-$y1);
         $m2 = -($x3-$x2) / ($y3-$y2);
        
	 if (($m1-$m2)==0)
	 {
	    $mx1 = ($x1 + $x2) / 2.0;
	    $my1 = ($y1 + $y2) / 2.0;
	    $xc = ($x3 + $x2) / 2.0;
	 } else
	 {   
	    $mx1 = ($x1 + $x2) / 2.0;
	    $mx2 = ($x2 + $x3) / 2.0;
	    $my1 = ($y1 + $y2) / 2.0;
	    $my2 = ($y2 + $y3) / 2.0;
	    $xc = ($m1*$mx1 - $m2*$mx2 + $my2 - $my1) / ($m1 - $m2);
	 }
         
         if ($absy1y2 > $absy2y3)
         {
            $yc = $m1 * ($xc - $mx1) + $my1;   
         } else
         {
            $yc = $m2 * ($xc - $mx2) + $my2;   
         }
      }
      
      $dx = $x2 - $xc;
      $dy = $y2 - $yc;
      $rsqr = $dx*$dx + $dy*$dy;
      //$r = sqrt($rsqr);
     
      return new Circle($xc, $yc, $rsqr);
   }

   function inside(Circle $c, $x, $y)
   {
      $dx = $x - $c->x;
      $dy = $y - $c->y;
      $drsqr = $dx*$dx + $dy*$dy;
      $inside = (($drsqr-$c->r) <= EPSILON) ? true : false;
      return $inside;
   }
   
   function getEdges($n, $x, $y, $z)
   {
      /*
         Set up the supertriangle
         This is a triangle which encompasses all the sample points.
         The supertriangle coordinates are added to the end of the
         vertex list. The supertriangle is the first triangle in
         the triangle list.
      */
      
      $x[$n+0] = -SUPER_TRIANGLE;
      $y[$n+0] = SUPER_TRIANGLE;
      $x[$n+1] = 0;
      $y[$n+1] = -SUPER_TRIANGLE;
      $x[$n+2] = SUPER_TRIANGLE;
      $y[$n+2] = SUPER_TRIANGLE;
    
      // indices       
      $v = array(); 
      $v[] = array($n,$n+1,$n+2);
      
      //sort buffer
      $complete = array();
      $complete[] = false;
      
      /*
         Include each point one at a time into the existing mesh
      */
      foreach ($x as $key => $arr)
      {        
         /*
            Set up the edge buffer.
            If the point (xp,yp) lies inside the circumcircle then the
            three edges of that triangle are added to the edge buffer
            and that triangle is removed.
         */
         
         $edges=array();
         foreach ($v as $vkey => $varr)
         {  
            if ($complete[$vkey]) continue;
            list($vi,$vj,$vk)=array($v[$vkey][0],$v[$vkey][1],$v[$vkey][2]);
            $c=$this->CircumCircle($x[$vi],$y[$vi],$x[$vj],$y[$vj],$x[$vk],$y[$vk]);
	    if ($c->x + $c->r < $x[$key]) $complete[$vkey]=1;
            if ($c->r > EPSILON && $this->inside($c, $x[$key],$y[$key]))
            {
	       $edges[]=array($vi,$vj);
	       $edges[]=array($vj,$vk);
	       $edges[]=array($vk,$vi);

               unset($v[$vkey]);
               unset($complete[$vkey]);
            }
         }
         
         /*
            Tag multiple edges
            Note: if all triangles are specified anticlockwise then all
            interior edges are opposite pointing in direction.
         */
         $edges=array_values($edges);
         foreach ($edges as $ekey => $earr)
         {   
            foreach ($edges as $ikey => $iarr)
            {
               if ($ekey != $ikey)
               {
                  if (($earr[0] == $iarr[1]) && ($earr[1] == $iarr[0]))
                  {
                     unset($edges[$ekey]);
                     unset($edges[$ikey]);
                     
                  } elseif (($earr[0] == $iarr[0]) && ($earr[1] == $iarr[1]))
                  {
                     unset($edges[$ekey]);
                     unset($edges[$ikey]);
                  }   
               }
            }
         }
         
         /*
            Form new triangles for the current point
            Skipping over any tagged edges.
            All edges are arranged in clockwise order.
         */
         $complete=array_values($complete);
         $v=array_values($v);
         $ntri=count($v);
         $edges=array_values($edges);
         foreach ($edges as $ekey => $earr)
         {
	    if ($edges[$ekey][0] != $key && $edges[$ekey][1] != $key) {
	       $v[] =array($edges[$ekey][0],$edges[$ekey][1],$key);
	    }
            $complete[$ntri++]=0;
         }
      }
      
      foreach ($v as $key => $arr)
      {
         $this->indices[$key]=$arr;
         $this->indices[$key][]=$arr[0];
         $this->delaunay[$key]=array(array($x[$arr[0]],$y[$arr[0]],$x[$arr[1]],$y[$arr[1]]),
                                 array($x[$arr[1]],$y[$arr[1]],$x[$arr[2]],$y[$arr[2]]),
                                 array($x[$arr[2]],$y[$arr[2]],$x[$arr[0]],$y[$arr[0]])                                 
                                 );
	 $dx=$x[$arr[1]]-$x[$arr[0]];
	 $dy=$y[$arr[1]]-$y[$arr[0]];
	 $this->dist[$key][]=$dx*$dx+$dy*$dy;
	 $dx=$x[$arr[2]]-$x[$arr[1]];
	 $dy=$y[$arr[2]]-$y[$arr[1]];
	 $this->dist[$key][]=$dx*$dx+$dy*$dy;
         $dx=$x[$arr[0]]-$x[$arr[2]];
	 $dy=$y[$arr[0]]-$y[$arr[2]];
	 $this->dist[$key][]=$dx*$dx+$dy*$dy;
      }
      return $v;
   }
   
   function main($pointset=0,$stageWidth=400,$stageHeight=400,$shape=0,$weight=6.899)
   {
      $this->stageWidth = $stageWidth;
      $this->stageHeight = $stageHeight;
      $this->delaunay = array();
      $this->pointset = array();
      $this->indices = array();
      $this->hull = array();
      $this->weight = $weight;
      $this->shape=$shape;
      
      $sortX = array();
      foreach($this->shape as $key => $arr)
      {
         $sortX[$key] = $arr[0];
      } 
      array_multisort($sortX, SORT_DESC, SORT_NUMERIC, $this->shape);
      
      $this->nvertx = $this->nverty = array(); 
      foreach($this->shape as $key => $arr)
      {
	 list($this->nvertx[],$this->nverty[])=$arr;
      }
      
      if ($pointset==0)
      {         
         for ($i=0; $i<1000; $i++) 
         {
            list($x,$y)=array((float)rand(1,$this->stageWidth),(float)rand(1,$this->stageHeight));
            $this->pointset[]=array($x,$y);
         }
      } else
      { 
         $this->pointset=$pointset;   
      }

      $x = $y = $sortX = array(); 
      foreach($this->pointset as $key => $arr)
      {
         $sortX[$key] = $arr[0];
      } 
      array_multisort($sortX, SORT_ASC, SORT_NUMERIC, $this->pointset);
         
      foreach ($this->pointset as $key => $arr)
      {
        list($x[],$y[]) = $arr;
      }
      $result=$this->getEdges(count($this->pointset), $x, $y, $z);
   
      $sum=$c=0;
      foreach ($this->dist as $key => $arr)
      {
	 if (array_sum($arr)<SUPER_TRIANGLE)
	 {
	    $sum += array_sum($arr);
	    $c += count($arr);   
	 }
      }
      $this->average=$sum/$c*$this->weight;
      
      foreach ($this->delaunay as $key => $arr)
      {       
         $this->cc[$key]=$this->CircumCircle($arr[0][0],$arr[0][1],
                                             $arr[0][2],$arr[0][3],
                                             $arr[1][2],$arr[1][3]);
      }    
   
      foreach ($this->indices as $key => $arr)
      {
         foreach ($this->indices as $ikey => $iarr)
         {
            if ($key != $ikey)
            {
               if ( ($arr[0]==$iarr[1] && $arr[1]==$iarr[0]) ||
                    ($arr[0]==$iarr[2] && $arr[1]==$iarr[1]) ||
                    ($arr[0]==$iarr[3] && $arr[1]==$iarr[2]) ||
                                 
                    ($arr[1]==$iarr[1] && $arr[2]==$iarr[0]) ||
                    ($arr[1]==$iarr[2] && $arr[2]==$iarr[1]) ||
                    ($arr[1]==$iarr[3] && $arr[2]==$iarr[2]) ||
                    
                    ($arr[2]==$iarr[1] && $arr[3]==$iarr[0]) ||
                    ($arr[2]==$iarr[2] && $arr[3]==$iarr[1]) ||
                    ($arr[2]==$iarr[3] && $arr[3]==$iarr[2]) 
                  )
               {
                  $this->voronoi[$key][$ikey]=array($this->cc[$key], $this->cc[$ikey]);
 		  $this->quads[$key][$ikey]=$this->delaunay[$key];
               }
            }
         }
      }
      
      $n=count($this->pointset);
      foreach ($this->indices as $key => $arr)
      {
         foreach ($this->indices as $ikey => $iarr)
         {
            if ($key != $ikey)
            {
               if ( ($arr[0]==$iarr[1] && $arr[1]==$iarr[0]) ||
                    ($arr[0]==$iarr[2] && $arr[1]==$iarr[1]) ||
                    ($arr[0]==$iarr[3] && $arr[1]==$iarr[2]) ||
                                 
                    ($arr[1]==$iarr[1] && $arr[2]==$iarr[0]) ||
                    ($arr[1]==$iarr[2] && $arr[2]==$iarr[1]) ||
                    ($arr[1]==$iarr[3] && $arr[2]==$iarr[2]) ||
                    
                    ($arr[2]==$iarr[1] && $arr[3]==$iarr[0]) ||
                    ($arr[2]==$iarr[2] && $arr[3]==$iarr[1]) ||
                    ($arr[2]==$iarr[3] && $arr[3]==$iarr[2]) 
                    
                  )
               {
		  if ($arr[0] >= $n || $arr[1] >= $n || $arr[2] >= $n || $arr[3] >= $n)
		  {   
		     $this->hull[$key]=$this->delaunay[$key];
		  } else
		  {
		     foreach ($this->dist[$key] as $iikey => $iiarr)
		     {
			if ($iiarr>$this->average)
			{				
			   $this->hull[$key]=$this->delaunay[$key];
			   break;
			}   
		     }
		  }
               }
            }
         }
      }      
  }
}
?>