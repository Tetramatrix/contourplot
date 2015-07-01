<?php

/*****************************************************************
 * Copyright notice
 *
 * (c) 2013-2015 Chi Hoang (info@chihoang.de)
 *  All rights reserved
 *
 ****************************************************************/
require_once("hilbert.php");

define("EPSILON",0.000001);
define("SUPER_TRIANGLE",(float)1000000000);

class Triangle {
   var $x,$y,$z;
   function __construct($x1,$y1,$x2,$y2,$x3,$y3,$x4,$y4,$x5,$y5,$x6,$y6) {
      $this->x=new Point(new Edge($x1,$y1),new Edge($x2,$y2));
      $this->y=new Point(new Edge($x3,$y3),new Edge($x4,$y4));
      $this->z=new Point(new Edge($x5,$y5),new Edge($x6,$y6));
   }
}

class Indices {
   var $x,$y,$z;
   function __construct($x=0,$y=0,$z=0) {
      $this->x=$x;
      $this->y=$y;
      $this->z=$z;
   }
}

class Edge
{
   var $e;
   function __construct($x,$y) {
      $this->e=new Point($x,$y);
   }
   
   public function __get($field) {
      if($field == 'x')
      {
	return $this->e->x;
      } else if($field == 'y')
      {
	 return $this->e->y;
      } else if($field == 'alpha') {
	 if($this->e->alpha==0) {
	    return rand(0,100);
	 } else {
	    return $this->e->alpha;
	 }
      }
   }
}

class Point
{
   var $x,$y,$z,$alpha;
   function __construct($x=0,$y=0,$z=0,$alpha=0) {
      $this->x=$x;
      $this->y=$y;
      $this->z=$z;
      $this->alpha=$alpha;
   }
}

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

   function db ($host,$user,$pwd)
   {
      if(!$this->connid = mysql_connect($host, $user, $pwd))
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
   var $path, $stageWidth, $stageHeight, $pad, $delaunay, $average, $shape, $hull, $nvertx, $nverty, $points, $indices, $contour,$interval;
   
   function __construct($path,$pObj)
   {
      $this->path=$path;
      $this->stageWidth=$pObj->stageWidth;
      $this->stageHeight=$pObj->stageHeight;
      $this->pad=20;
      $this->delaunay=$pObj->delaunay;
      $this->average=$pObj->average;
      $this->average2=$pObj->average2;
      $this->shape=$pObj->shape;
      $this->hull=$pObj->hull;
      $this->nvertx=$pObj->nvertx;
      $this->nverty=$pObj->nverty;
      $this->svertx=$pObj->svertx;
      $this->sverty=$pObj->sverty;
      $this->points=$pObj->points;
      $this->indices=$pObj->indices;
      $this->contour=$pObj->contour;
      $this->interval=$pObj->interval;
      $this->mean=$pObj->mean;
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
   
   function dotproduct($x1,$y1,$x2,$y2,$px,$py)
   {
      $dx1 = $x2-$x1;
      $dy1 = $y2-$y1;
      $dx2 = $px-$x1;
      $dy2 = $py-$y1;
      return ($dx1*$dy2)-($dy1*$dx2);
   }
   
   //http://stackoverflow.com/questions/30421985/line-segment-intersection
   //   class func linesCross(#line1: Line, line2: Line) -> Bool {
   //    let denominator = (line1.end.y - line1.start.y) * (line2.end.x - line2.start.x) -
   //        (line1.end.x - line1.start.x) * (line2.end.y - line2.start.y)
   //
   //    if denominator == 0 { return false } //lines are parallel
   //
   //    let ua = ((line1.end.x - line1.start.x) * (line2.start.y - line1.start.y) -
   //        (line1.end.y - line1.start.y) * (line2.start.x - line1.start.x)) / denominator
   //    let ub = ((line2.end.x - line2.start.x) * (line2.start.y - line1.start.y) -
   //        (line2.end.y - line2.start.y) * (line2.start.x - line1.start.x)) / denominator
   //
   //    //lines may touch each other - no test for equality here
   //    return ua > 0 && ua < 1 && ub > 0 && ub < 1
   //}
   
   // http://www.ecse.rpi.edu/~wrf/Research/Short_Notes/pnpoly.html
   function pnpoly($nvert, $vertx, $verty, $testx, $testy)
   {
      $i=$j=$c=0;
      for ($i=0, $j=$nvert-1; $i<$nvert; $j=$i++)
      {
	if ((($verty[$i]>$testy) != ($verty[$j]>$testy)) &&
	 ($testx < ($vertx[$j]-$vertx[$i]) * ($testy-$verty[$i])/($verty[$j]-$verty[$i]) + $vertx[$i]))
	 {
	    $c= !$c;
	 }
      }
      return $c;
   }
   
   function create()
   {
      // Generate the image variables
      $im = imagecreatetruecolor($this->stageWidth+200,$this->stageHeight+200);
      $white = imagecolorallocate ($im,0xff,0xff,0xff);
      $black = imagecolorallocate($im,0x00,0x00,0x00);
      $grey_lite = imagecolorallocate ($im,0xee,0xee,0xee);
      $grey_dark = imagecolorallocate ($im,0x7f,0x7f,0x7f);
      $firebrick = imagecolorallocate ($im,0xb2,0x22,0x22);
      $blue = imagecolorallocate ($im,0x00,0x00,0xff);
      $darkorange = imagecolorallocate ($im,0xff,0x8c,0x00);
      $red = imagecolorallocate ($im,0xff,0x00,0x00);
      $purple = imagecolorallocate ($im,0x80,0x00,0x80);
      
      $subject=$points=array();
      // Fill in the background of the image
      imagefilledrectangle($im, 0, 0, $this->stageWidth+200, $this->stageHeight+200, $white);

      goto triangle;
      
      for ($i=0,$end=count($this->nvertx);$i<$end;$i+=1) {
	 imagefilledellipse($im,$this->nvertx[$i]+$this->pad,$this->nverty[$i]+$this->pad, 4, 4, $black);
      }

triangle:

      //goto contour;
      
      $nn=count($this->nvertx);
      $ns=count($this->svertx);

      foreach ($this->delaunay as $key => $arr)
      {
	 $c=0;
	 foreach ($arr as $ikey => $iarr)
	 {
	    list($x1,$y1,$x2,$y2)=array($iarr->x->x,$iarr->x->y,$iarr->y->x,$iarr->y->y);
	    $dx=$x2-$x1;
	    $dy=$y2-$y1;
	    $d=$dx*$dx+$dy*$dy;
	    
	    $ok=0;
	    if (!$this->pnpoly($ns,$this->svertx,$this->sverty,$x1,$y1)) {
	      $ok=1; 
	    }	       
	    if (!$this->pnpoly($ns,$this->svertx,$this->sverty,$x2,$y2)) {
	      $ok=1; 
	    }

	    //http://stackoverflow.com/questions/1934210/finding-a-point-on-a-line
//	    $temp=sqrt($d);
//	    $r=2/$temp;
//	    $x3=$r*$x2+(1-$r)*$x1;
//	    $y3=$r*$y2+(1-$r)*$y1;
//	    if (!$this->pnpoly($ns,$this->svertx,$this->sverty,$x3,$y3)) {
//	       ++$c;
//            }
//	    
//	    $r=3/$temp;
//	    $x3=$r*$x2+(1-$r)*$x1;
//	    $y3=$r*$y2+(1-$r)*$y1;
//	    if (!$this->pnpoly($ns,$this->svertx,$this->sverty,$x3,$y3)) {
//	       ++$c;
//            }

	    if (!$this->pnpoly($ns,$this->svertx,$this->sverty,($x1+$x2)/2-10,($y1+$y2)/2-10)) {
	       ++$c;
            }
	    if (!$this->pnpoly($ns,$this->svertx,$this->sverty,($x1+$x2)/2+10,($y1+$y2)/2+10)) {
	       ++$c;
            }
	    
	    if ($c==0 && !$ok && abs($x1)!=SUPER_TRIANGLE && abs($y1)!=SUPER_TRIANGLE && abs($x2)!=SUPER_TRIANGLE && abs($y2)!=SUPER_TRIANGLE )
	    {
	       $points[$key][]=$arr->$ikey->x->x+$this->pad;
	       $points[$key][]=$arr->$ikey->x->y+$this->pad;
	       $subject[$key][$ikey]=$this->indices[$key]->$ikey;
	    
	    } else if ($c<3 && $d<$this->average && abs($x1)!=SUPER_TRIANGLE && abs($y1)!=SUPER_TRIANGLE && abs($x2)!=SUPER_TRIANGLE && abs($y2)!=SUPER_TRIANGLE) {
	       
	       $points[$key][]=$arr->$ikey->x->x+$this->pad;
	       $points[$key][]=$arr->$ikey->x->y+$this->pad;
	       $subject[$key][$ikey]=$this->indices[$key]->$ikey;
	    }
	 }
      }

      $ns=count($this->svertx);
      foreach ($points as $key=>$arr) {
	 $num=count($arr)/2;

	 if ($num>=3) {
	    $arr=array_values($arr);
	    
	    $averageX=($this->points[$subject[$key]["x"]]->alpha+$this->points[$subject[$key]["y"]]->alpha+$this->points[$subject[$key]["z"]]->alpha)/3;
	    
	    $zx = $this->points[$subject[$key]["x"]]->z;
	    $zy = $this->points[$subject[$key]["y"]]->z;
	    $zz = $this->points[$subject[$key]["z"]]->z;
	   
	    if ($zx<0 && $zy<0 && $zz<0) goto triangleEnd;
	    
	    $find=array($zx,$zy,$zz);
	    for ($i=0;$i<3;$i++) {
	       for ($j=0;$j<3;$j++) {
		  if ($i!=$j && $find[$i]<0 && $find[$j]>0 ) {
		     $find[$i]=$find[$j];
		  }
	       }
	    }
	    list($zx,$zy,$zz)=$find;
	    $averageZ=($zx+$zy+$zz)/3;
	    
	    if ($averageX>$averageZ) {
	       $delta=min(($averageX-$averageZ)*(255/STEPS),190);
	       if(imagecolorstotal($im)>=255) {
		  $col=imagecolorclosest($im,190-$delta,190-$delta,255);
	       } else {
		  $col=imagecolorallocate($im,190-$delta,190-$delta,255);
	       }
	    } else {
	       $delta=min(($averageZ-$averageX)*(255/STEPS),190);
	       if(imagecolorstotal($im)>=255) {
		  $col=imagecolorclosest ($im,255,190-$delta,190-$delta);
	       } else {
		  $col=imagecolorallocate ($im,255,190-$delta,190-$delta);
	       }
	    }
	 
	    //imagefilledellipse($im,$arr[$i],$arr[$i+1], 4, 4, $darkorange);
	    //imagefilledellipse($im,$arr[$i+2],$arr[$i+3], 4, 4, $darkorange);
	    //imagefilledellipse($im,($arr[$i]+$arr[$i+2])/2,($arr[$i+1]+$arr[$i+3])/2, 4, 4, $darkorange);

	    imagefilledpolygon($im,$arr,$num,$col);
	    
	    goto triangleEnd;

	    for ($i=0,$e=count($arr);$i<$e;$i+=2) {
	       list($x1,$y1,$x2,$y2)=array($arr[$i],$arr[$i+1],$arr[$i+2],$arr[$i+3]);
	       if ($x1!=0 && $y1!=0 && $x2!=0 && $y2!=0) {
		  //imagefilledellipse($im,$arr[$i],$arr[$i+1], 4, 4, $black);
		  imageline($im,$arr[$i],$arr[$i+1],$arr[$i+2],$arr[$i+3],$grey_dark);
	       }
	    }
	    imageline($im,$arr[0],$arr[1],$arr[$i-2],$arr[$i-1],$grey_dark);
triangleEnd:
	 }
      }

contour:

      //goto contour2;
      //goto shape;
      
      $ns=count($this->svertx);
      $nn=count($this->nvertx);
      
      foreach ($this->interval as $key => $arr) {
	 
	 foreach ($this->indices as $ikey => $iarr)
	 {
	    if ($this->contour[$ikey])
	    {
	       $pts=array();
	       foreach ($this->contour[$ikey] as $iikey => $iiarr) {
		  if ($iiarr[$key]) {
		     $pts[]=array($iiarr[$key]->x,$iiarr[$key]->y);
		  }
	       }
	       if (!empty($pts)) {
		  list($x1,$y1)=$pts[0];
		  list($x2,$y2)=$pts[1];
	       
		//  if (!$this->pnpoly($nn,$this->nvertx,$this->nverty,$x1,$y1)
		//       && !$this->pnpoly($nn,$this->nvertx,$this->nverty,$x2,$y2)) {
		//	imageline($im,$x1+$this->pad,$y1+$this->pad,$x2+$this->pad,$y2+$this->pad,$black);   
		//  }
		  
		  $ok=0;
		  if (!$this->pnpoly($ns,$this->svertx,$this->sverty,$x1,$y1)) {
		     $ok=1; 
		  }	       
		  if (!$this->pnpoly($ns,$this->svertx,$this->sverty,$x2,$y2)) {
		     $ok=1; 
		  }
		  // if (!$this->pnpoly($nn,$this->nvertx,$this->nverty,($x1+$x2)/2,($y1+$y2)/2)) {
		  //  $ok=1; 
		  //}
		  if (!$this->pnpoly($ns,$this->svertx,$this->sverty,($x1+$x2)/2,($y1+$y2)/2)) {
		    $ok=1; 
		  }
		  if (!$ok && $x1!=0 && $y1!=0 && $x2!=0 && $y2!=0) {
		     imageline($im,$x1+$this->pad,$y1+$this->pad,$x2+$this->pad,$y2+$this->pad,$black);
		  }
	       }
	    }
	 }
      }

      //goto alphashape;
      goto shape;
      
contour2:

      $ns=count($this->svertx);
      $nn=count($this->nvertx);
      $pts=array();
      
      foreach ($this->interval as $key => $arr) {
	 
	 foreach ($this->indices as $ikey => $iarr)
	 {
	    if ($this->contour[$ikey])
	    {
	       $temp=array();
	       foreach ($this->contour[$ikey] as $iikey => $iiarr) {
		  if ($iiarr[$key]) {
		     $temp[]=array($iiarr[$key]->x,$iiarr[$key]->y);
		  }
	       }
	       if (!empty($temp)) {
		  list($x1,$y1)=$temp[0];
		  list($x2,$y2)=$temp[1];
	       
		  $ok=0;
		  if (!$this->pnpoly($nn,$this->nvertx,$this->nverty,$x1,$y1)) {
		     $ok=1; 
		  }	       
		  if (!$this->pnpoly($nn,$this->nvertx,$this->nverty,$x2,$y2)) {
		     $ok=1; 
		  }
		  if (!$this->pnpoly($ns,$this->svertx,$this->sverty,$x1,$y1)) {
		     $ok=1; 
		  }	       
		  if (!$this->pnpoly($ns,$this->svertx,$this->sverty,$x2,$y2)) {
		     $ok=1; 
		  }
		   if (!$this->pnpoly($nn,$this->nvertx,$this->nverty,($x1+$x2)/2,($y1+$y2)/2)) {
		    $ok=1; 
		  }
		  if (!$this->pnpoly($ns,$this->svertx,$this->sverty,($x1+$x2)/2,($y1+$y2)/2)) {
		    $ok=1; 
		  }
		  if (!$ok && $x1!=0 && $y1!=0 && $x2!=0 && $y2!=0) {
		     $pts[$key][]=$x1;
		     $pts[$key][]=$y1;
		     $pts[$key][]=$x2;
		     $pts[$key][]=$y2;
		  }
	       }
	    }
	 }
      }
      
      foreach ($pts as $key=>$arr) {
	 if ($key>$this->mean) {
	    $delta=min(($key-$this->mean)*(255/STEPS),255);
	    $col=imagecolorallocate ($im,$delta,$delta,255);
	 } else {
	    $delta=min(($this->mean-$key)*(255/STEPS),255);
	    $col=imagecolorallocate ($im,255,$delta,$delta);
	 }
	 if (($num=count($arr)/2)>=6) {
	    imagefilledpolygon($im,$arr,$num,$col);
	 }
      }
      
      goto shape;
      
alphashape:

      $ns=count($this->svertx);
      foreach ($points as $key=>$arr) {
   	 if (count($arr)/2 >=3) {
   	    for ($i=0,$num=count($arr);$i<$num;$i+=4) {
   	       imagefilledellipse($im,$arr[$i],$arr[$i+1], 4, 4, $black);
   	       
   	       $ok=0;
   	       list($x1,$y1,$x2,$y2)=array($arr[$i],$arr[$i+1],$arr[$i+2],$arr[$i+3]);
   	       $dx = $x2-$x1;
   	       $dy = $y2-$y1;
   	       $d=$dx*$dx+$dy*$dy;
   	    
   	       if (!$this->pnpoly($ns,$this->svertx,$this->sverty,$arr[$i],$arr[$i+1])) {
   		 $ok=1; 
   	       }	       
   	       if (!$this->pnpoly($ns,$this->svertx,$this->sverty,$arr[$i+2],$arr[$i+3])) {
   		 $ok=1; 
   	       }
   	       if (!$this->pnpoly($ns,$this->svertx,$this->sverty,($arr[$i]+$arr[$i+2])/2,($arr[$i+1]+$arr[$i+3])/2)) {
   		 $ok=1; 
   	       }
   	       if (!$ok || $d<$this->average) {   
   		  imageline($im,$arr[$i],$arr[$i+1],$arr[$i+2],$arr[$i+3],$grey_dark);
   	       } 
   	    }
   	 }
      }
      
      $v=$s=array();
      foreach ($this->hull as $key => $arr)
      {
   	 foreach ($arr as $ikey => $iarr)
   	 {
   	    list($x1,$y1,$x2,$y2) = array($iarr->x->x,$iarr->x->y,$iarr->y->x,$iarr->y->y);
   	    $dx=$x2-$x1;
   	    $dy=$y2-$y1;
   	    $d=$dx*$dx+$dy*$dy;
   	    if ($d<$this->average)
   	    {
   	       $v[$x1.$y1]++;
   	       $v[$x2.$y2]++;
   	       imageline($im,$x1+$this->pad,$y1+$this->pad,$x2+$this->pad,$y2+$this->pad,$black); 	       
   	    }
   	    if ($d<$this->average2 && abs($x1)==SUPER_TRIANGLE || abs($y1)==SUPER_TRIANGLE || abs($x2)==SUPER_TRIANGLE || abs($y2)==SUPER_TRIANGLE)
   	    {
   	       $s[$key]++;
   	    }
   	 }
      }
         
      foreach ($s as $key=>$arr) {
   	 foreach ($this->hull[$key] as $ikey => $iarr)
   	 {
   	    list($x1,$y1,$x2,$y2) = array($iarr->x->x,$iarr->x->y,$iarr->y->x,$iarr->y->y);	    
   	    if (($v[$x1.$y1]<2 ||$v[$x2.$y2]<2) && (abs($x1)!=SUPER_TRIANGLE &&
   		  abs($y1)!=SUPER_TRIANGLE && abs($x2)!=SUPER_TRIANGLE && abs($y2)!=SUPER_TRIANGLE)) {
   	       imageline($im,$x1+$this->pad,$y1+$this->pad,$x2+$this->pad,$y2+$this->pad,$black);
   	    }
   	 }
      }
      
      goto ImageEnd;
      
shape:

      for ($i=0,$end=count($this->shape);$i<$end;$i+=2) {
	 switch (OMEGA) {
          case 0:
               list($x1,$y1)=$this->shape[$i];
               list($x2,$y2)=$this->shape[$i+1];
	       $dx=$x2-$x1;
	       $dy=$y2-$y1;
	       $d=$dx*$dx+$dy*$dy;
	       if ($d<$this->average)
	       {
		  imageline($im,$x1+$this->pad,$y1+$this->pad,
                      $x2+$this->pad,$y2+$this->pad,
                      $black);
	       }
	  break;	
          default:
       	       list($x1,$y1)=$this->shape[$i];
	       list($x2,$y2)=$this->shape[$i+1];
	       $dx=$x2-$x1;
	       $dy=$y2-$y1;
	       $d=$dx*$dx+$dy*$dy;
	       if ($d<$this->average)
	       {
	           imageline($im,$x1+$this->pad,$y1+$this->pad,
	 	      $x2+$this->pad,$y2+$this->pad,
		      $black);
	       }
          break;
	}
      }

ImageEnd:

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
   var $points = array();
   var $indices = array();
   var $cc = array();

   function dotproduct($x1,$y1,$x2,$y2,$px,$py)
   {
      $dx1 = $x2-$x1;
      $dy1 = $y2-$y1;
      $dx2 = $px-$x1;
      $dy2 = $py-$y1;
      return ($dx1*$dy2)-($dy1*$dx2);
   }
   
   //LEFT_SIDE = true, RIGHT_SIDE = false, 2 = COLINEAR
   function dotproduct2($x1,$y1,$x2,$y2,$px,$py)
   {
      $dx1 = $x2-$x1;
      $dy1 = $y2-$y1;
      $dx2 = $px-$x1;
      $dy2 = $py-$y1;
      $p = ($dx1*$dy2)-($dy1*$dx2);
      if ($p > 0.0) return(0);
      if ($p < 0.0) return(1);
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
   
   function getEdges($n, $points)
   {
      /*
         Set up the supertriangle
         This is a triangle which encompasses all the sample points.
         The supertriangle coordinates are added to the end of the
         vertex list. The supertriangle is the first triangle in
         the triangle list.
      */
      
      $points[$n+0] = new Point(-SUPER_TRIANGLE,SUPER_TRIANGLE);
      $points[$n+1] = new Point(0,-SUPER_TRIANGLE);
      $points[$n+2] = new Point(SUPER_TRIANGLE,SUPER_TRIANGLE);
    
      // indices       
      $v = array(); 
      $v[] = new Indices($n,$n+1,$n+2);
      
      //sort buffer
      $complete = array();
      $complete[] = false;
      
      /*
         Include each point one at a time into the existing mesh
      */
      foreach ($points as $key => $arr)
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
            list($vi,$vj,$vk)=array($v[$vkey]->x,$v[$vkey]->y,$v[$vkey]->z);
            $c=$this->CircumCircle($points[$vi]->x,$points[$vi]->y,
				   $points[$vj]->x,$points[$vj]->y,
				   $points[$vk]->x,$points[$vk]->y);
	    if ($c->x + $c->r < $points[$key]->x) $complete[$vkey]=1;
            if ($c->r > EPSILON && $this->inside($c, $points[$key]->x,$points[$key]->y))
            {
	       $edges[]=new Edge($vi,$vj);
	       $edges[]=new Edge($vj,$vk);
	       $edges[]=new Edge($vk,$vi); 

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
                  if (($earr->x == $iarr->y) && ($earr->y == $iarr->x))
                  {
                     unset($edges[$ekey]);
                     unset($edges[$ikey]);
                     
                  } else if (($earr->x == $iarr->x) && ($earr->y == $iarr->y))
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
	    if ($edges[$ekey]->x != $key && $edges[$ekey]->y != $key)
	    {
	       $v[] = new Indices($edges[$ekey]->x,$edges[$ekey]->y,$key);
	    }
            $complete[$ntri++]=0;
         }
      }
    
      foreach ($v as $key => $arr)
      {
         $this->indices[$key]=$arr;
         $this->delaunay[$key]=new Triangle($points[$arr->x]->x,$points[$arr->x]->y,
					  $points[$arr->y]->x,$points[$arr->y]->y,
					  $points[$arr->y]->x,$points[$arr->y]->y,
					  $points[$arr->z]->x,$points[$arr->z]->y,
					  $points[$arr->z]->x,$points[$arr->z]->y,
					  $points[$arr->x]->x,$points[$arr->x]->y                                 
				   );
	 
	 $dx=$points[$arr->y]->x-$points[$arr->x]->x;
	 $dy=$points[$arr->y]->y-$points[$arr->x]->y;
	 $this->dist[$key][]=$dx*$dx+$dy*$dy;
	 
	 $dx=$points[$arr->z]->x-$points[$arr->y]->x;
	 $dy=$points[$arr->z]->y-$points[$arr->y]->y;
	 $this->dist[$key][]=$dx*$dx+$dy*$dy;
         
	 $dx=$points[$arr->x]->x-$points[$arr->z]->x;
	 $dy=$points[$arr->x]->y-$points[$arr->z]->y;
	 $this->dist[$key][]=$dx*$dx+$dy*$dy;
      }
      return count($v);
   }

   // http://www.ecse.rpi.edu/~wrf/Research/Short_Notes/pnpoly.html
   function insidePoly($poly, $px, $py) 
   {
      $i=$j=0;
      $inside = false;
      for ($i=0,$j=count($poly)-1;$i<count($poly);$j=$i++) 
      {
	 if((($poly[$i]->lat>$py)!=($poly[$j]->lat>$py)) &&
	    ($px<($poly[$j]->lat-$poly[$i]->lat)*($py-$poly[$i]->lon)/($poly[$j]->lon-$poly[$i]->lon)+$poly[$i]->lat)) 
	 {
	    $inside = !$inside;   
	 }
      }
      return $inside;
   }
    
   // http://www.ecse.rpi.edu/~wrf/Research/Short_Notes/pnpoly.html
   function pnpoly($nvert, $vertx, $verty, $testx, $testy)
   {
      $i=$j=$c=0;
      for ($i=0, $j=$nvert-1; $i<$nvert; $j=$i++)
      {
	if ((($verty[$i]>$testy) != ($verty[$j]>$testy)) &&
	 ($testx < ($vertx[$j]-$vertx[$i]) * ($testy-$verty[$i]) / ($verty[$j]-$verty[$i]) + $vertx[$i]) )
	 {
	    $c = !$c;
	 }
      }
      return $c;
   }
   
   function main($points=0,$stageWidth=400,$stageHeight=400,$shape=0,$data=0,$mean,$zmax,$zmin,$weight=6.899)
   {
      $this->stageWidth = $stageWidth;
      $this->stageHeight = $stageHeight;
      $this->delaunay = array();
      $this->pointset = array();
      $this->indices = array();
      $this->hull = array();
      $this->weight = $weight;
      $this->shape=$shape;
      $this->data=$data;
      $this->mean=$mean;
      $this->ZMin=$zmin;
      $this->ZMax=$zmax;
      $this->interval=array();
      
      $sortX = array();
      foreach($this->data as $key => $arr)
      {
         $sortX[$key] = $arr[0];
      } 
      array_multisort($sortX, SORT_ASC, SORT_NUMERIC, $this->data);
      
      //pnpoly datapoints
      $this->nvertx=$this->nverty=array(); 
      foreach($this->data as $key => $arr)
      {
	 list($this->nvertx[],$this->nverty[])=$arr;
      }
      
      //pnpoly shape
      $this->svertx=$this->sverty=array(); 
      foreach($this->shape as $key => $arr)
      {
	 list($this->svertx[],$this->sverty[])=$arr;
      }
      
      if ($points==0)
      {         
         for ($i=0; $i<1000; $i++) 
         {
            list($x,$y)=array((float)rand(1,$this->stageWidth),(float)rand(1,$this->stageHeight));
            $this->points[]=new Point($x,$y);
         }
      } else
      { 
         for ($i=0,$end=count($points);$i<$end;$i++)
	 {
	    //$this->points[]=new Point($points[$i][0],$points[$i][1],$points[$i][2],$points[$i][2]);
	    $this->points[]=new Point($points[$i][0],$points[$i][1],$points[$i][2],$this->mean);
	 } 
      }
      
      //goto hilbert;
      
      $x=$y=$sortX=array(); 
      foreach($this->points as $key=>$arr)
      {
         $sortX[$key]=$arr->x;
      } 
      array_multisort($sortX, SORT_ASC, SORT_NUMERIC, $this->points);
      goto dt;
      
hilbert:
      $sortX=array(); 
      $maxx=$maxy=0;
      foreach ($this->points as $key => $arr)
      {
	 if ($maxx<$arr->x) $maxx=$arr->y;
	 if ($maxy<$arr->y) $maxy=$arr->y;
      }
      
      $hilbert = new hilbert();     
      $powx=$hilbert->power($maxx,2);     
      $powy=$hilbert->power($maxy,2);
      $order= ($powx<$powy) ? $powy : $powx;
 
      foreach($this->points as $key => $arr) {
	 $sort[$key] = $hilbert->point2hilbert($arr->x, $arr->y, $order);
      }
      array_multisort($sort, SORT_DESC, SORT_NUMERIC, $this->points);
dt:
      $result=$this->getEdges(count($this->points), $this->points);
   
      $sum=$c=0;
      foreach ($this->dist as $key => $arr)
      {
	 if (array_sum($arr)<SUPER_TRIANGLE)
	 {
	    $sum+=array_sum($arr);
	    $c+=count($arr);   
	 }
      }
      
      $this->average=($sum/$c)*$this->weight;
      $n=count($this->points);
      $nn=count($this->nvertx);
      $sum=$c=0;
      
      foreach ($this->indices as $key => $arr)
      {
         foreach ($this->indices as $ikey => $iarr)
         {
            if ($key != $ikey)
            {	       
	       if ( ($arr->x==$iarr->y && $arr->y==$iarr->x) ||
                    ($arr->x==$iarr->z && $arr->y==$iarr->y) ||
                    ($arr->x==$iarr->x && $arr->y==$iarr->z) ||
                                 
                    ($arr->y==$iarr->y && $arr->z==$iarr->x) ||
                    ($arr->y==$iarr->z && $arr->z==$iarr->y) ||
                    ($arr->y==$iarr->x && $arr->z==$iarr->z) ||
                    
                    ($arr->z==$iarr->y && $arr->x==$iarr->x) ||
                    ($arr->z==$iarr->z && $arr->x==$iarr->y) ||
                    ($arr->z==$iarr->x && $arr->x==$iarr->z) 
                  )
               {
		  if ($arr->x >= $n || $arr->y >= $n || $arr->z >= $n)
		  {   
		     $this->hull[$key]=$this->delaunay[$key];
		  } else
		  {
		     list($x1,$y1,$x2,$y2,$x3,$y3)=array($this->points[$arr->x]->x,$this->points[$arr->x]->y,
						         $this->points[$arr->y]->x,$this->points[$arr->x]->y,
						         $this->points[$arr->z]->x,$this->points[$arr->z]->y,
						       );		     
		     $ok=0;
		     if (!$this->pnpoly($nn,$this->nvertx,$this->nverty,$x1,$y1)) {
		       $ok=1; 
		     }	       
		     if (!$this->pnpoly($nn,$this->nvertx,$this->nverty,$x2,$y2)) {
		       $ok=1; 
		     }
		     if (!$this->pnpoly($nn,$this->nvertx,$this->nverty,$x3,$y3)) {
		       $ok=1; 
		     }
		     foreach ($this->dist[$key] as $iikey => $iiarr)
		     {
			if ($ok && $iiarr>$this->average)
			{
			   $sum+=$iiarr;
			   $c++;
			   $this->hull[$key]=$this->delaunay[$key];
			   break;
			}   
		     }
		  }
               }
            }
         }
      }
      
      $zd=($this->ZMax-$this->ZMin);
      for ($j=1;$j<2;$j++) {
	 foreach ($this->indices as $key => $arr)
	 {
	    foreach ($this->indices as $ikey => $iarr)
	    {
	       if ($key!=$ikey && !$this->hull[$key])
	       {	       
		  if ( ($arr->x==$iarr->y && $arr->y==$iarr->x) ||
		       ($arr->x==$iarr->z && $arr->y==$iarr->y) ||
		       ($arr->x==$iarr->x && $arr->y==$iarr->z) ||
				    
		       ($arr->y==$iarr->y && $arr->z==$iarr->x) ||
		       ($arr->y==$iarr->z && $arr->z==$iarr->y) ||
		       ($arr->y==$iarr->x && $arr->z==$iarr->z) ||
		       
		       ($arr->z==$iarr->y && $arr->x==$iarr->x) ||
		       ($arr->z==$iarr->z && $arr->x==$iarr->y) ||
		       ($arr->z==$iarr->x && $arr->x==$iarr->z) 
		     )
		  { 
		     //  list($x1,$y1,$x2,$y2,$x3,$y3)=array($this->points[$arr->x]->x,$this->points[$arr->x]->y,
		     //				         $this->points[$arr->y]->x,$this->points[$arr->x]->y,
		     //				         $this->points[$arr->z]->x,$this->points[$arr->z]->y,
		     //				       );		     
		     
		     $temp=array();
		     foreach ($arr as $ik=>$iv) {
			$temp[$iv]++;
		     }
		     foreach ($iarr as $ik=>$iv) {
			$temp[$iv]++;
		     }
		     arsort($temp);
		     $keys=array_keys($temp);
		     
		     list($tx,$ty)=array($this->points[$keys[0]],
				         $this->points[$keys[1]]);
			
		     //goto isoline2;
		     
		     /*** 
		      *  returns an array of the coordinates and value (an XYZ object) of any found interval values
		      *  uses strict linear interpolation
		      *  @author Zachary Forest Johnson (indiemaps.com/blog or zach.f.johnson@gmail.com)
		      *  @date June 2008
		      *  http://indiemaps.com/blog/2008/06/isolining-package-for-actionscript-3/
		      ***/
		     
		     if (($tx->x-$ty->x)==0) {
			if ($tx->y > $ty->y) {
			   $temp=$tx;
			   $tx=$ty;
			   $ty=$temp;
			}
		     }
		     
	    	     // first, determine which has the greater 'x' of p1 and p2 (b/c this changes how we calculate slope)
		     if ($ty->x > $tx->x)
		     {
			$startPt = $tx;
			$currVal = $tx->z;
			$slope = (($ty->y-$tx->y) / ($ty->x-$tx->x));
			
		     } else if (($tx->x-$ty->x)>0)
		     {
			$startPt = $ty;
			$currVal = $ty->z;
			$slope = (($tx->y-$ty->y) / ($tx->x-$ty->x));
		     }  else 
		     {
			$startPt = $tx;
			$currVal = $tx->z;
			$slope = (($ty->y-$tx->y) / EPSILON);
		     }
		     
		     if ($slope>0)
		     {
			$angle=asin($slope/(sqrt($slope*$slope+1))); // the angle of A
			$ymulti=-1;
		     } else 
		     {
			$angle=(2*M_PI)-asin($slope/(sqrt($slope*$slope+1))); // the angle of A
			$ymulti=1;
		     }

		     // stores the total line length of the edge (the hypotenuse, then)
		     if ( ($ty->x > $tx->x) || (($tx->x-$ty->x)>0))
		     {
			$dx=$ty->x-$tx->x;
			$dy=$ty->y-$tx->y;
			$lineLength=sqrt($dx*$dx+$dy*$dy);
		     } else {
			$dx=EPSILON;
			$dy=$ty->y-$tx->y;
			$lineLength=sqrt($dx*$dx+$dy*$dy);
		     }
		     
		     //now let's get ridiculous
		     $curr = ($tx->z < $ty->z) ? $tx->z : $ty->z; //curr is the current value (starts as the lower of the two line node values)
		     $end = ($ty->z > $tx->z) ? $ty->z : $tx->z; //end is the end value
		     
		     //now find the first interpolated point on the edge segment
		     $currInt = $curr+(INTERVAL-((($curr/INTERVAL)-floor($curr/INTERVAL))*INTERVAL));
	
		     if (($tx->z-$currInt)*($ty->z-$currInt)==0) {
			$tx->z+=EPSILON;
			$ty->z+=EPSILON;
		     }
		     
		     /*******
		      ****	lame Flash rounding errors (54.99999999999 instead of 55) are screwing this part up
		      ****	better way of doing this, but for now, I'm just rounding the number if the interval is an integer
		      ****	SO, if your interval is not an integer, this rounding error thing may screw you up
		      ****	my solution is lame -- make a better one
		      *******/
		     //if (!ctype_digit(INTERVAL)) {
		     //	$currInt = round($currInt);
		     //	$currInt = ceil($currInt / INTERVAL) * INTERVAL;
		     //}
		     
		     while ($currInt <= $end && ($tx->z-$currInt)*($ty->z-$currInt)<=0) {
			// will hold how far away from either $tx or $ty the next interpolated point is
			$dist=(abs($currInt-$currVal)/abs($tx->z-$ty->z))*$lineLength;
			$cx = $startPt->x+($dist*cos($angle));
			$cy = $startPt->y-($dist*sin($angle))*$ymulti;
			//create the new Interpolated Point object
			//and add the thing to the edge's array
			//$index=substr(md5($currInt),0,10);
			$this->contour[$key][$ikey][(string)$currInt] = new Point($cx,$cy);
			$this->interval[(string)$currInt]++;
			//then get next interval value from current position
			$currInt += INTERVAL;
		     }
		     goto isolineEnd;
		     
isoline2:
		     $intervalValue = ceil($this->ZMin/INTERVAL)*INTERVAL;
	 
		     while ($intervalValue <= $this->ZMax )
		     {
			// find the interpolated point along the first edge
			$cx = (($intervalValue-$tx->z) / ($ty->z-$tx->z)) * ($ty->x-$tx->x) + $tx->x;
			$cy = (($intervalValue-$tx->z) / ($ty->z-$tx->z)) * ($ty->y-$tx->y) + $tx->y;
			
			$this->contour[$key][$ikey][$currInt] = new Point($cx,$cy);
			$this->interval[$currInt]++;
			$intervalValue += INTERVAL;
		     }
isolineEnd:
		  }
	       }
	    }
	 }
      }	  
       
      goto end;
      
      foreach ($this->indices as $key => $arr)
      {
	 foreach ($this->indices as $ikey => $iarr)
	 {
	    if ($key != $ikey)
	    {	       
	       if ( ($arr->x==$iarr->y && $arr->y==$iarr->x) ||
		    ($arr->x==$iarr->z && $arr->y==$iarr->y) ||
		    ($arr->x==$iarr->x && $arr->y==$iarr->z) ||
				 
		    ($arr->y==$iarr->y && $arr->z==$iarr->x) ||
		    ($arr->y==$iarr->z && $arr->z==$iarr->y) ||
		    ($arr->y==$iarr->x && $arr->z==$iarr->z) ||
		    
		    ($arr->z==$iarr->y && $arr->x==$iarr->x) ||
		    ($arr->z==$iarr->z && $arr->x==$iarr->y) ||
		    ($arr->z==$iarr->x && $arr->x==$iarr->z) 
		  )
	       {
	       
		  list($x1,$y1,$x2,$y2,$x3,$y3)=array($this->delaunay[$ikey]->x->x->x,
						      $this->delaunay[$ikey]->x->x->y,
						      $this->delaunay[$ikey]->x->y->x,
						      $this->delaunay[$ikey]->x->y->y,
						      $this->delaunay[$ikey]->y->y->x,
						      $this->delaunay[$ikey]->y->y->y
						      );
		  $points=array();
		  $points[]=new Point($x1,$y1);
		  $points[]=new Point($x2,$y2);
		  $points[]=new Point($x3,$y3);
		  
		  $tt=$this->insidePoly($points,$this->delaunay[$key]->x->x->x,$this->delaunay[$key]->x->x->y);
		  if ($tt)
		  {
		     unset($this->delaunay[$key]);
		     unset($this->indices[$key]);
		     $deleted[]=$key;
		     break;
		  }    
		  $tt=$this->insidePoly($points,$this->delaunay[$key]->x->y->x,$this->delaunay[$key]->x->y->y);
		  if ($tt)
		  {
		     unset($this->delaunay[$key]);
		     unset($this->indices[$key]);
		     $deleted[]=$key;
		     break;
		  }     
		  $tt=$this->insidePoly($points,$this->delaunay[$key]->y->y->x,$this->delaunay[$key]->y->y->y);
		  if ($tt)
		  {
		     unset($this->delaunay[$key]);
		     unset($this->indices[$key]);
		     $deleted[]=$key;
		     break;
		  }
	       }                 
	    }                    
	 }
      }
end:       
      $this->average2=$sum/$c*$this->weight;
      return $result;
   }
}
?>
